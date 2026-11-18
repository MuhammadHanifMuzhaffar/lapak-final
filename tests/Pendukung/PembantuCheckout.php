<?php

declare(strict_types=1);

namespace Tests\Pendukung;

use App\Models\Alamat;
use App\Models\ButirKeranjang;
use App\Models\Keranjang;
use App\Models\Pengguna;
use App\Models\Produk;

final class PembantuCheckout
{
    /** @return array{0: Pengguna, 1: Alamat} */
    public static function buatPembeliDanAlamat(): array
    {
        $pembeli = Pengguna::factory()->pembeli()->create();
        $alamat = Alamat::factory()->for($pembeli, 'pengguna')->create();

        return [$pembeli, $alamat];
    }

    public static function isiKeranjang(Pengguna $pengguna, Produk $produk, int $kuantitas): void
    {
        $keranjang = Keranjang::query()->firstOrCreate(
            ['pengguna_id' => $pengguna->id],
        );

        $butir = new ButirKeranjang(['produk_id' => $produk->id, 'kuantitas' => $kuantitas]);
        $butir->forceFill(['keranjang_id' => $keranjang->id])->save();
    }
}
