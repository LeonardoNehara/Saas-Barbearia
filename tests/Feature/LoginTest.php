<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_has_real_form_and_safe_password_field(): void
    {
        $this->withoutVite()->get('/login')->assertOk()
            ->assertSee('Vamos organizar o seu')
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('type="password"', false)
            ->assertDontSee('name="estabelecimento_id"', false);
    }

    public function test_active_user_logs_in_with_own_establishment_and_redirects_to_appointments(): void
    {
        $user = User::factory()->active()->create();
        $other = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'estabelecimento_id' => $other->estabelecimento_id,
        ])->assertRedirect(route('agendamentos.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->estabelecimento_id, auth()->user()->estabelecimento_id);
        $this->get('/agendamentos')->assertOk();
    }

    #[TestWith([true, 'wrong-password'])]
    #[TestWith([false, 'password'])]
    public function test_invalid_credentials_and_inactive_users_cannot_login(bool $active, string $password): void
    {
        $user = User::factory()->create(['active' => $active]);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => $password])
            ->assertRedirect('/login')->assertSessionHasErrors('email')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_unknown_user_cannot_login(): void
    {
        $this->post('/login', ['email' => 'unknown@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password_and_renders_errors(): void
    {
        $this->from('/login')->post('/login', [])
            ->assertRedirect('/login')->assertSessionHasErrors(['email', 'password']);

        $this->withoutVite()->withCookie(config('session.cookie'), session()->getId())
            ->get('/login')->assertOk()
            ->assertSee('Informe seu email.')->assertSee('Informe sua senha.');
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')
            ->assertRedirect(route('agendamentos.index'));
    }

    public function test_repeated_attempts_are_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_account_deactivated_after_login_is_still_blocked(): void
    {
        $user = User::factory()->active()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('agendamentos.index'));

        $user->forceFill(['active' => false])->save();
        $this->actingAs($user->fresh())->get('/agendamentos')->assertForbidden();
    }
}
