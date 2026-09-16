<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHorarioProfissionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dia_semana' => [
                'required',
                'integer',
                'between:0,6',
            ],

            'hora_inicio' => [
                'required',
                'date_format:H:i',
            ],

            'hora_fim' => [
                'required',
                'date_format:H:i',
                'after:hora_inicio',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'dia_semana.between' => 'O dia da semana deve estar entre 0 e 6.',
            'hora_inicio.date_format' => 'O horário inicial deve estar no formato HH:MM.',
            'hora_fim.date_format' => 'O horário final deve estar no formato HH:MM.',
            'hora_fim.after' => 'O horário final deve ser posterior ao horário inicial.',
        ];
    }
}