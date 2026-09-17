<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisponibilidadeRequest extends FormRequest
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
            'data' => [
                'required',
                'date_format:Y-m-d',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'profissional_id.required' => 'Selecione o profissional.',
            'profissional_id.integer' => 'Selecione um profissional válido.',
            'servico_id.required' => 'Selecione o serviço.',
            'servico_id.integer' => 'Selecione um serviço válido.',
            'data.required' => 'Selecione a data.',
            'data.date_format' => 'Selecione uma data válida.',
        ];
    }
}
