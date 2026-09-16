<?php

use App\Http\Controllers\ProfissionalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'active'])->prefix('profissionais')->name('profissionais.')->group(function (): void {
    Route::get('/', [ProfissionalController::class, 'index'])->name('index');
    Route::post('/', [ProfissionalController::class, 'store'])->name('store');
    Route::get('/{profissional}', [ProfissionalController::class, 'show'])->name('show');
    Route::put('/{profissional}', [ProfissionalController::class, 'update'])->name('update');
    Route::patch('/{profissional}/status', [ProfissionalController::class, 'toggleStatus'])->name('status');
});
