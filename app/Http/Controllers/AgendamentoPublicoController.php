<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgendamentoPublicoRequest;
use App\Models\Estabelecimento;
use App\Services\AgendamentoService;
use Illuminate\Http\JsonResponse;

class AgendamentoPublicoController extends Controller
{
    public function store(
        StoreAgendamentoPublicoRequest $request,
        Estabelecimento $estabelecimento,
        AgendamentoService $service
    ): JsonResponse {
        if (!$estabelecimento->active) {
            abort(404);
        }

        $agendamento = $service->criar(
            $estabelecimento,
            $request->validated()
        );

        $agendamento->load([
            'profissional',
            'servico',
        ]);

        return response()->json([
            'message' => 'Agendamento realizado com sucesso.',
            'data' => $agendamento,
        ], 201);
    }
}