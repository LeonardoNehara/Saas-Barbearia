<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** @return array<string, string> */
    private function validData(): array
    {
        return [
            'name' => 'Novo usuário', 'email' => 'novo@example.test', 'role' => 'barbeiro',
            'password' => 'test-password', 'password_confirmation' => 'test-password',
        ];
    }

    public function test_list_renders_real_tenant_and_users_without_cross_tenant_data(): void
    {
        $admin = User::factory()->admin()->create();
        $colleague = User::factory()->for($admin->estabelecimento)->create();
        $outsider = User::factory()->create();

        $this->actingAs($admin)->get(route('usuarios.index'))->assertOk()
            ->assertSee($admin->estabelecimento->nome)->assertSee($colleague->email)
            ->assertDontSee($outsider->email)->assertSee('2 registros')
            ->assertSee('aria-current="page"', false)->assertSee('mobile-menu')
            ->assertSee(route('logout'))->assertDontSee('Plano Premium');
    }

    #[TestWith(['GET', 'index'])]
    #[TestWith(['GET', 'create'])]
    #[TestWith(['POST', 'store'])]
    #[TestWith(['GET', 'edit'])]
    #[TestWith(['PUT', 'update'])]
    #[TestWith(['PATCH', 'status'])]
    public function test_all_user_routes_reject_guests(string $method, string $action): void
    {
        $usuario = User::factory()->create();
        $this->json($method, route('usuarios.'.$action, $usuario), $this->validData())->assertUnauthorized();
    }

    #[TestWith(['barbeiro', true])]
    #[TestWith(['admin', false])]
    public function test_all_user_routes_reject_unauthorized_accounts(string $role, bool $active): void
    {
        $user = User::factory()->create(compact('role', 'active'));
        $target = User::factory()->for($user->estabelecimento)->create();

        foreach (['index' => 'GET', 'create' => 'GET', 'store' => 'POST', 'edit' => 'GET', 'update' => 'PUT', 'status' => 'PATCH'] as $action => $method) {
            $this->actingAs($user)->json($method, route('usuarios.'.$action, $target), $this->validData())->assertForbidden();
        }

        $this->assertDatabaseCount('users', 2);
        $this->assertTrue($target->fresh()->active);
    }

    public function test_admin_without_establishment_cannot_list_or_create(): void
    {
        $admin = User::factory()->admin()->make(['estabelecimento_id' => null]);
        $this->actingAs($admin)->get(route('usuarios.index'))->assertForbidden();
        $this->post(route('usuarios.store'), $this->validData())->assertForbidden();
    }

    #[TestWith(['GET', 'edit'])]
    #[TestWith(['PUT', 'update'])]
    #[TestWith(['PATCH', 'status'])]
    public function test_cross_tenant_access_returns_404_and_does_not_mutate(string $method, string $action): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->create();

        $this->actingAs($admin)->json($method, route('usuarios.'.$action, $outsider), [...$this->validData(), 'active' => false])
            ->assertNotFound();

        $this->assertSame($outsider->name, $outsider->fresh()->name);
        $this->assertTrue($outsider->fresh()->active);
    }

    public function test_create_uses_authenticated_establishment_and_hashes_password(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->create();
        $this->actingAs($admin)->get(route('usuarios.create'))->assertOk()->assertSee('Cadastrar usuário');
        $this->post(route('usuarios.store'), [
            ...$this->validData(), 'role' => 'admin', 'estabelecimento_id' => $outsider->estabelecimento_id,
            'active' => false, 'email_verified_at' => now(),
        ])->assertRedirect(route('usuarios.index'))->assertSessionHas('status');

        $created = User::where('email', 'novo@example.test')->firstOrFail();
        $this->assertSame($admin->estabelecimento_id, $created->estabelecimento_id);
        $this->assertTrue($created->active);
        $this->assertSame('admin', $created->role);
        $this->assertNull($created->email_verified_at);
        $this->assertTrue(Hash::check('test-password', $created->password));
    }

    #[TestWith(['name', ''])]
    #[TestWith(['email', 'invalid'])]
    #[TestWith(['role', 'atendente'])]
    #[TestWith(['password', 'short'])]
    #[TestWith(['password_confirmation', 'mismatch', 'password'])]
    public function test_invalid_creation_is_rejected(string $field, string $value, ?string $error = null): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->from(route('usuarios.create'))
            ->post(route('usuarios.store'), array_replace($this->validData(), [$field => $value]))
            ->assertRedirect(route('usuarios.create'))->assertSessionHasErrors($error ?? $field)
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_email_must_remain_globally_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->create();
        $this->actingAs($admin)->post(route('usuarios.store'), [...$this->validData(), 'email' => $outsider->email])
            ->assertSessionHasErrors('email');
        $this->put(route('usuarios.update', $admin), [...$this->validData(), 'role' => 'admin', 'email' => $outsider->email])
            ->assertSessionHasErrors('email');
    }

    public function test_edit_preserves_password_and_tenant_and_status(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->for($admin->estabelecimento)->create();
        $outsider = User::factory()->create();
        $this->actingAs($admin)->get(route('usuarios.edit', $target))->assertOk()->assertSee($target->email)
            ->assertDontSee($target->password)->assertDontSee('name="estabelecimento_id"', false);

        $this->put(route('usuarios.update', $target), [
            'name' => 'Nome atualizado', 'email' => $target->email, 'role' => 'admin', 'password' => '',
            'estabelecimento_id' => $outsider->estabelecimento_id, 'active' => false,
        ])->assertRedirect(route('usuarios.index'));

        $updated = $target->fresh();
        $this->assertSame('Nome atualizado', $updated->name);
        $this->assertSame('admin', $updated->role);
        $this->assertSame($target->password, $updated->password);
        $this->assertSame($admin->estabelecimento_id, $updated->estabelecimento_id);
        $this->assertTrue($updated->active);
    }

    public function test_edit_can_change_password_and_resets_email_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->for($admin->estabelecimento)->create();
        $this->actingAs($admin)->put(route('usuarios.update', $target), $this->validData())
            ->assertRedirect(route('usuarios.index'));

        $this->assertTrue(Hash::check('test-password', $target->fresh()->password));
        $this->assertNull($target->fresh()->email_verified_at);
    }

    #[TestWith([true, false])]
    #[TestWith([false, true])]
    public function test_status_changes_are_explicit_and_idempotent(bool $initial, bool $desired): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->for($admin->estabelecimento)->create(['active' => $initial]);
        $this->actingAs($admin)->patch(route('usuarios.status', $target), ['active' => $desired])
            ->assertRedirect(route('usuarios.index'));
        $this->patch(route('usuarios.status', $target), ['active' => $desired])->assertRedirect(route('usuarios.index'));
        $this->assertSame($desired, $target->fresh()->active);
    }

    public function test_status_requires_valid_value_and_admin_cannot_disable_or_demote_self(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->for($admin->estabelecimento)->create();
        $this->actingAs($admin)->patch(route('usuarios.status', $target), ['active' => 'invalid'])->assertSessionHasErrors('active');
        $this->patch(route('usuarios.status', $admin), ['active' => false])->assertForbidden();
        $this->put(route('usuarios.update', $admin), [...$this->validData(), 'email' => $admin->email])
            ->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->active);
        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertTrue($target->fresh()->active);
    }

    public function test_search_filters_and_pagination_are_scoped_and_preserved(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Gestor']);
        $matching = User::factory()->count(12)->for($admin->estabelecimento)->inactive()->create(['name' => 'Equipe alvo']);
        User::factory()->create(['email' => 'equipe-outro@example.test']);
        User::factory()->for($admin->estabelecimento)->active()->create(['name' => 'Equipe ativa']);
        $query = ['search' => 'Equipe', 'role' => 'barbeiro', 'active' => '0', 'per_page' => 10];

        $response = $this->actingAs($admin)->get(route('usuarios.index', $query))->assertOk()->assertDontSee('equipe-outro@example.test');
        $paginator = $response->viewData('usuarios');
        $this->assertSame(12, $paginator->total());
        $this->assertCount(10, $paginator->items());
        $this->assertStringContainsString('search=Equipe', $paginator->nextPageUrl());
        $this->assertStringContainsString('active=0', $paginator->nextPageUrl());
        $this->get($paginator->nextPageUrl())->assertOk()->assertViewHas('usuarios', fn ($users): bool => $users->count() === 2);
        $this->get(route('usuarios.index', ['search' => $matching->first()->email]))->assertOk()
            ->assertViewHas('usuarios', fn ($users): bool => $users->total() === 1);
        $this->get(route('usuarios.index', ['search' => 'sem-resultado-impossivel']))->assertOk()->assertSee('Nenhum usuário encontrado.');
    }

    #[TestWith(['per_page', 999])]
    #[TestWith(['role', 'owner'])]
    #[TestWith(['active', 'invalid'])]
    #[TestWith(['page', -1])]
    public function test_invalid_filters_are_rejected(string $field, mixed $value): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('usuarios.index', [$field => $value]))
            ->assertRedirect(route('usuarios.index'))->assertSessionHasErrors($field);
    }

    public function test_logout_invalidates_session_and_blocks_further_access(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->withSession(['private_data' => 'test'])->post(route('logout'))
            ->assertRedirect(route('login'))->assertSessionMissing('private_data');
        $this->assertGuest();
        $this->get(route('usuarios.index'))->assertUnauthorized();
    }

    public function test_inactive_user_can_logout_and_get_logout_is_not_available(): void
    {
        $this->actingAs(User::factory()->inactive()->create())->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/logout')->assertMethodNotAllowed();
    }

    public function test_delete_is_not_available(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->delete(route('usuarios.update', $admin))->assertMethodNotAllowed();
        $this->assertModelExists($admin);
    }

    public function test_validation_errors_render_in_form_with_old_values(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->from(route('usuarios.create'))->post(route('usuarios.store'), ['name' => 'Nome preservado'])
            ->assertSessionHasErrors(['email', 'role', 'password']);

        $this->withCookie(config('session.cookie'), session()->getId())->get(route('usuarios.create'))->assertOk()
            ->assertSee('Nome preservado')->assertSee('O campo email é obrigatório.');
    }
}
