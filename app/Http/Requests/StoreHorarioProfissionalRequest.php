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
            'intervalo_inicio' => ['nullable', 'required_with:intervalo_fim', 'date_format:H:i', 'after_or_equal:hora_inicio', 'before:hora_fim'],
            'intervalo_fim' => ['nullable', 'required_with:intervalo_inicio', 'date_format:H:i', 'after:intervalo_inicio', 'before_or_equal:hora_fim'],
        ];
    }

    public function messages(): array
    {
        return [
            'intervalo_inicio.required_with' => 'Informe o início do intervalo.',
            'intervalo_fim.required_with' => 'Informe o fim do intervalo.',
            'intervalo_inicio.date_format' => 'O início do intervalo deve estar no formato HH:MM.',
            'intervalo_fim.date_format' => 'O fim do intervalo deve estar no formato HH:MM.',
            'intervalo_inicio.after_or_equal' => 'O intervalo deve começar dentro do horário de atendimento.',
            'intervalo_inicio.before' => 'O intervalo deve começar antes do fim do atendimento.',
            'intervalo_fim.after' => 'O fim do intervalo deve ser posterior ao início.',
            'intervalo_fim.before_or_equal' => 'O intervalo deve terminar dentro do horário de atendimento.',
            'dia_semana.between' => 'O dia da semana deve estar entre 0 e 6.',
            'hora_inicio.date_format' => 'O horário inicial deve estar no formato HH:MM.',
            'hora_fim.date_format' => 'O horário final deve estar no formato HH:MM.',
            'hora_fim.after' => 'O horário final deve ser posterior ao horário inicial.',
        ];
    }
}
