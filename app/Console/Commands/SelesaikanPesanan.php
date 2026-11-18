<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PenyelesaianOtomatis;
use Illuminate\Console\Command;

final class SelesaikanPesanan extends Command
{
    protected $signature = 'lapak:selesaikan-pesanan';

    protected $description = 'Menyelesaikan pesanan yang sudah dikirim melewati batas hari penahanan dana (LP-12).';

    public function handle(PenyelesaianOtomatis $penyelesaian): int
    {
        $jumlah = $penyelesaian->jalankan();

        $this->info("Penyelesaian otomatis selesai: {$jumlah} pesanan diselesaikan.");

        return self::SUCCESS;
    }
}
