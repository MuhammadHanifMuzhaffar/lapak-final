# Kontrak Format Respons dan Galat API Lapak

Seluruh endpoint REST API Lapak wajib mematuhi struktur amplop respons JSON terpadu yang didefinisikan dalam dokumen ini.

## 1. Amplop Respons Berhasil (HTTP 200 / 201)
```json
{
  "sukses": true,
  "data": { ... } atau [ ... ],
  "meta": null atau {
    "halaman": 1,
    "per_halaman": 12,
    "total": 45,
    "halaman_terakhir": 4
  }
}
```

## 2. Amplop Respons Galat (HTTP 4xx / 5xx)
```json
{
  "sukses": false,
  "galat": {
    "kode": "KODE_GALAT",
    "pesan": "Penjelasan galat yang ramah pengguna.",
    "rincian": { ... } atau []
  }
}
```

## 3. Daftar Kode Galat Standar
| Kode Galat | Status HTTP | Keterangan |
|---|---|---|
| `VALIDASI` | 422 Unprocessable Entity | Masukan klien tidak memenuhi aturan validasi FormRequest. `rincian` memuat array nama field dan pesan. |
| `TIDAK_DITEMUKAN` | 404 Not Found | Data yang diminta tidak ada, berstatus draf, atau milik toko nonaktif (LP-4). |
| `DITOLAK` | 403 Forbidden | Akun tidak memiliki peran/izin untuk tindakan ini (LP-2). |
| `BELUM_MASUK` | 401 Unauthorized | Kunci sesi tidak ada, kedaluwarsa, atau dicabut (Sesi 2). Disertai header `WWW-Authenticate: Bearer`. |
| `METODE_DITOLAK` | 405 Method Not Allowed | Metode HTTP tidak diizinkan untuk alamat ini. |
| `TERLALU_SERING` | 429 Too Many Requests | Terlalu banyak permintaan. Coba lagi beberapa saat. |
| `GALAT_SERVER` | 500 Internal Server Error | Kesalahan tak terduga di server. Rincian internal disembunyikan saat produksi. |

## 4. Idempotensi
- Setiap permintaan yang mengubah uang atau stok wajib membawa header `Idempotency-Key` (LP-11): 16 sampai 64 karakter alphanumeric/dash/underscore.
- Permintaan pertama yang berhasil (2xx) dicatat bersama responsnya. Pengulangan dengan kunci dan badan yang sama menerima respons yang sama, ditandai header `Idempotency-Replayed: true`.
- Kunci yang sama dengan badan berbeda ditolak 422 `TIDAK_VALID`.

## 5. Rute Sistem & Audit (Sesi 5)
- `GET /api/v1/health`: Pengecekan kondisi server dan versi aplikasi.
- `GET /api/v1/audit`: Membaca catatan rantai log audit (khusus peran `auditor`, izin `audit.lihat`, dan wajib `sesi.segar`).
