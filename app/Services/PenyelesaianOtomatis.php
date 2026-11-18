<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Pesanan\PelakuPesanan;
use App\Domain\Pesanan\StatusPesanan;
use App\Models\Pesanan;

final class PenyelesaianOtomatis
{
    public function __construct(private readonly LayananPesanan $layanan) {}

    public function jalankan(): int
    {
        $selesai = 0;
        $batas = now()->subDays((int) config('lapak.tahan_dana_hari'));

        Pesanan::query()
            ->where('status', StatusPesanan::Dikirim)
            ->where('dikirim_pada', '<=', $batas)
            ->orderBy('id')
            ->chunkById(100, function ($daftar) use (&$selesai): void {
                foreach ($daftar as $pesanan) {
                    // Dibaca ulang di dalam kunci oleh layanan; pesanan yang baru saja
                    // dikonfirmasi pembeli tidak lagi berstatus dikirim.
                    $this->layanan->ubahStatus(
                        $pesanan, StatusPesanan::Selesai, PelakuPesanan::Sistem, null, 'Penyelesaian otomatis',
                    );
                    $selesai++;
                }
            });

        return $selesai;
    }
}
