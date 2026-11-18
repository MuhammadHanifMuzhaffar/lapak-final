<?php

declare(strict_types=1);

use App\Domain\Pesanan\StatusPesanan;
use App\Enums\JenisDompet;
use App\Models\Dompet;
use App\Models\MutasiStok;
use App\Models\Pencairan;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Services\BukuBesar;
use Illuminate\Support\Facades\Artisan;

it('invarian 5: periksa-buku gagal dan menyebut escrow saat saldo escrow digeser tanpa mengubah status pesanan', function () {
    $buku = app(BukuBesar::class);
    $escrow = $buku->dompetSistem(JenisDompet::Escrow);
    Dompet::query()->whereKey($escrow->id)->update(['saldo_rupiah' => 100_000]);

    Pesanan::factory()->create([
        'status' => StatusPesanan::Dibayar,
        'total_rupiah' => 100_000,
    ]);

    // Geser saldo escrow satu rupiah
    Dompet::query()->whereKey($escrow->id)->increment('saldo_rupiah', 1);

    $kodeKeluar = Artisan::call('lapak:periksa-buku');
    $keluaran = Artisan::output();

    expect($kodeKeluar)->toBe(1)
        ->and($keluaran)->toContain('Escrow 100001 tidak sama dengan pesanan ditahan 100000.');
});

it('invarian 6: periksa-buku gagal dan menyebut pencairan saat saldo dompet pencairan digeser tanpa mengubah pencairan', function () {
    $buku = app(BukuBesar::class);
    $tahan = $buku->dompetSistem(JenisDompet::Pencairan);
    Dompet::query()->whereKey($tahan->id)->update(['saldo_rupiah' => 50_000]);

    Pencairan::factory()->create([
        'status' => 'menunggu',
        'jumlah_rupiah' => 50_000,
    ]);

    // Geser saldo dompet pencairan
    Dompet::query()->whereKey($tahan->id)->increment('saldo_rupiah', 1);

    $kodeKeluar = Artisan::call('lapak:periksa-buku');
    $keluaran = Artisan::output();

    expect($kodeKeluar)->toBe(1)
        ->and($keluaran)->toContain('Dompet pencairan 50001 tidak sama dengan pencairan menunggu 50000.');
});

it('invarian 7: periksa-buku gagal bila rantai mutasi stok terputus atau tidak sama dengan stok produk', function () {
    $produk = Produk::factory()->create(['stok' => 15]);

    // Buat dua baris mutasi stok yang tidak berantai
    MutasiStok::create([
        'produk_id' => $produk->id,
        'jenis' => 'masuk',
        'jumlah' => 10,
        'stok_setelah' => 10,
        'rujukan' => 'PO-1',
        'dibuat_pada' => now(),
    ]);

    // Mutasi kedua salah: sebelumnya 10 + 5 harusnya 15, tapi dicatat 12
    MutasiStok::create([
        'produk_id' => $produk->id,
        'jenis' => 'masuk',
        'jumlah' => 5,
        'stok_setelah' => 12,
        'rujukan' => 'PO-2',
        'dibuat_pada' => now(),
    ]);

    $kodeKeluar = Artisan::call('lapak:periksa-buku');
    $keluaran = Artisan::output();

    expect($kodeKeluar)->toBe(1)
        ->and($keluaran)->toContain("Jejak stok {$produk->id} tidak berantai");
});

it('invarian 7: periksa-buku gagal bila akhir jejak mutasi tidak cocok dengan stok produk saat ini', function () {
    $produk = Produk::factory()->create(['stok' => 20]);

    MutasiStok::create([
        'produk_id' => $produk->id,
        'jenis' => 'masuk',
        'jumlah' => 10,
        'stok_setelah' => 10,
        'rujukan' => 'PO-1',
        'dibuat_pada' => now(),
    ]);

    $kodeKeluar = Artisan::call('lapak:periksa-buku');
    $keluaran = Artisan::output();

    expect($kodeKeluar)->toBe(1)
        ->and($keluaran)->toContain("Stok {$produk->id} tercatat 20, jejak berakhir 10.");
});
