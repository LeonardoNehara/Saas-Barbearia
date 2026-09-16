<?php

namespace Database\Factories;

use App\Models\Estabelecimento;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** @extends Factory<Estabelecimento> */
class EstabelecimentoFactory extends Factory
{
    /** @return array{nome: string, slug: string, cnpj: string, telefone: string, email: string, active: bool, trial_ends_at: Carbon} */
    public function definition(): array
    {
        $nome = 'Barbearia '.fake('pt_BR')->company();

        return [
            'nome' => $nome,
            'slug' => Str::slug($nome).'-'.fake()->unique()->uuid(),
            'cnpj' => fake('pt_BR')->cnpj(false),
            'telefone' => fake('pt_BR')->numerify('119########'),
            'email' => fake()->safeEmail(),
            'active' => true,
            'trial_ends_at' => now()->addDays(14),
        ];
    }
}
