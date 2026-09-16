<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'active'])
            ->get('/test-protected', fn () => response()->json(['allowed' => true]));
        Route::middleware(['web', 'auth', 'active', 'can:is-admin'])
            ->get('/test-admin', fn () => response()->json(['allowed' => true]));
        Route::middleware(['web', 'active'])
            ->get('/test-active', fn () => response()->json(['allowed' => true]));
    }

    #[TestWith(['admin', true, true])]
    #[TestWith(['admin', false, false])]
    #[TestWith(['barbeiro', true, false])]
    #[TestWith(['barbeiro', false, false])]
    public function test_admin_gate_checks_role_and_status(string $role, bool $active, bool $allowed): void
    {
        $user = User::factory()->create(['role' => $role, 'active' => $active]);

        $this->assertSame($allowed, Gate::forUser($user)->allows('is-admin'));
    }

    public function test_guest_is_denied_by_admin_gate(): void
    {
        $this->assertFalse(Gate::allows('is-admin'));
    }

    public function test_guest_receives_json_401_without_a_login_page(): void
    {
        $this->get('/test-protected')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_active_middleware_alone_rejects_guests(): void
    {
        $this->getJson('/test-active')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_inactive_user_receives_403_on_protected_route(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get('/test-protected')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Usuario inativo.']);
    }

    public function test_active_user_can_access_protected_route(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user)->getJson('/test-protected')
            ->assertOk()
            ->assertExactJson(['allowed' => true]);
    }

    public function test_active_admin_can_access_admin_route(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->getJson('/test-admin')
            ->assertOk()
            ->assertExactJson(['allowed' => true]);
    }

    public function test_barbeiro_receives_403_on_admin_route(): void
    {
        $user = User::factory()->barbeiro()->create();

        $this->actingAs($user)->getJson('/test-admin')->assertForbidden();
    }

    public function test_native_session_guard_authenticates_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'test-only-password']);

        $authenticated = Auth::guard('web')->attempt([
            'email' => $user->email,
            'password' => 'test-only-password',
            'active' => true,
        ]);

        $this->assertTrue($authenticated);
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_native_guard_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'test-only-password']);

        $this->assertFalse(Auth::guard('web')->attempt([
            'email' => $user->email,
            'password' => 'wrong-password',
            'active' => true,
        ]));
        $this->assertGuest('web');
    }

    public function test_native_guard_rejects_inactive_users_when_active_is_required(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'test-only-password']);

        $this->assertFalse(Auth::guard('web')->attempt([
            'email' => $user->email,
            'password' => 'test-only-password',
            'active' => true,
        ]));
        $this->assertGuest('web');
    }
}
