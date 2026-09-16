<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\HorarioProfissional;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioProfissionalTest extends TestCase
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

    public function test_admin_pode_criar_horario_para_profissional_do_proprio_estabelecimento(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->postJson(
                route('profissionais.horarios.store', $this->profissional),
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '08:00',
                    'hora_fim' => '12:00',
                ]
            );

        $response->assertCreated();

        $this->assertDatabaseHas('horarios_profissionais', [
            'profissional_id' => $this->profissional->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fim' => '12:00',
        ]);
    }

    public function test_pode_criar_dois_periodos_no_mesmo_dia(): void
    {
        $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.horarios.store', $this->profissional),
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '08:00',
                    'hora_fim' => '12:00',
                ]
            )
            ->assertCreated();

        $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.horarios.store', $this->profissional),
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '13:00',
                    'hora_fim' => '18:00',
                ]
            )
            ->assertCreated();

        $this->assertDatabaseCount('horarios_profissionais', 2);
    }

    public function test_horarios_adjacentes_sao_permitidos(): void
    {
        $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.horarios.store', $this->profissional),
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '08:00',
                    'hora_fim' => '12:00',
                ]
            )
            ->assertCreated();

        $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.horarios.store', $this->profissional),
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '12:00',
                    'hora_fim' => '18:00',
                ]
            )
            ->assertCreated();

        $this->assertDatabaseCount('horarios_profissionais', 2);
    }

    public function test_nao_permite_horarios_sobrepostos(): void
    {
        $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.horarios.store', $this->profissional),
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '08:00',
                    'hora_fim' => '12:00',
                ]
            )
            ->assertCreated();

        $response = $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.horarios.store', $this->profissional),
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '11:00',
                    'hora_fim' => '14:00',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'O horário informado conflita com outro horário do profissional.',
            ]);

        $this->assertDatabaseCount('horarios_profissionais', 1);
    }

    public function test_hora_fim_deve_ser_posterior_a_hora_inicio(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.horarios.store', $this->profissional),
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '18:00',
                    'hora_fim' => '08:00',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('hora_fim');
    }

    public function test_dia_semana_deve_estar_entre_zero_e_seis(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(
                route('profissionais.horarios.store', $this->profissional),
                [
                    'dia_semana' => 7,
                    'hora_inicio' => '08:00',
                    'hora_fim' => '12:00',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('dia_semana');
    }

    public function test_admin_nao_pode_acessar_horarios_de_outro_estabelecimento(): void
    {
        $outroEstabelecimento = Estabelecimento::factory()->create();

        $outroProfissional = Profissional::factory()
            ->for($outroEstabelecimento)
            ->create();

        $response = $this
            ->actingAs($this->admin)
            ->getJson(
                route('profissionais.horarios.index', $outroProfissional)
            );

        $response->assertNotFound();
    }

    public function test_admin_nao_pode_criar_horario_para_profissional_de_outro_estabelecimento(): void
    {
        $outroEstabelecimento = Estabelecimento::factory()->create();

        $outroProfissional = Profissional::factory()
            ->for($outroEstabelecimento)
            ->create();

        $response = $this
            ->actingAs($this->admin)
            ->postJson(
                route('profissionais.horarios.store', $outroProfissional),
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '08:00',
                    'hora_fim' => '12:00',
                ]
            );

        $response->assertNotFound();

        $this->assertDatabaseMissing('horarios_profissionais', [
            'profissional_id' => $outroProfissional->id,
        ]);
    }

    public function test_admin_pode_excluir_horario(): void
    {
        $horario = HorarioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fim' => '12:00',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->deleteJson(
                route('profissionais.horarios.destroy', [
                    'profissional' => $this->profissional,
                    'horario' => $horario,
                ])
            );

        $response->assertNoContent();

        $this->assertDatabaseMissing('horarios_profissionais', [
            'id' => $horario->id,
        ]);
    }

    public function test_nao_pode_excluir_horario_de_outro_profissional(): void
    {
        $outroProfissional = Profissional::factory()
            ->for($this->estabelecimento)
            ->create();

        $horario = HorarioProfissional::create([
            'profissional_id' => $outroProfissional->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fim' => '12:00',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->deleteJson(
                route('profissionais.horarios.destroy', [
                    'profissional' => $this->profissional,
                    'horario' => $horario,
                ])
            );

        $response->assertNotFound();

        $this->assertDatabaseHas('horarios_profissionais', [
            'id' => $horario->id,
        ]);
    }

    public function test_usuario_inativo_nao_pode_acessar_horarios(): void
    {
        $usuarioInativo = User::factory()
            ->admin()
            ->inactive()
            ->for($this->estabelecimento)
            ->create();

        $response = $this
            ->actingAs($usuarioInativo)
            ->getJson(
                route('profissionais.horarios.index', $this->profissional)
            );

        $response->assertForbidden();
    }

    public function test_barbeiro_nao_pode_administrar_horarios(): void
    {
        $barbeiro = User::factory()
            ->barbeiro()
            ->active()
            ->for($this->estabelecimento)
            ->create();

        $response = $this
            ->actingAs($barbeiro)
            ->postJson(
                route('profissionais.horarios.store', $this->profissional),
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '08:00',
                    'hora_fim' => '12:00',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseMissing('horarios_profissionais', [
            'profissional_id' => $this->profissional->id,
            'dia_semana' => 1,
        ]);
    }
}