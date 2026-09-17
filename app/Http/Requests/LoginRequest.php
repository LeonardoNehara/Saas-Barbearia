<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.required' => 'Informe seu email.',
            'email.email' => 'Informe um email válido.',
            'email.max' => 'O email deve ter no máximo 255 caracteres.',
            'password.required' => 'Informe sua senha.',
        ];
    }

    public function authenticate(): void
    {
        $key = Str::lower($this->string('email')->toString()).'|'.$this->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Muitas tentativas. Tente novamente em '.RateLimiter::availableIn($key).' segundos.',
            ]);
        }

        if (! Auth::guard('web')->attempt([...$this->validated(), 'active' => true])) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Não foi possível entrar. Verifique suas credenciais ou fale com o responsável pela barbearia.',
            ]);
        }

        RateLimiter::clear($key);
    }
}
