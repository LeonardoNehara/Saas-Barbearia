<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfissionalResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estabelecimento_id' => $this->estabelecimento_id,
            'user_id' => $this->user_id,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'email' => $this->email,
            'foto' => $this->foto,
            'descricao' => $this->descricao,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
