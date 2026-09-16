<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estabelecimento_id' => $this->estabelecimento_id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'duracao_minutos' => $this->duracao_minutos,
            'preco' => $this->preco,
            'active' => $this->active,
            'profissionais' => ProfissionalResource::collection($this->whenLoaded('profissionais')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
