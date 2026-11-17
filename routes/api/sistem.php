<?php

declare(strict_types=1);

use App\Http\Controllers\AuditController;
use App\Http\Controllers\KesehatanController;
use Illuminate\Support\Facades\Route;

Route::get('health', KesehatanController::class)->name('health');

Route::get('audit', [AuditController::class, 'index'])
    ->middleware(['auth:sesi', 'izin:audit.lihat', 'sesi.segar'])
    ->name('audit.index');
