<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Pesanan\PelakuPesanan;
use App\Domain\Pesanan\StatusPesanan;
use App\Http\Requests\DaftarPesananTokoRequest;
use App\Http\Resources\LogAuditResource;
use App\Http\Resources\PesananTokoResource;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Services\LayananPesanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pintu pengelolaan pesanan dari sisi penjual.
 * Seluruh akses wajib berangkat dari scopeMilikPenjual: pesanan milik toko lain dijawab 404.
 */
final class PesananTokoController extends Controller
{
    public function __construct(
        private readonly LayananPesanan $layanan,
    ) {}

    public function index(DaftarPesananTokoRequest $request): JsonResponse
    {
        $kueri = Pesanan::query()
            ->milikPenjual($request->user())
            ->with(['butir', 'toko'])
            ->orderByDesc('id');

        if ($status = $request->validated('status')) {
            $kueri->where('status', $status);
        }

        $perHalaman = (int) ($request->validated('per_halaman') ?? config('lapak.katalog.per_halaman_bawaan'));
        $halaman = $kueri->paginate($perHalaman, ['*'], 'halaman');

        return $this->berhalaman($halaman, PesananTokoResource::class);
    }

    public function show(Request $request, int $pesanan): JsonResponse
    {
        $pesananModel = $this->temukanPesanan($request->user(), $pesanan);

        return $this->sukses(PesananTokoResource::make($pesananModel));
    }

    public function riwayat(Request $request, int $pesanan): JsonResponse
    {
        $pesananModel = $this->temukanPesanan($request->user(), $pesanan);

        $riwayat = LogAudit::query()
            ->where('rujukan', $pesananModel->nomor)
            ->orderBy('nomor')
            ->get();

        return $this->sukses(LogAuditResource::collection($riwayat));
    }

    public function proses(Request $request, int $pesanan): JsonResponse
    {
        $pesananModel = $this->temukanPesanan($request->user(), $pesanan);

        $terbaru = $this->layanan->ubahStatus(
            $pesananModel,
            StatusPesanan::Dikemas,
            PelakuPesanan::Penjual,
            $request->user(),
        );

        return $this->sukses(PesananTokoResource::make($terbaru->load('butir')));
    }

    public function kirim(Request $request, int $pesanan): JsonResponse
    {
        $pesananModel = $this->temukanPesanan($request->user(), $pesanan);

        $terbaru = $this->layanan->ubahStatus(
            $pesananModel,
            StatusPesanan::Dikirim,
            PelakuPesanan::Penjual,
            $request->user(),
        );

        return $this->sukses(PesananTokoResource::make($terbaru->load('butir')));
    }

    private function temukanPesanan(Pengguna $penjual, int $id): Pesanan
    {
        return Pesanan::query()
            ->milikPenjual($penjual)
            ->with(['butir', 'toko'])
            ->whereKey($id)
            ->firstOrFail();
    }
}
