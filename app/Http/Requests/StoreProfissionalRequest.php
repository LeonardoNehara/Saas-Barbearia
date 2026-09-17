<?php

namespace App\Http\Requests;

use App\Models\Profissional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfissionalRequest extends FormRequest
{
    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do profissional.',
            'string' => 'O campo :attribute deve ser um texto.',
            'max' => 'O campo :attribute deve ter no máximo :max caracteres.',
            'email.email' => 'Informe um email válido.',
            'user_id.integer' => 'Selecione um usuário válido.',
            'user_id.exists' => 'Selecione um usuário do seu estabelecimento.',
            'user_id.unique' => 'Este usuário já está vinculado a outro profissional.',
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', Profissional::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $uniqueUser = Rule::unique('profissionais', 'user_id');

        if ($this->route('profissional') instanceof Profissional) {
            $uniqueUser->ignore($this->route('profissional'));
        }

        return [
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'foto' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:10000'],
            'user_id' => ['bail', 'nullable', 'integer',
                Rule::exists('users', 'id')->where('estabelecimento_id', $this->user()->estabelecimento_id),
                $uniqueUser,
            ],
        ];
    }
}
