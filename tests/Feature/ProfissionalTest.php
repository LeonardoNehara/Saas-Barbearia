<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Profissional;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProfissionalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_paginates_only_the_authenticated_tenant(): void
    {
        $admin = User::factory()->admin()->create();
        Profissional::factory()->for($admin->estabelecimento)->count(16)->create();
        $other = Profissional::factory()->create();

        $this->actingAs($admin)->getJson(route('profissionais.index'))
            ->assertOk()->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 16)->assertJsonPath('meta.per_page', 15)
            ->assertJsonMissing(['id' => $other->id, 'nome' => $other->nome]);
    }

    public function test_admin_can_show_own_profissional(): void
    {
        $admin = User::factory()->admin()->create();
        $profissional = Profissional::factory()->for($admin->estabelecimento)->create();

        $this->actingAs($admin)->getJson(route('profissionais.show', $profissional))
            ->assertOk()->assertJsonPath('data.id', $profissional->id);
    }

    #[TestWith(['GET', 'show'])]
    #[TestWith(['PUT', 'update'])]
    #[TestWith(['PATCH', 'status'])]
    public function test_other_tenant_returns_404_without_changes(string $method, string $action): void
    {
        $admin = User::factory()->admin()->create();
        $profissional = Profissional::factory()->create();
        $original = $profissional->refresh()->getAttributes();

        $this->actingAs($admin)->json($method, route('profissionais.'.$action, $profissional), ['nome' => 'Invadido'])
            ->assertNotFound()->assertJsonMissing(['nome' => $profissional->nome]);

        $this->assertSame($original, $profissional->fresh()->getAttributes());
    }

    public function test_create_uses_authenticated_tenant_and_ignores_protected_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $other = Estabelecimento::factory()->create();

        $response = $this->actingAs($admin)->postJson(route('profissionais.store'), [
            'nome' => 'Leonardo', 'telefone' => '11999999999',
            'email' => 'leo@example.test', 'foto' => 'profissionais/leo.jpg',
            'descricao' => 'Barbeiro', 'user_id' => null,
            'estabelecimento_id' => $other->id, 'active' => false,
        ]);

        $response->assertCreated()->assertJsonPath('data.estabelecimento_id', $admin->estabelecimento_id)
            ->assertJsonPath('data.active', true)->assertJsonPath('data.user_id', null);
        $this->assertDatabaseHas('profissionais', [
            'id' => $response->json('data.id'), 'nome' => 'Leonardo',
            'estabelecimento_id' => $admin->estabelecimento_id, 'user_id' => null,
            'telefone' => '11999999999', 'email' => 'leo@example.test',
            'foto' => 'profissionais/leo.jpg', 'descricao' => 'Barbeiro', 'active' => true,
        ]);
    }

    public function test_create_accepts_user_of_same_tenant(): void
    {
        $admin = User::factory()->admin()->create();
        $barbeiro = User::factory()->for($admin->estabelecimento)->create();

        $this->actingAs($admin)->postJson(route('profissionais.store'), [
            'nome' => 'Leonardo', 'user_id' => $barbeiro->id,
        ])->assertCreated()->assertJsonPath('data.user_id', $barbeiro->id);

        $this->assertDatabaseHas('profissionais', ['nome' => 'Leonardo', 'user_id' => $barbeiro->id]);
    }

    public function test_update_edits_fields_without_changing_tenant_or_status(): void
    {
        $admin = User::factory()->admin()->create();
        $barbeiro = User::factory()->for($admin->estabelecimento)->create();
        $profissional = Profissional::factory()->for($admin->estabelecimento)->create();
        $other = Estabelecimento::factory()->create();

        $this->actingAs($admin)->putJson(route('profissionais.update', $profissional), [
            'nome' => 'Atualizado', 'telefone' => null, 'email' => 'novo@example.test',
            'foto' => 'foto.jpg', 'descricao' => 'Nova descrição', 'user_id' => $barbeiro->id,
            'estabelecimento_id' => $other->id, 'active' => false,
        ])->assertOk()->assertJsonPath('data.nome', 'Atualizado');

        $this->assertDatabaseHas('profissionais', [
            'id' => $profissional->id, 'nome' => 'Atualizado', 'telefone' => null,
            'email' => 'novo@example.test', 'foto' => 'foto.jpg', 'descricao' => 'Nova descrição',
            'user_id' => $barbeiro->id, 'estabelecimento_id' => $admin->estabelecimento_id, 'active' => true,
        ]);
    }

    public function test_update_allows_keeping_the_same_user(): void
    {
        $admin = User::factory()->admin()->create();
        $profissional = Profissional::factory()->for($admin->estabelecimento)->for($admin)->create();

        $this->actingAs($admin)->putJson(route('profissionais.update', $profissional), [
            'nome' => 'Novo nome', 'user_id' => $admin->id,
        ])->assertOk();

        $this->assertDatabaseHas('profissionais', ['id' => $profissional->id, 'nome' => 'Novo nome', 'user_id' => $admin->id]);
    }

    public function test_update_can_unlink_user(): void
    {
        $admin = User::factory()->admin()->create();
        $profissional = Profissional::factory()->for($admin->estabelecimento)->for($admin)->create();

        $this->actingAs($admin)->putJson(route('profissionais.update', $profissional), [
            'nome' => $profissional->nome, 'user_id' => null,
        ])->assertOk()->assertJsonPath('data.user_id', null);

        $this->assertDatabaseHas('profissionais', ['id' => $profissional->id, 'user_id' => null]);
    }

    #[TestWith([true, false])]
    #[TestWith([false, true])]
    public function test_admin_toggles_status_without_deleting(bool $initial, bool $expected): void
    {
        $admin = User::factory()->admin()->create();
        $profissional = Profissional::factory()->for($admin->estabelecimento)->create(['active' => $initial]);

        $this->actingAs($admin)->patchJson(route('profissionais.status', $profissional))
            ->assertOk()->assertJsonPath('data.active', $expected);

        $this->assertDatabaseHas('profissionais', ['id' => $profissional->id, 'active' => $expected]);
    }

    #[TestWith(['GET', 'index'])]
    #[TestWith(['POST', 'store'])]
    #[TestWith(['GET', 'show'])]
    #[TestWith(['PUT', 'update'])]
    #[TestWith(['PATCH', 'status'])]
    public function test_guests_receive_401(string $method, string $action): void
    {
        $profissional = Profissional::factory()->create();

        $this->json($method, route('profissionais.'.$action, $profissional), ['nome' => 'Invadido'])
            ->assertUnauthorized();

        $this->assertDatabaseCount('profissionais', 1);
        $this->assertDatabaseHas('profissionais', ['id' => $profissional->id, 'nome' => $profissional->nome, 'active' => true]);
    }

    #[TestWith(['GET', 'index', 'barbeiro', true])]
    #[TestWith(['POST', 'store', 'barbeiro', true])]
    #[TestWith(['GET', 'show', 'barbeiro', true])]
    #[TestWith(['PUT', 'update', 'barbeiro', true])]
    #[TestWith(['PATCH', 'status', 'barbeiro', true])]
    #[TestWith(['GET', 'index', 'admin', false])]
    #[TestWith(['POST', 'store', 'admin', false])]
    #[TestWith(['GET', 'show', 'admin', false])]
    #[TestWith(['PUT', 'update', 'admin', false])]
    #[TestWith(['PATCH', 'status', 'admin', false])]
    public function test_unprivileged_users_receive_403(string $method, string $action, string $role, bool $active): void
    {
        $user = User::factory()->create(['role' => $role, 'active' => $active]);
        $profissional = Profissional::factory()->for($user->estabelecimento)->create();

        $this->actingAs($user)->json($method, route('profissionais.'.$action, $profissional), ['nome' => 'Invadido'])
            ->assertForbidden();

        $this->assertDatabaseCount('profissionais', 1);
        $this->assertDatabaseHas('profissionais', ['id' => $profissional->id, 'nome' => $profissional->nome, 'active' => true]);
    }

    #[TestWith(['POST'])]
    #[TestWith(['PUT'])]
    public function test_cross_tenant_user_is_rejected(string $method): void
    {
        $admin = User::factory()->admin()->create();
        $otherUser = User::factory()->create();
        $profissional = Profissional::factory()->for($admin->estabelecimento)->create();
        $url = $method === 'POST' ? route('profissionais.store') : route('profissionais.update', $profissional);

        $this->actingAs($admin)->json($method, $url, ['nome' => 'Invadido', 'user_id' => $otherUser->id])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');

        $this->assertDatabaseCount('profissionais', 1);
        $this->assertDatabaseHas('profissionais', ['id' => $profissional->id, 'nome' => $profissional->nome, 'user_id' => null]);
    }

    #[TestWith(['POST'])]
    #[TestWith(['PUT'])]
    public function test_user_cannot_be_linked_twice(string $method): void
    {
        $admin = User::factory()->admin()->create();
        Profissional::factory()->for($admin->estabelecimento)->for($admin)->create();
        $profissional = Profissional::factory()->for($admin->estabelecimento)->create();
        $url = $method === 'POST' ? route('profissionais.store') : route('profissionais.update', $profissional);

        $this->actingAs($admin)->json($method, $url, ['nome' => 'Duplicado', 'user_id' => $admin->id])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');

        $this->assertDatabaseCount('profissionais', 2);
        $this->assertDatabaseHas('profissionais', ['id' => $profissional->id, 'user_id' => null, 'nome' => $profissional->nome]);
    }

    #[TestWith(['nome', null])]
    #[TestWith(['nome', ''])]
    #[TestWith(['nome', 123])]
    #[TestWith(['telefone', []])]
    #[TestWith(['email', 'invalido'])]
    #[TestWith(['foto', []])]
    #[TestWith(['descricao', []])]
    #[TestWith(['user_id', 'abc'])]
    #[TestWith(['user_id', 999999])]
    public function test_invalid_fields_return_422_without_creating(string $field, mixed $value): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('profissionais.store'), array_replace(
            ['nome' => 'Leonardo'], [$field => $value],
        ))->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('profissionais', 0);
    }

    #[TestWith(['nome', 256])]
    #[TestWith(['telefone', 256])]
    #[TestWith(['email', 256])]
    #[TestWith(['foto', 256])]
    #[TestWith(['descricao', 10001])]
    public function test_oversized_fields_are_rejected(string $field, int $length): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('profissionais.store'), array_replace(
            ['nome' => 'Leonardo'], [$field => str_repeat('a', $length)],
        ))->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('profissionais', 0);
    }

    public function test_relationships_and_inactive_factory_state(): void
    {
        $user = User::factory()->create();
        $profissional = Profissional::factory()->for($user->estabelecimento)->for($user)->inactive()->create();

        $this->assertTrue($user->estabelecimento->profissionais->sole()->is($profissional));
        $this->assertTrue($profissional->estabelecimento->is($user->estabelecimento));
        $this->assertTrue($profissional->user->is($user));
        $this->assertTrue($user->profissional->is($profissional));
        $this->assertFalse($profissional->active);
    }

    #[TestWith(['admin', true, true])]
    #[TestWith(['admin', false, false])]
    #[TestWith(['barbeiro', true, false])]
    #[TestWith(['barbeiro', false, false])]
    public function test_policy_permission_matrix(string $role, bool $active, bool $allowed): void
    {
        $user = User::factory()->create(['role' => $role, 'active' => $active]);
        $profissional = Profissional::factory()->for($user->estabelecimento)->create();
        $other = Profissional::factory()->create();
        $gate = Gate::forUser($user);

        foreach (['viewAny', 'create'] as $ability) {
            $this->assertSame($allowed, $gate->allows($ability, Profissional::class));
        }
        foreach (['view', 'update'] as $ability) {
            $this->assertSame($allowed, $gate->allows($ability, $profissional));
            $this->assertSame(404, $gate->inspect($ability, $other)->status());
        }
        $this->assertFalse($gate->allows('delete', $profissional));
    }

    #[TestWith(['missing_tenant'])]
    #[TestWith(['invalid_tenant'])]
    #[TestWith(['cross_tenant_user'])]
    #[TestWith(['duplicate_user'])]
    public function test_database_rejects_invalid_links(string $scenario): void
    {
        $user = User::factory()->create();
        $other = Estabelecimento::factory()->create();
        if ($scenario === 'duplicate_user') {
            Profissional::factory()->for($user->estabelecimento)->for($user)->create();
        }
        $attributes = match ($scenario) {
            'missing_tenant' => ['estabelecimento_id' => null, 'user_id' => null],
            'invalid_tenant' => ['estabelecimento_id' => 999999, 'user_id' => null],
            'cross_tenant_user' => ['estabelecimento_id' => $other->id, 'user_id' => $user->id],
            'duplicate_user' => ['estabelecimento_id' => $user->estabelecimento_id, 'user_id' => $user->id],
        };

        $this->expectException(QueryException::class);

        DB::table('profissionais')->insert(['nome' => 'Inválido', ...$attributes]);
    }

    public function test_linked_user_cannot_move_to_another_tenant(): void
    {
        $user = User::factory()->create();
        $other = Estabelecimento::factory()->create();
        Profissional::factory()->for($user->estabelecimento)->for($user)->create();

        $this->expectException(QueryException::class);

        $user->update(['estabelecimento_id' => $other->id]);
    }

    public function test_database_seeder_is_idempotent_and_links_demo_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('estabelecimentos', 1);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('profissionais', 3);
        $user = User::where('email', 'barbeiro@barbearia-demo.test')->firstOrFail();
        $this->assertDatabaseHas('profissionais', ['nome' => 'João', 'user_id' => $user->id, 'estabelecimento_id' => $user->estabelecimento_id]);
    }

    public function test_delete_endpoint_is_not_available(): void
    {
        $admin = User::factory()->admin()->create();
        $profissional = Profissional::factory()->for($admin->estabelecimento)->create();

        $this->actingAs($admin)->deleteJson(route('profissionais.show', $profissional))
            ->assertMethodNotAllowed();

        $this->assertModelExists($profissional);
    }
}
