<?php

declare(strict_types=1);

namespace App\Enums;

enum JenisMutasi: string
{
    case DanaMasuk = 'dana_masuk';       // eksternal -> escrow
    case LepasDana = 'lepas_dana';       // escrow -> penjual
    case Komisi = 'komisi';              // escrow -> pendapatan
    case Pengembalian = 'pengembalian'; // escrow -> eksternal atau pencairan -> penjual
    case Pencairan = 'pencairan';        // penjual -> eksternal atau pencairan -> eksternal
    case PenahananPencairan = 'penahanan_pencairan'; // penjual -> pencairan
}
