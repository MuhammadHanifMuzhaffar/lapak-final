<?php

declare(strict_types=1);

use App\Domain\Pesanan\StatusPesanan;
use App\Enums\JenisDompet;
use App\Enums\StatusPembayaran;
use App\Models\Checkout;
use App\Models\Pesanan;
use App\Models\Toko;
use App\Services\BukuBesar;
use App\Services\LayananPembayaran;
use App\Services\TandaTanganGateway;

beforeEach(function () {
    $this->buku = app(BukuBesar::class);
    $this->tandaTangan = app(TandaTanganGateway::class);
    $this->layananPembayaran = app(LayananPembayaran::class);
});

it('menolak webhook tanpa header tanda tangan dengan status 401 dan badan galat identik dengan tanda tangan salah', function () {
    $payload = [
        'id_peristiwa' => 'EVT-001',
        'kode_pembayaran' => 'BYR-TEST',
        'jumlah_rupiah' => 100_000,
    ];
    $badan = json_encode($payload);

    // 1. Tanpa header
    $responTanpaHeader = $this->call(
        'POST',
        '/api/v1/webhook/pembayaran',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        $badan,
    );

    // 2. Tanda tangan salah bentuk
    $responSalahBentuk = $this->call(
        'POST',
        '/api/v1/webhook/pembayaran',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_GATEWAY_SIGNATURE' => 'tanda-tangan-ngawur',
        ],
        $badan,
    );

    $responTanpaHeader->assertStatus(401);
    $responSalahBentuk->assertStatus(401);

    expect($responTanpaHeader->json())->toBe($responSalahBentuk->json())
        ->and($responTanpaHeader->json('galat.kode'))->toBe('TANDA_TANGAN_SALAH');
});

it('menolak seluruh webhook dengan 401 saat rahasia gateway kosong (gagal tertutup)', function () {
    config(['lapak.gateway.rahasia' => '']);

    $payload = [
        'id_peristiwa' => 'EVT-002',
        'kode_pembayaran' => 'BYR-TEST',
        'jumlah_rupiah' => 100_000,
    ];
    $badan = json_encode($payload);
    $tanda = $this->tandaTangan->buat($badan, null, '');

    $respon = $this->call(
        'POST',
        '/api/v1/webhook/pembayaran',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_GATEWAY_SIGNATURE' => $tanda,
        ],
        $badan,
    );

    $respon->assertStatus(401)
        ->assertJson([
            'sukses' => false,
            'galat' => [
                'kode' => 'TANDA_TANGAN_SALAH',
            ],
        ]);
});

it('menolak pembayaran bila jumlah tidak cocok: 422 JUMLAH_TIDAK_COCOK dan tagihan tetap menunggu', function () {
    $checkout = Checkout::factory()->create(['total_rupiah' => 150_000]);
    $pembayaran = $this->layananPembayaran->buat($checkout);

    $payload = [
        'id_peristiwa' => 'EVT-003',
        'kode_pembayaran' => $pembayaran->kode,
        'jumlah_rupiah' => 100_000, // Seharusnya 150.000
    ];
    $badan = json_encode($payload);
    $tanda = $this->tandaTangan->buat($badan);

    $respon = $this->call(
        'POST',
        '/api/v1/webhook/pembayaran',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_GATEWAY_SIGNATURE' => $tanda,
        ],
        $badan,
    );

    $respon->assertStatus(422)
        ->assertJson([
            'sukses' => false,
            'galat' => [
                'kode' => 'JUMLAH_TIDAK_COCOK',
            ],
        ]);

    expect($pembayaran->refresh()->status)->toBe(StatusPembayaran::Menunggu);
});

it('kabar kembar: yang kedua menjawab sudah_diproses dan escrow hanya bertambah sekali', function () {
    $checkout = Checkout::factory()->create(['total_rupiah' => 100_000]);
    $pesanan = Pesanan::factory()->create([
        'checkout_id' => $checkout->id,
        'subtotal_rupiah' => 85_000,
        'ongkir_rupiah' => 15_000,
        'total_rupiah' => 100_000,
    ]);
    $pembayaran = $this->layananPembayaran->buat($checkout);

    $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);
    $saldoEscrowAwal = $escrow->saldo_rupiah;

    $payload = [
        'id_peristiwa' => 'EVT-004',
        'kode_pembayaran' => $pembayaran->kode,
        'jumlah_rupiah' => 100_000,
    ];
    $badan = json_encode($payload);
    $tanda = $this->tandaTangan->buat($badan);

    // Panggilan pertama: melunasi
    $respon1 = $this->call(
        'POST',
        '/api/v1/webhook/pembayaran',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_GATEWAY_SIGNATURE' => $tanda,
        ],
        $badan,
    );

    $respon1->assertStatus(200)
        ->assertJson(['sukses' => true, 'data' => ['hasil' => 'dilunasi']]);

    expect($escrow->refresh()->saldo_rupiah)->toBe($saldoEscrowAwal + 100_000)
        ->and($pesanan->refresh()->status)->toBe(StatusPesanan::Dibayar);

    // Panggilan kedua: kabar kembar (id_peristiwa sama atau pengiriman ulang)
    $respon2 = $this->call(
        'POST',
        '/api/v1/webhook/pembayaran',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_GATEWAY_SIGNATURE' => $tanda,
        ],
        $badan,
    );

    $respon2->assertStatus(200)
        ->assertJson(['sukses' => true, 'data' => ['hasil' => 'sudah_diproses']]);

    // Escrow tetap hanya bertambah sekali
    expect($escrow->refresh()->saldo_rupiah)->toBe($saldoEscrowAwal + 100_000);
});

it('satu checkout dua toko: escrow bertambah dua kali sebesar total masing-masing pesanan', function () {
    $tokoA = Toko::factory()->create();
    $tokoB = Toko::factory()->create();

    $checkout = Checkout::factory()->create(['total_rupiah' => 250_000]);

    $pesananA = Pesanan::factory()->create([
        'checkout_id' => $checkout->id,
        'toko_id' => $tokoA->id,
        'subtotal_rupiah' => 90_000,
        'ongkir_rupiah' => 10_000,
        'total_rupiah' => 100_000,
    ]);

    $pesananB = Pesanan::factory()->create([
        'checkout_id' => $checkout->id,
        'toko_id' => $tokoB->id,
        'subtotal_rupiah' => 135_000,
        'ongkir_rupiah' => 15_000,
        'total_rupiah' => 150_000,
    ]);

    $pembayaran = $this->layananPembayaran->buat($checkout);

    $escrow = $this->buku->dompetSistem(JenisDompet::Escrow);
    $saldoEscrowAwal = $escrow->saldo_rupiah;

    $payload = [
        'id_peristiwa' => 'EVT-005',
        'kode_pembayaran' => $pembayaran->kode,
        'jumlah_rupiah' => 250_000,
    ];
    $badan = json_encode($payload);
    $tanda = $this->tandaTangan->buat($badan);

    $respon = $this->call(
        'POST',
        '/api/v1/webhook/pembayaran',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_GATEWAY_SIGNATURE' => $tanda,
        ],
        $badan,
    );

    $respon->assertStatus(200)
        ->assertJson(['sukses' => true, 'data' => ['hasil' => 'dilunasi']]);

    // Escrow bertambah total pesanan A (100.000) + pesanan B (150.000) = 250.000
    expect($escrow->refresh()->saldo_rupiah)->toBe($saldoEscrowAwal + 250_000)
        ->and($pesananA->refresh()->status)->toBe(StatusPesanan::Dibayar)
        ->and($pesananB->refresh()->status)->toBe(StatusPesanan::Dibayar);
});
