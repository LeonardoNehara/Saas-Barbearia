<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloqueioProfissionalRequest;
use App\Models\BloqueioProfissional;
use App\Models\Profissional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BloqueioProfissionalController extends Controller
{
    public function index(
        Request $request,
        Profissional $profissional
    ): JsonResponse|RedirectResponse {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        if (! $request->expectsJson()) {
            return redirect()->to(route('profissionais.horarios.index', $profissional).'#bloqueios');
        }

        $bloqueios = $profissional->bloqueios()
            ->orderBy('inicio')
            ->get();

        return response()->json($bloqueios);
    }

    public function store(
        StoreBloqueioProfissionalRequest $request,
        Profissional $profissional
    ): JsonResponse|RedirectResponse {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        $dados = $request->validated();

        $existeSobreposicao = $profissional->bloqueios()
            ->where('inicio', '<', $dados['fim'])
            ->where('fim', '>', $dados['inicio'])
            ->exists();

        if ($existeSobreposicao) {
            if (! $request->expectsJson()) {
                return redirect()->to(route('profissionais.horarios.index', $profissional).'#bloqueios')->withErrors(['inicio' => 'O período informado conflita com outro bloqueio do profissional.'])->withInput();
            }

            return response()->json([
                'message' => 'O período informado conflita com outro bloqueio do profissional.',
            ], 422);
        }

        $bloqueio = $profissional->bloqueios()->create($dados);

        return $request->expectsJson()
            ? response()->json($bloqueio, 201)
            : redirect()->to(route('profissionais.horarios.index', $profissional).'#bloqueios')->with('status', 'Bloqueio adicionado.');
    }

    public function destroy(
        Request $request,
        Profissional $profissional,
        BloqueioProfissional $bloqueio
    ): JsonResponse|RedirectResponse {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        if ($bloqueio->profissional_id !== $profissional->id) {
            abort(404);
        }

        $bloqueio->delete();

        return $request->expectsJson()
            ? response()->json(null, 204)
            : redirect()->to(route('profissionais.horarios.index', $profissional).'#bloqueios')->with('status', 'Bloqueio removido.');
    }

    private function garantirMesmoEstabelecimento(
        Request $request,
        Profissional $profissional
    ): void {
        if (
            $profissional->estabelecimento_id !==
            $request->user()->estabelecimento_id
        ) {
            abort(404);
        }
    }
}
