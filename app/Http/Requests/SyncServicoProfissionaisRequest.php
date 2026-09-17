<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SyncServicoProfissionaisRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->expectsJson() && $this->input('profissionais_present') === '1' && ! $this->has('profissionais')) {
            $this->merge(['profissionais' => []]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'profissionais.present' => 'Informe os profissionais do serviço.',
            'profissionais.array' => 'Selecione uma lista válida de profissionais.',
            'profissionais.list' => 'Selecione uma lista válida de profissionais.',
            'profissionais.*.required' => 'Selecione um profissional válido.',
            'profissionais.*.integer' => 'Selecione um profissional válido.',
            'profissionais.*.distinct' => 'Não repita um profissional.',
            'profissionais.*.exists' => 'Selecione apenas profissionais do seu estabelecimento.',
        ];
    }

    public function authorize(): bool
    {
        Gate::authorize('syncProfissionais', $this->route('servico'));

        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'profissionais' => ['present', 'array', 'list'],
            'profissionais.*' => ['bail', 'required', 'integer', 'distinct',
                Rule::exists('profissionais', 'id')->where('estabelecimento_id', $this->user()->estabelecimento_id),
            ],
        ];
    }
}
