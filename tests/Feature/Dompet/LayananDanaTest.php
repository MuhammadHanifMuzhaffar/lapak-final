<?php

declare(strict_types=1);

use App\Domain\Pesanan\StatusPesanan;
use App\Domain\Uang;
use App\Enums\JenisDompet;
use App\Enums\JenisMutasi;
use App\Enums\PeristiwaAudit;
use App\Exceptions\GalatDomain;
use App\Models\LogAudit;
use App\Models\Pesanan;
use App\Services\BukuBesar;
use App\Services\LayananDana;

beforeEach(function () {
    $this->buku = app(BukuBesar::class);
    $this->layanan = app(LayananDana::class);
});

it('melepas dana pesanan selesai dengan komisi 5 persen dari subtotal dan ongkir utuh', function () {
    $pesanan = Pesanan::factory()->create([
        'status' => StatusPesanan::Selesai,
        'subtotal_rupiah' => 100_000,
        'ongkir_rupiah' => 18_000,
        'total_rupiah' => 118_000,
    ]);

    $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);
    $eksternal = $this->buku->dompetSistem(JenisDompet::Eksternal);
    $pendapatan = $this->buku->dompetSistem(JenisDompet::Pendapatan);
    $dompetPenjual = $this->buku->dompetPenjual($pesanan->toko->penjual);

    // Simulasi dana masuk dari gerbang ke escrow saat pesanan dibayar
    $this->buku->pindahkan(
        $eksternal,
        $escrow,
        Uang::dari(118_000),
        JenisMutasi::DanaMasuk,
        $pesanan->nomor,
    );

    $saldoEscrowAwal = $escrow->refresh()->saldo_rupiah;
    $saldoPenjualAwal = $dompetPenjual->refresh()->saldo_rupiah;
    $saldoPendapatanAwal = $pendapatan->refresh()->saldo_rupiah;

    $this->layanan->lepas($pesanan);

    // Escrow berkurang 118.000, penjual bertambah 113.000, pendapatan (komisi) bertambah 5.000
    expect($escrow->refresh()->saldo_rupiah)->toBe($saldoEscrowAwal - 118_000)
        ->and($dompetPenjual->refresh()->saldo_rupiah)->toBe($saldoPenjualAwal + 113_000)
        ->and($pendapatan->refresh()->saldo_rupiah)->toBe($saldoPendapatanAwal + 5_000)
        ->and($pesanan->refresh()->dana_dilepas_pada)->not->toBeNull();

    // Catatan audit tersimpan
    $audit = LogAudit::query()->where('peristiwa', PeristiwaAudit::DanaDilepas->value)->latest('id')->first();
    expect($audit)->not->toBeNull()
        ->and($audit->rujukan)->toBe($pesanan->nomor)
        ->and($audit->data)->toMatchArray(['komisi' => 5_000, 'penjual' => 113_000]);
});

it('idempoten: pemanggilan lepas kedua tidak memindahkan dana lagi', function () {
    $pesanan = Pesanan::factory()->create([
        'status' => StatusPesanan::Selesai,
        'subtotal_rupiah' => 100_000,
        'ongkir_rupiah' => 18_000,
        'total_rupiah' => 118_000,
    ]);

    $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);
    $eksternal = $this->buku->dompetSistem(JenisDompet::Eksternal);
    $dompetPenjual = $this->buku->dompetPenjual($pesanan->toko->penjual);

    $this->buku->pindahkan(
        $eksternal,
        $escrow,
        Uang::dari(118_000),
        JenisMutasi::DanaMasuk,
        $pesanan->nomor,
    );

    $this->layanan->lepas($pesanan);

    $saldoEscrowSetelahLepas = $escrow->refresh()->saldo_rupiah;
    $saldoPenjualSetelahLepas = $dompetPenjual->refresh()->saldo_rupiah;

    // Pemanggilan kedua
    $this->layanan->lepas($pesanan);

    expect($escrow->refresh()->saldo_rupiah)->toBe($saldoEscrowSetelahLepas)
        ->and($dompetPenjual->refresh()->saldo_rupiah)->toBe($saldoPenjualSetelahLepas);
});

it('menolak pelepasan dana bila status pesanan belum selesai', function () {
    $pesanan = Pesanan::factory()->create([
        'status' => StatusPesanan::Dibayar,
        'subtotal_rupiah' => 100_000,
        'ongkir_rupiah' => 18_000,
        'total_rupiah' => 118_000,
    ]);

    $this->layanan->lepas($pesanan);
})->throws(GalatDomain::class, 'Dana hanya dilepas untuk pesanan yang sudah selesai.');

it('mengembalikan dana dari escrow ke eksternal saat pesanan dibatalkan', function () {
    $pesanan = Pesanan::factory()->create([
        'status' => StatusPesanan::Dibatalkan,
        'subtotal_rupiah' => 100_000,
        'ongkir_rupiah' => 18_000,
        'total_rupiah' => 118_000,
    ]);

    $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);
    $eksternal = $this->buku->dompetSistem(JenisDompet::Eksternal);

    $this->buku->pindahkan(
        $eksternal,
        $escrow,
        Uang::dari(118_000),
        JenisMutasi::DanaMasuk,
        $pesanan->nomor,
    );

    $saldoEscrowAwal = $escrow->refresh()->saldo_rupiah;
    $saldoEksternalAwal = $eksternal->refresh()->saldo_rupiah;

    $this->layanan->kembalikan($pesanan);

    expect($escrow->refresh()->saldo_rupiah)->toBe($saldoEscrowAwal - 118_000)
        ->and($eksternal->refresh()->saldo_rupiah)->toBe($saldoEksternalAwal + 118_000)
        ->and($pesanan->refresh()->dana_dikembalikan_pada)->not->toBeNull();

    // Idempoten
    $this->layanan->kembalikan($pesanan);
    expect($escrow->refresh()->saldo_rupiah)->toBe($saldoEscrowAwal - 118_000);
});
