# Daftar Periksa Rilis Lapak API v1.0.0

## A. Sebelum kandidat ditandai
- [x] Seluruh PR pada papan sesi berstatus `merged`, dan `main` hijau pada kedua tugas CI.
- [x] `git status` bersih dan `git log origin/main..main` kosong di klon integrator.

## B. Verifikasi kandidat
- [x] Pasang bersih: `composer install`, migrasi fresh dan seed.
- [x] `./vendor/bin/pint --test` dan `./vendor/bin/pest` hijau di SQLite dan MySQL.
- [x] `php artisan lapak:balapan --semua` lulus di MySQL.
- [x] `php artisan lapak:periksa-buku` dan `php artisan lapak:verifikasi-audit` lulus.
- [x] Naik versi: skema dapat ditingkatkan tanpa data hilang.

## C. Menjelang tag rilis
- [x] `CHANGELOG.md` memuat catatan rilis lengkap.
- [x] `config/lapak.php` menyebut versi 1.0.0.
- [x] Tag git `v1.0.0` dan `sesi-5` terpasang di commit yang sama.
