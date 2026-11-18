<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Pesanan\StatusPesanan;
use App\Domain\Uang;
use App\Enums\JenisDompet;
use App\Enums\JenisMutasi;
use App\Enums\PeristiwaAudit;
use App\Exceptions\GalatDomain;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;

final class LayananDana
{
    public function __construct(
        private readonly BukuBesar $buku,
        private readonly PencatatAudit $audit,
    ) {}

    public function lepas(Pesanan $pesanan): void
    {
        DB::transaction(function () use ($pesanan): void {
            $terkunci = Pesanan::query()->lockForUpdate()->findOrFail($pesanan->id);

            // Idempoten: pemanggilan kedua tidak memindahkan apa pun.
            if ($terkunci->dana_dilepas_pada !== null || $terkunci->dana_dikembalikan_pada !== null) {
                return;
            }
            if ($terkunci->status !== StatusPesanan::Selesai) {
                throw new GalatDomain('LP-12', 'Dana hanya dilepas untuk pesanan yang sudah selesai.', 409);
            }

            $komisi = Uang::dari($terkunci->subtotal_rupiah)->persen((int) config('lapak.komisi_persen'));
            $total = Uang::dari($terkunci->total_rupiah);
            $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);

            $this->buku->pindahkan(
                $escrow,
                $this->buku->dompetPenjual($terkunci->toko->penjual),
                $total->kurang($komisi),
                JenisMutasi::LepasDana,
                $terkunci->nomor,
            );
            $this->buku->pindahkan(
                $escrow,
                $this->buku->dompetSistem(JenisDompet::Pendapatan),
                $komisi,
                JenisMutasi::Komisi,
                $terkunci->nomor,
            );

            $terkunci->forceFill(['dana_dilepas_pada' => now()])->save();

            $this->audit->catat(
                PeristiwaAudit::DanaDilepas,
                pelaku: null,
                sasaran: (string) $terkunci->id,
                rujukan: $terkunci->nomor,
                data: ['komisi' => $komisi->rupiah, 'penjual' => $total->kurang($komisi)->rupiah],
            );
        });
    }

    public function kembalikan(Pesanan $pesanan): void
    {
        DB::transaction(function () use ($pesanan): void {
            $terkunci = Pesanan::query()->lockForUpdate()->findOrFail($pesanan->id);

            // Idempoten: pemanggilan kedua tidak memindahkan apa pun.
            if ($terkunci->dana_dilepas_pada !== null || $terkunci->dana_dikembalikan_pada !== null) {
                return;
            }

            $total = Uang::dari($terkunci->total_rupiah);
            $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);
            $eksternal = $this->buku->dompetSistem(JenisDompet::Eksternal);

            $this->buku->pindahkan(
                $escrow,
                $eksternal,
                $total,
                JenisMutasi::Pengembalian,
                $terkunci->nomor,
            );

            $terkunci->forceFill(['dana_dikembalikan_pada' => now()])->save();
        });
    }
}
