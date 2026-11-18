<?php

declare(strict_types=1);

use App\Domain\Pesanan\StatusPesanan;
use App\Models\ButirPesanan;
use App\Models\Checkout;
use App\Models\MutasiStok;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Toko;

beforeEach(function () {
    $this->penjual = Pengguna::factory()->penjual()->create();
    $this->toko = Toko::factory()->for($this->penjual, 'pemilik')->create();
    $this->produk = Produk::factory()->for($this->toko)->create(['stok' => 10]);

    $this->pembeli = Pengguna::factory()->pembeli()->create();
    $this->checkout = Checkout::factory()->create(['pembeli_id' => $this->pembeli->id]);
});

function buatPesananUji(Toko $toko, Pengguna $pembeli, Checkout $checkout, StatusPesanan $status): Pesanan
{
    $pesanan = Pesanan::factory()->for($toko)->create([
        'checkout_id' => $checkout->id,
        'pembeli_id' => $pembeli->id,
        'status' => $status,
        'subtotal_rupiah' => 50_000,
        'ongkir_rupiah' => 10_000,
        'total_rupiah' => 60_000,
        'dibayar_pada' => $status !== StatusPesanan::MenungguPembayaran ? now() : null,
        'dikemas_pada' => in_array($status, [StatusPesanan::Dikemas, StatusPesanan::Dikirim, StatusPesanan::Selesai], true) ? now() : null,
        'dikirim_pada' => in_array($status, [StatusPesanan::Dikirim, StatusPesanan::Selesai], true) ? now() : null,
    ]);

    ButirPesanan::create([
        'pesanan_id' => $pesanan->id,
        'produk_id' => $toko->produk()->first()?->id ?? Produk::factory()->for($toko)->create()->id,
        'nama_produk' => 'Produk Uji',
        'harga_rupiah' => 50_000,
        'kuantitas' => 1,
        'subtotal_rupiah' => 50_000,
    ]);

    return $pesanan;
}

it('pesanan milik toko lain dijawab 404 bukan 403 pada show, proses, dan kirim', function () {
    $penjualLain = Pengguna::factory()->penjual()->create();
    $tokoLain = Toko::factory()->for($penjualLain, 'pemilik')->create();
    $pesananLain = buatPesananUji($tokoLain, $this->pembeli, $this->checkout, StatusPesanan::Dibayar);

    // Penjual pertama mencoba mengakses pesanan milik toko lain -> dijawab 404
    $this->sebagai($this->penjual)
        ->getJson("/api/v1/toko/pesanan/{$pesananLain->id}")
        ->assertNotFound();

    $this->sebagai($this->penjual)
        ->postJson("/api/v1/toko/pesanan/{$pesananLain->id}/proses")
        ->assertNotFound();

    $this->sebagai($this->penjual)
        ->postJson("/api/v1/toko/pesanan/{$pesananLain->id}/kirim")
        ->assertNotFound();

    $this->sebagai($this->penjual)
        ->getJson("/api/v1/toko/pesanan/{$pesananLain->id}/riwayat")
        ->assertNotFound();
});

it('penjual memproses pesanan dibayar: status berubah menjadi dikemas dan dikemas_pada terisi', function () {
    $pesanan = buatPesananUji($this->toko, $this->pembeli, $this->checkout, StatusPesanan::Dibayar);

    $this->sebagai($this->penjual)
        ->postJson("/api/v1/toko/pesanan/{$pesanan->id}/proses")
        ->assertOk()
        ->assertJsonPath('data.status', 'dikemas');

    $pesanan->refresh();
    expect($pesanan->status)->toBe(StatusPesanan::Dikemas)
        ->and($pesanan->dikemas_pada)->not->toBeNull();
});

it('penjual mengirim pesanan dikemas: status berubah menjadi dikirim dan dikirim_pada terisi', function () {
    $pesanan = buatPesananUji($this->toko, $this->pembeli, $this->checkout, StatusPesanan::Dikemas);

    $this->sebagai($this->penjual)
        ->postJson("/api/v1/toko/pesanan/{$pesanan->id}/kirim")
        ->assertOk()
        ->assertJsonPath('data.status', 'dikirim');

    $pesanan->refresh();
    expect($pesanan->status)->toBe(StatusPesanan::Dikirim)
        ->and($pesanan->dikirim_pada)->not->toBeNull();
});

