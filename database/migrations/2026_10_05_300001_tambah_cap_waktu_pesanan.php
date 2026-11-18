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
            $table->dateTime('dikemas_pada')->nullable()->after('dibayar_pada');
            $table->dateTime('dikirim_pada')->nullable()->after('dikemas_pada');
            $table->dateTime('selesai_pada')->nullable()->after('dikirim_pada');
            $table->index(['status', 'dikirim_pada']);
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table): void {
            $table->dropIndex(['status', 'dikirim_pada']);
            $table->dropColumn(['dikemas_pada', 'dikirim_pada', 'selesai_pada']);
        });
    }
};
