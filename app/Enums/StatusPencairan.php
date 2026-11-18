<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusPencairan: string
{
    case Menunggu = 'menunggu';
    case Dibayar = 'dibayar';
    case Ditolak = 'ditolak';
}
