# Catatan Perubahan (Changelog)

Semua perubahan penting pada repositori **Lapak API (Marketplace UMKM Solo Raya)** dicatat dalam berkas ini.

Format berkas ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/) dan proyek ini mematuhi [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-11-18

Rilis resmi v1.0.0 menandai selesainya seluruh target pada Sesi 5: Pelepasan escrow, saldo dan penarikan 2-orang, log audit berantai hash SHA-256 anti-tamper, idempotensi per domain, serta pengujian beban serempak (uji balapan).

### Ditambahkan
- **Domain 5 (Audit & Kualitas - Zidnii):**
  - Tabel `log_audit` dan `rantai_audit` dengan pengikatan hash berantai SHA-256 anti-tamper (LP-17).
  - Layanan `PencatatAudit` dan `PemeriksaRantaiAudit` dengan payload kanonik deterministik.
  - Perintah `php artisan lapak:verifikasi-audit` untuk memeriksa integritas rantai audit.
  - Endpoint `GET /api/v1/audit` khusus peran `auditor` dengan izin `audit.lihat` dan middleware `sesi.segar`.
  - Skenario pengujian serempak `audit-serempak` di `Balapan.php`.

- **Domain 1 (Identitas & Sesi - Hanif):**
  - Deteksi pemakaian ulang kunci sesi yang telah dicabut (`PemeriksaSesi::catatPemakaianUlang`) dengan audit keamanan dan pencabutan seluruh sesi aktif keluarga akun (LP-14).
  - Jejak audit autentikasi: `masuk`, `keluar`, dan `peran.ubah`.
  - Penjaga lingkungan produksi pada `PenggunaSeeder` untuk mencegah penulisan akun sampel ke basis data produksi.

- **Domain 4 (Dana & Dompet - Kaishan):**
  - Layanan pelepasan dana escrow `LayananDana` dengan potongan komisi platform 5% ke dompet pendapatan dan ongkir utuh ke dompet penjual (LP-8, LP-12).
  - Layanan penarikan dana dua orang `LayananPencairan` melalui tabel `pencairan`: pengaju tidak boleh menyetujui penarikannya sendiri (LP-16).
  - Tujuh invarian buku besar ganda pada `php artisan lapak:periksa-buku` (LP-13).
  - Tabel `mutasi_stok` untuk mencatat jejak inventaris barang.

- **Domain 3 (Pesanan & Belanja - Novendra):**
  - Layanan status pesanan satu pintu `LayananPesanan` yang mengoordinasikan gembok checkout, cap waktu (`dikemas_pada`, `dikirim_pada`, `selesai_pada`), akibat pelepasan dana/stok, dan log audit secara atomik.
  - Rute aksi pembeli `POST /api/v1/pesanan/{nomor}/terima` dan `POST /api/v1/pesanan/{nomor}/batal` terlindung `sesi.segar`.
  - Layanan dan command penyelesaian otomatis `php artisan lapak:selesaikan-pesanan` untuk pesanan yang telah lewat masa tahan dana.
  - Penegakan pengembalian stok berurutan (`ksort`) untuk mencegah deadlock pada pembatalan pesanan.

- **Domain 2 (Toko & Katalog - Azizah):**
  - Izin baru `NamaIzin::PesananProses` (`pesanan.proses`) untuk peran Penjual.
  - Lima rute manajemen pesanan toko (`GET /api/v1/toko/pesanan`, `GET /api/v1/toko/pesanan/{pesanan}`, `GET /api/v1/toko/pesanan/{pesanan}/riwayat`, `POST /api/v1/toko/pesanan/{pesanan}/proses`, `POST /api/v1/toko/pesanan/{pesanan}/kirim`).
  - Isolasi multi-tenant antar-toko: akses pesanan milik toko lain dijawab `404 TIDAK_DITEMUKAN` melalui `scopeMilikPenjual`.
  - Layanan append-only `PencatatStok` yang terintegrasi di `PenjagaStok` untuk mencatat setiap pengurangan, pengembalian, dan penyesuaian stok ke `mutasi_stok`.

---

## [0.1.0] - 2026-10-01
- Fondasi awal arsitektur modular Lapak API Marketplace UMKM Solo Raya.
