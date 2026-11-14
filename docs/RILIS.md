# Daftar Periksa Rilis Lapak API v1.0.0

## A. Sebelum kandidat ditandai
- [ ] Seluruh PR pada papan sesi berstatus `merged`, dan `main` hijau pada kedua tugas CI.
- [ ] `git status` bersih dan `git log origin/main..main` kosong di klon integrator.

## B. Verifikasi kandidat
- [ ] Pasang bersih: `composer install`, migrasi fresh dan seed.
- [ ] `./vendor/bin/pint --test` dan `./vendor/bin/pest` hijau di SQLite dan MySQL.
- [ ] `php artisan lapak:balapan --semua` lulus di MySQL.
- [ ] `php artisan lapak:periksa-buku` dan `php artisan lapak:verifikasi-audit` lulus.
- [ ] Naik versi: skema dapat ditingkatkan tanpa data hilang.

## C. Menjelang tag rilis
- [ ] `CHANGELOG.md` memuat catatan rilis lengkap.
- [ ] `config/lapak.php` menyebut versi 1.0.0.
- [ ] Tag git `v1.0.0` dan `sesi-5` terpasang di commit yang sama.
