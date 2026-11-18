<?php

declare(strict_types=1);

use App\Domain\Pesanan\StatusPesanan;
use App\Domain\Uang;
use App\Enums\JenisDompet;
use App\Enums\JenisMutasi;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Toko;
use App\Services\BukuBesar;
use App\Services\PenyelesaianOtomatis;
use Illuminate\Support\Facades\Artisan;
use Tests\Pendukung\PembantuCheckout;

beforeEach(function () {
    $this->buku = app(BukuBesar::class);
    [$this->pembeli, $this->alamat] = PembantuCheckout::buatPembeliDanAlamat();
    $this->penjual = Pengguna::factory()->penjual()->create();
    $this->toko = Toko::factory()->for($this->penjual, 'pemilik')->create(['ongkir_rupiah' => 10_000]);
    $this->produk = Produk::factory()->for($this->toko)->create([
        'harga_rupiah' => 90_000,
        'stok' => 10,
    ]);
});

it('menyelesaikan pesanan dikirim yang telah melewati batas tahan dana', function () {
    $pesananLewat = Pesanan::factory()->for($this->toko)->create([
        'pembeli_id' => $this->pembeli->id,
        'status' => StatusPesanan::Dikirim,
        'subtotal_rupiah' => 90_000,
        'ongkir_rupiah' => 10_000,
        'total_rupiah' => 100_000,
        'dikirim_pada' => now()->subDays(4),
    ]);

    $pesananBaru = Pesanan::factory()->for($this->toko)->create([
        'pembeli_id' => $this->pembeli->id,
        'status' => StatusPesanan::Dikirim,
        'subtotal_rupiah' => 90_000,
        'ongkir_rupiah' => 10_000,
        'total_rupiah' => 100_000,
        'dikirim_pada' => now()->subDay(),
    ]);

    // Masukkan dana escrow untuk pesanan yang lewat
    $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);
    $eksternal = $this->buku->dompetSistem(JenisDompet::Eksternal);
    $this->buku->pindahkan(
        $eksternal,
        $escrow,
        Uang::dari(100_000),
        JenisMutasi::DanaMasuk,
        $pesananLewat->nomor,
    );

    $selesai = app(PenyelesaianOtomatis::class)->jalankan();

    expect($selesai)->toBe(1)
        ->and($pesananLewat->refresh()->status)->toBe(StatusPesanan::Selesai)
        ->and($pesananLewat->selesai_pada)->not->toBeNull()
        ->and($pesananLewat->dana_dilepas_pada)->not->toBeNull()
        // Pesanan yang baru 1 hari tetap dikirim
        ->and($pesananBaru->refresh()->status)->toBe(StatusPesanan::Dikirim);
});

it('perintah konsol lapak:selesaikan-pesanan menyelesaikan pesanan lewat batas waktu', function () {
    $pesanan = Pesanan::factory()->for($this->toko)->create([
        'pembeli_id' => $this->pembeli->id,
        'status' => StatusPesanan::Dikirim,
        'subtotal_rupiah' => 90_000,
        'ongkir_rupiah' => 10_000,
        'total_rupiah' => 100_000,
        'dikirim_pada' => now()->subDays(4),
    ]);

    $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);
    $eksternal = $this->buku->dompetSistem(JenisDompet::Eksternal);
    $this->buku->pindahkan(
        $eksternal,
        $escrow,
        Uang::dari(100_000),
        JenisMutasi::DanaMasuk,
        $pesanan->nomor,
    );

    $kode = Artisan::call('lapak:selesaikan-pesanan');

    expect($kode)->toBe(0)
        ->and(Artisan::output())->toContain('1 pesanan diselesaikan')
        ->and($pesanan->refresh()->status)->toBe(StatusPesanan::Selesai);
});
