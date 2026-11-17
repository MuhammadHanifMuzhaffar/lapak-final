# Log Penggunaan AI - Sesi 5

- **Nama:** Zidnii Aghniya Rajwa
- **NIM:** V3425038
- **Domain:** Domain 5 - Respons, Kualitas, dan Audit

## Rincian

| Tanggal | Aktivitas / Topik | Prompt Pokok | Hasil / Keputusan Teknis |
|---|---|---|---|
| 17 November 2026 | Desain Rantai Audit Anti-Tamper | "Bagaimana merancang rantai hash SHA-256 pada tabel log audit dengan penulisan konkuren yang aman dari race condition?" | Menggunakan tabel rantai_audit single row yang dikunci lockForUpdate di awal transaksi, dengan rumus hash = sha256(hash_sebelumnya + '\|' + kanonik). |
| 17 November 2026 | Serialisasi Kanonik LogAudit | "Cara memastikan payload JSON deterministik untuk verifikasi hash berantai di PHP?" | Membentuk method kanonik() terpusat di model LogAudit dengan urutan field tetap dan flag JSON_UNESCAPED_SLASHES \| JSON_UNESCAPED_UNICODE. |
| 17 November 2026 | Gerbang Kualitas & Penolakan SQLite | "Bagaimana memastikan perintah lapak:balapan tidak sengaja dijalankan pada SQLite?" | Memeriksa DB::connection()->getDriverName() === 'sqlite' di awal method handle() dan mengembalikan status FAILURE dengan pesan informatif. |

## Yang saya verifikasi sendiri
- `php vendor/bin/pint --test` (0 pelanggaran gaya kode)
- `php vendor/bin/pest` (semua uji hijau)
- `php artisan migrate:fresh --seed` (tabel log_audit dan rantai_audit terbuat dan terinisialisasi)
- `php artisan lapak:verifikasi-audit` (rantai audit diverifikasi utuh)
- `php artisan lapak:balapan audit-serempak` (8 proses konkuren serentak lulus tanpa nomor berlubang)
- `DB_CONNECTION=sqlite php artisan lapak:balapan audit-serempak` (berhasil ditolak)

## Yang saya ubah dari saran AI
- Memastikan presisi `dateTime(6)` mikrodetik pada kolom `dibuat_pada` dan format `Y-m-d H:i:s.u` agar peristiwa dalam detik yang sama tetap memiliki timestamp mikrodetik yang presisi dan tidak menghasilkan kanonik yang identik.
