<?php

namespace App\Http\Requests;

use App\Models\Servico;
use Illuminate\Foundation\Http\FormRequest;

class StoreServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Servico::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:10000'],
            'duracao_minutos' => ['required', 'integer', 'min:5', 'max:480'],
            'preco' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'regex:/^\d{1,8}(\.\d{1,2})?$/D'],
        ];
    }
}
