@php
    $dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
@endphp
<section class="mt-6">
    <h3 class="font-semibold">Horários semanais</h3>
    <p class="mt-1 text-xs text-muted">Defina as faixas de atendimento e o intervalo opcional de cada dia.</p>
    <form method="POST" action="{{ route('profissionais.horarios.store', $profissional) }}" data-busy-form class="mt-5 grid gap-4 sm:grid-cols-3">
        @csrf
        @include('profissionais.partials.horario-fields', ['horario' => null])
        <div class="flex justify-end sm:col-span-3"><button type="submit" class="admin-button" data-busy-label>Adicionar horário</button></div>
    </form>
    <div class="mt-5 divide-y divide-slate-100">
        @forelse ($profissional->horarios as $horario)
            <div class="py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <x-icon name="clock" class="size-4 text-brand-dark" />
                        <div class="text-sm">
                            <p><span class="font-medium">{{ $dias[$horario->dia_semana] }}</span> <span class="ml-2 text-muted">{{ substr($horario->hora_inicio, 0, 5) }} – {{ substr($horario->hora_fim, 0, 5) }}</span></p>
                            <p class="mt-1 text-xs text-muted">{{ $horario->intervalo_inicio ? 'Intervalo: ' . substr($horario->intervalo_inicio, 0, 5) . ' – ' . substr($horario->intervalo_fim, 0, 5) : 'Sem intervalo' }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('profissionais.horarios.destroy', [$profissional, $horario]) }}" data-busy-form data-confirm="Remover este horário?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm text-red-700 hover:bg-red-50">Remover</button>
                    </form>
                </div>
                <details class="mt-3" @if (old('form_context') === 'horario-profissional-' . $profissional->id && (string) old('horario_id') === (string) $horario->id) open @endif>
                    <summary class="cursor-pointer text-sm font-medium text-brand-dark">Editar horário e intervalo</summary>
                    <form method="POST" action="{{ route('profissionais.horarios.update', [$profissional, $horario]) }}" data-busy-form class="mt-4 grid gap-4 sm:grid-cols-3">
                        @csrf
                        @method('PUT')
                        @include('profissionais.partials.horario-fields', ['horario' => $horario])
                        <div class="flex justify-end sm:col-span-3"><button type="submit" class="admin-button" data-busy-label>Salvar horário</button></div>
                    </form>
                </details>
            </div>
        @empty
            <p class="py-4 text-sm text-muted">Nenhum horário cadastrado.</p>
        @endforelse
    </div>
</section>
