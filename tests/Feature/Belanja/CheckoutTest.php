<?php

declare(strict_types=1);

use App\Domain\Pesanan\StatusPesanan;
use App\Models\ButirPesanan;
use App\Models\Keranjang;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Toko;
use App\Services\LayananPembayaran;
use App\Services\PembatalanOtomatis;
use App\Services\TandaTanganGateway;
use Tests\Pendukung\PembantuCheckout;

beforeEach(function () {
    [$this->pembeli, $this->alamat] = PembantuCheckout::buatPembeliDanAlamat();
    $this->tokoA = Toko::factory()->create();
    $this->tokoB = Toko::factory()->create();

    $this->produkA = Produk::factory()->for($this->tokoA)->create([
        'harga_rupiah' => 50_000,
        'stok' => 10,
    ]);
    $this->produkB = Produk::factory()->for($this->tokoB)->create([
        'harga_rupiah' => 75_000,
        'stok' => 10,
    ]);
});

it('checkout memecah pesanan per toko, mengosongkan keranjang, dan mengurangi stok sesuai butir', function () {
    PembantuCheckout::isiKeranjang($this->pembeli, $this->produkA, 2);
    PembantuCheckout::isiKeranjang($this->pembeli, $this->produkB, 3);

    $respon = $this->sebagai($this->pembeli)
        ->withHeader('Idempotency-Key', 'kunci-idempoten-split-01')
        ->postJson('/api/v1/checkout', ['alamat_id' => $this->alamat->id])
        ->assertCreated();

    // 2 pesanan berbeda sesuai toko
    $respon->assertJsonCount(2, 'data.pesanan');

    // Keranjang pembeli dikosongkan
    expect(Keranjang::query()->where('pengguna_id', $this->pembeli->id)->first()->butir()->count())->toBe(0)
        // Stok berkurang tepat sejumlah yang dibeli
        ->and($this->produkA->refresh()->stok)->toBe(8)
        ->and($this->produkB->refresh()->stok)->toBe(7);
});

it('checkout dengan keranjang kosong dijawab 422 dan tidak membuat pesanan', function () {
    $jumlahPesananSebelum = Pesanan::count();

    $this->sebagai($this->pembeli)
        ->withHeader('Idempotency-Key', 'kunci-idempoten-empty-01')
        ->postJson('/api/v1/checkout', ['alamat_id' => $this->alamat->id])
        ->assertStatus(422)
        ->assertJsonPath('galat.kode', 'KERANJANG_KOSONG');

    expect(Pesanan::count())->toBe($jumlahPesananSebelum);
});

it('tidak menyisakan apa pun ketika tagihan gagal dibuat', function () {
    PembantuCheckout::isiKeranjang($this->pembeli, $this->produkA, 2);

    $this->mock(LayananPembayaran::class, function ($mock): void {
        $mock->shouldReceive('buat')->once()->andThrow(new RuntimeException('gerbang mati'));
    });

    $sebelum = [Pesanan::count(), ButirPesanan::count(), Produk::sum('stok')];

    $this->sebagai($this->pembeli)
        ->withHeader('Idempotency-Key', 'kunci-idempoten-fail-01')
        ->postJson('/api/v1/checkout', ['alamat_id' => $this->alamat->id])
        ->assertStatus(500);

    expect([Pesanan::count(), ButirPesanan::count(), Produk::sum('stok')])->toBe($sebelum);
});

it('checkout di atas batas segar tanpa konfirmasi sandi dijawab 403 dan stok utuh', function () {
    $produkMahal = Produk::factory()->for($this->tokoA)->create([
        'harga_rupiah' => 2_500_000,
        'stok' => 5,
    ]);

    PembantuCheckout::isiKeranjang($this->pembeli, $produkMahal, 1);

    // Sesi tidak segar (misal sudah lewat 10 menit tanpa konfirmasi sandi)
    $this->sebagai($this->pembeli, segar: false)
        ->withHeader('Idempotency-Key', 'kunci-idempoten-segar-01')
        ->postJson('/api/v1/checkout', ['alamat_id' => $this->alamat->id])
        ->assertStatus(403)
        ->assertJsonPath('galat.kode', 'LP-15');

    expect($produkMahal->refresh()->stok)->toBe(5);
});

it('pembatalan otomatis tidak membatalkan tagihan yang sudah lunas', function () {
    PembantuCheckout::isiKeranjang($this->pembeli, $this->produkA, 1);

    $respon = $this->sebagai($this->pembeli)
        ->withHeader('Idempotency-Key', 'kunci-idempoten-lunas-01')
        ->postJson('/api/v1/checkout', ['alamat_id' => $this->alamat->id])
        ->assertCreated();

    $kodePembayaran = $respon->json('data.pembayaran.kode');
    $totalRupiah = $respon->json('data.pembayaran.jumlah.rupiah');

    // Lunasi pembayaran via webhook
    $badan = json_encode([
        'id_peristiwa' => 'EVT-LUNAS-CHK',
        'kode_pembayaran' => $kodePembayaran,
        'jumlah_rupiah' => $totalRupiah,
    ]);
    $tanda = app(TandaTanganGateway::class)->buat($badan);

    $this->call('POST', '/api/v1/webhook/pembayaran', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_GATEWAY_SIGNATURE' => $tanda,
    ], $badan)->assertOk();

    // Waktu berjalan 25 jam (melewati batas bayar 24 jam)
    $this->travel(25)->hours();

    $dibatalkan = app(PembatalanOtomatis::class)->jalankan();

    expect($dibatalkan)->toBe(0)
        ->and(Pesanan::query()->where('pembeli_id', $this->pembeli->id)->first()->status)
        ->toBe(StatusPesanan::Dibayar);
});

it('pembatalan otomatis mengembalikan stok tepat sekali, urut id produk', function () {
    $stokAwal = $this->produkA->stok;

    PembantuCheckout::isiKeranjang($this->pembeli, $this->produkA, 3);

    $this->sebagai($this->pembeli)
        ->withHeader('Idempotency-Key', 'kunci-idempoten-batal-01')
        ->postJson('/api/v1/checkout', ['alamat_id' => $this->alamat->id])
        ->assertCreated();

    expect($this->produkA->refresh()->stok)->toBe($stokAwal - 3);

    // Waktu lewat 25 jam
    $this->travel(25)->hours();

    $dibatalkan = app(PembatalanOtomatis::class)->jalankan();

    expect($dibatalkan)->toBe(1)
        ->and($this->produkA->refresh()->stok)->toBe($stokAwal);

    // Pemanggilan kedua tidak mengembalikan stok lagi
    $dibatalkanLagi = app(PembatalanOtomatis::class)->jalankan();
    expect($dibatalkanLagi)->toBe(0)
        ->and($this->produkA->refresh()->stok)->toBe($stokAwal);
});
