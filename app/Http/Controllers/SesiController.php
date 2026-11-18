<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PeristiwaAudit;
use App\Exceptions\KredensialSalah;
use App\Http\Requests\KonfirmasiSandiRequest;
use App\Http\Resources\PenggunaResource;
use App\Http\Resources\SesiResource;
use App\Models\Sesi;
use App\Services\PemeriksaIzin;
use App\Services\PemeriksaKataSandi;
use App\Services\PencatatAudit;
use App\Services\PenerbitSesi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SesiController extends Controller
{
    public function __construct(
        private readonly PenerbitSesi $penerbit,
        private readonly PencatatAudit $audit,
    ) {}

    public function saya(Request $request, PemeriksaIzin $izin): JsonResponse
    {
        return $this->sukses([
            'pengguna' => PenggunaResource::make($request->user()->load('peran')),
            'izin' => $izin->daftar($request->user()),
            'sesi' => SesiResource::make(Sesi::dariPermintaan($request)),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $sesi = Sesi::query()
            ->berlaku()
            ->where('pengguna_id', $request->user()->id)
            ->orderByDesc('terakhir_dipakai_pada')
            ->orderByDesc('id')
            ->get();

        return $this->sukses(SesiResource::collection($sesi));
    }

    public function keluar(Request $request): JsonResponse
    {
        $sesi = Sesi::dariPermintaan($request);

        DB::transaction(function () use ($sesi, $request): void {
            $this->penerbit->cabut($sesi);
            $this->audit->catat(PeristiwaAudit::Keluar, pelaku: $request->user(), rujukan: $sesi->pengenal);
        });

        return $this->sukses();
    }

    public function konfirmasi(KonfirmasiSandiRequest $request, PemeriksaKataSandi $sandi): JsonResponse
    {
        if (! $sandi->cocok($request->user(), $request->validated('kata_sandi'))) {
            throw KredensialSalah::saatKonfirmasi();
        }

        $sesi = Sesi::dariPermintaan($request);
        $this->penerbit->tandaiSegar($sesi);
        $kunciBaru = $this->penerbit->rotasi($sesi);

        return $this->sukses([
            'kunci' => $kunciBaru,
            'sesi' => SesiResource::saatIni($sesi),
        ]);
    }

    public function destroy(Request $request, string $pengenal): JsonResponse
    {
        $sesi = Sesi::query()
            ->berlaku()
            ->where('pengguna_id', $request->user()->id)
            ->where('pengenal', $pengenal)
            ->firstOrFail();

        DB::transaction(function () use ($sesi, $request): void {
            $this->penerbit->cabut($sesi);
            $this->audit->catat(
                PeristiwaAudit::SesiDicabut,
                pelaku: $request->user(),
                rujukan: $sesi->pengenal,
                data: ['perangkat' => $sesi->nama_perangkat],
            );
        });

        return $this->sukses();
    }
}
