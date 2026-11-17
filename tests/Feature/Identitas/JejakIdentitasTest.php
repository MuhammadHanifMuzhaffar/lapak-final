<?php

declare(strict_types=1);

use App\Enums\NamaPeran;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\PenerbitSesi;
use App\Services\PengelolaPeran;
use Database\Seeders\PenggunaSeeder;

it('masuk berhasil menulis tepat satu baris audit tanpa kata sandi', function () {
    $pengguna = Pengguna::factory()->create([
        'surel' => 'hanif@lapak.test',
        'kata_sandi' => 'RahasiaBatik2026',
    ]);

    $sebelum = LogAudit::count();

    $this->postJson('/api/v1/masuk', [
        'surel' => 'hanif@lapak.test',
        'kata_sandi' => 'RahasiaBatik2026',
        'nama_perangkat' => 'Laptop Hanif',
    ])->assertCreated();

    expect(LogAudit::count())->toBe($sebelum + 1);

    $audit = LogAudit::query()->latest('nomor')->first();
    expect($audit->peristiwa)->toBe('masuk')
        ->and($audit->pelaku_id)->toBe($pengguna->id)
        ->and(json_encode($audit->data))->not->toContain('RahasiaBatik2026');
});

it('masuk gagal menulis jejak audit dengan surel tanpa kata sandi', function () {
    $pengguna = Pengguna::factory()->create([
        'surel' => 'hanif2@lapak.test',
        'kata_sandi' => 'RahasiaBatik2026',
    ]);

    $this->postJson('/api/v1/masuk', [
        'surel' => 'hanif2@lapak.test',
        'kata_sandi' => 'SandiSalah123',
        'nama_perangkat' => 'Ponsel',
    ])->assertUnauthorized()
        ->assertJsonPath('galat.kode', 'KREDENSIAL_SALAH');

    $this->assertDatabaseHas('log_audit', [
        'peristiwa' => 'masuk-gagal',
        'pelaku_id' => $pengguna->id,
    ]);

    $audit = LogAudit::query()->where('peristiwa', 'masuk-gagal')->latest('nomor')->first();
    expect(json_encode($audit->data))->not->toContain('SandiSalah123')
        ->and(json_encode($audit->data))->toContain('hanif2@lapak.test');
});

it('masuk gagal untuk surel tidak dikenal tetap mencatat audit tanpa membocorkan', function () {
    $this->postJson('/api/v1/masuk', [
        'surel' => 'asing@lapak.test',
        'kata_sandi' => 'SandiAsing123',
        'nama_perangkat' => 'Ponsel',
    ])->assertUnauthorized()
        ->assertJsonPath('galat.kode', 'KREDENSIAL_SALAH');

    $this->assertDatabaseHas('log_audit', [
        'peristiwa' => 'masuk-gagal',
        'pelaku_id' => null,
    ]);
});

it('keluar mencatat peristiwa keluar untuk sesi yang dipakai', function () {
    $pengguna = Pengguna::factory()->create();
    [$sesi, $kunci] = app(PenerbitSesi::class)->terbitkan($pengguna, 'Komputer');

    $this->denganKunci($kunci)->postJson('/api/v1/keluar')
        ->assertOk();

    $this->assertDatabaseHas('log_audit', [
        'peristiwa' => 'keluar',
        'pelaku_id' => $pengguna->id,
        'rujukan' => $sesi->pengenal,
    ]);
});

it('mencabut seluruh sesi ketika kunci yang sudah dicabut dipakai lagi', function () {
    $pengguna = Pengguna::factory()->create();
    [$sesiLama, $kunciLama] = app(PenerbitSesi::class)->terbitkan($pengguna, 'Ponsel');
    [$sesiLain] = app(PenerbitSesi::class)->terbitkan($pengguna, 'Laptop');
    app(PenerbitSesi::class)->cabut($sesiLama);

    $this->denganKunci($kunciLama)->getJson('/api/v1/saya')
        ->assertStatus(401)
        ->assertJsonPath('galat.kode', 'BELUM_MASUK');

    expect($sesiLain->fresh()->dicabut_pada)->not->toBeNull();

    $this->assertDatabaseHas('log_audit', ['peristiwa' => 'sesi-dipakai-ulang']);
    $this->assertDatabaseMissing('log_audit', ['peristiwa' => 'masuk-gagal']);
});

it('memperlakukan kunci yang lewat batas mutlak sama dengan kunci dicabut', function () {
    $pengguna = Pengguna::factory()->create();
    [$sesiKadaluwarsa, $kunciKadaluwarsa] = app(PenerbitSesi::class)->terbitkan($pengguna, 'Tablet');
    [$sesiAktif] = app(PenerbitSesi::class)->terbitkan($pengguna, 'Desktop');

    $sesiKadaluwarsa->forceFill(['kedaluwarsa_pada' => now()->subDay()])->save();

    $this->denganKunci($kunciKadaluwarsa)->getJson('/api/v1/saya')
        ->assertStatus(401);

    expect($sesiAktif->fresh()->dicabut_pada)->not->toBeNull();
    $this->assertDatabaseHas('log_audit', ['peristiwa' => 'sesi-dipakai-ulang']);
});

it('tidak mencabut sesi lain jika sesi hanya menganggur', function () {
    $pengguna = Pengguna::factory()->create();
    [$sesiMenganggur, $kunciMenganggur] = app(PenerbitSesi::class)->terbitkan($pengguna, 'Tablet');
    [$sesiAktif] = app(PenerbitSesi::class)->terbitkan($pengguna, 'Desktop');

    $sesiMenganggur->forceFill(['terakhir_dipakai_pada' => now()->subHours(3)])->save();

    $this->denganKunci($kunciMenganggur)->getJson('/api/v1/saya')
        ->assertStatus(401);

    expect($sesiAktif->fresh()->dicabut_pada)->toBeNull();
    $this->assertDatabaseMissing('log_audit', ['peristiwa' => 'sesi-dipakai-ulang']);
});

it('perubahan peran mencatat peran diubah dan seeder tidak mencatat audit', function () {
    $admin = Pengguna::factory()->create();
    $target = Pengguna::factory()->create();

    $sebelum = LogAudit::count();

    // Seeder / sistem (oleh = null)
    app(PengelolaPeran::class)->beri($target, NamaPeran::Pembeli, oleh: null);
    expect(LogAudit::count())->toBe($sebelum);

    // Oleh admin (oleh != null)
    app(PengelolaPeran::class)->beri($target, NamaPeran::Keuangan, oleh: $admin);
    expect(LogAudit::count())->toBe($sebelum + 1);

    $this->assertDatabaseHas('log_audit', [
        'peristiwa' => 'peran-diubah',
        'pelaku_id' => $admin->id,
        'sasaran' => (string) $target->id,
    ]);
});

it('tidak membuat akun saat PenggunaSeeder dijalankan di lingkungan produksi', function () {
    app()->detectEnvironment(fn () => 'production');

    $sebelum = Pengguna::count();
    $this->artisan('db:seed', ['--class' => PenggunaSeeder::class, '--force' => true])
        ->assertSuccessful();

    expect(Pengguna::count())->toBe($sebelum);
});
