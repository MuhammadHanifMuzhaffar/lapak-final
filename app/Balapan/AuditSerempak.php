<?php

declare(strict_types=1);

namespace App\Balapan;

use App\Enums\PeristiwaAudit;
use App\Services\PemeriksaRantaiAudit;
use App\Services\PencatatAudit;
use Illuminate\Support\Facades\DB;

final class AuditSerempak implements SkenarioBalapan
{
    public const JUMLAH = 8;

    public function keterangan(): string
    {
        return 'Delapan penulis mencatat audit bersamaan: nomor tidak berlubang dan rantai tidak bercabang';
    }

    public function proses(): int
    {
        return self::JUMLAH;
    }

    public function siapkan(): array
    {
        return ['sebelum' => (int) DB::table('rantai_audit')->value('nomor_terakhir')];
    }

    public function jalankan(array $konteks, int $nomor): string
    {
        DB::transaction(function () use ($nomor): void {
            app(PencatatAudit::class)->catat(PeristiwaAudit::UjiBalapan, data: ['anak' => $nomor]);
        });

        return 'tercatat';
    }

    public function periksa(array $konteks, array $hasil): array
    {
        $pelanggaran = [];

        if (count(array_filter($hasil, fn ($h) => $h === 'tercatat')) !== self::JUMLAH) {
            $pelanggaran[] = 'Ada proses anak yang gagal mencatat.';
        }

        $nomor = DB::table('log_audit')
            ->where('nomor', '>', $konteks['sebelum'])
            ->orderBy('nomor')
            ->pluck('nomor')
            ->all();
        $harap = range($konteks['sebelum'] + 1, $konteks['sebelum'] + self::JUMLAH);
        if ($nomor !== $harap) {
            $pelanggaran[] = 'Nomor audit berlubang atau berurutan salah.';
        }

        return array_merge($pelanggaran, app(PemeriksaRantaiAudit::class)->periksa());
    }
}
