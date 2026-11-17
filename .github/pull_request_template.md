## Ringkasan
<!-- dua sampai empat kalimat: masalah, pendekatan, dampak -->

## PR terkait
- Menggabungkan: #
- Bergantung pada: #

## Perubahan
- <!-- berkas/kelas utama yang ditambah atau diubah -->
- <!-- migration atau perintah baru -->

## Bukti (tempel apa adanya dari mesin)
```
vendor\bin\pint --test
vendor\bin\pest
php artisan lapak:periksa-buku
php artisan lapak:balapan --semua
php artisan lapak:verifikasi-audit
```

## Cara menguji di mesin peninjau
1. git switch <cabang>
2. composer install && php artisan migrate:fresh --seed
3. <perintah spesifik PR ini>
4. Harapan: <keluaran>

## Risiko dan batasan
- <!-- apa yang belum tercakup, dan mengapa itu dapat diterima -->

## Daftar periksa penulis
- [ ] Uji baru gagal bila perubahan inti dicabut (sudah dicoba)
- [ ] Tidak ada actingAs(), tidak ada angka ajaib
- [ ] docs/log-ai/<nama>.md diperbarui
- [ ] Matriks otorisasi dan kontrak galat diperbarui bila menambah rute/kode
