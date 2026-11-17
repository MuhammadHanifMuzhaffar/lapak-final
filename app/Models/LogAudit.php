<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LogAudit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'log_audit';

    protected $casts = [
        'nomor' => 'integer',
        'data' => 'array',
        'dibuat_pada' => 'datetime',
    ];

    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pelaku_id');
    }

    public function kanonik(): string
    {
        return json_encode([
            $this->peristiwa,
            $this->pelaku_id,
            $this->sasaran,
            $this->rujukan,
            $this->data,
            $this->dibuat_pada->format('Y-m-d H:i:s.u'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
