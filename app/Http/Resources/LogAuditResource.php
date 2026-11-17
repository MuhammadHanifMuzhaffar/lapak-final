<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LogAudit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LogAudit
 */
final class LogAuditResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nomor' => $this->nomor,
            'peristiwa' => $this->peristiwa,
            'pelaku_id' => $this->pelaku_id,
            'sasaran' => $this->sasaran,
            'rujukan' => $this->rujukan,
            'data' => $this->data,
            'dibuat_pada' => $this->dibuat_pada->format('Y-m-d H:i:s.u'),
            'hash_sebelumnya' => $this->hash_sebelumnya,
            'hash' => $this->hash,
        ];
    }
}
