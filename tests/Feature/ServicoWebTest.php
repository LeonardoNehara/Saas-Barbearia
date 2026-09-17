<?php

namespace Tests\Feature;

use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ServicoWebTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** @return array<string, string|int> */
    private function validData(): array
    {
        return ['nome' => 'Serviço de teste', 'descricao' => 'Descrição de teste', 'duracao_minutos' => 45, 'preco' => '75.90'];
    }

    public function test_list_uses_admin_layout_and_only_real_tenant_services(): void
    {
        $admin = User::factory()->admin()->create();
        $own = Servico::factory()->for($admin->estabelecimento)->create(['preco' => '1234.56', 'duracao_minutos' => 45]);
        $other = Servico::factory()->create(['nome' => 'Serviço privado de outro estabelecimento']);

        $this->actingAs($admin)->get(route('servicos.index'))->assertOk()->assertViewIs('servicos.index')
            ->assertSee($own->nome)->assertSee('R$ 1.234,56')->assertSee('45 min')
            ->assertSee($admin->estabelecimento->nome)->assertSee('1 serviço')
            ->assertSee('aria-current="page"', false)->assertSee('mobile-menu')
            ->assertDontSee($other->nome)->assertDontSee('Todas as categorias');
    }

    #[TestWith(['index'])]
    #[TestWith(['create'])]
    #[TestWith(['edit'])]
    public function test_guest_cannot_access_web_pages(string $action): void
    {
        $service = Servico::factory()->create();
        $this->get(route('servicos.'.$action, $service))->assertUnauthorized();
    }

    #[TestWith(['admin', false])]
    #[TestWith(['barbeiro', true])]
    public function test_web_pages_preserve_authorization(string $role, bool $active): void
    {
        $user = User::factory()->create(compact('role', 'active'));
        $service = Servico::factory()->for($user->estabelecimento)->create();
        foreach (['index', 'create', 'edit'] as $action) {
            $this->actingAs($user)->get(route('servicos.'.$action, $service))->assertForbidden();
        }
    }

    #[TestWith(['GET', 'edit'])]
    #[TestWith(['PUT', 'update'])]
    #[TestWith(['PATCH', 'status'])]
    #[TestWith(['PUT', 'profissionais'])]
    public function test_cross_tenant_web_actions_are_rejected(string $method, string $action): void
    {
        $admin = User::factory()->admin()->create();
        $service = Servico::factory()->create();
        $this->actingAs($admin)->call($method, route('servicos.'.$action, $service), $this->validData())
            ->assertNotFound();
        $this->assertSame($service->nome, $service->fresh()->nome);
        $this->assertTrue($service->fresh()->active);
    }

    public function test_create_uses_existing_validation_and_authenticated_tenant(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();
        $this->actingAs($admin)->get(route('servicos.create'))->assertOk()->assertSee('Cadastrar serviço')
            ->assertDontSee('name="estabelecimento_id"', false);
        $response = $this->post(route('servicos.store'), [
            ...$this->validData(), 'estabelecimento_id' => $other->estabelecimento_id, 'active' => false,
        ]);
        $service = Servico::where('nome', 'Serviço de teste')->firstOrFail();
        $response->assertRedirect(route('servicos.edit', $service))->assertSessionHas('status');
        $this->assertSame($admin->estabelecimento_id, $service->estabelecimento_id);
        $this->assertSame('75.90', $service->preco);
        $this->assertTrue($service->active);
    }

    public function test_creation_errors_and_old_input_are_rendered(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->from(route('servicos.create'))->post(route('servicos.store'), [
            'nome' => 'Nome preservado', 'preco' => '4.999', 'duracao_minutos' => 1,
        ])->assertRedirect(route('servicos.create'))->assertSessionHasErrors(['preco', 'duracao_minutos']);
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('servicos.create'))->assertOk()
            ->assertSee('Nome preservado')->assertSee('Informe o preço com até duas casas decimais.');
        $this->assertDatabaseCount('servicos', 0);
    }

    public function test_edit_loads_only_tenant_professionals_and_saves_service(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Servico::factory()->for($admin->estabelecimento)->create();
        $own = Profissional::factory()->for($admin->estabelecimento)->create(['nome' => 'Profissional local']);
        $other = Profissional::factory()->create(['nome' => 'Profissional de outra empresa']);
        $service->profissionais()->attach($own);

        $this->actingAs($admin)->get(route('servicos.edit', $service))->assertOk()
            ->assertSee($service->nome)->assertSee($own->nome)->assertDontSee($other->nome)
            ->assertSee('checked', false);
        $this->put(route('servicos.update', $service), [...$this->validData(), 'estabelecimento_id' => $other->estabelecimento_id, 'active' => false])
            ->assertRedirect(route('servicos.edit', $service));
        $this->assertSame('75.90', $service->fresh()->preco);
        $this->assertSame($admin->estabelecimento_id, $service->fresh()->estabelecimento_id);
        $this->assertTrue($service->fresh()->active);
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_web_status_uses_existing_toggle(bool $active): void
    {
        $admin = User::factory()->admin()->create();
        $service = Servico::factory()->for($admin->estabelecimento)->create(compact('active'));
        $this->actingAs($admin)->patch(route('servicos.status', $service))->assertRedirect(route('servicos.index'));
        $this->assertSame(! $active, $service->fresh()->active);
    }

    public function test_search_status_and_pagination_work_together_without_tenant_leak(): void
    {
        $admin = User::factory()->admin()->create();
        Servico::factory()->count(12)->for($admin->estabelecimento)->create(['nome' => 'Seleção de serviços', 'active' => false]);
        Servico::factory()->for($admin->estabelecimento)->create(['nome' => 'Seleção ativa', 'active' => true]);
        $other = Servico::factory()->create(['descricao' => 'Seleção de serviços', 'active' => false]);
        $query = ['search' => 'Seleção', 'active' => '0', 'per_page' => 10];
        $response = $this->actingAs($admin)->get(route('servicos.index', $query))->assertOk()->assertDontSee($other->nome);
        $paginator = $response->viewData('servicos');
        $this->assertSame(12, $paginator->total());
        $this->assertCount(10, $paginator->items());
        $this->assertStringContainsString('active=0', $paginator->nextPageUrl());
        $this->assertStringContainsString('per_page=10', $paginator->nextPageUrl());
        $this->get($paginator->nextPageUrl())->assertOk()->assertViewHas('servicos', fn ($services): bool => $services->count() === 2 && $services->total() === 12);
        $this->get(route('servicos.index', [...$query, 'per_page' => 25]))->assertOk()
            ->assertViewHas('servicos', fn ($services): bool => $services->count() === 12);
    }

    public function test_search_matches_description_and_zero(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Servico::factory()->for($admin->estabelecimento)->create(['nome' => 'Modelo', 'descricao' => 'Detalhe 0 exclusivo']);
        $this->actingAs($admin)->get(route('servicos.index', ['search' => '0']))->assertOk()->assertSee($service->nome)
            ->assertViewHas('servicos', fn ($services): bool => $services->total() === 1);
    }

    #[TestWith(['per_page', 500])]
    #[TestWith(['active', 'invalid'])]
    #[TestWith(['search', ['invalid']])]
    #[TestWith(['page', -1])]
    public function test_invalid_filters_return_to_clean_list(string $field, mixed $value): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('servicos.index', [$field => $value]))
            ->assertRedirect(route('servicos.index'))->assertSessionHasErrors($field);
    }

    public function test_empty_states_distinguish_no_services_from_no_matches(): void
    {
        $admin = User::factory()->admin()->create();
        Servico::factory()->create();
        $this->actingAs($admin)->get(route('servicos.index'))->assertOk()->assertSee('Nenhum serviço cadastrado.')->assertSee('Cadastrar primeiro serviço');
        Servico::factory()->for($admin->estabelecimento)->create(['nome' => 'Existente']);
        $this->get(route('servicos.index', ['search' => 'inexistente-987']))->assertOk()->assertSee('Nenhum resultado encontrado.')->assertSee('Limpar filtros');
    }

    public function test_web_sync_saves_and_clears_professionals_without_js(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Servico::factory()->for($admin->estabelecimento)->create();
        $professional = Profissional::factory()->for($admin->estabelecimento)->create();
        $this->actingAs($admin)->put(route('servicos.profissionais', $service), [
            'profissionais_present' => '1', 'profissionais' => [$professional->id],
        ])->assertRedirect(route('servicos.edit', $service));
        $this->assertSame([$professional->id], $service->profissionais()->pluck('profissionais.id')->all());
        $this->put(route('servicos.profissionais', $service), ['profissionais_present' => '1'])
            ->assertRedirect(route('servicos.edit', $service));
        $this->assertSame(0, $service->profissionais()->count());
    }

    public function test_web_sync_rejects_cross_tenant_professional_without_losing_links(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Servico::factory()->for($admin->estabelecimento)->create();
        $own = Profissional::factory()->for($admin->estabelecimento)->create();
        $other = Profissional::factory()->create();
        $service->profissionais()->attach($own);
        $this->actingAs($admin)->from(route('servicos.edit', $service))->put(route('servicos.profissionais', $service), [
            'profissionais' => [$other->id], 'profissionais_present' => '1',
        ])->assertRedirect(route('servicos.edit', $service))->assertSessionHasErrors('profissionais.0');
        $this->assertSame([$own->id], $service->profissionais()->pluck('profissionais.id')->all());
    }

    public function test_json_sync_still_requires_explicit_array(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Servico::factory()->for($admin->estabelecimento)->create();
        $this->actingAs($admin)->putJson(route('servicos.profissionais', $service), ['profissionais_present' => '1'])
            ->assertUnprocessable()->assertJsonValidationErrors('profissionais');
    }

    public function test_web_forms_preserve_csrf_and_method_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Servico::factory()->for($admin->estabelecimento)->create();
        $this->actingAs($admin)->get(route('servicos.edit', $service))->assertOk()
            ->assertSee('name="_token"', false)->assertSee('value="PUT"', false)
            ->assertSee(route('servicos.profissionais', $service));

    }
}
