<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NfeImportController;
use App\Http\Controllers\SefazCertificateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('nfe-importadas', [NfeImportController::class, 'index'])->name('nfe-importadas.index');
    Route::get('nfe-importadas/{nfeImport}', [NfeImportController::class, 'show'])->name('nfe-importadas.show');
    Route::get('nfe-importadas/{nfeImport}/danfe', [NfeImportController::class, 'danfe'])->name('nfe-importadas.danfe');
    Route::get('nfe-importadas/{nfeImport}/precificar', [NfeImportController::class, 'pricing'])->name('nfe-importadas.pricing');
    Route::put('nfe-importadas/{nfeImport}/precificar', [NfeImportController::class, 'updatePricing'])->name('nfe-importadas.pricing.update');
    Route::post('nfe-importadas', [NfeImportController::class, 'store'])->name('nfe-importadas.store');

    Route::get('certificado', [SefazCertificateController::class, 'edit'])->name('certificate.edit');
    Route::post('certificado', [SefazCertificateController::class, 'update'])->name('certificate.update');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
