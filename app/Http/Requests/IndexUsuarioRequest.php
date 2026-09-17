<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUsuarioRequest extends FormRequest
{
    protected function getRedirectUrl(): string
    {
        return route('usuarios.index');
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
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
            'role' => ['nullable', Rule::in(['admin', 'barbeiro'])],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'search.string' => 'Informe um texto para pesquisar.',
            'search.max' => 'A pesquisa deve ter no máximo 255 caracteres.',
            'role.in' => 'Selecione um perfil válido.',
            'active.boolean' => 'Selecione uma situação válida.',
            'per_page.integer' => 'Selecione uma quantidade válida por página.',
            'per_page.in' => 'Exiba 10, 15, 25 ou 50 registros por página.',
            'page.integer' => 'Selecione uma página válida.',
            'page.min' => 'Selecione uma página válida.',
        ];
    }
}
