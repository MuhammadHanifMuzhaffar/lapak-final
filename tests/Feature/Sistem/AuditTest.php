<?php

declare(strict_types=1);

use App\Enums\NamaPeran;
use App\Enums\PeristiwaAudit;
use App\Models\Pengguna;
use App\Models\RantaiAudit;
use App\Services\PemeriksaRantaiAudit;
use App\Services\PencatatAudit;
use Illuminate\Support\Facades\DB;

it('mencatat audit berantai di dalam transaksi', function () {
    DB::rollBack();
    try {
        expect(fn () => app(PencatatAudit::class)->catat(PeristiwaAudit::Masuk))
            ->toThrow(LogicException::class, 'PencatatAudit hanya boleh dipanggil di dalam transaksi.');
    } finally {
        DB::beginTransaction();
    }

    $baris1 = DB::transaction(fn () => app(PencatatAudit::class)->catat(PeristiwaAudit::Masuk, rujukan: 'SESI-1'));
    $baris2 = DB::transaction(fn () => app(PencatatAudit::class)->catat(PeristiwaAudit::Keluar, rujukan: 'SESI-1'));

    expect($baris1->nomor)->toBe(1)
        ->and($baris1->hash_sebelumnya)->toBe(str_repeat('0', 64))
        ->and($baris2->nomor)->toBe(2)
        ->and($baris2->hash_sebelumnya)->toBe($baris1->hash);

    $kepala = RantaiAudit::first();
    expect($kepala->nomor_terakhir)->toBe(2)
        ->and($kepala->hash_terakhir)->toBe($baris2->hash);

    $pelanggaran = app(PemeriksaRantaiAudit::class)->periksa();
    expect($pelanggaran)->toBeEmpty();
});

it('mendeteksi perubahan isi pada baris audit', function () {
    DB::transaction(fn () => app(PencatatAudit::class)->catat(PeristiwaAudit::Masuk, rujukan: 'SESI-1'));
    DB::transaction(fn () => app(PencatatAudit::class)->catat(PeristiwaAudit::Keluar, rujukan: 'SESI-1'));

    // Ubah baris nomor 1 secara ilegal
    DB::table('log_audit')->where('nomor', 1)->update(['sasaran' => 'diubah_secara_ilegal']);

    $pelanggaran = app(PemeriksaRantaiAudit::class)->periksa();
    expect($pelanggaran)->not->toBeEmpty()
        ->and($pelanggaran[0])->toContain('Baris 1');

    $this->artisan('lapak:verifikasi-audit')
        ->assertFailed();
});

it('mendeteksi penghapusan baris audit di tengah rantai', function () {
    DB::transaction(fn () => app(PencatatAudit::class)->catat(PeristiwaAudit::Masuk, rujukan: 'SESI-1'));
    DB::transaction(fn () => app(PencatatAudit::class)->catat(PeristiwaAudit::Keluar, rujukan: 'SESI-1'));
    DB::transaction(fn () => app(PencatatAudit::class)->catat(PeristiwaAudit::Masuk, rujukan: 'SESI-2'));

    // Hapus baris nomor 2
    DB::table('log_audit')->where('nomor', 2)->delete();

    $pelanggaran = app(PemeriksaRantaiAudit::class)->periksa();
    expect($pelanggaran)->not->toBeEmpty();

    $this->artisan('lapak:verifikasi-audit')
        ->assertFailed();
});

it('hanya memperbolehkan auditor membaca GET /api/v1/audit dengan sesi segar', function () {
    $auditor = Pengguna::factory()->create();
    DB::table('pengguna_peran')->insert([
        'pengguna_id' => $auditor->id,
        'peran_id' => DB::table('peran')->where('nama', NamaPeran::Auditor->value)->value('id'),
    ]);

    $pembeli = Pengguna::factory()->create();
    DB::table('pengguna_peran')->insert([
        'pengguna_id' => $pembeli->id,
        'peran_id' => DB::table('peran')->where('nama', NamaPeran::Pembeli->value)->value('id'),
    ]);

    // Tanpa login -> 401
    $this->getJson('/api/v1/audit')
        ->assertUnauthorized();

    // Pembeli -> 403 DITOLAK
    $this->sebagai($pembeli)
        ->getJson('/api/v1/audit')
        ->assertForbidden()
        ->assertJsonPath('galat.kode', 'DITOLAK');

    // POST -> 405 METODE_DITOLAK
    $this->sebagai($auditor)
        ->postJson('/api/v1/audit')
        ->assertStatus(405);

    // Auditor sesi tidak segar -> 403 SESI_KADALUARSA / BUTUH_KONFIRMASI
    $this->sebagai($auditor, segar: false)
        ->getJson('/api/v1/audit')
        ->assertForbidden();

    // Auditor sesi segar -> 200
    $this->sebagai($auditor, segar: true)
        ->getJson('/api/v1/audit')
        ->assertOk()
        ->assertJsonStructure([
            'sukses',
            'data',
            'meta' => ['halaman', 'per_halaman', 'total', 'halaman_terakhir'],
        ]);
});

it('menolak uji balapan pada koneksi sqlite', function () {
    // Di lingkungan SQLite (testing), lapak:balapan harus menolak
    $this->artisan('lapak:balapan', ['skenario' => 'alamat-pertama'])
        ->assertFailed();
});
