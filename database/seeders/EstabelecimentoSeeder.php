<?php

namespace Database\Seeders;

use App\Models\Estabelecimento;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstabelecimentoSeeder extends Seeder
{
    /** Seed demo credentials exclusively for local development and testing. */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        DB::transaction(function (): void {
            $estabelecimento = Estabelecimento::firstOrCreate(
                ['slug' => 'barbearia-demo'],
                ['nome' => 'Barbearia Demo'],
            );

            $admin = $estabelecimento->users()->firstOrCreate(
                ['email' => 'admin@barbearia-demo.test'],
                [
                    'name' => 'Admin Demo',
                    'password' => 'demo-local-only',
                ],
            );
            $admin->forceFill(['role' => 'admin', 'active' => true])->save();

            $barbeiro = $estabelecimento->users()->firstOrCreate(
                ['email' => 'barbeiro@barbearia-demo.test'],
                [
                    'name' => 'Barbeiro Demo',
                    'password' => 'demo-local-only',
                ],
            );
            $barbeiro->forceFill(['role' => 'barbeiro', 'active' => true])->save();
        });
    }
}
