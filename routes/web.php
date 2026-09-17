<?php

use App\Http\Controllers\ProfissionalController;
use App\Http\Controllers\ServicoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HorarioProfissionalController;
use App\Http\Controllers\BloqueioProfissionalController;
use App\Http\Controllers\AgendamentoController;

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

    Route::get('/{profissional}/horarios', [HorarioProfissionalController::class, 'index'])->name('horarios.index')->middleware('can:is-admin');
    Route::post('/{profissional}/horarios', [HorarioProfissionalController::class, 'store'])->name('horarios.store')->middleware('can:is-admin');
    Route::delete('/{profissional}/horarios/{horario}', [HorarioProfissionalController::class, 'destroy'])->name('horarios.destroy')->middleware('can:is-admin');

    Route::get('/{profissional}/bloqueios',[BloqueioProfissionalController::class, 'index'])->name('bloqueios.index')->middleware('can:is-admin');
    Route::post('/{profissional}/bloqueios',[BloqueioProfissionalController::class, 'store'])->name('bloqueios.store')->middleware('can:is-admin');
    Route::delete('/{profissional}/bloqueios/{bloqueio}',[BloqueioProfissionalController::class, 'destroy'])->name('bloqueios.destroy')->middleware('can:is-admin');
});

Route::middleware(['auth', 'active'])->prefix('agendamentos')->name('agendamentos.')->group(function (): void {
    Route::get('/', [AgendamentoController::class, 'index'])->name('index');
    Route::post('/', [AgendamentoController::class, 'store'])->name('store');
    Route::get('/{agendamento}', [AgendamentoController::class, 'show'])->name('show');
    Route::patch('/{agendamento}/cancelar', [AgendamentoController::class, 'cancelar'])->name('cancelar');
});
