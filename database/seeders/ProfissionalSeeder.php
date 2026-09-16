<?php

namespace Database\Seeders;

use App\Models\Estabelecimento;
use Illuminate\Database\Seeder;

class ProfissionalSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $estabelecimento = Estabelecimento::where('slug', 'barbearia-demo')->firstOrFail();
        $barbeiro = $estabelecimento->users()->where('email', 'barbeiro@barbearia-demo.test')->firstOrFail();

        foreach (['João', 'Carlos', 'Marcos'] as $index => $nome) {
            $estabelecimento->profissionais()->firstOrCreate(
                ['email' => 'profissional'.($index + 1).'@barbearia-demo.test'],
                ['nome' => $nome, 'user_id' => $index === 0 ? $barbeiro->id : null],
            );
        }
    }
}
