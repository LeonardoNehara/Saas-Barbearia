<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Estabelecimento;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class AgendamentoService
{
    public function criar(
        Estabelecimento $estabelecimento,
        array $dados
    ): Agendamento {
        return DB::transaction(function () use ($estabelecimento, $dados) {
            $profissional = Profissional::query()
                ->where('id', $dados['profissional_id'])
                ->where('estabelecimento_id', $estabelecimento->id)
                ->where('active', true)
                ->lockForUpdate()
                ->first();

            if (!$profissional) {
                throw ValidationException::withMessages([
                    'profissional_id' => 'Profissional inválido ou indisponível.',
                ]);
            }

            $servico = Servico::query()
                ->where('id', $dados['servico_id'])
                ->where('estabelecimento_id', $estabelecimento->id)
                ->where('active', true)
                ->first();

            if (!$servico) {
                throw ValidationException::withMessages([
                    'servico_id' => 'Serviço inválido ou indisponível.',
                ]);
            }

            $executaServico = $profissional->servicos()
                ->whereKey($servico->id)
                ->exists();

            if (!$executaServico) {
                throw ValidationException::withMessages([
                    'servico_id' => 'Este profissional não realiza o serviço informado.',
                ]);
            }

            $inicio = Carbon::parse(
                $dados['inicio'],
                $estabelecimento->timezone
            );

            $fim = $inicio->copy()
                ->addMinutes($servico->duracao_minutos);

            $agora = Carbon::now(
                $estabelecimento->timezone
            );

            if ($inicio->lt($agora)) {
                throw ValidationException::withMessages([
                    'inicio' => 'Não é possível realizar um agendamento no passado.',
                ]);
            }

            $this->validarHorarioTrabalho(
                $profissional,
                $inicio,
                $fim
            );

            $this->validarBloqueios(
                $profissional,
                $inicio,
                $fim
            );

            $this->validarConflitoAgendamento(
                $profissional,
                $inicio,
                $fim
            );

            return Agendamento::create([
                'estabelecimento_id' => $estabelecimento->id,
                'profissional_id' => $profissional->id,
                'servico_id' => $servico->id,
                'cliente_nome' => $dados['cliente_nome'],
                'cliente_telefone' => $dados['cliente_telefone'],
                'inicio' => $inicio,
                'fim' => $fim,
                'status' => 'agendado',
                'observacoes' => $dados['observacoes'] ?? null,
            ]);
        });
    }

    private function validarHorarioTrabalho(
        Profissional $profissional,
        Carbon $inicio,
        Carbon $fim
    ): void {
        $diaSemana = $inicio->dayOfWeek;

        $dentroDoHorario = $profissional->horarios()
            ->where('dia_semana', $diaSemana)
            ->where('hora_inicio', '<=', $inicio->format('H:i:s'))
            ->where('hora_fim', '>=', $fim->format('H:i:s'))
            ->exists();

        if (!$dentroDoHorario) {
            throw ValidationException::withMessages([
                'inicio' => 'O horário está fora da jornada do profissional.',
            ]);
        }
    }

    private function validarBloqueios(
        Profissional $profissional,
        Carbon $inicio,
        Carbon $fim
    ): void {
        $bloqueado = $profissional->bloqueios()
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->exists();

        if ($bloqueado) {
            throw ValidationException::withMessages([
                'inicio' => 'O profissional está indisponível neste período.',
            ]);
        }
    }

    private function validarConflitoAgendamento(
        Profissional $profissional,
        Carbon $inicio,
        Carbon $fim
    ): void {
        $conflito = $profissional->agendamentos()
            ->where('status', '!=', 'cancelado')
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->exists();

        if ($conflito) {
            throw ValidationException::withMessages([
                'inicio' => 'Este horário não está mais disponível.',
            ]);
        }
    }
}