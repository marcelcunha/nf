<?php

use App\Http\Controllers\NfeImportController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('people', [PersonController::class, 'index'])->name('people.index');
    Route::get('nfe-imports', [NfeImportController::class, 'index'])->name('nfe-imports.index');
    Route::post('nfe-imports', [NfeImportController::class, 'store'])->name('nfe-imports.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
