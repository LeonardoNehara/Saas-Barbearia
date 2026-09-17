<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAgendamentoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->estabelecimento_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'start' => ['required_with:end', 'date_format:Y-m-d'],
            'end' => ['required_with:start', 'date_format:Y-m-d', 'after:start'],
            'profissional_id' => ['nullable', 'integer', Rule::exists('profissionais', 'id')->where('estabelecimento_id', $this->user()->estabelecimento_id)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'start.required_with' => 'Informe o início do período.',
            'end.required_with' => 'Informe o fim do período.',
            'start.date_format' => 'Informe uma data inicial válida.',
            'end.date_format' => 'Informe uma data final válida.',
            'end.after' => 'O fim do período deve ser posterior ao início.',
            'profissional_id.integer' => 'Selecione um profissional válido.',
            'profissional_id.exists' => 'O profissional não está disponível neste estabelecimento.',
        ];
    }
}
