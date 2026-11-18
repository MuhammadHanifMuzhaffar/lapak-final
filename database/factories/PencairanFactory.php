<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pencairan;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pencairan>
 */
final class PencairanFactory extends Factory
{
    protected $model = Pencairan::class;

    public function definition(): array
    {
        return [
            'penjual_id' => Pengguna::factory(),
            'jumlah_rupiah' => 50_000,
            'status' => 'menunggu',
            'diajukan_oleh' => Pengguna::factory(),
            'diputuskan_oleh' => null,
            'diputuskan_pada' => null,
            'catatan' => null,
        ];
    }
}
