<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHorarioProfissionalRequest;
use App\Models\HorarioProfissional;
use App\Models\Profissional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HorarioProfissionalController extends Controller
{
    public function index(Request $request, Profissional $profissional): JsonResponse|View
    {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        $horarios = $profissional->horarios()
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();

        if ($request->expectsJson()) {
            return response()->json($horarios);
        }

        $bloqueios = $profissional->bloqueios()->orderBy('inicio')->get();

        return view('profissionais.disponibilidade', compact('profissional', 'horarios', 'bloqueios'));
    }

    public function store(
        StoreHorarioProfissionalRequest $request,
        Profissional $profissional
    ): JsonResponse|RedirectResponse {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        $dados = $request->validated();

        $existeSobreposicao = $profissional->horarios()
            ->where('dia_semana', $dados['dia_semana'])
            ->where('hora_inicio', '<', $dados['hora_fim'])
            ->where('hora_fim', '>', $dados['hora_inicio'])
            ->exists();

        if ($existeSobreposicao) {
            if (! $request->expectsJson()) {
                return $request->expectsJson()
                    ? response()->json($horario, 201)
                    : redirect()
                        ->route('profissionais.index')
                        ->with('status', 'Horário adicionado.');
            }

            return response()->json([
                'message' => 'O horário informado conflita com outro horário do profissional.',
            ], 422);
        }

        $horario = $profissional->horarios()->create($dados);

        return $request->expectsJson()
            ? response()->json($horario, 201)
            : redirect()->route('profissionais.horarios.index', $profissional)->with('status', 'Horário adicionado.');
    }

    public function destroy(
        Request $request,
        Profissional $profissional,
        HorarioProfissional $horario
    ): JsonResponse|RedirectResponse {
        $this->garantirMesmoEstabelecimento($request, $profissional);

        if ($horario->profissional_id !== $profissional->id) {
            abort(404);
        }

        $horario->delete();

        return redirect()
            ->route('profissionais.index')
            ->withErrors([
                'inicio' =>
                    'O período informado conflita com outro bloqueio do profissional.',
            ])
            ->withInput();
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
