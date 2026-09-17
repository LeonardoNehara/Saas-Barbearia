<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\HorarioProfissional;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AgendamentoPublicoTest extends TestCase
{
    use RefreshDatabase;

    private Estabelecimento $estabelecimento;
    private Profissional $profissional;
    private Servico $servico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estabelecimento = Estabelecimento::factory()->create([
            'slug' => 'barbearia-teste',
            'active' => true,
            'timezone' => 'America/Sao_Paulo',
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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

    public function test_visitante_pode_criar_agendamento(): void
    {
        $response = $this->postJson(
            '/publico/barbearia-teste/agendamentos',
            $this->payload()
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Agendamento realizado com sucesso.'
            )
            ->assertJsonPath(
                'data.cliente_nome',
                'Leonardo'
            )
            ->assertJsonPath(
                'data.status',
                'agendado'
            );

        $this->assertDatabaseHas('agendamentos', [
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'cliente_nome' => 'Leonardo',
            'cliente_telefone' => '44999999999',
            'status' => 'agendado',
        ]);
    }

    public function test_nao_permite_agendar_horario_ja_ocupado(): void
    {
        $this->postJson(
            '/publico/barbearia-teste/agendamentos',
            $this->payload()
        )->assertCreated();

        $this->postJson(
            '/publico/barbearia-teste/agendamentos',
            $this->payload()
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('inicio');

        $this->assertDatabaseCount(
            'agendamentos',
            1
        );
    }

    public function test_estabelecimento_inativo_nao_aceita_agendamento(): void
    {
        $this->estabelecimento->active = false;
        $this->estabelecimento->save();

        $this->postJson(
            '/publico/barbearia-teste/agendamentos',
            $this->payload()
        )->assertNotFound();

        $this->assertDatabaseCount(
            'agendamentos',
            0
        );
    }

    public function test_slug_inexistente_retorna_404(): void
    {
        $this->postJson(
            '/publico/nao-existe/agendamentos',
            $this->payload()
        )->assertNotFound();

        $this->assertDatabaseCount(
            'agendamentos',
            0
        );
    }

    public function test_campos_obrigatorios_sao_validados(): void
    {
        $this->postJson(
            '/publico/barbearia-teste/agendamentos',
            []
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'profissional_id',
                'servico_id',
                'cliente_nome',
                'cliente_telefone',
                'inicio',
            ]);

        $this->assertDatabaseCount(
            'agendamentos',
            0
        );
    }

    public function test_cliente_nao_pode_controlar_campos_protegidos(): void
    {
        $outroEstabelecimento =
            Estabelecimento::factory()->create();

        $response = $this->postJson(
            '/publico/barbearia-teste/agendamentos',
            $this->payload([
                'estabelecimento_id' => $outroEstabelecimento->id,
                'fim' => '2030-01-01 23:59:59',
                'status' => 'concluido',
                'cancelado_em' => '2030-01-01 10:00:00',
            ])
        );

        $response->assertCreated();

        $agendamento = $this->estabelecimento
            ->agendamentos()
            ->firstOrFail();

        $this->assertSame(
            $this->estabelecimento->id,
            $agendamento->estabelecimento_id
        );

        $this->assertSame(
            'agendado',
            $agendamento->status
        );

        $this->assertNull(
            $agendamento->cancelado_em
        );

        $this->assertSame(
            '2026-09-21 14:30:00',
            $agendamento->fim->format('Y-m-d H:i:s')
        );
    }

    public function test_visitante_nao_pode_agendar_horario_no_passado(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2026-09-21 14:00:00',
                'America/Sao_Paulo'
            )
        );

        $response = $this->postJson(
            '/publico/barbearia-teste/agendamentos',
            $this->payload([
                'inicio' => '2026-09-21 13:30:00',
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('inicio');

        $this->assertDatabaseCount(
            'agendamentos',
            0
        );
    }
}