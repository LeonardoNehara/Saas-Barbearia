<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgendamentoPublicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profissional_id' => [
                'required',
                'integer',
            ],
            'servico_id' => [
                'required',
                'integer',
            ],
            'cliente_nome' => [
                'required',
                'string',
                'max:150',
            ],
            'cliente_telefone' => [
                'required',
                'string',
                'max:20',
            ],
            'inicio' => [
                'required',
                'date',
            ],
            'observacoes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}