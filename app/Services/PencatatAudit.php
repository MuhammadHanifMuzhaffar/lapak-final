<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PeristiwaAudit;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Models\RantaiAudit;
use Illuminate\Support\Facades\DB;
use LogicException;

final class PencatatAudit
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function catat(
        PeristiwaAudit $peristiwa,
        ?Pengguna $pelaku = null,
        ?string $sasaran = null,
        ?string $rujukan = null,
        array $data = [],
    ): LogAudit {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('PencatatAudit hanya boleh dipanggil di dalam transaksi.');
        }

        $kepala = RantaiAudit::query()->lockForUpdate()->firstOrFail();

        $baris = new LogAudit;
        $baris->forceFill([
            'nomor' => $kepala->nomor_terakhir + 1,
            'peristiwa' => $peristiwa->value,
            'pelaku_id' => $pelaku?->id,
            'sasaran' => $sasaran,
            'rujukan' => $rujukan,
            'data' => $data,
            'dibuat_pada' => now(),
            'hash_sebelumnya' => $kepala->hash_terakhir,
        ]);

        $baris->hash = hash('sha256', $baris->hash_sebelumnya.'|'.$baris->kanonik());
        $baris->save();

        $kepala->forceFill([
            'nomor_terakhir' => $baris->nomor,
            'hash_terakhir' => $baris->hash,
            'diperbarui_pada' => $baris->dibuat_pada,
        ])->save();

        return $baris;
    }
}
