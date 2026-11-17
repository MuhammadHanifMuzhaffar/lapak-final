# Papan Sesi 5: Escrow, Saldo, Audit, dan Rilis

- **Integrator:** Zidnii Aghniya Rajwa (@ZidniiAghniyaRajwa)
- **Tag Penutup:** `sesi-5` & `v1.0.0`
- **Target Capaian:** Pelepasan escrow, saldo dan penarikan 2-orang, log audit berantai hash SHA-256 anti-tamper, idempotensi per domain, dan rilis v1.0.0.

> [!NOTE]
> Sesuai Rencana Kerja Sesi 5, jendela A (kontrak) digabung ke PR domain masing-masing untuk mencegah konflik berkas bersama:
> - PR #48 Zidni (Domain 5): Rantai audit SHA-256, verifikasi integritas, dan gerbang kualitas (menggabungkan kontrak audit).
> - PR #49 Hanif (Domain 1): Jejak identitas, penjagaan sesi, dan catatan perubahan (menggabungkan CHANGELOG).
> - PR #50 Kaishan (Domain 4): Pelepasan escrow, pencairan dua orang, dan invarian 5-7 (menggabungkan kontrak dana).
> - PR #51 Novendra (Domain 3): Layanan pesanan satu pintu, penyelesaian otomatis, dan uji checkout (menggabungkan kontrak pesanan).
> - PR #52 Azizah (Domain 2): Pintu pesanan toko dan jejak mutasi stok (menggabungkan jejak stok).

## Matriks Pull Request Sesi 5 (Dual Reviewer)

| PR | Domain | Cabang | Penulis | Isi / Fitur | Dual Reviewer | Status |
|:---:|:---:|:---|:---:|:---|:---:|:---:|
| 47 | Pembuka | `sesi-5/pembuka-sekar` | Zidnii | Papan sesi 5, daftar periksa rilis, refactor berkas bersama | Hanif, Azizah | Merged |
| 48 | D5 (Audit & Kualitas) | `sesi-5/rantai-audit-sekar` | Zidnii | Tabel log audit, rantai hash SHA-256, PencatatAudit, lapak:verifikasi-audit, GET /audit, balapan serempak | Hanif, Azizah | Review |
| 49 | D1 (Identitas & Sesi) | `sesi-5/jejak-identitas-bambang` | Hanif | Deteksi kunci sesi revoked, jejak masuk/keluar/peran, penjaga seeder produksi | Azizah, Novendra | Menunggu |
| 50 | D4 (Dana & Dompet) | `sesi-5/pencairan-dimas` | Kaishan | Pelepasan escrow komisi 5%, pencairan dua orang, invarian 5-7, uji webhook | Zidnii, Hanif | Menunggu |
| 51 | D3 (Pesanan & Belanja) | `sesi-5/tindakan-pembeli-rani` | Novendra | LayananPesanan satu pintu, terima/batal, penyelesaian otomatis, 6 uji checkout | Kaishan, Zidnii | Menunggu |
| 52 | D2 (Toko & Katalog) | `sesi-5/pesanan-toko-bagas` | Azizah | Izin pesanan.proses, 5 rute pesanan toko, 404 cross-shop, append-only mutasi_stok | Novendra, Kaishan | Menunggu |
| - | Rilis & Tag | `main` | Zidnii | Verifikasi klon baru, versi 1.0.0, tag ganda `sesi-5` dan `v1.0.0` | Semua | Menunggu |
