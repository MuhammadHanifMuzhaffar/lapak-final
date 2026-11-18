<?php

namespace Database\Seeders;

use App\Enums\JenisDompet;
use App\Services\BukuBesar;
use Illuminate\Database\Seeder;

class DompetSeeder extends Seeder
{
    public function run(BukuBesar $buku): void
    {
        foreach ([JenisDompet::Eksternal, JenisDompet::Escrow, JenisDompet::Pendapatan, JenisDompet::Pencairan] as $jenis) {
            $buku->dompetSistem($jenis);
        }
    }
}
