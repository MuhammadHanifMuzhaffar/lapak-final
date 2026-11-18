<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Pencairan extends Model
{
    use HasFactory;

    protected $table = 'pencairan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'jumlah_rupiah' => 'integer',
            'status' => 'string',
            'diputuskan_pada' => 'datetime',
        ];
    }

    public function penjual(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'penjual_id');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diajukan_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diputuskan_oleh');
    }
}
