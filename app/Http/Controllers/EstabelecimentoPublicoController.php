<?php

namespace App\Http\Controllers;

use App\Models\Estabelecimento;
use Illuminate\Http\JsonResponse;

class EstabelecimentoPublicoController extends Controller
{
    public function show(
        Estabelecimento $estabelecimento
    ): JsonResponse {
        if (! $estabelecimento->active) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'nome' => $estabelecimento->nome,
                'slug' => $estabelecimento->slug,
                'telefone' => $estabelecimento->telefone,
                'logo' => $estabelecimento->logo,
                'timezone' => $estabelecimento->timezone,
            ],
        ]);
    }

    public function servicos(
        Estabelecimento $estabelecimento
    ): JsonResponse {
        if (! $estabelecimento->active) {
            abort(404);
        }

        $servicos = $estabelecimento
            ->servicos()
            ->where('active', true)
            ->orderBy('nome')
            ->get([
                'id',
                'nome',
                'descricao',
                'duracao_minutos',
                'preco',
            ]);

        return response()->json([
            'data' => $servicos,
        ]);
    }

    public function profissionais(
        Estabelecimento $estabelecimento,
        int $servico
    ): JsonResponse {
        if (! $estabelecimento->active) {
            abort(404);
        }

        $servico = $estabelecimento
            ->servicos()
            ->where('id', $servico)
            ->where('active', true)
            ->firstOrFail();

        $profissionais = $servico
            ->profissionais()
            ->where('profissionais.estabelecimento_id', $estabelecimento->id)
            ->where('profissionais.active', true)
            ->orderBy('profissionais.nome')
            ->get([
                'profissionais.id',
                'profissionais.nome',
                'profissionais.foto',
                'profissionais.descricao',
            ]);

        return response()->json([
            'data' => $profissionais,
        ]);
    }
}