# Log Penggunaan AI — Sesi 1

- **Nama:** Muhammad Hanif Muzhaffar
- **NIM:** V3425072
- **Domain:** Domain 1 (Identitas dan Sesi)

## Rincian Konsultasi AI pada Sesi 1

| Tanggal | Aktivitas / Topik | Prompt Pokok | Hasil / Keputusan Teknis |
|---|---|---|---|
| 27 Oktober 2026 | Desain Skema Pengguna & Factory | "Bagaimana memastikan kata sandi ter-hash otomatis dan status akun terlindung dari mass-assignment di Laravel 11?" | Menggunakan attribute casting `'kata_sandi' => 'hashed'` dan mengeluarkan `status` dari `$fillable` model Pengguna. |
| 27 Oktober 2026 | Validasi Registrasi & Normalisasi Surel | "Praktik terbaik normalisasi surel dan validasi aturan password kuat pada FormRequest." | Menerapkan `prepareForValidation()` dengan `mb_strtolower(trim())` dan `Password::min(10)->letters()->numbers()`. |
| 27 Oktober 2026 | Pengujian Pest Keamanan Password | "Cara menguji endpoint agar tidak membocorkan hash password pada payload respons JSON." | Menggunakan assertions `assertJsonMissingPath('data.kata_sandi')` dan `expect($response->getContent())->not->toContain('$2y$')`. |

---

# Log Penggunaan AI — Sesi 5

- **Nama:** Muhammad Hanif Muzhaffar
- **NIM:** V3425072
- **Domain:** Domain 1 (Identitas dan Sesi)

## Rincian Konsultasi AI pada Sesi 5

| Tanggal | Aktivitas / Topik | Prompt Pokok | Hasil / Keputusan Teknis |
|---|---|---|---|
| 17 November 2026 | Jejak Audit Peristiwa Identitas | "Bagaimana mencatat peristiwa masuk berhasil dan masuk gagal ke log audit tanpa membocorkan kata sandi atau keberadaan surel?" | Memanggil PencatatAudit di dalam transaksi tanpa pernah memasukkan kata sandi ke parameter catat(), dan tetap melempar KredensialSalah yang seragam. |
| 17 November 2026 | Penanganan Kunci Sesi Dicabut | "Bagaimana mendeteksi penggunaan ulang kunci sesi yang sudah dicabut dan mencabut seluruh sesi perangkat lain?" | Menambahkan metode catatPemakaianUlang di PemeriksaSesi yang memvalidasi kepemilikan kunci lama sebelum memanggil cabutSemua() dan mencatat audit. |
| 17 November 2026 | Penjaga PenggunaSeeder Produksi | "Bagaimana mencegah pembuatan akun contoh di lingkungan produksi pada database seeder?" | Menambahkan pemeriksaan app()->isProduction() di awal method run() PenggunaSeeder dan mengembalikan peringatan tanpa seeding akun. |

## Yang saya verifikasi sendiri
- `vendor\bin\pint --test` (0 pelanggaran gaya kode)
- `vendor\bin\pest tests/Feature/Identitas` (72 lulus)
- `php artisan lapak:periksa-buku` (buku besar seimbang)
- `php artisan lapak:balapan --semua` (semua skenario lolos)
- `php artisan lapak:verifikasi-audit` (rantai audit utuh)

## Yang saya ubah dari saran AI
- Memastikan pengecualian sesi menganggur pada `catatPemakaianUlang`: pemakai yang sekadar melewati waktu menganggur tidak boleh mematikan seluruh sesi perangkat lainnya.
