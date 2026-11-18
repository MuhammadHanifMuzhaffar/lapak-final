<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Uang;
use App\Enums\JenisDompet;
use App\Enums\JenisMutasi;
use App\Exceptions\GalatDomain;
use App\Models\Dompet;
use App\Models\Pencairan;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

final class LayananPencairan
{
    public function __construct(
        private readonly BukuBesar $buku,
    ) {}

    public function ajukan(Pengguna $penjual, int $jumlah, Pengguna $pengaju): Pencairan
    {
        $minimum = (int) config('lapak.pencairan_minimum_rupiah');
        if ($jumlah < $minimum) {
            throw new GalatDomain('PENCAIRAN_MINIMUM', 'Minimal pencairan Rp '.number_format($minimum, 0, ',', '.').'.', 422);
        }

        return DB::transaction(function () use ($penjual, $jumlah, $pengaju): Pencairan {
            $dompet = Dompet::query()->lockForUpdate()->findOrFail(
                $this->buku->dompetPenjual($penjual)->id,
            );

            if (Pencairan::query()->where('penjual_id', $penjual->id)->where('status', 'menunggu')->exists()) {
                throw new GalatDomain('PENCAIRAN_GANDA', 'Masih ada pencairan yang menunggu keputusan.', 409);
            }

            $pencairan = Pencairan::create([
                'penjual_id' => $penjual->id,
                'jumlah_rupiah' => $jumlah,
                'status' => 'menunggu',
                'diajukan_oleh' => $pengaju->id,
            ]);

            $this->buku->pindahkan(
                $dompet,
                $this->buku->dompetSistem(JenisDompet::Pencairan),
                Uang::dari($jumlah),
                JenisMutasi::PenahananPencairan,
                'PC-'.$pencairan->id,
            );

            return $pencairan;
        });
    }

    public function putuskan(Pencairan $pencairan, Pengguna $penyetuju, bool $setuju): Pencairan
    {
        return DB::transaction(function () use ($pencairan, $penyetuju, $setuju): Pencairan {
            $terkunci = Pencairan::query()->lockForUpdate()->findOrFail($pencairan->id);

            if ($terkunci->status !== 'menunggu') {
                throw new GalatDomain('SUDAH_DIPUTUS', 'Pencairan ini sudah diputuskan.', 409);
            }
            if ($terkunci->diajukan_oleh === $penyetuju->id) {
                throw new GalatDomain('DUA_ORANG', 'Pengaju tidak boleh menyetujui pencairannya sendiri.', 403);
            }

            $tahan = $this->buku->dompetSistem(JenisDompet::Pencairan);

            if ($setuju) {
                $this->buku->pindahkan(
                    $tahan,
                    $this->buku->dompetSistem(JenisDompet::Eksternal),
                    Uang::dari($terkunci->jumlah_rupiah),
                    JenisMutasi::Pencairan,
                    'PC-'.$terkunci->id,
                );
            } else {
                $this->buku->pindahkan(
                    $tahan,
                    $this->buku->dompetPenjual($terkunci->penjual),
                    Uang::dari($terkunci->jumlah_rupiah),
                    JenisMutasi::Pengembalian,
                    'PC-'.$terkunci->id,
                );
            }

            $terkunci->forceFill([
                'status' => $setuju ? 'dibayar' : 'ditolak',
                'diputuskan_oleh' => $penyetuju->id,
                'diputuskan_pada' => now(),
            ])->save();

            return $terkunci;
        });
    }
}
