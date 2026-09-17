<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Estabelecimento;
use App\Models\HorarioProfissional;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendamentoTest extends TestCase
{
    use RefreshDatabase;

    private Estabelecimento $estabelecimento;
    private User $user;
    private Profissional $profissional;
    private Servico $servico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estabelecimento = Estabelecimento::factory()->create([
            'timezone' => 'America/Sao_Paulo',
        ]);

        $this->user = User::factory()->create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'active' => true,
        ]);

        $this->profissional = Profissional::factory()
            ->for($this->estabelecimento)
            ->create([
                'active' => true,
            ]);

        $this->servico = Servico::factory()
            ->for($this->estabelecimento)
            ->create([
                'active' => true,
                'duracao_minutos' => 30,
            ]);

        $this->profissional
            ->servicos()
            ->attach($this->servico->id);

        HorarioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00:00',
            'hora_fim' => '18:00:00',
        ]);
    }

    private function payload(array $alteracoes = []): array
    {
        return array_merge([
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'cliente_nome' => 'Leonardo',
            'cliente_telefone' => '44999999999',
            'inicio' => '2026-09-21 14:00:00',
        ], $alteracoes);
    }

    public function test_usuario_autenticado_pode_criar_agendamento(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/agendamentos', $this->payload());

        $response
            ->assertCreated()
            ->assertJsonPath('cliente_nome', 'Leonardo')
            ->assertJsonPath('status', 'agendado');

        $this->assertDatabaseHas('agendamentos', [
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'cliente_nome' => 'Leonardo',
            'status' => 'agendado',
        ]);
    }

    public function test_campos_protegidos_nao_podem_ser_controlados_pelo_request(): void
    {
        $outroEstabelecimento = Estabelecimento::factory()->create();

        $response = $this
            ->actingAs($this->user)
            ->postJson('/agendamentos', $this->payload([
                'estabelecimento_id' => $outroEstabelecimento->id,
                'fim' => '2030-01-01 23:59:59',
                'status' => 'concluido',
                'cancelado_em' => '2030-01-01 10:00:00',
            ]));

        $response->assertCreated();

        $agendamento = Agendamento::latest('id')->firstOrFail();

        $this->assertSame(
            $this->estabelecimento->id,
            $agendamento->estabelecimento_id
        );

        $this->assertSame('agendado', $agendamento->status);

        $this->assertNull($agendamento->cancelado_em);

        $this->assertSame(
            '2026-09-21 14:30:00',
            $agendamento->fim->format('Y-m-d H:i:s')
        );
    }

    public function test_index_mostra_somente_agendamentos_do_estabelecimento(): void
    {
        $outroEstabelecimento = Estabelecimento::factory()->create();

        Agendamento::factory()->create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
        ]);

        $outroProfissional = Profissional::factory()
            ->for($outroEstabelecimento)
            ->create();

        $outroServico = Servico::factory()
            ->for($outroEstabelecimento)
            ->create();

        Agendamento::factory()->create([
            'estabelecimento_id' => $outroEstabelecimento->id,
            'profissional_id' => $outroProfissional->id,
            'servico_id' => $outroServico->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/agendamentos');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_nao_pode_visualizar_agendamento_de_outro_estabelecimento(): void
    {
        $outroEstabelecimento = Estabelecimento::factory()->create();

        $profissional = Profissional::factory()
            ->for($outroEstabelecimento)
            ->create();

        $servico = Servico::factory()
            ->for($outroEstabelecimento)
            ->create();

        $agendamento = Agendamento::factory()->create([
            'estabelecimento_id' => $outroEstabelecimento->id,
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
        ]);

        $this
            ->actingAs($this->user)
            ->getJson("/agendamentos/{$agendamento->id}")
            ->assertNotFound();
    }

    public function test_pode_cancelar_agendamento(): void
    {
        $agendamento = Agendamento::factory()->create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'status' => 'agendado',
        ]);

        $this
            ->actingAs($this->user)
            ->patchJson("/agendamentos/{$agendamento->id}/cancelar")
            ->assertOk()
            ->assertJsonPath('status', 'cancelado');

        $agendamento->refresh();

        $this->assertSame('cancelado', $agendamento->status);
        $this->assertNotNull($agendamento->cancelado_em);
    }

    public function test_nao_pode_cancelar_agendamento_de_outro_estabelecimento(): void
    {
        $outroEstabelecimento = Estabelecimento::factory()->create();

        $profissional = Profissional::factory()
            ->for($outroEstabelecimento)
            ->create();

        $servico = Servico::factory()
            ->for($outroEstabelecimento)
            ->create();

        $agendamento = Agendamento::factory()->create([
            'estabelecimento_id' => $outroEstabelecimento->id,
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
        ]);

        $this
            ->actingAs($this->user)
            ->patchJson("/agendamentos/{$agendamento->id}/cancelar")
            ->assertNotFound();
    }

    public function test_visitante_nao_pode_acessar_agendamentos_administrativos(): void
    {
        $this
            ->getJson('/agendamentos')
            ->assertUnauthorized();

        $this
            ->postJson('/agendamentos', $this->payload())
            ->assertUnauthorized();
    }

    public function test_usuario_inativo_nao_pode_acessar_agendamentos(): void
    {
        $this->user->active = false;
        $this->user->save();
        $this->user->refresh();

        $this->assertFalse((bool) $this->user->active);

        $this
            ->actingAs($this->user)
            ->getJson('/agendamentos')
            ->assertForbidden();
    }
}