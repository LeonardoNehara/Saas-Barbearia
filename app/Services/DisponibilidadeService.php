<?php

namespace App\Services;

use App\Models\Estabelecimento;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class DisponibilidadeService
{
    private const INTERVALO_SLOTS_MINUTOS = 15;

    public function buscar(
        Estabelecimento $estabelecimento,
        int $profissionalId,
        int $servicoId,
        string $data
    ): array {
        $profissional = Profissional::query()
            ->where('id', $profissionalId)
            ->where('estabelecimento_id', $estabelecimento->id)
            ->where('active', true)
            ->first();

        if (! $profissional) {
            throw ValidationException::withMessages([
                'profissional_id' => 'Profissional inválido ou indisponível.',
            ]);
        }

        $servico = Servico::query()
            ->where('id', $servicoId)
            ->where('estabelecimento_id', $estabelecimento->id)
            ->where('active', true)
            ->first();

        if (! $servico) {
            throw ValidationException::withMessages([
                'servico_id' => 'Serviço inválido ou indisponível.',
            ]);
        }

        if (! $profissional->servicos()->whereKey($servico->id)->exists()) {
            throw ValidationException::withMessages([
                'servico_id' => 'Este profissional não realiza o serviço informado.',
            ]);
        }

        $dia = Carbon::parse(
            $data,
            $estabelecimento->timezone
        )->startOfDay();

        $agora = Carbon::now(
            $estabelecimento->timezone
        );

        $horarios = $profissional->horarios()
            ->where('dia_semana', $dia->dayOfWeek)
            ->orderBy('hora_inicio')
            ->get();

        if ($horarios->isEmpty()) {
            return [];
        }

        $inicioDia = $dia->copy();
        $fimDia = $dia->copy()->endOfDay();

        $bloqueios = $profissional->bloqueios()
            ->where('inicio', '<', $fimDia->format('Y-m-d H:i:s'))
            ->where('fim', '>', $inicioDia->format('Y-m-d H:i:s'))
            ->get();

        $agendamentos = $profissional->agendamentos()
            ->where('status', '!=', 'cancelado')
            ->where('inicio', '<', $fimDia->format('Y-m-d H:i:s'))
            ->where('fim', '>', $inicioDia->format('Y-m-d H:i:s'))
            ->get();

        $slots = [];

        foreach ($horarios as $horario) {
            $inicioJornada = Carbon::parse(
                $dia->format('Y-m-d').' '.$horario->hora_inicio,
                $estabelecimento->timezone
            );

            $fimJornada = Carbon::parse(
                $dia->format('Y-m-d').' '.$horario->hora_fim,
                $estabelecimento->timezone
            );

            $inicioSlot = $inicioJornada->copy()
                ->ceilMinutes(self::INTERVALO_SLOTS_MINUTOS);

            while (true) {
                $fimSlot = $inicioSlot->copy()
                    ->addMinutes($servico->duracao_minutos);

                if ($fimSlot->gt($fimJornada)) {
                    break;
                }

                $slotJaPassou = $inicioSlot->lt($agora);

                if (
                    ! $slotJaPassou
                    && ! $this->conflitaComPeriodos(
                        $inicioSlot,
                        $fimSlot,
                        $bloqueios
                    )
                    && ! $this->conflitaComPeriodos(
                        $inicioSlot,
                        $fimSlot,
                        $agendamentos
                    )
                ) {
                    $slots[] = [
                        'inicio' => $inicioSlot->format('Y-m-d H:i:s'),
                        'fim' => $fimSlot->format('Y-m-d H:i:s'),
                    ];
                }

                $inicioSlot->addMinutes(
                    self::INTERVALO_SLOTS_MINUTOS
                );
            }
        }

        return $slots;
    }

    private function conflitaComPeriodos(
        Carbon $inicio,
        Carbon $fim,
        iterable $periodos
    ): bool {
        foreach ($periodos as $periodo) {
            $inicioPeriodo = Carbon::parse(
                $periodo->inicio->format('Y-m-d H:i:s'),
                $inicio->timezone
            );

            $fimPeriodo = Carbon::parse(
                $periodo->fim->format('Y-m-d H:i:s'),
                $inicio->timezone
            );

            if (
                $inicio->lt($fimPeriodo)
                && $fim->gt($inicioPeriodo)
            ) {
                return true;
            }
        }

        return false;
    }
}
