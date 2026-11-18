<?php

return [
    'GET api/v1/toko-saya' => '*',
    'PATCH api/v1/toko-saya' => '*',
    'POST api/v1/toko-saya' => ['pembeli'],

    'GET api/v1/toko-saya/produk' => ['penjual'],
    'POST api/v1/toko-saya/produk' => ['penjual'],
    'PATCH api/v1/toko-saya/produk/{id}' => ['penjual'],
    'DELETE api/v1/toko-saya/produk/{id}' => ['penjual'],
    'POST api/v1/toko-saya/produk/{id}/stok' => ['penjual'],

    'GET api/v1/admin/toko' => ['admin'],
    'POST api/v1/admin/toko/{id}/setujui' => ['admin'],
    'POST api/v1/admin/toko/{id}/tangguhkan' => ['admin'],

    'GET api/v1/toko/pesanan' => ['penjual'],
    'GET api/v1/toko/pesanan/{pesanan}' => ['penjual'],
    'GET api/v1/toko/pesanan/{pesanan}/riwayat' => ['penjual'],
    'POST api/v1/toko/pesanan/{pesanan}/proses' => ['penjual'],
    'POST api/v1/toko/pesanan/{pesanan}/kirim' => ['penjual'],
];
