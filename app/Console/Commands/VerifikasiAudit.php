<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LogAudit;
use App\Services\PemeriksaRantaiAudit;
use Illuminate\Console\Command;

final class VerifikasiAudit extends Command
{
    protected $signature = 'lapak:verifikasi-audit {--dari=}';

    protected $description = 'Memverifikasi keutuhan rantai hash log audit anti-tamper';

    public function handle(PemeriksaRantaiAudit $pemeriksa): int
    {
        $pelanggaran = $pemeriksa->periksa($this->option('dari') ? (int) $this->option('dari') : null);

        if ($pelanggaran !== []) {
            foreach ($pelanggaran as $pesan) {
                $this->error($pesan);
            }

            return self::FAILURE;
        }

        $this->info('Rantai audit utuh: '.LogAudit::query()->count().' baris, kepala cocok.');

        return self::SUCCESS;
    }
}
