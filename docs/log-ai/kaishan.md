# Log Penggunaan AI — Sesi 5

- **Nama:** Kaishan Arya Setiko
- **NIM:** V3425036
- **Domain:** Domain 4 - Dompet dan Buku Besar

## Rincian

| Tanggal | Aktivitas / Topik | Prompt Pokok | Hasil / Keputusan Teknis |
|---|---|---|---|
| 18 November 2026 | Pelepasan Escrow & Komisi 5% | "Bagaimana memastikan komisi 5% dihitung dari subtotal saja dan pelepasan dana bersifat idempoten dengan lockForUpdate?" | Menghitung komisi dari `subtotal_rupiah` menggunakan `Uang::persen()`, ongkir tetap utuh untuk penjual, dan mengunci baris pesanan untuk memeriksa `dana_dilepas_pada`. |
| 18 November 2026 | Pencairan Saldo Aturan Dua Orang | "Bagaimana merancang alur pencairan dengan dompet penahan dan mencegah persetujuan oleh pengaju sendiri?" | Memindahkan dana ke dompet sistem `pencairan` saat diajukan, menolak persetujuan jika penyetuju sama dengan pengaju (403 `DUA_ORANG`), dan mencegah keputusan berulang (409 `SUDAH_DIPUTUS`). |
| 18 November 2026 | Perluasan Invarian Buku Besar | "Bagaimana merumuskan tiga invarian baru (escrow, pencairan, dan jejak mutasi stok) pada command lapak:periksa-buku?" | Menambahkan invarian 5 (escrow = total pesanan ditahan), invarian 6 (dompet pencairan = total pencairan menunggu), dan invarian 7 (jejak mutasi stok berantai). |

## Yang saya verifikasi sendiri
- `vendor\bin\pint --test` (0 pelanggaran gaya kode)
- `vendor\bin\pest` (seluruh pengujian lulus hijau)
- `php artisan migrate:fresh --seed` (berhasil, akun contoh dibuat, dompet pencairan terinisialisasi)
- `php artisan lapak:periksa-buku` (tujuh invarian seimbang, 0 pelanggaran)
- `php artisan lapak:balapan --semua` (semua skenario lolos)
- `php artisan lapak:verifikasi-audit` (rantai audit utuh dan kepala cocok)

## Yang saya ubah dari saran AI
- Menambahkan alias relasi `$toko->penjual` ke `$toko->pemilik()` pada model `Toko` agar `LayananDana` sesuai dengan dokumen rancangan.
- Menguji secara langsung perturbasi saldo escrow 1 rupiah pada `InvarianBaruTest` untuk memastikan command `lapak:periksa-buku` mengembalikan kode keluar 1 dan pesan kesalahan yang akurat.
