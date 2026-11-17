<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NamaPeran;
use App\Enums\PeristiwaAudit;
use App\Models\Pengguna;
use App\Models\Peran;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use LogicException;

final class PengelolaPeran
{
    public function __construct(
        private readonly PemeriksaIzin $izin,
        private readonly PenerbitSesi $sesi,
        private readonly PencatatAudit $audit,
    ) {}

    public function beri(Pengguna $pengguna, NamaPeran $peran, ?Pengguna $oleh = null): bool
    {
        $idPeran = $this->idPeran($peran);

        if ($pengguna->peran()->where('peran.id', $idPeran)->exists()) {
            return false;
        }

        try {
            $pengguna->peran()->attach($idPeran, ['diberikan_oleh' => $oleh?->id]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        $jumlahSesi = $this->setelahBerubah($pengguna);

        if ($oleh !== null) {
            DB::transaction(function () use ($pengguna, $peran, $oleh, $jumlahSesi): void {
                $this->audit->catat(
                    PeristiwaAudit::PeranDiubah,
                    pelaku: $oleh,
                    sasaran: (string) $pengguna->id,
                    data: ['tindakan' => 'beri', 'peran' => $peran->value, 'sesi_dicabut' => $jumlahSesi],
                );
            });
        }

        return true;
    }

    public function cabut(Pengguna $pengguna, NamaPeran $peran, ?Pengguna $oleh = null): bool
    {
        if ($pengguna->peran()->detach($this->idPeran($peran)) === 0) {
            return false;
        }

        $jumlahSesi = $this->setelahBerubah($pengguna);

        if ($oleh !== null) {
            DB::transaction(function () use ($pengguna, $peran, $oleh, $jumlahSesi): void {
                $this->audit->catat(
                    PeristiwaAudit::PeranDiubah,
                    pelaku: $oleh,
                    sasaran: (string) $pengguna->id,
                    data: ['tindakan' => 'cabut', 'peran' => $peran->value, 'sesi_dicabut' => $jumlahSesi],
                );
            });
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function daftar(Pengguna $pengguna): array
    {
        return $pengguna->peran()->orderBy('peran.nama')->pluck('peran.nama')->all();
    }

    private function setelahBerubah(Pengguna $pengguna): int
    {
        if (method_exists($this->izin, 'lupakan')) {
            $this->izin->lupakan($pengguna);
        }

        return $this->sesi->cabutSemua($pengguna);
    }

    private function idPeran(NamaPeran $peran): int
    {
        $id = Peran::query()->where('nama', $peran->value)->value('id');

        if ($id === null) {
            throw new LogicException("Tabel peran belum memuat peran {$peran->value}. Jalankan seeder.");
        }

        return $id;
    }
}
