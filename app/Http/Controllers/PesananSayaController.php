<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Pesanan\PelakuPesanan;
use App\Domain\Pesanan\StatusPesanan;
use App\Http\Resources\PembayaranResource;
use App\Http\Resources\PesananResource;
use App\Models\Pesanan;
use App\Services\LayananPembayaran;
use App\Services\LayananPesanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Pesanan milik pembeli yang sedang masuk. Seluruh kuerinya berangkat dari scopeMilikPembeli. */
final class PesananSayaController extends Controller
{
    public function __construct(
        private readonly LayananPesanan $layanan,
    ) {}

    /** GET /pesanan: terbaru lebih dulu. */
    public function index(Request $request): JsonResponse
    {
        $halaman = Pesanan::query()
            ->milikPembeli($request->user())
            ->with(['toko', 'butir'])
            ->orderByDesc('id')
            ->paginate(config('lapak.katalog.per_halaman_bawaan'), ['*'], 'halaman');

        return $this->berhalaman($halaman, PesananResource::class);
    }

    /**
     * GET /pesanan/{nomor}. Nomor pesanan orang lain dijawab 404, sama seperti nomor yang tidak
     * pernah ada. Nomor pesanan berurutan dan mudah ditebak; yang menjaganya adalah kueri ini.
     */
    public function show(
        Request $request,
        LayananPembayaran $pembayaran,
        string $nomor,
    ): JsonResponse {
        $pesanan = Pesanan::query()
            ->milikPembeli($request->user())
            ->with(['toko', 'butir', 'checkout'])
            ->where('nomor', $nomor)
            ->firstOrFail();

        return $this->sukses([
            'pesanan' => PesananResource::make($pesanan),
            'pembayaran' => PembayaranResource::make($pembayaran->untuk($pesanan->checkout)),
        ]);
    }

    public function terima(Request $request, string $nomor): JsonResponse
    {
        $pesanan = Pesanan::query()->milikPembeli($request->user())->where('nomor', $nomor)->firstOrFail();

        return $this->sukses(PesananResource::make(
            $this->layanan->ubahStatus($pesanan, StatusPesanan::Selesai, PelakuPesanan::Pembeli, $request->user()),
        ));
    }

    public function batal(Request $request, string $nomor): JsonResponse
    {
        $pesanan = Pesanan::query()->milikPembeli($request->user())->where('nomor', $nomor)->firstOrFail();
        $alasan = $request->input('alasan') ? (string) $request->input('alasan') : null;

        return $this->sukses(PesananResource::make(
            $this->layanan->ubahStatus($pesanan, StatusPesanan::Dibatalkan, PelakuPesanan::Pembeli, $request->user(), $alasan),
        ));
    }
}
