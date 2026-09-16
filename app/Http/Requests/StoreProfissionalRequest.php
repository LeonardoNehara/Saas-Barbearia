<?php

namespace App\Http\Requests;

use App\Models\Profissional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfissionalRequest extends FormRequest
{
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
