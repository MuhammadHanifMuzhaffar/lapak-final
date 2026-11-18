<?php

declare(strict_types=1);

use App\Domain\Uang;
use App\Enums\JenisDompet;
use App\Enums\JenisMutasi;
use App\Exceptions\GalatDomain;
use App\Exceptions\SaldoTidakCukup;
use App\Models\Pencairan;
use App\Models\Pengguna;
use App\Services\BukuBesar;
use App\Services\LayananPencairan;

beforeEach(function () {
    $this->buku = app(BukuBesar::class);
    $this->layanan = app(LayananPencairan::class);
    $this->penjual = Pengguna::factory()->create();
    $this->pengaju = Pengguna::factory()->create();
    $this->penyetuju = Pengguna::factory()->create();

    // Isi saldo dompet penjual dengan 100.000
    $eksternal = $this->buku->dompetSistem(JenisDompet::Eksternal);
    $dompetPenjual = $this->buku->dompetPenjual($this->penjual);
    $this->buku->pindahkan(
        $eksternal,
        $dompetPenjual,
        Uang::dari(100_000),
        JenisMutasi::DanaMasuk,
        'SALDO-AWAL',
    );
});

it('berhasil mengajukan pencairan dan memindahkan dana ke dompet penahan', function () {
    $dompetPenjual = $this->buku->dompetPenjual($this->penjual);
    $dompetPencairan = $this->buku->dompetSistem(JenisDompet::Pencairan);

    $pencairan = $this->layanan->ajukan($this->penjual, 50_000, $this->pengaju);

    expect($pencairan->status)->toBe('menunggu')
        ->and($pencairan->jumlah_rupiah)->toBe(50_000)
        ->and($pencairan->diajukan_oleh)->toBe($this->pengaju->id)
        ->and($pencairan->penjual_id)->toBe($this->penjual->id)
        ->and($dompetPenjual->refresh()->saldo_rupiah)->toBe(50_000)
        ->and($dompetPencairan->refresh()->saldo_rupiah)->toBe(50_000);
});

it('menolak pengajuan bila jumlah di bawah batas minimum', function () {
    $this->layanan->ajukan($this->penjual, 49_999, $this->pengaju);
})->throws(GalatDomain::class, 'Minimal pencairan Rp 50.000.');

it('menolak pengajuan bila masih ada pencairan yang berstatus menunggu untuk penjual yang sama', function () {
    $this->layanan->ajukan($this->penjual, 50_000, $this->pengaju);

    // Pengajuan kedua
    $this->layanan->ajukan($this->penjual, 50_000, $this->pengaju);
})->throws(GalatDomain::class, 'Masih ada pencairan yang menunggu keputusan.');

it('menolak pengajuan bila saldo penjual tidak mencukupi', function () {
    $this->layanan->ajukan($this->penjual, 200_000, $this->pengaju);
})->throws(SaldoTidakCukup::class);

it('menolak keputusan bila pengaju mencoba menyetujui pencairannya sendiri (aturan dua orang)', function () {
    $pencairan = $this->layanan->ajukan($this->penjual, 50_000, $this->pengaju);

    $this->layanan->putuskan($pencairan, $this->pengaju, true);
})->throws(GalatDomain::class, 'Pengaju tidak boleh menyetujui pencairannya sendiri.');

it('berhasil menyetujui pencairan dan memindahkan dana ke dompet eksternal', function () {
    $dompetPencairan = $this->buku->dompetSistem(JenisDompet::Pencairan);
    $dompetEksternal = $this->buku->dompetSistem(JenisDompet::Eksternal);
    $saldoEksternalSebelum = $dompetEksternal->saldo_rupiah;

    $pencairan = $this->layanan->ajukan($this->penjual, 50_000, $this->pengaju);

    $hasil = $this->layanan->putuskan($pencairan, $this->penyetuju, true);

    expect($hasil->status)->toBe('dibayar')
        ->and($hasil->diputuskan_oleh)->toBe($this->penyetuju->id)
        ->and($hasil->diputuskan_pada)->not->toBeNull()
        ->and($dompetPencairan->refresh()->saldo_rupiah)->toBe(0)
        ->and($dompetEksternal->refresh()->saldo_rupiah)->toBe($saldoEksternalSebelum + 50_000);
});

it('berhasil menolak pencairan dan mengembalikan dana ke dompet penjual', function () {
    $dompetPenjual = $this->buku->dompetPenjual($this->penjual);
    $dompetPencairan = $this->buku->dompetSistem(JenisDompet::Pencairan);

    $pencairan = $this->layanan->ajukan($this->penjual, 50_000, $this->pengaju);

    $hasil = $this->layanan->putuskan($pencairan, $this->penyetuju, false);

    expect($hasil->status)->toBe('ditolak')
        ->and($hasil->diputuskan_oleh)->toBe($this->penyetuju->id)
        ->and($hasil->diputuskan_pada)->not->toBeNull()
        ->and($dompetPencairan->refresh()->saldo_rupiah)->toBe(0)
        ->and($dompetPenjual->refresh()->saldo_rupiah)->toBe(100_000);
});

it('menolak keputusan kedua atas pencairan yang sudah diputuskan', function () {
    $pencairan = $this->layanan->ajukan($this->penjual, 50_000, $this->pengaju);
    $this->layanan->putuskan($pencairan, $this->penyetuju, true);

    // Keputusan kedua
    $this->layanan->putuskan($pencairan, $this->penyetuju, false);
})->throws(GalatDomain::class, 'Pencairan ini sudah diputuskan.');
