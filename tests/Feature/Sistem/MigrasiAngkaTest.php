<?php

declare(strict_types=1);

it('tidak memakai angka pecahan di migration', function () {
    foreach (glob(database_path('migrations/*.php')) as $berkas) {
        $isi = file_get_contents($berkas);

        foreach (['->float(', '->double(', '->decimal(', '->unsignedDecimal('] as $terlarang) {
            expect($isi)->not->toContain($terlarang, basename($berkas));
        }
    }
});

it('menyertakan WWW-Authenticate pada 401', function () {
    $this->getJson('/api/v1/saya')
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer');
});
