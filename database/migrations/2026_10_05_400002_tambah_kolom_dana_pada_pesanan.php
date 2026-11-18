<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table): void {
            $table->dateTime('dana_dilepas_pada')->nullable();
            $table->dateTime('dana_dikembalikan_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table): void {
            $table->dropColumn(['dana_dilepas_pada', 'dana_dikembalikan_pada']);
        });
    }
};
