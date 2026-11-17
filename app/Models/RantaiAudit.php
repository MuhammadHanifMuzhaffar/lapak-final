<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RantaiAudit extends Model
{
    public $timestamps = false;

    protected $table = 'rantai_audit';

    protected $casts = [
        'nomor_terakhir' => 'integer',
        'diperbarui_pada' => 'datetime',
    ];
}