it('pembeli mencoba rute toko dijawab 403 DITOLAK', function () {
    $pesanan = buatPesananUji($this->toko, $this->pembeli, $this->checkout, StatusPesanan::Dibayar);

    $this->sebagai($this->pembeli)
        ->getJson('/api/v1/toko/pesanan')
        ->assertForbidden()
        ->assertJsonPath('galat.kode', 'DITOLAK');

    $this->sebagai($this->pembeli)
        ->getJson("/api/v1/toko/pesanan/{$pesanan->id}")
        ->assertForbidden()
        ->assertJsonPath('galat.kode', 'DITOLAK');

    $this->sebagai($this->pembeli)
        ->postJson("/api/v1/toko/pesanan/{$pesanan->id}/proses")
        ->assertForbidden()
        ->assertJsonPath('galat.kode', 'DITOLAK');

    $this->sebagai($this->pembeli)
        ->postJson("/api/v1/toko/pesanan/{$pesanan->id}/kirim")
        ->assertForbidden()
        ->assertJsonPath('galat.kode', 'DITOLAK');

    $this->sebagai($this->pembeli)
        ->getJson("/api/v1/toko/pesanan/{$pesanan->id}/riwayat")
        ->assertForbidden()
        ->assertJsonPath('galat.kode', 'DITOLAK');
});

it('transisi tidak sah dijawab 409', function () {
    // Pesanan sudah dikirim tidak boleh diproses ulang menjadi dikemas
    $pesananDikirim = buatPesananUji($this->toko, $this->pembeli, $this->checkout, StatusPesanan::Dikirim);

    $this->sebagai($this->penjual)
        ->postJson("/api/v1/toko/pesanan/{$pesananDikirim->id}/proses")
        ->assertStatus(409);

    // Pesanan belum dibayar tidak boleh langsung dikirim
    $pesananBelumBayar = buatPesananUji($this->toko, $this->pembeli, $this->checkout, StatusPesanan::MenungguPembayaran);

    $this->sebagai($this->penjual)
        ->postJson("/api/v1/toko/pesanan/{$pesananBelumBayar->id}/kirim")
        ->assertStatus(409);
});

it('riwayat hanya memuat baris audit dengan rujukan nomor pesanan tersebut', function () {
    $pesanan1 = buatPesananUji($this->toko, $this->pembeli, $this->checkout, StatusPesanan::Dibayar);
    $checkout2 = Checkout::factory()->create(['pembeli_id' => $this->pembeli->id]);
    $pesanan2 = buatPesananUji($this->toko, $this->pembeli, $checkout2, StatusPesanan::Dibayar);

    $this->sebagai($this->penjual)->postJson("/api/v1/toko/pesanan/{$pesanan1->id}/proses")->assertOk();
    $this->sebagai($this->penjual)->postJson("/api/v1/toko/pesanan/{$pesanan2->id}/proses")->assertOk();

    $respons = $this->sebagai($this->penjual)
        ->getJson("/api/v1/toko/pesanan/{$pesanan1->id}/riwayat")
        ->assertOk();

    $baris = $respons->json('data');
    expect($baris)->not->toBeEmpty();

    foreach ($baris as $item) {
        expect($item['rujukan'])->toBe($pesanan1->nomor)
            ->and($item['rujukan'])->not->toBe($pesanan2->nomor);
    }
});

it('penyesuaian stok menulis satu baris mutasi_stok dengan stok_setelah yang benar', function () {
    $stokAwal = $this->produk->stok;

    $this->sebagai($this->penjual)->postJson(
        "/api/v1/toko-saya/produk/{$this->produk->id}/stok",
        ['selisih' => 5],
        ['Idempotency-Key' => 'kunci-uji-stok-mutasi-12345'],
    )->assertOk();

    $mutasi = MutasiStok::query()
        ->where('produk_id', $this->produk->id)
        ->latest('id')
        ->first();

    expect($mutasi)->not->toBeNull()
        ->and($mutasi->jenis)->toBe('penyesuaian')
        ->and($mutasi->jumlah)->toBe(5)
        ->and($mutasi->stok_setelah)->toBe($stokAwal + 5);
});

it('seluruh 5 rute baru lolos matriks otorisasi', function () {
    $matriks = require __DIR__.'/../Sistem/matriks/katalog.php';

    $ruteBaru = [
        'GET api/v1/toko/pesanan',
        'GET api/v1/toko/pesanan/{pesanan}',
        'GET api/v1/toko/pesanan/{pesanan}/riwayat',
        'POST api/v1/toko/pesanan/{pesanan}/proses',
        'POST api/v1/toko/pesanan/{pesanan}/kirim',
    ];

    foreach ($ruteBaru as $rute) {
        expect(array_key_exists($rute, $matriks))->toBeTrue("Rute {$rute} wajib tercatat di matriks otorisasi");
        expect($matriks[$rute])->toBe(['penjual']);
    }

    // Penjual dapat mengakses index pesanan toko
    $this->sebagai($this->penjual)
        ->getJson('/api/v1/toko/pesanan')
        ->assertOk();
});
