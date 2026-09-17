<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Profissional;

class EstabelecimentoPublicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_pode_visualizar_estabelecimento(): void
    {
        $estabelecimento = Estabelecimento::factory()->create([
            'nome' => 'Barbearia Teste',
            'slug' => 'barbearia-teste',
            'active' => true,
        ]);

        $this->getJson(
            '/publico/barbearia-teste'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.nome',
                'Barbearia Teste'
            )
            ->assertJsonPath(
                'data.slug',
                'barbearia-teste'
            );
    }

    public function test_estabelecimento_inativo_nao_fica_publico(): void
    {
        Estabelecimento::factory()->create([
            'slug' => 'barbearia-inativa',
            'active' => false,
        ]);

        $this->getJson(
            '/publico/barbearia-inativa'
        )->assertNotFound();
    }

    public function test_retorna_apenas_servicos_ativos(): void
    {
        $estabelecimento = Estabelecimento::factory()->create([
            'slug' => 'barbearia-teste',
            'active' => true,
        ]);

        $ativo = Servico::factory()
            ->for($estabelecimento)
            ->create([
                'nome' => 'Corte Masculino',
                'active' => true,
            ]);

        Servico::factory()
            ->for($estabelecimento)
            ->create([
                'nome' => 'Serviço desativado',
                'active' => false,
            ]);

        $response = $this->getJson(
            '/publico/barbearia-teste/servicos'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $ativo->id
            )
            ->assertJsonPath(
                'data.0.nome',
                'Corte Masculino'
            );
    }

    public function test_nao_retorna_servicos_de_outro_estabelecimento(): void
    {
        $estabelecimento = Estabelecimento::factory()->create([
            'slug' => 'barbearia-a',
        ]);

        $outro = Estabelecimento::factory()->create([
            'slug' => 'barbearia-b',
        ]);

        Servico::factory()
            ->for($estabelecimento)
            ->create([
                'nome' => 'Corte A',
                'active' => true,
            ]);

        Servico::factory()
            ->for($outro)
            ->create([
                'nome' => 'Corte B',
                'active' => true,
            ]);

        $response = $this->getJson(
            '/publico/barbearia-a/servicos'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.nome',
                'Corte A'
            );
    }

    public function test_slug_inexistente_retorna_404(): void
    {
        $this->getJson(
            '/publico/nao-existe'
        )->assertNotFound();
    }

    public function test_retorna_profissionais_ativos_que_realizam_o_servico(): void
    {
        $estabelecimento = Estabelecimento::factory()->create([
            'slug' => 'barbearia-teste',
            'active' => true,
        ]);

        $servico = Servico::factory()
            ->for($estabelecimento)
            ->create([
                'active' => true,
            ]);

        $profissional = Profissional::factory()
            ->for($estabelecimento)
            ->create([
                'nome' => 'Carlos',
                'active' => true,
            ]);

        $profissional->servicos()->attach(
            $servico->id
        );

        $response = $this->getJson(
            "/publico/barbearia-teste/servicos/{$servico->id}/profissionais"
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $profissional->id
            )
            ->assertJsonPath(
                'data.0.nome',
                'Carlos'
            );
    }

    public function test_nao_retorna_profissional_inativo(): void
    {
        $estabelecimento = Estabelecimento::factory()->create([
            'slug' => 'barbearia-teste',
            'active' => true,
        ]);

        $servico = Servico::factory()
            ->for($estabelecimento)
            ->create([
                'active' => true,
            ]);

        $profissional = Profissional::factory()
            ->for($estabelecimento)
            ->create([
                'active' => false,
            ]);

        $profissional->servicos()->attach(
            $servico->id
        );

        $this->getJson(
            "/publico/barbearia-teste/servicos/{$servico->id}/profissionais"
        )
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_nao_retorna_profissional_que_nao_realiza_o_servico(): void
    {
        $estabelecimento = Estabelecimento::factory()->create([
            'slug' => 'barbearia-teste',
            'active' => true,
        ]);

        $servico = Servico::factory()
            ->for($estabelecimento)
            ->create([
                'active' => true,
            ]);

        Profissional::factory()
            ->for($estabelecimento)
            ->create([
                'active' => true,
            ]);

        $this->getJson(
            "/publico/barbearia-teste/servicos/{$servico->id}/profissionais"
        )
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_nao_acessa_servico_de_outro_estabelecimento(): void
    {
        $estabelecimentoA = Estabelecimento::factory()->create([
            'slug' => 'barbearia-a',
            'active' => true,
        ]);

        $estabelecimentoB = Estabelecimento::factory()->create([
            'slug' => 'barbearia-b',
            'active' => true,
        ]);

        $servicoB = Servico::factory()
            ->for($estabelecimentoB)
            ->create([
                'active' => true,
            ]);

        $this->getJson(
            "/publico/barbearia-a/servicos/{$servicoB->id}/profissionais"
        )->assertNotFound();
    }

    public function test_servico_inativo_nao_fica_disponivel_publicamente(): void
    {
        $estabelecimento = Estabelecimento::factory()->create([
            'slug' => 'barbearia-teste',
            'active' => true,
        ]);

        $servico = Servico::factory()
            ->for($estabelecimento)
            ->create([
                'active' => false,
            ]);

        $this->getJson(
            "/publico/barbearia-teste/servicos/{$servico->id}/profissionais"
        )->assertNotFound();
    }
}