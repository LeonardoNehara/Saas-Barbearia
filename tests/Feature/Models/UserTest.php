<?php

namespace Tests\Feature\Models;

use App\Models\Estabelecimento;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_database_defaults_to_active_barbeiro(): void
    {
        $estabelecimento = Estabelecimento::factory()->create();

        $user = $estabelecimento->users()->create([
            'name' => 'Usuario',
            'email' => 'usuario@example.test',
            'password' => 'test-only-password',
        ])->refresh();

        $this->assertSame('barbeiro', $user->role);
        $this->assertTrue($user->active);
        $this->assertTrue($user->isBarbeiro());
        $this->assertFalse($user->isAdmin());
    }

    public function test_admin_is_recognized_and_can_be_inactive(): void
    {
        $user = User::factory()->admin()->inactive()->create()->refresh();

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isBarbeiro());
        $this->assertFalse($user->active);
    }

    public function test_unknown_role_is_rejected_by_database(): void
    {
        $this->expectException(QueryException::class);

        User::factory()->create(['role' => 'owner']);
    }

    public function test_role_and_active_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->barbeiro()->inactive()->create();

        $user->fill(['role' => 'admin', 'active' => true])->save();

        $this->assertSame('barbeiro', $user->fresh()->role);
        $this->assertFalse($user->fresh()->active);
    }
}
