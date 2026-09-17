<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgendamentoRequest;
use App\Models\Agendamento;
use App\Services\AgendamentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendamentoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agendamentos = Agendamento::query()
            ->where(
                'estabelecimento_id',
                $request->user()->estabelecimento_id
            )
            ->with([
                'profissional',
                'servico',
            ])
            ->orderBy('inicio')
            ->paginate(20);

        return response()->json($agendamentos);
    }

    public function show(
        Request $request,
        Agendamento $agendamento
    ): JsonResponse {
        $this->garantirMesmoEstabelecimento(
            $request,
            $agendamento
        );

        $agendamento->load([
            'profissional',
            'servico',
        ]);

        return response()->json($agendamento);
    }

    public function store(
        StoreAgendamentoRequest $request,
        AgendamentoService $service
    ): JsonResponse {
        $agendamento = $service->criar(
            $request->user()->estabelecimento,
            $request->validated()
        );

        $agendamento->load([
            'profissional',
            'servico',
        ]);

        return response()->json(
            $agendamento,
            201
        );
    }

    public function cancelar(
        Request $request,
        Agendamento $agendamento
    ): JsonResponse {
        $this->garantirMesmoEstabelecimento(
            $request,
            $agendamento
        );

        if ($agendamento->status === 'cancelado') {
            return response()->json([
                'message' => 'O agendamento já está cancelado.',
            ], 422);
        }

        $agendamento->update([
            'status' => 'cancelado',
            'cancelado_em' => now(),
        ]);

        return response()->json($agendamento);
    }

    private function garantirMesmoEstabelecimento(
        Request $request,
        Agendamento $agendamento
    ): void {
        if (
            $agendamento->estabelecimento_id
            !== $request->user()->estabelecimento_id
        ) {
            abort(404);
        }
    }
}