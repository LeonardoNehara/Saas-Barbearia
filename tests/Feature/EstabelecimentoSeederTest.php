<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EstabelecimentoSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_seed_creates_an_associated_user_and_can_be_repeated(): void
    {
        $this->seed(DatabaseSeeder::class);
        $originalPassword = User::where('email', 'admin@barbearia-demo.test')->sole()->password;

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('estabelecimentos', 1);
        $this->assertDatabaseCount('users', 2);
        $user = User::where('email', 'admin@barbearia-demo.test')->sole();
        $this->assertSame('Admin Demo', $user->name);
        $this->assertSame('admin@barbearia-demo.test', $user->email);
        $this->assertSame('Barbearia Demo', $user->estabelecimento->nome);
        $this->assertSame('barbearia-demo', $user->estabelecimento->slug);
        $this->assertTrue(Hash::check('demo-local-only', $user->password));
        $this->assertSame($originalPassword, $user->password);
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->active);

        $barbeiro = User::where('email', 'barbeiro@barbearia-demo.test')->sole();
        $this->assertSame('Barbeiro Demo', $barbeiro->name);
        $this->assertTrue($barbeiro->isBarbeiro());
        $this->assertTrue($barbeiro->active);
        $this->assertSame($user->estabelecimento_id, $barbeiro->estabelecimento_id);
        $this->assertTrue(Hash::check('demo-local-only', $barbeiro->password));
    }

    public function test_demo_seed_does_not_create_data_in_production(): void
    {
        $this->app->instance('env', 'production');

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true, '--no-interaction' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('estabelecimentos', 0);
        $this->assertDatabaseCount('users', 0);
    }
}
