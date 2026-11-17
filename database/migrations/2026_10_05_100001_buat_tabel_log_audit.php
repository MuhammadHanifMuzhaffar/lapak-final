<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_audit', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('nomor')->unique();
            $table->string('peristiwa', 40);
            $table->foreignId('pelaku_id')->nullable()->constrained('pengguna');
            $table->string('sasaran', 64)->nullable();
            $table->string('rujukan', 64)->nullable();
            $table->json('data')->nullable();
            $table->dateTime('dibuat_pada', 6);
            $table->char('hash_sebelumnya', 64);
            $table->char('hash', 64);
            $table->index(['peristiwa', 'dibuat_pada']);
            $table->index('rujukan');
        });

        Schema::create('rantai_audit', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('nomor_terakhir')->default(0);
            $table->char('hash_terakhir', 64);
            $table->dateTime('diperbarui_pada', 6)->nullable();
        });

        DB::table('rantai_audit')->insert([
            'nomor_terakhir' => 0,
            'hash_terakhir' => str_repeat('0', 64),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('rantai_audit');
        Schema::dropIfExists('log_audit');
    }
};
