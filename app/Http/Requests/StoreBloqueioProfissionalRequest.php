<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBloqueioProfissionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inicio' => ['required', 'date'],
            'fim' => ['required', 'date', 'after:inicio'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'inicio.required' => 'Informe o início do bloqueio.',
            'inicio.date' => 'O início deve ser uma data válida.',
            'fim.required' => 'Informe o fim do bloqueio.',
            'fim.date' => 'O fim deve ser uma data válida.',
            'fim.after' => 'O fim do bloqueio deve ser posterior ao início.',
            'motivo.max' => 'O motivo deve possuir no máximo 255 caracteres.',
        ];
    }
}