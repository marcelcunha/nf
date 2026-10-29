<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\NfeImportController;
use App\Http\Controllers\PriceTableController;
use App\Http\Controllers\SefazCertificateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('tabelas-de-preco', [PriceTableController::class, 'index'])->name('price-tables.index');
    Route::get('tabelas-de-preco/{nfeImport}', [PriceTableController::class, 'show'])->name('price-tables.show');

    Route::middleware('admin')->group(function () {
        Route::get('nfe-importadas', [NfeImportController::class, 'index'])->name('nfe-importadas.index');
        Route::get('nfe-importadas/{nfeImport}', [NfeImportController::class, 'show'])->name('nfe-importadas.show');
        Route::get('nfe-importadas/{nfeImport}/danfe', [NfeImportController::class, 'danfe'])->name('nfe-importadas.danfe');
        Route::get('nfe-importadas/{nfeImport}/precificar', [NfeImportController::class, 'pricing'])->name('nfe-importadas.pricing');
        Route::put('nfe-importadas/{nfeImport}/precificar', [NfeImportController::class, 'updatePricing'])->name('nfe-importadas.pricing.update');
        Route::delete('nfe-importadas/{nfeImport}/publicar', [NfeImportController::class, 'unpublish'])->name('nfe-importadas.unpublish');

        Route::get('certificado', [SefazCertificateController::class, 'edit'])->name('certificate.edit');
        Route::post('certificado', [SefazCertificateController::class, 'update'])->name('certificate.update');

        Route::get('funcionarios', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('funcionarios/criar', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('funcionarios', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('funcionarios/{employee}/editar', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('funcionarios/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::post('funcionarios/{employee}/senha', [EmployeeController::class, 'resetPassword'])->name('employees.reset-password');
        Route::patch('funcionarios/{employee}/ativo', [EmployeeController::class, 'toggleActive'])->name('employees.toggle-active');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
