# Piagam Domain 2: Toko dan Katalog

- **Penanggung Jawab:** Ni'matul Azizah (@NimatulAzizahh)
- **Lingkup:** Toko, Kategori, Produk, Inventaris Stok, dan Pengelolaan Pesanan Toko

## Batasan dan Tanggung Jawab Domain
1. Bertanggung jawab atas pengelolaan entitas toko (`toko`), kategori produk (`kategori`), dan produk (`produk`).
2. Menegakkan daur hidup toko: pendaftaran toko berstatus `menunggu`, moderasi oleh admin (`aktif` atau `ditangguhkan`).
3. Mengatur visibilitas produk pada katalog publik: produk hanya dapat dibeli jika toko berstatus `aktif` dan produk berstatus `aktif` dengan stok mencukupi (LP-4).
4. Menjaga integritas inventaris stok secara atomik melalui `PenjagaStok` dengan penyesuaian stok append-only ke `mutasi_stok` melalui `PencatatStok`.
5. Menyediakan pintu gerbang bagi penjual untuk mengelola pesanan tokonya (`/api/v1/toko/pesanan`) dengan otorisasi berbasis izin `pesanan.proses`.
6. Menegakkan isolasi pesanan toko via `scopeMilikPenjual`: upaya mengakses atau memanipulasi pesanan milik toko lain dijawab `404 TIDAK_DITEMUKAN` (bukan 403) untuk mencegah kebocoran informasi keberadaan pesanan.
7. Memastikan seluruh perubahan status pesanan toko didelegasikan kepada `LayananPesanan` sebagai pintu tunggal transisi status.

## Aturan Bisnis yang Dikawal
- **LP-3:** Moderasi toko oleh admin (hanya toko aktif yang dapat beroperasi secara normal).
- **LP-4:** Visibilitas produk dan integritas stok (tidak boleh terjadi minus stok atau race condition pada inventaris).
- **LP-10:** Daur hidup pesanan dipatuhi secara ketat saat toko memproses (`dikemas`) dan mengirim (`dikirim`) barang.
- **LP-11:** Idempotensi penyesuaian stok melalui `Idempotency-Key` dan idempotensi aksi status via status mesin.
- **LP-15:** Operasi tulis kritis pada pesanan toko menuntut sesi segar (`sesi.segar`).
