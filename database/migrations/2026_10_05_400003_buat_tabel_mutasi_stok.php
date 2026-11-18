<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_stok', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk');
            $table->string('jenis', 15);              // masuk | keluar | penyesuaian
            $table->integer('jumlah');                // bertanda
            $table->integer('stok_setelah');
            $table->string('rujukan', 64)->nullable();
            $table->dateTime('dibuat_pada');
            $table->index(['produk_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_stok');
    }
};
