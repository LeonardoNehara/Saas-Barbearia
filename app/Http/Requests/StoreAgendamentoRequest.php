<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profissional_id' => ['required', 'integer', 'exists:profissionais,id'],
            'servico_id' => ['required', 'integer', 'exists:servicos,id'],

            'cliente_nome' => ['required', 'string', 'max:150'],
            'cliente_telefone' => ['required', 'string', 'max:20'],

            'inicio' => ['required', 'date'],

            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Informe :attribute.',
            'integer' => 'Selecione um valor válido para :attribute.',
            'exists' => 'O valor selecionado para :attribute não está disponível.',
            'string' => 'Informe um texto válido para :attribute.',
            'max' => 'O campo :attribute deve ter no máximo :max caracteres.',
            'date' => 'Selecione uma data e um horário válidos.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'profissional_id' => 'profissional', 'servico_id' => 'serviço',
            'cliente_nome' => 'nome do cliente', 'cliente_telefone' => 'telefone',
            'inicio' => 'horário', 'observacoes' => 'observações',
        ];
    }
}
