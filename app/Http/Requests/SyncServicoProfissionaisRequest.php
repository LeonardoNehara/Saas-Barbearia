<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SyncServicoProfissionaisRequest extends FormRequest
{
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
