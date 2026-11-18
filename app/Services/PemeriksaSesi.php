<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Sesi\KunciSesi;
use App\Enums\PeristiwaAudit;
use App\Enums\StatusAkun;
use App\Models\Sesi;
use Illuminate\Support\Facades\DB;

final class PemeriksaSesi
{
    public function __construct(
        private readonly PenerbitSesi $penerbit,
        private readonly PencatatAudit $audit,
    ) {}

    public function periksa(?string $teksKunci): ?Sesi
    {
        $kunci = KunciSesi::urai($teksKunci ?? '');
        if ($kunci === null) {
            return null;
        }

        $sesi = Sesi::query()
            ->berlaku()
            ->with('pengguna')
            ->where('pengenal', $kunci->pengenal)
            ->first();

        if ($sesi === null) {
            $this->catatPemakaianUlang($kunci);

            return null;
        }

        if (! $kunci->cocok($sesi->hash_kunci)) {
            return null;
        }

        if ($sesi->pengguna->status !== StatusAkun::Aktif) {
            return null;
        }

        $this->sentuh($sesi);

        return $sesi;
    }

    private function catatPemakaianUlang(KunciSesi $kunci): void
    {
        $lama = Sesi::query()->with('pengguna')->where('pengenal', $kunci->pengenal)->first();

        // Hanya bereaksi bila pemegang kunci benar-benar membuktikan possession, dan sesinya
        // dicabut eksplisit atau sudah lewat batas mutlak. Menganggur saja bukan alasan.
        if ($lama === null || ! $kunci->cocok($lama->hash_kunci)) {
            return;
        }

        if ($lama->dicabut_pada === null && $lama->kedaluwarsa_pada->isFuture()) {
            return;
        }

        DB::transaction(function () use ($lama): void {
            $jumlah = $this->penerbit->cabutSemua($lama->pengguna);

            $this->audit->catat(
                PeristiwaAudit::SesiDipakaiUlang,
                pelaku: $lama->pengguna,
                rujukan: $lama->pengenal,
                data: ['sesi_dicabut' => $jumlah, 'ip' => request()->ip()],
            );
        });
    }

    private function sentuh(Sesi $sesi): void
    {
        $batas = now()->subSeconds(config('lapak.sesi.jeda_sentuh_detik'));

        if ($sesi->terakhir_dipakai_pada->lt($batas)) {
            $sesi->forceFill(['terakhir_dipakai_pada' => now()])->save();
        }
    }
}
