<?php

namespace App\Http\Requests;

use App\Models\Servico;
use Illuminate\Foundation\Http\FormRequest;

class StoreServicoRequest extends FormRequest
{
    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do serviço.',
            'nome.string' => 'Informe um nome válido.',
            'nome.max' => 'O nome deve ter no máximo 255 caracteres.',
            'descricao.string' => 'Informe uma descrição válida.',
            'descricao.max' => 'A descrição deve ter no máximo 10.000 caracteres.',
            'duracao_minutos.required' => 'Informe a duração do serviço.',
            'duracao_minutos.integer' => 'Informe a duração em minutos inteiros.',
            'duracao_minutos.min' => 'A duração mínima é de 5 minutos.',
            'duracao_minutos.max' => 'A duração máxima é de 480 minutos.',
            'preco.required' => 'Informe o preço do serviço.',
            'preco.numeric' => 'Informe um preço válido.',
            'preco.min' => 'O preço não pode ser negativo.',
            'preco.max' => 'O preço máximo é R$ 99.999.999,99.',
            'preco.regex' => 'Informe o preço com até duas casas decimais.',
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', Servico::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:10000'],
            'duracao_minutos' => ['required', 'integer', 'min:5', 'max:480'],
            'preco' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'regex:/^\d{1,8}(\.\d{1,2})?$/D'],
        ];
    }
}
