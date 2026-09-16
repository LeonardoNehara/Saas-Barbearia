<?php

use App\Http\Controllers\ProfissionalController;
use App\Http\Controllers\ServicoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'active'])->prefix('servicos')->name('servicos.')->group(function (): void {
    Route::get('/', [ServicoController::class, 'index'])->name('index');
    Route::post('/', [ServicoController::class, 'store'])->name('store');
    Route::get('/{servico}', [ServicoController::class, 'show'])->name('show');
    Route::put('/{servico}', [ServicoController::class, 'update'])->name('update');
    Route::patch('/{servico}/status', [ServicoController::class, 'toggleStatus'])->name('status');
    Route::put('/{servico}/profissionais', [ServicoController::class, 'syncProfissionais'])->name('profissionais');
});

Route::middleware(['auth', 'active'])->prefix('profissionais')->name('profissionais.')->group(function (): void {
    Route::get('/', [ProfissionalController::class, 'index'])->name('index');
    Route::post('/', [ProfissionalController::class, 'store'])->name('store');
    Route::get('/{profissional}', [ProfissionalController::class, 'show'])->name('show');
    Route::put('/{profissional}', [ProfissionalController::class, 'update'])->name('update');
    Route::patch('/{profissional}/status', [ProfissionalController::class, 'toggleStatus'])->name('status');
});
