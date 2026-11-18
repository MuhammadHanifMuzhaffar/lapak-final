<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pencairan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('penjual_id')->constrained('pengguna');
            $table->unsignedBigInteger('jumlah_rupiah');
            $table->string('status', 20)->default('menunggu');
            $table->foreignId('diajukan_oleh')->constrained('pengguna');
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('pengguna');
            $table->dateTime('diputuskan_pada')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();
            $table->index(['penjual_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pencairan');
    }
};
