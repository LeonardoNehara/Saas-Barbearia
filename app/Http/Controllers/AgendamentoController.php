<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisponibilidadeRequest;
use App\Http\Requests\IndexAgendamentoRequest;
use App\Http\Requests\StoreAgendamentoRequest;
use App\Models\Agendamento;
use App\Models\BloqueioProfissional;
use App\Models\Profissional;
use App\Models\Servico;
use App\Services\AgendamentoService;
use App\Services\DisponibilidadeService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AgendamentoController extends Controller
{
    public function index(IndexAgendamentoRequest $request): JsonResponse|View
    {
        if (! $request->expectsJson()) {
            $estabelecimento = $request->user()->estabelecimento;
            $profissionais = Profissional::where('estabelecimento_id', $estabelecimento->id)
                ->with('horarios')->orderBy('nome')->get();
            $servicos = Servico::where('estabelecimento_id', $estabelecimento->id)->where('active', true)
                ->with(['profissionais' => fn ($query) => $query->where('profissionais.estabelecimento_id', $estabelecimento->id)->where('profissionais.active', true)])
                ->orderBy('nome')->get();
            $agendaConfig = [
                'feedUrl' => route('agendamentos.index'),
                'storeUrl' => route('agendamentos.store'),
                'availabilityUrl' => route('agendamentos.disponibilidade'),
                'timezone' => $estabelecimento->timezone,
                'now' => CarbonImmutable::now($estabelecimento->timezone)->format('Y-m-d\TH:i:s'),
                'services' => $servicos->map(fn (Servico $servico): array => [
                    'id' => $servico->id, 'name' => $servico->nome,
                    'duration' => $servico->duracao_minutos,
                    'price' => $servico->preco,
                    'professionals' => $servico->profissionais->map(fn (Profissional $profissional): array => ['id' => $profissional->id, 'name' => $profissional->nome])->values(),
                ])->values(),
                'professionals' => $profissionais->map(fn (Profissional $profissional): array => [
                    'id' => $profissional->id,
                    'name' => $profissional->nome,
                    'active' => $profissional->active,
                    'hours' => $profissional->horarios->map(fn ($horario): array => [
                        'daysOfWeek' => [(int) $horario->dia_semana],
                        'startTime' => $horario->hora_inicio, 'endTime' => $horario->hora_fim,
                        'breakStart' => $horario->intervalo_inicio, 'breakEnd' => $horario->intervalo_fim,
                    ])->values(),
                ])->values(),
            ];

            return view('agendamentos.index', compact('agendaConfig', 'profissionais', 'servicos'));
        }

        $query = Agendamento::query()
            ->where(
                'estabelecimento_id',
                $request->user()->estabelecimento_id
            )
            ->with([
                'profissional',
                'servico',
            ])
            ->when($request->validated('profissional_id'), fn ($query, $id) => $query->where('profissional_id', $id))
            ->orderBy('inicio');

        if ($request->filled('start')) {
            $start = CarbonImmutable::parse($request->validated('start'));
            $end = CarbonImmutable::parse($request->validated('end'));

            if ($start->diffInDays($end) > 62) {
                throw ValidationException::withMessages(['end' => 'Consulte um período de até 62 dias.']);
            }

            $events = $query->where('inicio', '<', $end->format('Y-m-d H:i:s'))
                ->where('fim', '>', $start->format('Y-m-d H:i:s'))->get()
                ->map(fn (Agendamento $agendamento): array => $this->calendarEvent($agendamento));

            if ($request->filled('profissional_id')) {
                $blocks = BloqueioProfissional::where('profissional_id', $request->validated('profissional_id'))
                    ->whereHas('profissional', fn ($query) => $query->where('estabelecimento_id', $request->user()->estabelecimento_id))
                    ->where('inicio', '<', $end->format('Y-m-d H:i:s'))->where('fim', '>', $start->format('Y-m-d H:i:s'))->get();
                foreach ($blocks as $block) {
                    $events->push([
                        'id' => 'bloqueio-'.$block->id, 'title' => 'Indisponível',
                        'start' => $block->inicio->format('Y-m-d\TH:i:s'), 'end' => $block->fim->format('Y-m-d\TH:i:s'),
                        'display' => 'background', 'backgroundColor' => '#f5c7c7',
                        'classNames' => ['agenda-blocked'],
                        'extendedProps' => ['kind' => 'blocked'],
                    ]);
                }
            }

            return response()->json($events->values());
        }

        $agendamentos = $query->paginate(20);

        return response()->json($agendamentos);
    }

    public function disponibilidade(DisponibilidadeRequest $request, DisponibilidadeService $service): JsonResponse
    {
        abort_if($request->user()->estabelecimento === null, 403);
        $dados = $request->validated();

        return response()->json(['data' => $service->buscar(
            $request->user()->estabelecimento, $dados['profissional_id'], $dados['servico_id'], $dados['data'],
        )]);
    }

    /** @return array<string, mixed> */
    private function calendarEvent(Agendamento $agendamento): array
    {
        return [
            'id' => (string) $agendamento->id,
            'title' => $agendamento->cliente_nome.' · '.$agendamento->servico?->nome,
            'start' => $agendamento->inicio->format('Y-m-d\TH:i:s'),
            'end' => $agendamento->fim->format('Y-m-d\TH:i:s'),
            'classNames' => [$agendamento->status === 'cancelado' ? 'agenda-cancelado' : 'agenda-agendado'],
            'extendedProps' => [
                'detailsUrl' => route('agendamentos.show', $agendamento),
                'cancelUrl' => route('agendamentos.cancelar', $agendamento),
                'cliente' => $agendamento->cliente_nome, 'telefone' => $agendamento->cliente_telefone,
                'servico' => $agendamento->servico?->nome, 'profissional' => $agendamento->profissional?->nome,
                'status' => $agendamento->status, 'observacoes' => $agendamento->observacoes,
                'periodo' => $agendamento->inicio->format('d/m/Y H:i').' – '.$agendamento->fim->format('d/m/Y H:i'),
            ],
        ];
    }

    public function show(
        Request $request,
        Agendamento $agendamento
    ): JsonResponse {
        $this->garantirMesmoEstabelecimento(
            $request,
            $agendamento
        );

        $agendamento->load([
            'profissional',
            'servico',
        ]);

        return response()->json($request->boolean('calendar') ? $this->calendarEvent($agendamento) : $agendamento);
    }

    public function store(
        StoreAgendamentoRequest $request,
        AgendamentoService $service
    ): JsonResponse {
        $agendamento = $service->criar(
            $request->user()->estabelecimento,
            $request->validated()
        );

        $agendamento->load([
            'profissional',
            'servico',
        ]);

        return response()->json(
            $agendamento,
            201
        );
    }

    public function cancelar(
        Request $request,
        Agendamento $agendamento
    ): JsonResponse {
        $this->garantirMesmoEstabelecimento(
            $request,
            $agendamento
        );

        if ($agendamento->status === 'cancelado') {
            return response()->json([
                'message' => 'O agendamento já está cancelado.',
            ], 422);
        }

        $agendamento->update([
            'status' => 'cancelado',
            'cancelado_em' => now(),
        ]);

        return response()->json($agendamento);
    }

    private function garantirMesmoEstabelecimento(
        Request $request,
        Agendamento $agendamento
    ): void {
        if (
            $agendamento->estabelecimento_id
            !== $request->user()->estabelecimento_id
        ) {
            abort(404);
        }
    }
}
