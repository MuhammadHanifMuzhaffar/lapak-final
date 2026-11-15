# Papan Sesi 5: Escrow, Saldo, Audit, dan Rilis

- **Integrator:** Zidnii Aghniya Rajwa (@ZidniiAghniyaRajwa)
- **Tag Penutup:** `sesi-5` & `v1.0.0`
- **Target Capaian:** Pelepasan escrow, saldo dan penarikan 2-orang, log audit berantai hash SHA-256 anti-tamper, idempotensi per domain, dan rilis v1.0.0.

## Matriks Pull Request Sesi 5 (Dual Reviewer)

| PR | Jendela | Cabang | Penulis | Isi / Fitur | Dual Reviewer | Status |
|:---:|:---:|:---|:---:|:---|:---:|:---:|
| 47 | Pembuka | `sesi-5/pembuka-sekar` | Zidnii | Papan sesi 5, daftar periksa rilis, refactor berkas bersama | Hanif, Azizah | Open |
| 48 | A1 | `sesi-5/kontrak-audit-sekar` | Zidnii | Tabel log audit, rantai hash, PencatatAudit versi kontrak | Hanif, Azizah | Menunggu |
| 49 | A2 | `sesi-5/kontrak-dana-dimas` | Kaishan | Tabel pencairan, dompet penahan escrow, LayananDana | Zidnii, Hanif | Menunggu |
| 50 | A3 | `sesi-5/kontrak-pesanan-rani` | Novendra | Cap waktu proses, LayananPesanan, pendukung uji | Kaishan, Zidnii | Menunggu |
| 51 | B1 | `sesi-5/jejak-identitas-bambang` | Hanif | Deteksi kunci sesi revoked, jejak masuk, keluar, peran | Azizah, Novendra | Menunggu |
| 52 | B2 | `sesi-5/pesanan-toko-bagas` | Azizah | Izin pesanan.proses, 5 rute pesanan toko, jejak moderasi | Novendra, Kaishan | Menunggu |
| 53 | B3 | `sesi-5/tindakan-pembeli-rani` | Novendra | Terima barang, batalkan pesanan, penyelesaian otomatis | Kaishan, Zidnii | Menunggu |
| 54 | B4 | `sesi-5/pencairan-dimas` | Kaishan | Pembayaran telat, pencairan rekening penjual, invarian ke-6 | Zidnii, Hanif | Menunggu |
| 55 | B5 | `sesi-5/rantai-audit-sekar` | Zidnii | Kunci kepala rantai audit, verifikasi integritas, CI | Hanif, Azizah | Menunggu |
| 56 | Rilis | `sesi-5/rilis-sekar` | Zidnii | Uji penutup, versi 1.0.0, README final, tag git | Hanif, Azizah | Menunggu |
| 57 | P1 | `sesi-5/jejak-stok-bagas` | Azizah | Jejak audit penyesuaian stok | Novendra, Kaishan | Menunggu |
| 58 | Catatan | `sesi-5/catatan-perubahan-bambang` | Hanif | Dokumen resmi CHANGELOG.md | Azizah, Novendra | Menunggu |
