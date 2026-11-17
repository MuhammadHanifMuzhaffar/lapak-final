<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LogAudit;
use App\Models\RantaiAudit;

final class PemeriksaRantaiAudit
{
    /**
     * @return list<string> pesan pelanggaran, kosong bila utuh
     */
    public function periksa(?int $dari = null): array
    {
        $pelanggaran = [];
        $sebelumnya = str_repeat('0', 64);

        if ($dari !== null && $dari > 1) {
            $barisSebelum = LogAudit::query()->where('nomor', $dari - 1)->first();
            if ($barisSebelum !== null) {
                $sebelumnya = $barisSebelum->hash;
            }
        }

        $nomorTerakhir = 0;

        foreach (LogAudit::query()->when($dari, fn ($q) => $q->where('nomor', '>=', $dari))
            ->orderBy('nomor')->cursor() as $baris) {
            $nomorTerakhir = $baris->nomor;

            if ($baris->hash_sebelumnya !== $sebelumnya) {
                $pelanggaran[] = "Baris {$baris->nomor}: hash_sebelumnya tidak menyambung.";

                return $pelanggaran;
            }

            if (! hash_equals(hash('sha256', $sebelumnya.'|'.$baris->kanonik()), $baris->hash)) {
                $pelanggaran[] = "Baris {$baris->nomor}: isi tidak cocok dengan hash.";

                return $pelanggaran;
            }

            $sebelumnya = $baris->hash;
        }

        $kepala = RantaiAudit::query()->firstOrFail();
        if ($dari === null || $nomorTerakhir === $kepala->nomor_terakhir) {
            if ($kepala->nomor_terakhir !== $nomorTerakhir || $kepala->hash_terakhir !== $sebelumnya) {
                $pelanggaran[] = 'Kepala rantai tidak cocok dengan baris terakhir.';
            }
        }

        return $pelanggaran;
    }
}
