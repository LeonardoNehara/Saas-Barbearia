<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloqueioProfissionalRequest;
use App\Models\BloqueioProfissional;
use App\Models\Profissional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BloqueioProfissionalController extends Controller
{
    public function index(
        Request $request,
        Profissional $profissional
    ): JsonResponse {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        $bloqueios = $profissional->bloqueios()
            ->orderBy('inicio')
            ->get();

        return response()->json($bloqueios);
    }

    public function store(
        StoreBloqueioProfissionalRequest $request,
        Profissional $profissional
    ): JsonResponse {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        $dados = $request->validated();

        $existeSobreposicao = $profissional->bloqueios()
            ->where('inicio', '<', $dados['fim'])
            ->where('fim', '>', $dados['inicio'])
            ->exists();

        if ($existeSobreposicao) {
            return response()->json([
                'message' => 'O período informado conflita com outro bloqueio do profissional.',
            ], 422);
        }

        $bloqueio = $profissional->bloqueios()->create($dados);

        return response()->json($bloqueio, 201);
    }

    public function destroy(
        Request $request,
        Profissional $profissional,
        BloqueioProfissional $bloqueio
    ): JsonResponse {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        if ($bloqueio->profissional_id !== $profissional->id) {
            abort(404);
        }

        $bloqueio->delete();

        return response()->json(null, 204);
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