<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MutasiStok;
use App\Models\Produk;

final class PencatatStok
{
    /** Hanya menambah baris; tidak ada update atau delete di kelas ini. */
    public function catat(Produk $produk, string $jenis, int $jumlah, int $stokSetelah, ?string $rujukan = null): void
    {
        $baris = new MutasiStok;
        $baris->forceFill([
            'produk_id' => $produk->id,
            'jenis' => $jenis,              // masuk | keluar | penyesuaian
            'jumlah' => $jumlah,            // bertanda: keluar negatif
            'stok_setelah' => $stokSetelah,
            'rujukan' => $rujukan,
            'dibuat_pada' => now(),
        ])->save();
    }
}
