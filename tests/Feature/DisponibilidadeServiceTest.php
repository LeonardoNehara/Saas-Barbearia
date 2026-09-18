<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\BloqueioProfissional;
use App\Models\Estabelecimento;
use App\Models\HorarioProfissional;
use App\Models\Profissional;
use App\Models\Servico;
use App\Services\DisponibilidadeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DisponibilidadeServiceTest extends TestCase
{
    use RefreshDatabase;

    private Estabelecimento $estabelecimento;

    private Profissional $profissional;

    private Servico $servico;

    private DisponibilidadeService $service;

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
                'active' => true,
                'duracao_minutos' => 30,
            ]);

        $this->profissional
            ->servicos()
            ->attach($this->servico->id);

        $this->service = app(DisponibilidadeService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function criarHorario(
        int $diaSemana,
        string $inicio,
        string $fim
    ): void {
        HorarioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => $inicio,
            'hora_fim' => $fim,
        ]);
    }

    public function test_retorna_slots_disponiveis_da_jornada(): void
    {
        // 21/09/2026 = segunda-feira
        $this->criarHorario(
            1,
            '08:00:00',
            '10:00:00'
        );

        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );

        $this->assertSame([
            [
                'inicio' => '2026-09-21 08:00:00',
                'fim' => '2026-09-21 08:30:00',
            ],
            [
                'inicio' => '2026-09-21 08:15:00',
                'fim' => '2026-09-21 08:45:00',
            ],
            [
                'inicio' => '2026-09-21 08:30:00',
                'fim' => '2026-09-21 09:00:00',
            ],
            [
                'inicio' => '2026-09-21 08:45:00',
                'fim' => '2026-09-21 09:15:00',
            ],
            [
                'inicio' => '2026-09-21 09:00:00',
                'fim' => '2026-09-21 09:30:00',
            ],
            [
                'inicio' => '2026-09-21 09:15:00',
                'fim' => '2026-09-21 09:45:00',
            ],
            [
                'inicio' => '2026-09-21 09:30:00',
                'fim' => '2026-09-21 10:00:00',
            ],
        ], $slots);
    }

    public function test_nao_retorna_slots_que_conflitam_com_agendamento(): void
    {
        $this->criarHorario(
            1,
            '08:00:00',
            '10:00:00'
        );

        Agendamento::factory()->create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'inicio' => '2026-09-21 08:30:00',
            'fim' => '2026-09-21 09:00:00',
            'status' => 'agendado',
        ]);

        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );

        $this->assertSame([
            '2026-09-21 08:00:00',
            '2026-09-21 09:00:00',
            '2026-09-21 09:15:00',
            '2026-09-21 09:30:00',
        ], array_column($slots, 'inicio'));

        $this->assertFalse(
            collect($slots)->contains(
                fn (array $slot) => $slot['inicio'] === '2026-09-21 08:30:00'
            )
        );
    }

    public function test_nao_retorna_slots_que_conflitam_com_bloqueio(): void
    {
        $this->criarHorario(
            1,
            '08:00:00',
            '10:00:00'
        );

        BloqueioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'inicio' => '2026-09-21 09:00:00',
            'fim' => '2026-09-21 09:30:00',
            'motivo' => 'Consulta',
        ]);

        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );

        $this->assertSame([
            '2026-09-21 08:00:00',
            '2026-09-21 08:15:00',
            '2026-09-21 08:30:00',
            '2026-09-21 09:30:00',
        ], array_column($slots, 'inicio'));

        $this->assertFalse(
            collect($slots)->contains(
                fn (array $slot) => $slot['inicio'] === '2026-09-21 09:00:00'
            )
        );
    }

    public function test_respeita_intervalo_entre_duas_jornadas(): void
    {
        $this->criarHorario(
            1,
            '08:00:00',
            '12:00:00'
        );

        $this->criarHorario(
            1,
            '13:00:00',
            '14:00:00'
        );

        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );

        $inicios = collect($slots)->pluck('inicio');

        $this->assertFalse(
            $inicios->contains('2026-09-21 12:00:00')
        );

        $this->assertFalse(
            $inicios->contains('2026-09-21 12:30:00')
        );

        $this->assertTrue(
            $inicios->contains('2026-09-21 13:00:00')
        );
    }

    public function test_servico_precisa_caber_inteiro_na_jornada(): void
    {
        $this->servico->update([
            'duracao_minutos' => 60,
        ]);

        $this->criarHorario(
            1,
            '08:00:00',
            '09:30:00'
        );

        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );

        $this->assertSame([
            [
                'inicio' => '2026-09-21 08:00:00',
                'fim' => '2026-09-21 09:00:00',
            ],
            [
                'inicio' => '2026-09-21 08:15:00',
                'fim' => '2026-09-21 09:15:00',
            ],
            [
                'inicio' => '2026-09-21 08:30:00',
                'fim' => '2026-09-21 09:30:00',
            ],
        ], $slots);
    }

    public function test_agendamento_cancelado_nao_remove_disponibilidade(): void
    {
        $this->criarHorario(
            1,
            '08:00:00',
            '09:00:00'
        );

        Agendamento::factory()->create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'inicio' => '2026-09-21 08:00:00',
            'fim' => '2026-09-21 08:30:00',
            'status' => 'cancelado',
        ]);

        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );

        $this->assertCount(3, $slots);
    }

    public function test_dia_sem_jornada_retorna_lista_vazia(): void
    {
        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );

        $this->assertSame([], $slots);
    }

    public function test_profissional_de_outro_estabelecimento_e_rejeitado(): void
    {
        $outroEstabelecimento =
            Estabelecimento::factory()->create();

        $outroProfissional = Profissional::factory()
            ->for($outroEstabelecimento)
            ->create([
                'active' => true,
            ]);

        $this->expectException(
            ValidationException::class
        );

        $this->service->buscar(
            $this->estabelecimento,
            $outroProfissional->id,
            $this->servico->id,
            '2026-09-21'
        );
    }

    public function test_profissional_precisa_realizar_servico(): void
    {
        $outroServico = Servico::factory()
            ->for($this->estabelecimento)
            ->create([
                'active' => true,
            ]);

        $this->expectException(
            ValidationException::class
        );

        $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $outroServico->id,
            '2026-09-21'
        );
    }

    public function test_servico_longo_nao_pode_atravessar_agendamento(): void
    {
        $this->servico->update([
            'duracao_minutos' => 60,
        ]);

        $this->criarHorario(
            1,
            '08:00:00',
            '11:00:00'
        );

        Agendamento::factory()->create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'inicio' => '2026-09-21 09:00:00',
            'fim' => '2026-09-21 09:30:00',
            'status' => 'agendado',
        ]);

        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );

        $inicios = collect($slots)->pluck('inicio');

        $this->assertTrue(
            $inicios->contains('2026-09-21 08:00:00')
        );

        $this->assertFalse(
            $inicios->contains('2026-09-21 08:30:00')
        );

        $this->assertFalse(
            $inicios->contains('2026-09-21 09:00:00')
        );

        $this->assertTrue(
            $inicios->contains('2026-09-21 09:30:00')
        );
    }

    public function test_servico_longo_nao_pode_atravessar_bloqueio(): void
    {
        $this->servico->update([
            'duracao_minutos' => 60,
        ]);

        $this->criarHorario(
            1,
            '08:00:00',
            '11:00:00'
        );

        BloqueioProfissional::create([
            'profissional_id' => $this->profissional->id,
            'inicio' => '2026-09-21 09:00:00',
            'fim' => '2026-09-21 09:30:00',
            'motivo' => 'Compromisso',
        ]);

        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );

        $inicios = collect($slots)->pluck('inicio');

        $this->assertTrue(
            $inicios->contains('2026-09-21 08:00:00')
        );

        $this->assertFalse(
            $inicios->contains('2026-09-21 08:30:00')
        );

        $this->assertFalse(
            $inicios->contains('2026-09-21 09:00:00')
        );

        $this->assertTrue(
            $inicios->contains('2026-09-21 09:30:00')
        );
    }

    public function test_profissional_inativo_nao_possui_disponibilidade(): void
    {
        $this->profissional->active = false;
        $this->profissional->save();

        $this->expectException(
            ValidationException::class
        );

        $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );
    }

    public function test_servico_inativo_nao_possui_disponibilidade(): void
    {
        $this->servico->active = false;
        $this->servico->save();

        $this->expectException(
            ValidationException::class
        );

        $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );
    }

    public function test_nao_retorna_horarios_que_ja_passaram_no_dia_atual(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2026-09-21 14:20:00',
                'America/Sao_Paulo'
            )
        );

        $this->criarHorario(
            1,
            '13:00:00',
            '16:00:00'
        );

        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-21'
        );

        $inicios = collect($slots)->pluck('inicio')->all();

        $this->assertSame([
            '2026-09-21 14:30:00',
            '2026-09-21 14:45:00',
            '2026-09-21 15:00:00',
            '2026-09-21 15:15:00',
            '2026-09-21 15:30:00',
        ], $inicios);
    }

    public function test_data_passada_nao_retorna_disponibilidade(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2026-09-21 14:20:00',
                'America/Sao_Paulo'
            )
        );

        // 14/09/2026 também é segunda-feira.
        $this->criarHorario(
            1,
            '08:00:00',
            '10:00:00'
        );

        $slots = $this->service->buscar(
            $this->estabelecimento,
            $this->profissional->id,
            $this->servico->id,
            '2026-09-14'
        );

        $this->assertSame([], $slots);
    }
}
