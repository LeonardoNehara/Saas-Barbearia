<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\BloqueioProfissional;
use App\Models\Estabelecimento;
use App\Models\HorarioProfissional;
use App\Models\Profissional;
use App\Models\Servico;
use App\Services\AgendamentoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AgendamentoServiceTest extends TestCase
{
    use RefreshDatabase;

    private Estabelecimento $estabelecimento;

    private Profissional $profissional;

    private Servico $servico;

    private AgendamentoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-20 12:00:00', 'America/Sao_Paulo'));

        $this->estabelecimento = Estabelecimento::factory()->create([
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
                'duracao_minutos' => 30,
                'active' => true,
            ]);

        $this->profissional->servicos()->attach($this->servico->id);

        // 21/09/2026 é segunda-feira.
        HorarioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00:00',
            'hora_fim' => '18:00:00',
        ]);

        $this->service = app(AgendamentoService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function dados(array $alteracoes = []): array
    {
        return array_merge([
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'cliente_nome' => 'Leonardo',
            'cliente_telefone' => '44999999999',
            'inicio' => '2026-09-21 14:00:00',
        ], $alteracoes);
    }

    public function test_cria_agendamento_e_calcula_fim_pela_duracao_do_servico(): void
    {
        $agendamento = $this->service->criar(
            $this->estabelecimento,
            $this->dados(['inicio' => '2026-09-21 14:15:00'])
        );

        $this->assertDatabaseHas('agendamentos', [
            'id' => $agendamento->id,
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'cliente_nome' => 'Leonardo',
            'cliente_telefone' => '44999999999',
            'status' => 'agendado',
        ]);

        $this->assertSame(
            '2026-09-21 14:15:00',
            $agendamento->inicio->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-09-21 14:45:00',
            $agendamento->fim->format('Y-m-d H:i:s')
        );
    }

    public function test_nao_permite_agendamento_fora_da_jornada(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->criar(
            $this->estabelecimento,
            $this->dados([
                'inicio' => '2026-09-21 18:00:00',
            ])
        );
    }

    public function test_servico_precisa_caber_inteiro_na_jornada(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->criar(
            $this->estabelecimento,
            $this->dados([
                'inicio' => '2026-09-21 17:45:00',
            ])
        );
    }

    public function test_nao_permite_agendamento_durante_bloqueio(): void
    {
        BloqueioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'inicio' => '2026-09-21 14:00:00',
            'fim' => '2026-09-21 16:00:00',
            'motivo' => 'Compromisso',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->criar(
            $this->estabelecimento,
            $this->dados()
        );
    }

    public function test_nao_permite_agendamento_que_invade_inicio_de_bloqueio(): void
    {
        BloqueioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'inicio' => '2026-09-21 14:00:00',
            'fim' => '2026-09-21 15:00:00',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->criar(
            $this->estabelecimento,
            $this->dados([
                'inicio' => '2026-09-21 13:45:00',
            ])
        );
    }

    public function test_nao_permite_conflito_com_outro_agendamento(): void
    {
        Agendamento::create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'cliente_nome' => 'Cliente existente',
            'cliente_telefone' => '44988888888',
            'inicio' => '2026-09-21 14:00:00',
            'fim' => '2026-09-21 14:30:00',
            'status' => 'agendado',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->criar(
            $this->estabelecimento,
            $this->dados([
                'inicio' => '2026-09-21 14:15:00',
            ])
        );
    }

    public function test_permite_agendamentos_adjacentes(): void
    {
        Agendamento::create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'cliente_nome' => 'Primeiro cliente',
            'cliente_telefone' => '44988888888',
            'inicio' => '2026-09-21 14:00:00',
            'fim' => '2026-09-21 14:30:00',
            'status' => 'agendado',
        ]);

        $agendamento = $this->service->criar(
            $this->estabelecimento,
            $this->dados([
                'inicio' => '2026-09-21 14:30:00',
            ])
        );

        $this->assertSame(
            '2026-09-21 14:30:00',
            $agendamento->inicio->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-09-21 15:00:00',
            $agendamento->fim->format('Y-m-d H:i:s')
        );

        $this->assertDatabaseCount('agendamentos', 2);
    }

    public function test_agendamento_cancelado_nao_ocupa_horario(): void
    {
        Agendamento::create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'cliente_nome' => 'Cliente cancelado',
            'cliente_telefone' => '44988888888',
            'inicio' => '2026-09-21 14:00:00',
            'fim' => '2026-09-21 14:30:00',
            'status' => 'cancelado',
            'cancelado_em' => '2026-09-20 10:00:00',
        ]);

        $novo = $this->service->criar(
            $this->estabelecimento,
            $this->dados()
        );

        $this->assertSame('agendado', $novo->status);

        $this->assertDatabaseCount('agendamentos', 2);
    }

    public function test_profissional_precisa_realizar_o_servico(): void
    {
        $outroServico = Servico::factory()
            ->for($this->estabelecimento)
            ->create([
                'duracao_minutos' => 30,
                'active' => true,
            ]);

        $this->expectException(ValidationException::class);

        $this->service->criar(
            $this->estabelecimento,
            $this->dados([
                'servico_id' => $outroServico->id,
            ])
        );
    }

    public function test_nao_permite_profissional_de_outro_estabelecimento(): void
    {
        $outroEstabelecimento = Estabelecimento::factory()->create();

        $outroProfissional = Profissional::factory()
            ->for($outroEstabelecimento)
            ->create([
                'active' => true,
            ]);

        $this->expectException(ValidationException::class);

        $this->service->criar(
            $this->estabelecimento,
            $this->dados([
                'profissional_id' => $outroProfissional->id,
            ])
        );
    }

    public function test_nao_permite_servico_de_outro_estabelecimento(): void
    {
        $outroEstabelecimento = Estabelecimento::factory()->create();

        $outroServico = Servico::factory()
            ->for($outroEstabelecimento)
            ->create([
                'duracao_minutos' => 30,
                'active' => true,
            ]);

        $this->expectException(ValidationException::class);

        $this->service->criar(
            $this->estabelecimento,
            $this->dados([
                'servico_id' => $outroServico->id,
            ])
        );
    }

    public function test_nao_permite_agendamento_durante_intervalo_entre_jornadas(): void
    {
        // Remove a jornada contínua criada no setUp.
        $this->profissional->horarios()->delete();

        HorarioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00:00',
            'hora_fim' => '12:00:00',
        ]);

        HorarioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'dia_semana' => 1,
            'hora_inicio' => '13:30:00',
            'hora_fim' => '18:00:00',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->criar(
            $this->estabelecimento,
            $this->dados([
                'inicio' => '2026-09-21 11:45:00',
            ])
        );
    }

    public function test_agendamento_pode_terminar_exatamente_no_final_da_jornada(): void
    {
        $agendamento = $this->service->criar(
            $this->estabelecimento,
            $this->dados([
                'inicio' => '2026-09-21 17:30:00',
            ])
        );

        $this->assertSame(
            '2026-09-21 18:00:00',
            $agendamento->fim->format('Y-m-d H:i:s')
        );
    }

    public function test_nao_permite_agendamento_no_passado(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2026-09-21 14:00:00',
                'America/Sao_Paulo'
            )
        );

        $this->expectException(
            ValidationException::class
        );

        $this->service->criar(
            $this->estabelecimento,
            [
                'profissional_id' => $this->profissional->id,
                'servico_id' => $this->servico->id,
                'cliente_nome' => 'Leonardo',
                'cliente_telefone' => '44999999999',
                'inicio' => '2026-09-21 13:30:00',
            ]
        );
    }
}
