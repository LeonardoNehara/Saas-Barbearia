<?php

namespace Database\Factories;

use App\Models\Estabelecimento;
use App\Models\Profissional;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Profissional> */
class ProfissionalFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'estabelecimento_id' => Estabelecimento::factory(),
            'user_id' => null,
            'nome' => fake()->name(),
            'telefone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'descricao' => fake()->sentence(),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => false]);
    }
}
