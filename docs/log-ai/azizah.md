# Log Penggunaan AI — Sesi 5

- **Nama:** Ni'matul Azizah
- **NIM:** V3425077
- **Domain:** Domain 2 - Toko dan Katalog

## Rincian

| Tanggal | Aktivitas / Topik | Prompt Pokok | Hasil / Keputusan Teknis |
|---|---|---|---|
| 18 November 2026 | Izin dan Rute Pesanan Toko | "Bagaimana merancang rute pengelolaan pesanan bagi penjual dengan izin pesanan.proses, sesi segar untuk aksi tulis, dan penegakan isolasi multi-tenant antar-toko?" | Menambahkan izin `NamaIzin::PesananProses` pada peran Penjual, membuat `PesananTokoController` dengan 5 rute (`index`, `show`, `riwayat`, `proses`, `kirim`), dan menegakkan 404 via `scopeMilikPenjual` jika pesanan bukan milik tokonya. |
| 18 November 2026 | Jejak Append-Only Mutasi Stok | "Bagaimana memastikan setiap pengurangan, pengembalian, dan penyesuaian stok tercatat secara transparan pada tabel mutasi_stok tanpa ada operasi update/delete?" | Mengimplementasikan `PencatatStok` dan mengintegrasikannya ke dalam `PenjagaStok` (`kurangi`, `kembalikan`, `sesuaikan`) sehingga setiap pergeseran stok mencatat jenis, jumlah bertanda, stok setelah, dan nomor rujukan. |
| 18 November 2026 | Pengujian Feature & Matriks Otorisasi | "Bagaimana menyusun 8 pengujian komprehensif untuk pesanan toko dan memverifikasi matriks otorisasi serta idempotensi rute?" | Membuat `PesananTokoTest` mencakup akses cross-shop 404, proses pesanan dibayar ke dikemas, kirim pesanan dikemas ke dikirim, penolakan pembeli 403 DITOLAK, transisi tidak sah 409, isolasi riwayat audit per pesanan, pencatatan mutasi stok, serta validasi matriks otorisasi. |

## Yang saya verifikasi sendiri
- `vendor\bin\pint --test` (0 pelanggaran gaya kode, format rapi)
- `vendor\bin\pest` (seluruh 585 pengujian lulus hijau)
- `DB_CONNECTION=mysql php artisan migrate:fresh --seed` (tabel `mutasi_stok` tercipta dan data terisi sempurna)
- `php artisan lapak:periksa-buku` (buku besar seimbang, 0 pelanggaran)
- `php artisan lapak:balapan --semua` (seluruh 8 skenario pengujian beban serempak lulus)
- `php artisan lapak:verifikasi-audit` (rantai log audit utuh tanpa cabang atau celah)

## Yang saya ubah dari saran AI
- Urutan registrasi rute di `routes/api/katalog.php`: memindahkan grup rute `toko/pesanan` ke atas sebelum `toko/{slug}` agar tidak tertelan oleh wildcard slug publik.
- Memastikan method `proses` dan `kirim` di `PesananTokoController` tidak memulai `DB::transaction()` sendiri, melainkan mendelegasikannya secara penuh ke gerbang tunggal `LayananPesanan`.
