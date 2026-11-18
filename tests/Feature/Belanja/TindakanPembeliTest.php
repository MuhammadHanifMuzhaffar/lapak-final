<?php

declare(strict_types=1);

use App\Domain\Pesanan\StatusPesanan;
use App\Domain\Uang;
use App\Enums\JenisDompet;
use App\Enums\JenisMutasi;
use App\Enums\PeristiwaAudit;
use App\Models\ButirPesanan;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Toko;
use App\Services\BukuBesar;
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

it('pembeli dapat menerima pesanan yang sudah dikirim: status selesai dan dana dilepas', function () {
    $pesanan = Pesanan::factory()->for($this->toko)->create([
        'pembeli_id' => $this->pembeli->id,
        'status' => StatusPesanan::Dikirim,
        'subtotal_rupiah' => 90_000,
        'ongkir_rupiah' => 10_000,
        'total_rupiah' => 100_000,
        'dikirim_pada' => now()->subDay(),
    ]);

    // Masukkan 100.000 ke escrow
    $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);
    $eksternal = $this->buku->dompetSistem(JenisDompet::Eksternal);
    $dompetPenjual = $this->buku->dompetPenjual($this->penjual);
    $this->buku->pindahkan(
        $eksternal,
        $escrow,
        Uang::dari(100_000),
        JenisMutasi::DanaMasuk,
        $pesanan->nomor,
    );

    $this->sebagai($this->pembeli)
        ->postJson("/api/v1/pesanan/{$pesanan->nomor}/terima")
        ->assertOk()
        ->assertJsonPath('data.status', 'selesai');

    expect($pesanan->refresh()->status)->toBe(StatusPesanan::Selesai)
        ->and($pesanan->selesai_pada)->not->toBeNull()
        ->and($pesanan->dana_dilepas_pada)->not->toBeNull()
        ->and($escrow->refresh()->saldo_rupiah)->toBe(0)
        // Penjual menerima: total 100.000 - komisi 5% dari 90.000 (4.500) = 95.500
        ->and($dompetPenjual->refresh()->saldo_rupiah)->toBe(95_500);

    // Audit peristiwa tercatat
    expect(LogAudit::query()->where('rujukan', $pesanan->nomor)->where('peristiwa', PeristiwaAudit::StatusPesanan->value)->exists())->toBeTrue();
});

it('menolak tindakan terima pada pesanan milik pembeli lain dengan 404', function () {
    $pembeliLain = Pengguna::factory()->pembeli()->create();
    $pesanan = Pesanan::factory()->for($this->toko)->create([
        'pembeli_id' => $pembeliLain->id,
        'status' => StatusPesanan::Dikirim,
    ]);

    $this->sebagai($this->pembeli)
        ->postJson("/api/v1/pesanan/{$pesanan->nomor}/terima")
        ->assertNotFound();
});

it('menolak tindakan terima bila pesanan belum dikirim dengan 409', function () {
    $pesanan = Pesanan::factory()->for($this->toko)->create([
        'pembeli_id' => $this->pembeli->id,
        'status' => StatusPesanan::Dibayar,
    ]);

    $this->sebagai($this->pembeli)
        ->postJson("/api/v1/pesanan/{$pesanan->nomor}/terima")
        ->assertStatus(409);
});

it('menolak tindakan terima bila sesi pembeli tidak segar dengan 403', function () {
    $pesanan = Pesanan::factory()->for($this->toko)->create([
        'pembeli_id' => $this->pembeli->id,
        'status' => StatusPesanan::Dikirim,
    ]);

    $this->sebagai($this->pembeli, segar: false)
        ->postJson("/api/v1/pesanan/{$pesanan->nomor}/terima")
        ->assertStatus(403);
});

it('pembeli dapat membatalkan pesanan yang belum dikirim dan mengembalikan stok serta dana', function () {
    $pesanan = Pesanan::factory()->for($this->toko)->create([
        'pembeli_id' => $this->pembeli->id,
        'status' => StatusPesanan::Dibayar,
        'subtotal_rupiah' => 90_000,
        'ongkir_rupiah' => 10_000,
        'total_rupiah' => 100_000,
    ]);

    // Tambahkan butir pesanan sebanyak 2 produk
    ButirPesanan::create([
        'pesanan_id' => $pesanan->id,
        'produk_id' => $this->produk->id,
        'nama_produk' => $this->produk->nama,
        'harga_rupiah' => 45_000,
        'kuantitas' => 2,
        'subtotal_rupiah' => 90_000,
    ]);

    // Kurangi stok produk seolah sudah di-checkout
    $this->produk->decrement('stok', 2);
    expect($this->produk->refresh()->stok)->toBe(8);

    // Dana 100.000 sudah masuk escrow
    $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);
    $eksternal = $this->buku->dompetSistem(JenisDompet::Eksternal);
    $this->buku->pindahkan(
        $eksternal,
        $escrow,
        Uang::dari(100_000),
        JenisMutasi::DanaMasuk,
        $pesanan->nomor,
    );

    $this->sebagai($this->pembeli)
        ->postJson("/api/v1/pesanan/{$pesanan->nomor}/batal", ['alasan' => 'Ingin ganti alamat'])
        ->assertOk()
        ->assertJsonPath('data.status', 'dibatalkan');

    expect($pesanan->refresh()->status)->toBe(StatusPesanan::Dibatalkan)
        ->and($pesanan->dibatalkan_pada)->not->toBeNull()
        ->and($pesanan->dana_dikembalikan_pada)->not->toBeNull()
        // Stok dikembalikan
        ->and($this->produk->refresh()->stok)->toBe(10)
        // Dana escrow dikembalikan ke eksternal
        ->and($escrow->refresh()->saldo_rupiah)->toBe(0);
});

it('menolak pembatalan pesanan yang sudah dikirim oleh pembeli dengan 409', function () {
    $pesanan = Pesanan::factory()->for($this->toko)->create([
        'pembeli_id' => $this->pembeli->id,
        'status' => StatusPesanan::Dikirim,
    ]);

    $this->sebagai($this->pembeli)
        ->postJson("/api/v1/pesanan/{$pesanan->nomor}/batal")
        ->assertStatus(409);
});
