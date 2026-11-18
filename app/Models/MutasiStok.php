<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MutasiStok extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'mutasi_stok';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'stok_setelah' => 'integer',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
