<?php

namespace Database\Seeders;

use App\Models\Estabelecimento;
use Illuminate\Database\Seeder;

class ServicoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $estabelecimento = Estabelecimento::where('slug', 'barbearia-demo')->firstOrFail();

        foreach ([
            ['Corte Masculino', 30, '40.00', [1, 2, 3]],
            ['Barba', 20, '30.00', [1, 3]],
            ['Corte + Barba', 50, '65.00', [3]],
        ] as [$nome, $duracao, $preco, $profissionais]) {
            $servico = $estabelecimento->servicos()->firstOrCreate(
                ['nome' => $nome], ['duracao_minutos' => $duracao, 'preco' => $preco],
            );
            $emails = array_map(fn (int $index): string => 'profissional'.$index.'@barbearia-demo.test', $profissionais);
            $servico->profissionais()->syncWithoutDetaching(
                $estabelecimento->profissionais()->whereIn('email', $emails)->pluck('id'),
            );
        }
    }
}
