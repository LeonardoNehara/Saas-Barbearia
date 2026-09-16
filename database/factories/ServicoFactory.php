<?php

namespace Database\Factories;

use App\Models\Estabelecimento;
use App\Models\Servico;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Servico> */
class ServicoFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'estabelecimento_id' => Estabelecimento::factory(),
            'nome' => fake()->randomElement(['Corte Masculino', 'Barba', 'Corte + Barba']),
            'descricao' => fake()->sentence(),
            'duracao_minutos' => fake()->numberBetween(5, 480),
            'preco' => fake()->numberBetween(0, 500).'.'.str_pad((string) fake()->numberBetween(0, 99), 2, '0', STR_PAD_LEFT),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => false]);
    }
}
