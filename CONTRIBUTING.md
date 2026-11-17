# Panduan Kontribusi - Lapak API

Dokumen ini mengatur alur kerja tim, standar commit, prosedur pull request (PR), dan aturan verifikasi pengujian untuk pengembangan proyek Lapak.

## 1. Aturan Dasar Kontribusi
1. **Satu PR, Satu Cabang, Satu Pemilik:** Setiap anggota mengerjakan domainnya di cabang terpisah.
2. **Dual Reviewer (Dua Peninjau):** Setiap PR wajib diperiksa dan disetujui oleh dua peninjau yang ditugaskan di matriks sesi sebelum boleh di-merge.
3. **Commit Terotentikasi:** Commit wajib mencantumkan nama dan surel resmi anggota yang bersangkutan.
4. **Verifikasi Nyata di Mesin:** Seluruh pengujian wajib dijalankan langsung di mesin lokal dengan MySQL 8 hidup. Dilarang menyalin keluaran atau mengarang ulasan.
5. **Pencatatan AI:** Setiap penggunaan AI dicatat apa adanya di `docs/log-ai/<nama>.md`.

## 2. Perintah Verifikasi Wajib
Sebelum membuka PR atau menyetujui PR rekan, jalankan perintah ini di mesin Anda:
```bash
vendor/bin/pint --test
vendor/bin/pest
php artisan migrate:fresh --seed
php artisan lapak:periksa-buku
php artisan lapak:balapan --semua
php artisan lapak:verifikasi-audit
```

## 3. Format Ulasan (Review) Peninjau
Peninjau tidak boleh langsung menyetujui tanpa menjalankan cabang di mesinnya. Setiap temuan ditulis menggunakan format:
```text
[BLOKIR] <berkas>:<baris>
Alasan: <aturan atau butir modul yang dilanggar>
Bukti: <perintah yang dijalankan dan keluarannya>
Usul: <perbaikan kecil yang konkret>

[SARAN] <berkas>:<baris>
<hal yang tidak menghalangi merge tetapi sebaiknya diperbaiki>

[PERTANYAAN] <berkas>:<baris>
<hal yang harus bisa dijelaskan penulis saat demo>
```
PR hanya boleh disetujui setelah seluruh status `[BLOKIR]` terselesaikan dan peninjau menempelkan bukti keluaran pengujian terakhir di komentar persetujuan.
