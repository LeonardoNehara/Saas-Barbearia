<?php

namespace Tests\Feature;

use App\Models\BloqueioProfissional;
use App\Models\Estabelecimento;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloqueioProfissionalTest extends TestCase
{
    use RefreshDatabase;

    private Estabelecimento $estabelecimento;
    private User $admin;
    private Profissional $profissional;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estabelecimento = Estabelecimento::factory()->create();

        $this->admin = User::factory()
            ->admin()
            ->active()
            ->for($this->estabelecimento)
            ->create();

        $this->profissional = Profissional::factory()
            ->for($this->estabelecimento)
            ->create();
    }

    public function test_admin_pode_criar_bloqueio(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.bloqueios.store', $this->profissional),
                [
                    'inicio' => '2026-09-21 14:00:00',
                    'fim' => '2026-09-21 16:00:00',
                    'motivo' => 'Consulta médica',
                ]
            );

        $response->assertCreated();

        $this->assertDatabaseHas('bloqueios_profissionais', [
            'profissional_id' => $this->profissional->id,
            'motivo' => 'Consulta médica',
        ]);
    }

    public function test_admin_pode_listar_bloqueios(): void
    {
        BloqueioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'inicio' => '2026-09-21 14:00:00',
            'fim' => '2026-09-21 16:00:00',
            'motivo' => 'Compromisso',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(
                route('profissionais.bloqueios.index', $this->profissional)
            );

        $response
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_fim_deve_ser_posterior_ao_inicio(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.bloqueios.store', $this->profissional),
                [
                    'inicio' => '2026-09-21 16:00:00',
                    'fim' => '2026-09-21 14:00:00',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('fim');
    }

    public function test_nao_permite_bloqueios_sobrepostos(): void
    {
        BloqueioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'inicio' => '2026-09-21 10:00:00',
            'fim' => '2026-09-21 12:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.bloqueios.store', $this->profissional),
                [
                    'inicio' => '2026-09-21 11:00:00',
                    'fim' => '2026-09-21 13:00:00',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'O período informado conflita com outro bloqueio do profissional.',
            ]);

        $this->assertDatabaseCount('bloqueios_profissionais', 1);
    }

    public function test_bloqueios_adjacentes_sao_permitidos(): void
    {
        BloqueioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'inicio' => '2026-09-21 10:00:00',
            'fim' => '2026-09-21 12:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.bloqueios.store', $this->profissional),
                [
                    'inicio' => '2026-09-21 12:00:00',
                    'fim' => '2026-09-21 14:00:00',
                ]
            );

        $response->assertCreated();

        $this->assertDatabaseCount('bloqueios_profissionais', 2);
    }

    public function test_admin_nao_pode_acessar_profissional_de_outro_estabelecimento(): void
    {
        $outroEstabelecimento = Estabelecimento::factory()->create();

        $outroProfissional = Profissional::factory()
            ->for($outroEstabelecimento)
            ->create();

        $this->actingAs($this->admin)
            ->getJson(
                route('profissionais.bloqueios.index', $outroProfissional)
            )
            ->assertNotFound();
    }

    public function test_admin_nao_pode_criar_bloqueio_em_outro_estabelecimento(): void
    {
        $outroEstabelecimento = Estabelecimento::factory()->create();

        $outroProfissional = Profissional::factory()
            ->for($outroEstabelecimento)
            ->create();

        $response = $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.bloqueios.store', $outroProfissional),
                [
                    'inicio' => '2026-09-21 14:00:00',
                    'fim' => '2026-09-21 16:00:00',
                ]
            );

        $response->assertNotFound();

        $this->assertDatabaseMissing('bloqueios_profissionais', [
            'profissional_id' => $outroProfissional->id,
        ]);
    }

    public function test_admin_pode_excluir_bloqueio(): void
    {
        $bloqueio = BloqueioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'inicio' => '2026-09-21 14:00:00',
            'fim' => '2026-09-21 16:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(
                route('profissionais.bloqueios.destroy', [
                    'profissional' => $this->profissional,
                    'bloqueio' => $bloqueio,
                ])
            );

        $response->assertNoContent();

        $this->assertDatabaseMissing('bloqueios_profissionais', [
            'id' => $bloqueio->id,
        ]);
    }

    public function test_nao_pode_excluir_bloqueio_de_outro_profissional(): void
    {
        $outroProfissional = Profissional::factory()
            ->for($this->estabelecimento)
            ->create();

        $bloqueio = BloqueioProfissional::create([
            'profissional_id' => $outroProfissional->id,
            'inicio' => '2026-09-21 14:00:00',
            'fim' => '2026-09-21 16:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(
                route('profissionais.bloqueios.destroy', [
                    'profissional' => $this->profissional,
                    'bloqueio' => $bloqueio,
                ])
            );

        $response->assertNotFound();

        $this->assertDatabaseHas('bloqueios_profissionais', [
            'id' => $bloqueio->id,
        ]);
    }

    public function test_barbeiro_nao_pode_administrar_bloqueios(): void
    {
        $barbeiro = User::factory()
            ->barbeiro()
            ->active()
            ->for($this->estabelecimento)
            ->create();

        $response = $this->actingAs($barbeiro)
            ->postJson(
                route('profissionais.bloqueios.store', $this->profissional),
                [
                    'inicio' => '2026-09-21 14:00:00',
                    'fim' => '2026-09-21 16:00:00',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('bloqueios_profissionais', 0);
    }

    public function test_usuario_inativo_nao_pode_administrar_bloqueios(): void
    {
        $usuario = User::factory()
            ->admin()
            ->inactive()
            ->for($this->estabelecimento)
            ->create();

        $this->actingAs($usuario)
            ->getJson(
                route('profissionais.bloqueios.index', $this->profissional)
            )
            ->assertForbidden();
    }
}