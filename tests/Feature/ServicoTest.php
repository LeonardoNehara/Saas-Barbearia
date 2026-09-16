<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ServicoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_paginates_only_the_authenticated_tenant(): void
    {
        $admin = User::factory()->admin()->create();
        Servico::factory()->for($admin->estabelecimento)->count(16)->create();
        $other = Servico::factory()->create(['nome' => 'Outro estabelecimento']);

        $this->actingAs($admin)->getJson(route('servicos.index'))
            ->assertOk()->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 16)->assertJsonPath('meta.per_page', 15)
            ->assertJsonMissing(['id' => $other->id, 'nome' => $other->nome]);
    }

    public function test_admin_can_show_own_service_with_profissionais(): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->for($admin->estabelecimento)->create(['preco' => '40.10']);
        $profissional = Profissional::factory()->for($admin->estabelecimento)->create();
        $servico->profissionais()->attach($profissional);

        $this->actingAs($admin)->getJson(route('servicos.show', $servico))
            ->assertOk()->assertJsonPath('data.id', $servico->id)
            ->assertJsonPath('data.preco', '40.10')
            ->assertJsonPath('data.profissionais.0.id', $profissional->id);
    }

    public function test_create_uses_authenticated_tenant_and_ignores_protected_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $other = Estabelecimento::factory()->create();

        $response = $this->actingAs($admin)->postJson(route('servicos.store'), [
            'nome' => 'Corte', 'descricao' => 'Tesoura', 'duracao_minutos' => 30, 'preco' => '40.10',
            'estabelecimento_id' => $other->id, 'active' => false, 'profissionais' => [999],
        ]);

        $response->assertCreated()->assertJsonPath('data.estabelecimento_id', $admin->estabelecimento_id)
            ->assertJsonPath('data.active', true)->assertJsonPath('data.preco', '40.10');
        $this->assertDatabaseHas('servicos', [
            'id' => $response->json('data.id'), 'nome' => 'Corte', 'descricao' => 'Tesoura',
            'duracao_minutos' => 30, 'preco' => '40.10', 'active' => true,
            'estabelecimento_id' => $admin->estabelecimento_id,
        ]);
        $this->assertDatabaseCount('profissional_servico', 0);
    }

    public function test_update_edits_fields_without_changing_tenant_or_status(): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->for($admin->estabelecimento)->create();
        $other = Estabelecimento::factory()->create();

        $this->actingAs($admin)->putJson(route('servicos.update', $servico), [
            'nome' => 'Atualizado', 'descricao' => null, 'duracao_minutos' => 50, 'preco' => '65.99',
            'estabelecimento_id' => $other->id, 'active' => false,
        ])->assertOk()->assertJsonPath('data.preco', '65.99');

        $this->assertDatabaseHas('servicos', [
            'id' => $servico->id, 'nome' => 'Atualizado', 'descricao' => null,
            'duracao_minutos' => 50, 'preco' => '65.99',
            'estabelecimento_id' => $admin->estabelecimento_id, 'active' => true,
        ]);
    }

    #[TestWith(['GET', 'show'])]
    #[TestWith(['PUT', 'update'])]
    #[TestWith(['PATCH', 'status'])]
    #[TestWith(['PUT', 'profissionais'])]
    public function test_other_tenant_returns_404_without_changes(string $method, string $action): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->create();
        $profissional = Profissional::factory()->for($servico->estabelecimento)->create();
        $servico->profissionais()->attach($profissional);
        $original = $servico->refresh()->getAttributes();

        $this->actingAs($admin)->json($method, route('servicos.'.$action, $servico), [
            'nome' => 'Invadido', 'duracao_minutos' => 30, 'preco' => '10.00', 'profissionais' => [],
        ])->assertNotFound()->assertJsonMissing(['nome' => $servico->nome]);

        $this->assertSame($original, $servico->fresh()->getAttributes());
        $this->assertSame([$profissional->id], $servico->profissionais()->pluck('profissionais.id')->all());
    }

    #[TestWith([true, false])]
    #[TestWith([false, true])]
    public function test_admin_toggles_status_without_deleting(bool $initial, bool $expected): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->for($admin->estabelecimento)->create(['active' => $initial]);
        $profissional = Profissional::factory()->for($admin->estabelecimento)->create();
        $servico->profissionais()->attach($profissional);

        $this->actingAs($admin)->patchJson(route('servicos.status', $servico))
            ->assertOk()->assertJsonPath('data.active', $expected);

        $this->assertDatabaseHas('servicos', ['id' => $servico->id, 'active' => $expected]);
        $this->assertDatabaseHas('profissional_servico', ['servico_id' => $servico->id, 'profissional_id' => $profissional->id]);
    }

    #[TestWith(['GET', 'index'])]
    #[TestWith(['POST', 'store'])]
    #[TestWith(['GET', 'show'])]
    #[TestWith(['PUT', 'update'])]
    #[TestWith(['PATCH', 'status'])]
    #[TestWith(['PUT', 'profissionais'])]
    public function test_guests_receive_401(string $method, string $action): void
    {
        $servico = Servico::factory()->create();
        $original = $servico->refresh()->getAttributes();

        $this->json($method, route('servicos.'.$action, $servico), ['profissionais' => []])
            ->assertUnauthorized();

        $this->assertDatabaseCount('servicos', 1);
        $this->assertSame($original, $servico->fresh()->getAttributes());
        $this->assertDatabaseCount('profissional_servico', 0);
    }

    #[TestWith(['GET', 'index', 'barbeiro', true])]
    #[TestWith(['POST', 'store', 'barbeiro', true])]
    #[TestWith(['GET', 'show', 'barbeiro', true])]
    #[TestWith(['PUT', 'update', 'barbeiro', true])]
    #[TestWith(['PATCH', 'status', 'barbeiro', true])]
    #[TestWith(['PUT', 'profissionais', 'barbeiro', true])]
    #[TestWith(['GET', 'index', 'admin', false])]
    #[TestWith(['POST', 'store', 'admin', false])]
    #[TestWith(['GET', 'show', 'admin', false])]
    #[TestWith(['PUT', 'update', 'admin', false])]
    #[TestWith(['PATCH', 'status', 'admin', false])]
    #[TestWith(['PUT', 'profissionais', 'admin', false])]
    public function test_unprivileged_users_receive_403(string $method, string $action, string $role, bool $active): void
    {
        $user = User::factory()->create(['role' => $role, 'active' => $active]);
        $servico = Servico::factory()->for($user->estabelecimento)->create();
        $profissional = Profissional::factory()->for($user->estabelecimento)->create();
        $servico->profissionais()->attach($profissional);
        $original = $servico->refresh()->getAttributes();

        $this->actingAs($user)->json($method, route('servicos.'.$action, $servico), [
            'nome' => 'Invadido', 'duracao_minutos' => 30, 'preco' => '10.00', 'profissionais' => [],
        ])->assertForbidden();

        $this->assertDatabaseCount('servicos', 1);
        $this->assertSame($original, $servico->fresh()->getAttributes());
        $this->assertSame([$profissional->id], $servico->profissionais()->pluck('profissionais.id')->all());
    }

    #[TestWith(['nome', null])]
    #[TestWith(['nome', 123])]
    #[TestWith(['descricao', []])]
    #[TestWith(['duracao_minutos', null])]
    #[TestWith(['duracao_minutos', 4])]
    #[TestWith(['duracao_minutos', 481])]
    #[TestWith(['duracao_minutos', 30.5])]
    #[TestWith(['preco', null])]
    #[TestWith(['preco', '-0.01'])]
    #[TestWith(['preco', 'abc'])]
    #[TestWith(['preco', '100000000.00'])]
    #[TestWith(['preco', '40.001'])]
    #[TestWith(['preco', '4e1'])]
    #[TestWith(['preco', '40,00'])]
    public function test_invalid_fields_return_422_without_changes(string $field, mixed $value): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('servicos.store'), array_replace(
            ['nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => '40.00'], [$field => $value],
        ))->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('servicos', 0);
    }

    #[TestWith(['nome', 256])]
    #[TestWith(['descricao', 10001])]
    public function test_oversized_fields_are_rejected(string $field, int $length): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('servicos.store'), array_replace(
            ['nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => '40.00'], [$field => str_repeat('a', $length)],
        ))->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('servicos', 0);
    }

    public function test_invalid_update_preserves_existing_values(): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->for($admin->estabelecimento)->create();
        $original = $servico->refresh()->getAttributes();

        $this->actingAs($admin)->putJson(route('servicos.update', $servico), [
            'nome' => 'Alterado', 'duracao_minutos' => 4, 'preco' => '-1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['preco', 'duracao_minutos']);

        $this->assertSame($original, $servico->fresh()->getAttributes());
    }

    #[TestWith([5, '0', '0.00'])]
    #[TestWith([480, '99999999.99', '99999999.99'])]
    public function test_valid_boundaries_are_persisted(int $duration, string $price, string $expected): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson(route('servicos.store'), [
            'nome' => 'Corte', 'duracao_minutos' => $duration, 'preco' => $price,
        ])->assertCreated()->assertJsonPath('data.preco', $expected);

        $this->assertDatabaseHas('servicos', [
            'id' => $response->json('data.id'), 'duracao_minutos' => $duration, 'preco' => $expected,
        ]);
    }

    public function test_sync_links_multiple_profissionais_with_timestamps(): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->for($admin->estabelecimento)->create();
        $profissionais = Profissional::factory()->for($admin->estabelecimento)->count(2)->create();

        $this->actingAs($admin)->putJson(route('servicos.profissionais', $servico), [
            'profissionais' => $profissionais->modelKeys(),
        ])->assertOk()->assertJsonCount(2, 'data.profissionais');

        $this->assertSame($profissionais->modelKeys(), $servico->profissionais()->orderBy('profissionais.id')->pluck('profissionais.id')->all());
        foreach ($servico->profissionais as $profissional) {
            $this->assertNotNull($profissional->pivot->created_at);
            $this->assertNotNull($profissional->pivot->updated_at);
        }
    }

    public function test_cross_tenant_profissional_is_rejected_without_changing_existing_links(): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->for($admin->estabelecimento)->create();
        $own = Profissional::factory()->for($admin->estabelecimento)->create();
        $other = Profissional::factory()->create();
        $servico->profissionais()->attach($own);

        $this->actingAs($admin)->putJson(route('servicos.profissionais', $servico), [
            'profissionais' => [$own->id, $other->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('profissionais.1');

        $this->assertSame([$own->id], $servico->profissionais()->pluck('profissionais.id')->all());
    }

    #[TestWith([[]])]
    #[TestWith([['profissionais' => null]])]
    #[TestWith([['profissionais' => 'invalid']])]
    #[TestWith([['profissionais' => [999999]]])]
    #[TestWith([['profissionais' => ['abc']]])]
    #[TestWith([['profissionais' => [null]]])]
    #[TestWith([['profissionais' => ['key' => 1]]])]
    public function test_invalid_sync_payload_preserves_links(array $payload): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->for($admin->estabelecimento)->create();
        $own = Profissional::factory()->for($admin->estabelecimento)->create();
        $servico->profissionais()->attach($own);

        $this->actingAs($admin)->putJson(route('servicos.profissionais', $servico), $payload)
            ->assertUnprocessable()->assertJsonStructure(['errors']);

        $this->assertSame([$own->id], $servico->profissionais()->pluck('profissionais.id')->all());
    }

    public function test_duplicate_ids_are_rejected_without_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->for($admin->estabelecimento)->create();
        $profissional = Profissional::factory()->for($admin->estabelecimento)->create();

        $this->actingAs($admin)->putJson(route('servicos.profissionais', $servico), [
            'profissionais' => [$profissional->id, $profissional->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('profissionais.0');

        $this->assertDatabaseCount('profissional_servico', 0);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_sync_removes_omitted_links_and_accepts_empty_list(bool $clearAll): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->for($admin->estabelecimento)->create();
        $profissionais = Profissional::factory()->for($admin->estabelecimento)->count(2)->create();
        $servico->profissionais()->attach($profissionais);
        $expected = $clearAll ? [] : [$profissionais->first()->id];

        $this->actingAs($admin)->putJson(route('servicos.profissionais', $servico), [
            'profissionais' => $expected,
        ])->assertOk()->assertJsonCount(count($expected), 'data.profissionais');

        $this->assertSame($expected, $servico->profissionais()->pluck('profissionais.id')->all());
    }

    public function test_relationships_and_inactive_factory_state(): void
    {
        $estabelecimento = Estabelecimento::factory()->create();
        $servicos = Servico::factory()->for($estabelecimento)->inactive()->count(2)->create();
        $profissionais = Profissional::factory()->for($estabelecimento)->count(2)->create();
        foreach ($servicos as $servico) {
            $servico->profissionais()->attach($profissionais);
        }

        $this->assertSame($servicos->modelKeys(), $estabelecimento->servicos()->orderBy('id')->pluck('id')->all());
        $this->assertTrue($servicos->first()->estabelecimento->is($estabelecimento));
        $this->assertFalse($servicos->first()->active);
        $this->assertSame($servicos->modelKeys(), $profissionais->first()->servicos()->orderBy('servicos.id')->pluck('servicos.id')->all());
        $this->assertSame($profissionais->modelKeys(), $servicos->first()->profissionais()->orderBy('profissionais.id')->pluck('profissionais.id')->all());
    }

    #[TestWith(['admin', true, true])]
    #[TestWith(['admin', false, false])]
    #[TestWith(['barbeiro', true, false])]
    #[TestWith(['barbeiro', false, false])]
    public function test_policy_permission_matrix(string $role, bool $active, bool $allowed): void
    {
        $user = User::factory()->create(['role' => $role, 'active' => $active]);
        $servico = Servico::factory()->for($user->estabelecimento)->create();
        $other = Servico::factory()->create();
        $gate = Gate::forUser($user);

        foreach (['viewAny', 'create'] as $ability) {
            $this->assertSame($allowed, $gate->allows($ability, Servico::class));
        }
        foreach (['view', 'update', 'syncProfissionais'] as $ability) {
            $this->assertSame($allowed, $gate->allows($ability, $servico));
            $this->assertSame(404, $gate->inspect($ability, $other)->status());
        }
        $this->assertFalse($gate->allows('delete', $servico));
    }

    public function test_admin_without_tenant_cannot_create_or_list(): void
    {
        $admin = User::factory()->admin()->make(['estabelecimento_id' => null]);
        $gate = Gate::forUser($admin);

        $this->assertFalse($gate->allows('viewAny', Servico::class));
        $this->assertFalse($gate->allows('create', Servico::class));
    }

    public function test_database_rejects_duplicate_pivot(): void
    {
        $servico = Servico::factory()->create();
        $profissional = Profissional::factory()->for($servico->estabelecimento)->create();
        $servico->profissionais()->attach($profissional);

        $this->expectException(QueryException::class);

        $servico->profissionais()->attach($profissional);
    }

    #[TestWith([null])]
    #[TestWith([999999])]
    public function test_database_requires_existing_tenant(?int $tenant): void
    {
        $this->expectException(QueryException::class);

        DB::table('servicos')->insert([
            'estabelecimento_id' => $tenant, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => '40.00',
        ]);
    }

    public function test_database_seeder_is_idempotent_and_links_expected_demo_profissionais(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('servicos', 3);
        $this->assertDatabaseCount('profissional_servico', 6);
        $estabelecimento = Estabelecimento::where('slug', 'barbearia-demo')->firstOrFail();
        foreach ([
            ['Corte Masculino', 30, '40.00', [1, 2, 3]],
            ['Barba', 20, '30.00', [1, 3]],
            ['Corte + Barba', 50, '65.00', [3]],
        ] as [$nome, $duration, $price, $indexes]) {
            $servico = $estabelecimento->servicos()->where('nome', $nome)->firstOrFail();
            $this->assertSame($duration, $servico->duracao_minutos);
            $this->assertSame($price, $servico->preco);
            $this->assertSame(
                array_map(fn (int $index): string => 'profissional'.$index.'@barbearia-demo.test', $indexes),
                $servico->profissionais()->orderBy('email')->pluck('email')->all(),
            );
        }
    }

    public function test_delete_endpoint_is_not_available(): void
    {
        $admin = User::factory()->admin()->create();
        $servico = Servico::factory()->for($admin->estabelecimento)->create();

        $this->actingAs($admin)->deleteJson(route('servicos.show', $servico))->assertMethodNotAllowed();

        $this->assertModelExists($servico);
    }
}
