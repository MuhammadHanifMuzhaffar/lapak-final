<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\LogAuditResource;
use App\Models\LogAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $baris = LogAudit::query()
            ->when($request->query('peristiwa'), fn ($q, $p) => $q->where('peristiwa', $p))
            ->when($request->query('rujukan'), fn ($q, $r) => $q->where('rujukan', $r))
            ->orderByDesc('nomor')
            ->paginate(50, ['*'], 'halaman');

        return $this->berhalaman($baris, LogAuditResource::class);
    }
}
