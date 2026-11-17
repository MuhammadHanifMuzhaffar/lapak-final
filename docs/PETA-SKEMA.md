# Peta Skema Basis Data Lapak

Dokumen ini memetakan kepemilikan tabel, penomoran migration, dan arah foreign key antardomain untuk seluruh siklus pengembangan Lapak (Sesi 1 sampai Sesi 5).

## Anggota Tim Pengembang
- **D1 - Identitas dan Sesi:** Muhammad Hanif Muzhaffar (V3425072 / @MuhammadHanifMuzhaffar)
- **D2 - Toko dan Katalog:** Ni'matul Azizah (V3425077 / @NimatulAzizahh)
- **D3 - Keranjang dan Pesanan:** Novendra Anggoro Adji Nugroho (V3425016 / @NovendraAnggoroAdjiNugroho)
- **D4 - Dompet dan Buku Besar:** Kaishan Arya Setiko (V3425036 / @KaishanAryaSetiko)
- **D5 - Respons, Kualitas, dan Audit:** Zidnii Aghniya Rajwa (V3425038 / @ZidniiAghniyaRajwa) — Integrator Sesi 5

## Konvensi Penomoran Migration
Seluruh migration wajib mengikuti pola:
`2026_10_SS_DNNNNN_nama.php`
- `SS`: Nomor sesi (misal `01` untuk Sesi 1)
- `D`: Nomor domain (`1` = Identitas, `2` = Katalog, `3` = Belanja, `4` = Dompet, `5` = Kualitas)
- `NNNNN`: Nomor urut migration dalam domain tersebut (5 digit)

## Kepemilikan Tabel per Domain
| Domain | Penanggung Jawab | Tabel | Sesi |
|---|---|---|---|
| D1 Identitas dan Sesi | Hanif | `pengguna`, `sesi`, `alamat` | Sesi 1, 2, 4, 5 |
| D2 Toko dan Katalog | Azizah | `toko`, `kategori`, `produk`, `mutasi_stok` | Sesi 1, 3, 5 |
| D3 Keranjang dan Pesanan | Novendra | `keranjang`, `butir_keranjang`, `checkout`, `pesanan`, `butir_pesanan` | Sesi 1, 2, 4, 5 |
| D4 Dompet dan Buku Besar | Kaishan | `dompet`, `mutasi_saldo`, `pembayaran`, `pencairan` | Sesi 1, 2, 4, 5 |
| D5 Respons, Kualitas, dan Audit | Zidni | `kunci_idempotensi`, `log_audit`, `rantai_audit` | Sesi 4, 5 |

## Hubungan Antartabel
```text
pengguna (D1) <- dompet (D4) <- mutasi_saldo (D4)
pengguna (D1) <- alamat (D1)
pengguna (D1) <- checkout (D3) <- pesanan (D3) <- butir_pesanan (D3)
toko (D2) <- pesanan (D3)          produk (D2) <- butir_pesanan (D3)
checkout (D3) <- pembayaran (D4)
pengguna (D1) <- pencairan (D4)
produk (D2) <- mutasi_stok (D2)
pengguna (D1) <- kunci_idempotensi (D5)
pengguna (D1) <- log_audit (D5)
```

## Urutan Kunci Baris (Mencegah Deadlock)

Berlaku sejak Sesi 4 dan diperkuat di Sesi 5. Transaksi yang mengunci lebih dari satu baris mengambil kuncinya menurut urutan di bawah ini. Sebuah transaksi boleh melompati anak tangga, tetapi tidak pernah naik kembali.

| Urut | Baris | Dikunci oleh |
| --- | --- | --- |
| 1 | `pengguna`, lalu `alamat` miliknya | `BukuAlamat` |
| 2 | `keranjang`, lalu `butir_keranjang` | `LayananCheckout` |
| 3 | `checkout` | `GembokCheckout` |
| 4 | `pembayaran`, atau `pencairan` | `LayananPembayaran`, `LayananPencairan` |
| 5 | `pesanan` | `MesinStatusPesanan`, `LayananPesanan` |
| 6 | `produk`, urut id produk menaik | `PenjagaStok` |
| 7 | `dompet`, urut id dompet menaik | `BukuBesar` |
| 8 | `rantai_audit`, kepala rantai log audit | `PencatatAudit` |

Tiga aturan menyertainya:
1. Kunci hanya diambil di dalam `DB::transaction()`. Di luar transaksi, kunci lepas begitu pernyataannya selesai.
2. Kunci dulu, baca kemudian. Data yang menjadi dasar keputusan dibaca dengan `lockForUpdate()` setelah kunci induknya didapat.
3. Tidak ada panggilan ke layanan luar dan tidak ada pekerjaan lama di dalam transaksi.
