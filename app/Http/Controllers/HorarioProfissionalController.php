<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHorarioProfissionalRequest;
use App\Models\HorarioProfissional;
use App\Models\Profissional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorarioProfissionalController extends Controller
{
    public function index(Request $request, Profissional $profissional): JsonResponse
    {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        $horarios = $profissional->horarios()
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();

        return response()->json($horarios);
    }

    public function store(
        StoreHorarioProfissionalRequest $request,
        Profissional $profissional
    ): JsonResponse {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        $dados = $request->validated();

        $existeSobreposicao = $profissional->horarios()
            ->where('dia_semana', $dados['dia_semana'])
            ->where('hora_inicio', '<', $dados['hora_fim'])
            ->where('hora_fim', '>', $dados['hora_inicio'])
            ->exists();

        if ($existeSobreposicao) {
            return response()->json([
                'message' => 'O horário informado conflita com outro horário do profissional.',
            ], 422);
        }

        $horario = $profissional->horarios()->create($dados);

        return response()->json($horario, 201);
    }

    public function destroy(
        Request $request,
        Profissional $profissional,
        HorarioProfissional $horario
    ): JsonResponse {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        if ($horario->profissional_id !== $profissional->id) {
            abort(404);
        }

        $horario->delete();

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