<?php

namespace Database\Factories;

use App\Models\Estabelecimento;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Agendamento>
 */
class AgendamentoFactory extends Factory
{
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('+1 day', '+30 days');

        return [
            'estabelecimento_id' => Estabelecimento::factory(),
            'profissional_id' => Profissional::factory(),
            'servico_id' => Servico::factory(),

            'cliente_nome' => fake()->name(),
            'cliente_telefone' => '44999999999',

            'inicio' => $inicio,
            'fim' => (clone $inicio)->modify('+30 minutes'),

            'status' => 'agendado',
            'observacoes' => null,
            'cancelado_em' => null,
        ];
    }
}