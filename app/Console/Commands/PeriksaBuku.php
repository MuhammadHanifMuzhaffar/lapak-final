<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pesanan\StatusPesanan;
use App\Enums\JenisDompet;
use App\Models\Dompet;
use App\Models\MutasiSaldo;
use App\Models\MutasiStok;
use App\Models\Pencairan;
use App\Models\Pesanan;
use App\Models\Produk;
use Illuminate\Console\Command;

final class PeriksaBuku extends Command
{
    protected $signature = 'lapak:periksa-buku';

    protected $description = 'Memeriksa tujuh invarian buku besar (LP-13); kode keluar 1 bila dilanggar.';

    public function handle(): int
    {
        $pelanggaran = [];

        // 1. Uang tidak tercipta dan tidak lenyap: jumlah seluruh mutasi harus nol.
        $jumlahSemua = (int) MutasiSaldo::query()->sum('jumlah_rupiah');
        if ($jumlahSemua !== 0) {
            $pelanggaran[] = "Jumlah seluruh mutasi adalah {$jumlahSemua}, seharusnya 0.";
        }

        // 2. Setiap pemindahan terdiri atas tepat dua baris yang saling meniadakan.
        $kelompokTimpang = MutasiSaldo::query()
            ->select('kelompok')
            ->groupBy('kelompok')
            ->havingRaw('count(*) <> 2 or sum(jumlah_rupiah) <> 0')
            ->pluck('kelompok');
        foreach ($kelompokTimpang as $kelompok) {
            $pelanggaran[] = "Kelompok {$kelompok} timpang.";
        }

        $baris = [];
        foreach (Dompet::query()->withSum('mutasi', 'jumlah_rupiah')->orderBy('id')->get() as $dompet) {
            $jumlahMutasi = (int) $dompet->mutasi_sum_jumlah_rupiah;

            // 3. Saldo tersimpan harus sama dengan jumlah mutasinya.
            if ($dompet->saldo_rupiah !== $jumlahMutasi) {
                $pelanggaran[] = "Saldo {$dompet->kode} tercatat {$dompet->saldo_rupiah}, "
                    ."jumlah mutasinya {$jumlahMutasi}.";
            }

            // 4. Hanya dompet eksternal yang boleh negatif.
            if ($dompet->saldo_rupiah < 0 && $dompet->jenis !== JenisDompet::Eksternal) {
                $pelanggaran[] = "Saldo {$dompet->kode} negatif.";
            }

            $saldo = number_format($dompet->saldo_rupiah, 0, ',', '.');
            $baris[] = [$dompet->kode, $dompet->jenis->value, $saldo];
        }

        $this->table(['Dompet', 'Jenis', 'Saldo (Rp)'], $baris);

        // 5. Escrow = jumlah pesanan yang dananya masih ditahan.
        $escrow = (int) Dompet::query()->where('jenis', JenisDompet::Escrow->value)->sum('saldo_rupiah');
        $ditahan = (int) Pesanan::query()
            ->whereIn('status', [StatusPesanan::Dibayar, StatusPesanan::Dikemas, StatusPesanan::Dikirim])
            ->sum('total_rupiah');
        if ($escrow !== $ditahan) {
            $pelanggaran[] = "Escrow {$escrow} tidak sama dengan pesanan ditahan {$ditahan}.";
        }

        // 6. Dompet pencairan = jumlah pencairan yang menunggu.
        $tahan = (int) Dompet::query()->where('jenis', JenisDompet::Pencairan->value)->sum('saldo_rupiah');
        $menunggu = (int) Pencairan::query()->where('status', 'menunggu')->sum('jumlah_rupiah');
        if ($tahan !== $menunggu) {
            $pelanggaran[] = "Dompet pencairan {$tahan} tidak sama dengan pencairan menunggu {$menunggu}.";
        }

        // 7. Jejak stok berantai dan berakhir di stok produk.
        foreach (Produk::query()->orderBy('id')->cursor() as $produk) {
            $sebelum = null;
            foreach (MutasiStok::query()->where('produk_id', $produk->id)->orderBy('id')->cursor() as $baris) {
                if ($sebelum !== null && $baris->stok_setelah !== $sebelum + $baris->jumlah) {
                    $pelanggaran[] = "Jejak stok {$produk->id} tidak berantai pada mutasi {$baris->id}.";
                    break;
                }
                $sebelum = $baris->stok_setelah;
            }
            if ($sebelum !== null && $sebelum !== $produk->stok) {
                $pelanggaran[] = "Stok {$produk->id} tercatat {$produk->stok}, jejak berakhir {$sebelum}.";
            }
        }

        if ($pelanggaran !== []) {
            foreach ($pelanggaran as $pesan) {
                $this->error($pesan);
            }

            return self::FAILURE;
        }

        $this->info('Buku besar seimbang: '.MutasiSaldo::query()->count().' mutasi, 0 pelanggaran.');

        return self::SUCCESS;
    }
}
