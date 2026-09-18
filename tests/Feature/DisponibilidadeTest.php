<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\HorarioProfissional;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisponibilidadeTest extends TestCase
{
    use RefreshDatabase;

    private Estabelecimento $estabelecimento;

    private Profissional $profissional;

    private Servico $servico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-20 12:00:00', 'America/Sao_Paulo'));

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
            'hora_fim' => '10:00:00',
        ]);
    }

    public function test_visitante_pode_consultar_disponibilidade(): void
    {
        $response = $this->getJson(
            '/publico/barbearia-teste/disponibilidade?'.
            http_build_query([
                'profissional_id' => $this->profissional->id,
                'servico_id' => $this->servico->id,
                'data' => '2026-09-21',
            ])
        );

        $response
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('data.1.inicio', '2026-09-21 08:15:00')
            ->assertJsonPath('data.1.fim', '2026-09-21 08:45:00')
            ->assertJsonPath(
                'data.0.inicio',
                '2026-09-21 08:00:00'
            );
    }

    public function test_estabelecimento_inativo_nao_tem_disponibilidade_publica(): void
    {
        $this->estabelecimento->active = false;
        $this->estabelecimento->save();

        $this->getJson(
            '/publico/barbearia-teste/disponibilidade?'.
            http_build_query([
                'profissional_id' => $this->profissional->id,
                'servico_id' => $this->servico->id,
                'data' => '2026-09-21',
            ])
        )->assertNotFound();
    }

    public function test_slug_inexistente_retorna_404(): void
    {
        $this->getJson(
            '/publico/nao-existe/disponibilidade?'.
            http_build_query([
                'profissional_id' => $this->profissional->id,
                'servico_id' => $this->servico->id,
                'data' => '2026-09-21',
            ])
        )->assertNotFound();
    }

    public function test_parametros_obrigatorios_sao_validados(): void
    {
        $this
            ->getJson(
                '/publico/barbearia-teste/disponibilidade'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'profissional_id',
                'servico_id',
                'data',
            ]);
    }

    public function test_data_deve_estar_no_formato_correto(): void
    {
        $this->getJson(
            '/publico/barbearia-teste/disponibilidade?'.
            http_build_query([
                'profissional_id' => $this->profissional->id,
                'servico_id' => $this->servico->id,
                'data' => '21/09/2026',
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('data');
    }
}
