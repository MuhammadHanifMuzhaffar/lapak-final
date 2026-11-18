<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Pesanan\PelakuPesanan;
use App\Domain\Pesanan\StatusPesanan;
use App\Enums\PeristiwaAudit;
use App\Models\Checkout;
use App\Models\Pengguna;
use App\Models\Pesanan;

final class LayananPesanan
{
    public function __construct(
        private readonly GembokCheckout $gembok,
        private readonly MesinStatusPesanan $mesin,
        private readonly PenjagaStok $stok,
        private readonly LayananDana $dana,
        private readonly PencatatAudit $audit,
    ) {}

    public function ubahStatus(
        Pesanan $pesanan,
        StatusPesanan $ke,
        PelakuPesanan $oleh,
        ?Pengguna $aktor = null,
        ?string $alasan = null,
    ): Pesanan {
        return $this->gembok->dalam($pesanan->checkout_id, function (Checkout $checkout) use (
            $pesanan, $ke, $oleh, $aktor, $alasan,
        ): Pesanan {
            return $this->ubahStatusTerkunci($pesanan->id, $ke, $oleh, $aktor, $alasan);
        });
    }

    /** Dipakai bila pemanggil sudah memegang gembok checkout. */
    public function ubahStatusTerkunci(
        int $idPesanan,
        StatusPesanan $ke,
        PelakuPesanan $oleh,
        ?Pengguna $aktor = null,
        ?string $alasan = null,
    ): Pesanan {
        $terkunci = Pesanan::query()->lockForUpdate()->findOrFail($idPesanan);
        $sebelum = $terkunci->status;

        $this->mesin->pindahkan($terkunci, $ke, $oleh, $alasan);
        $terkunci->forceFill($this->capWaktu($ke))->save();

        $this->akibat($terkunci, $sebelum, $ke);

        $this->audit->catat(
            PeristiwaAudit::StatusPesanan,
            pelaku: $aktor,
            sasaran: (string) $terkunci->id,
            rujukan: $terkunci->nomor,
            data: ['dari' => $sebelum->value, 'ke' => $ke->value, 'alasan' => $alasan],
        );

        return $terkunci->refresh();
    }

    private function capWaktu(StatusPesanan $ke): array
    {
        return match ($ke) {
            StatusPesanan::Dikemas => ['dikemas_pada' => now()],
            StatusPesanan::Dikirim => ['dikirim_pada' => now()],
            StatusPesanan::Selesai => ['selesai_pada' => now()],
            default => [],
        };
    }

    private function akibat(Pesanan $pesanan, StatusPesanan $sebelum, StatusPesanan $ke): void
    {
        if ($ke === StatusPesanan::Selesai) {
            $this->dana->lepas($pesanan);          // idempoten, dijaga di #54

            return;
        }

        if ($ke !== StatusPesanan::Dibatalkan) {
            return;
        }

        // Kumpulkan dahulu, urutkan, baru kembalikan: urutan kunci selalu sama.
        $kembali = [];
        foreach ($pesanan->butir()->orderBy('produk_id')->get() as $butir) {
            $kembali[$butir->produk_id] = ($kembali[$butir->produk_id] ?? 0) + $butir->kuantitas;
        }
        ksort($kembali);

        foreach ($kembali as $idProduk => $jumlah) {
            $this->stok->kembalikan($idProduk, $jumlah, $pesanan->nomor);
        }

        if ($sebelum === StatusPesanan::Dibayar || $sebelum === StatusPesanan::Dikemas) {
            $this->dana->kembalikan($pesanan);      // escrow kembali ke eksternal
        }
    }
}
