<?php

namespace App\Http\Requests;

use App\Models\Profissional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProfissionalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Profissional::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'servico_id' => ['nullable', 'integer', Rule::exists('servicos', 'id')->where('estabelecimento_id', $this->user()->estabelecimento_id)],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('profissionais.index');
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'search.string' => 'Informe um texto para pesquisar.',
            'search.max' => 'Pesquise com até 255 caracteres.',
            'active.boolean' => 'Selecione um status válido.',
            'servico_id.integer' => 'Selecione um serviço válido.',
            'servico_id.exists' => 'Selecione um serviço do seu estabelecimento.',
            'per_page.integer' => 'Selecione uma quantidade válida.',
            'per_page.in' => 'Exiba 10, 15, 25 ou 50 registros por página.',
            'page.integer' => 'Selecione uma página válida.',
            'page.min' => 'Selecione uma página válida.',
        ];
    }
}
