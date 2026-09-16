<?php

namespace Tests\Feature\Models;

use App\Models\Estabelecimento;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EstabelecimentoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_estabelecimento_can_be_created_with_only_required_fields(): void
    {
        $estabelecimento = Estabelecimento::create([
            'nome' => 'Barbearia Central',
            'slug' => 'barbearia-central',
        ])->refresh();

        $this->assertModelExists($estabelecimento);
        $this->assertSame('Barbearia Central', $estabelecimento->nome);
        $this->assertTrue($estabelecimento->active);
        $this->assertNull($estabelecimento->cnpj);
        $this->assertNull($estabelecimento->telefone);
        $this->assertNull($estabelecimento->email);
        $this->assertNull($estabelecimento->logo);
        $this->assertNull($estabelecimento->trial_ends_at);
    }

    public function test_user_belongs_to_the_associated_estabelecimento(): void
    {
        $estabelecimento = Estabelecimento::factory()->create();

        $user = User::factory()->for($estabelecimento)->create()->fresh();

        $this->assertModelExists($user);
        $this->assertSame($estabelecimento->id, $user->estabelecimento_id);
        $this->assertTrue($user->estabelecimento->is($estabelecimento));
    }

    public function test_estabelecimento_returns_only_its_own_users(): void
    {
        $estabelecimento = Estabelecimento::factory()->create();
        $users = User::factory()->count(2)->for($estabelecimento)->create();
        User::factory()->create();

        $relatedUsers = $estabelecimento->users()->orderBy('id')->get();

        $this->assertSame($users->modelKeys(), $relatedUsers->modelKeys());
    }

    public function test_duplicate_slugs_are_rejected(): void
    {
        Estabelecimento::factory()->create(['slug' => 'barbearia-central']);

        $this->expectException(QueryException::class);

        Estabelecimento::factory()->create(['slug' => 'barbearia-central']);
    }

    public function test_inactive_estabelecimento_is_cast_to_false(): void
    {
        $estabelecimento = Estabelecimento::factory()->create(['active' => false]);

        $this->assertFalse($estabelecimento->fresh()->active);
    }

    public function test_trial_end_is_read_as_datetime(): void
    {
        $estabelecimento = Estabelecimento::factory()->create([
            'trial_ends_at' => '2026-10-01 12:30:00',
        ]);

        $trialEndsAt = $estabelecimento->fresh()->trial_ends_at;

        $this->assertInstanceOf(Carbon::class, $trialEndsAt);
        $this->assertSame('2026-10-01 12:30:00', $trialEndsAt->format('Y-m-d H:i:s'));
    }

    public function test_estabelecimento_with_users_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $estabelecimento = $user->estabelecimento;

        try {
            $estabelecimento->delete();
            $this->fail('An establishment with users must not be deleted.');
        } catch (QueryException) {
            $this->assertModelExists($estabelecimento);
            $this->assertModelExists($user);
        }
    }

    public function test_user_cannot_have_a_missing_estabelecimento(): void
    {
        $this->expectException(QueryException::class);

        User::factory()->create(['estabelecimento_id' => 999999]);
    }

    public function test_user_cannot_have_a_null_estabelecimento(): void
    {
        $this->expectException(QueryException::class);

        User::factory()->create(['estabelecimento_id' => null]);
    }

    public function test_factory_creates_unique_slugs_and_a_fourteen_day_trial(): void
    {
        $this->travelTo(Carbon::parse('2026-09-16 12:00:00'));

        $estabelecimentos = Estabelecimento::factory()->count(3)->create();

        $this->assertCount(3, $estabelecimentos->pluck('slug')->unique());
        $this->assertSame('2026-09-30 12:00:00', $estabelecimentos->first()->fresh()->trial_ends_at->format('Y-m-d H:i:s'));
    }
}
