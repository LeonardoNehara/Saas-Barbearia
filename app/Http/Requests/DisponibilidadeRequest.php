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
}