<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisponibilidadeRequest;
use App\Models\Estabelecimento;
use App\Services\DisponibilidadeService;
use Illuminate\Http\JsonResponse;

class DisponibilidadeController extends Controller
{
    public function index(
        DisponibilidadeRequest $request,
        Estabelecimento $estabelecimento,
        DisponibilidadeService $service
    ): JsonResponse {
        if (!$estabelecimento->active) {
            abort(404);
        }

        $dados = $request->validated();

        $slots = $service->buscar(
            $estabelecimento,
            $dados['profissional_id'],
            $dados['servico_id'],
            $dados['data']
        );

        return response()->json([
            'data' => $slots,
        ]);
    }
}