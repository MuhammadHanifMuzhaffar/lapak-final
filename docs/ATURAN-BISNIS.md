# Penegakan Aturan Bisnis Lapak (LP-1 sampai LP-17)

| Kode | Aturan | Ditegakkan di |
|---|---|---|
| LP-1 | Bilangan bulat positif untuk uang dan stok | AturanValidasi, Uang value object, migration unsignedInteger |
| LP-2 | Izin berbasis peran | Enum NamaIzin, NamaPeran, middleware izin, PengelolaPeran |
| LP-3 | Moderasi toko oleh admin | ModerasiToko, TokoPolicy, TransisiTokoTidakSah |
| LP-4 | Visibilitas produk dan stok penjual | Produk::scopeTampil, PenyusunBelanja, PenjagaStok, PenyesuaianStok |
| LP-5 | Larangan membeli barang toko sendiri | PenyusunBelanja::pilah, LayananKeranjang::aturButir, LayananCheckout |
| LP-6 | Pembekuan harga dan nama produk | SimulasiBelanjaRequest, PenyusunBelanja, CheckoutRequest, kolom butir_pesanan |
| LP-7 | Hubungan checkout dan pembayaran | PenghitungBelanja, LayananCheckout, unique pada pembayaran.checkout_id |
| LP-8 | Pemotongan saldo dompet penjual & pelepasan escrow | BukuBesar::pindahkan, LayananDana (komisi 5% subtotal, ongkir utuh) |
| LP-9 | Format nomor pesanan unik | PenomorPesanan (LPK-YYYYMMDD-NNNNNN), unique pada pesanan.nomor |
| LP-10 | Daur hidup pesanan dan satu pintu status | AturanTransisi, MesinStatusPesanan, LayananPesanan, PembatalanOtomatis |
| LP-11 | Idempotensi permintaan bermutasi | Middleware idempoten, kunci_idempotensi, RuteIdempotenTest |
| LP-12 | Dana tertahan di escrow | LayananPembayaran::terima, BukuBesar dana masuk escrow |
| LP-13 | Invarian buku besar ganda | BukuBesar, MutasiSaldo, lapak:periksa-buku (7 invarian) |
| LP-14 | Masa hidup sesi dan deteksi token dicabut | PemeriksaSesi, Sesi model scopeBerlaku, pembunuhan sesi saat reuse |
| LP-15 | Sesi segar dan batas laju transaksi | SesiSegar middleware, PenjagaTransaksi ambang_segar_rupiah |
| LP-16 | Pencairan dana dua orang | LayananPencairan (pengaju tidak boleh menyetujui sendiri, dompet penahan) |
| LP-17 | Rantai log audit anti-tamper SHA-256 | PencatatAudit, PemeriksaRantaiAudit, LogAudit::kanonik, lapak:verifikasi-audit |
