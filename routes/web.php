<?php

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AgendamentoPublicoController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\BloqueioProfissionalController;
use App\Http\Controllers\DisponibilidadeController;
use App\Http\Controllers\EstabelecimentoPublicoController;
use App\Http\Controllers\HorarioProfissionalController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ProfissionalController;
use App\Http\Controllers\ServicoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Página inicial
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('agendamentos.index')
        : view('auth.login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->prefix('usuarios')->name('usuarios.')->group(function (): void {
    Route::get('/', [UsuarioController::class, 'index'])->name('index');
    Route::get('/novo', [UsuarioController::class, 'create'])->name('create');
    Route::post('/', [UsuarioController::class, 'store'])->name('store');
    Route::get('/{usuario}/editar', [UsuarioController::class, 'edit'])->name('edit');
    Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update');
    Route::patch('/{usuario}/status', [UsuarioController::class, 'updateStatus'])->name('status');
});

/*
|--------------------------------------------------------------------------
| Rotas públicas
|--------------------------------------------------------------------------
|
| Utilizadas pelo cliente final para consultar a barbearia,
| serviços, profissionais, disponibilidade e realizar agendamentos.
|
*/

Route::prefix('publico/{estabelecimento}')
    ->name('publico.')
    ->group(function (): void {

        // Estabelecimento
        Route::get(
            '/',
            [EstabelecimentoPublicoController::class, 'show']
        )->name('estabelecimento.show');

        // Serviços
        Route::get(
            '/servicos',
            [EstabelecimentoPublicoController::class, 'servicos']
        )->name('servicos.index');

        // Profissionais que realizam determinado serviço
        Route::get(
            '/servicos/{servico}/profissionais',
            [EstabelecimentoPublicoController::class, 'profissionais']
        )->name('servicos.profissionais');

        // Disponibilidade
        Route::get(
            '/disponibilidade',
            [DisponibilidadeController::class, 'index']
        )->name('disponibilidade');

        // Agendamento
        Route::post(
            '/agendamentos',
            [AgendamentoPublicoController::class, 'store']
        )->name('agendamentos.store');
    });

/*
|--------------------------------------------------------------------------
| Serviços
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])
    ->prefix('servicos')
    ->name('servicos.')
    ->group(function (): void {

        Route::get('/novo', [ServicoController::class, 'create'])->name('create');
        Route::get('/{servico}/editar', [ServicoController::class, 'edit'])->name('edit');

        Route::get(
            '/',
            [ServicoController::class, 'index']
        )->name('index');

        Route::post(
            '/',
            [ServicoController::class, 'store']
        )->name('store');

        Route::get(
            '/{servico}',
            [ServicoController::class, 'show']
        )->name('show');

        Route::put(
            '/{servico}',
            [ServicoController::class, 'update']
        )->name('update');

        Route::patch(
            '/{servico}/status',
            [ServicoController::class, 'toggleStatus']
        )->name('status');

        Route::put(
            '/{servico}/profissionais',
            [ServicoController::class, 'syncProfissionais']
        )->name('profissionais');
    });

/*
|--------------------------------------------------------------------------
| Profissionais
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])
    ->prefix('profissionais')
    ->name('profissionais.')
    ->group(function (): void {

        Route::get('/novo', [ProfissionalController::class, 'create'])->name('create');
        Route::get('/{profissional}/editar', [ProfissionalController::class, 'edit'])->name('edit');

        Route::get(
            '/',
            [ProfissionalController::class, 'index']
        )->name('index');

        Route::post(
            '/',
            [ProfissionalController::class, 'store']
        )->name('store');

        Route::get(
            '/{profissional}',
            [ProfissionalController::class, 'show']
        )->name('show');

        Route::put(
            '/{profissional}',
            [ProfissionalController::class, 'update']
        )->name('update');

        Route::patch(
            '/{profissional}/status',
            [ProfissionalController::class, 'toggleStatus']
        )->name('status');

        /*
        |--------------------------------------------------------------------------
        | Horários do profissional
        |--------------------------------------------------------------------------
        */

        Route::middleware('can:is-admin')
            ->prefix('{profissional}/horarios')
            ->name('horarios.')
            ->group(function (): void {

                Route::get(
                    '/',
                    [HorarioProfissionalController::class, 'index']
                )->name('index');

                Route::post(
                    '/',
                    [HorarioProfissionalController::class, 'store']
                )->name('store');

                Route::delete(
                    '/{horario}',
                    [HorarioProfissionalController::class, 'destroy']
                )->name('destroy');
            });

        /*
        |--------------------------------------------------------------------------
        | Bloqueios do profissional
        |--------------------------------------------------------------------------
        */

        Route::middleware('can:is-admin')
            ->prefix('{profissional}/bloqueios')
            ->name('bloqueios.')
            ->group(function (): void {

                Route::get(
                    '/',
                    [BloqueioProfissionalController::class, 'index']
                )->name('index');

                Route::post(
                    '/',
                    [BloqueioProfissionalController::class, 'store']
                )->name('store');

                Route::delete(
                    '/{bloqueio}',
                    [BloqueioProfissionalController::class, 'destroy']
                )->name('destroy');
            });
    });

/*
|--------------------------------------------------------------------------
| Agendamentos
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])
    ->prefix('agendamentos')
    ->name('agendamentos.')
    ->group(function (): void {

        Route::get('/disponibilidade', [AgendamentoController::class, 'disponibilidade'])->name('disponibilidade');

        Route::get(
            '/',
            [AgendamentoController::class, 'index']
        )->name('index');

        Route::post(
            '/',
            [AgendamentoController::class, 'store']
        )->name('store');

        Route::get(
            '/{agendamento}',
            [AgendamentoController::class, 'show']
        )->name('show');

        Route::patch(
            '/{agendamento}/cancelar',
            [AgendamentoController::class, 'cancelar']
        )->name('cancelar');
    });
