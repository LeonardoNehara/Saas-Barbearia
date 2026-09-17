<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends StoreUsuarioRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('usuario'));

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('usuario'))];
        $rules['password'][0] = 'nullable';

        if ($this->user()->is($this->route('usuario'))) {
            $rules['role'] = ['required', Rule::in(['admin'])];
        }

        return $rules;
    }
}
