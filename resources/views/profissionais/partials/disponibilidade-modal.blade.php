@php
    $dias = [
        'Domingo',
        'Segunda-feira',
        'Terça-feira',
        'Quarta-feira',
        'Quinta-feira',
        'Sexta-feira',
        'Sábado',
    ];

    $horarioContext = 'horario-profissional-' . $profissional->id;
    $bloqueioContext = 'bloqueio-profissional-' . $profissional->id;

    $hasHorarioErrors = old('form_context') === $horarioContext;
    $hasBloqueioErrors = old('form_context') === $bloqueioContext;
@endphp

<dialog
    id="modal-disponibilidade-profissional-{{ $profissional->id }}"
    class="admin-dialog"
    aria-labelledby="disponibilidade-title-{{ $profissional->id }}"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2
                id="disponibilidade-title-{{ $profissional->id }}"
                class="text-xl font-bold"
            >
                Horários e bloqueios
            </h2>

            <p class="mt-1 text-sm text-muted">
                {{ $profissional->nome }}
            </p>
        </div>

        <button
            type="button"
            class="admin-secondary"
            aria-label="Fechar"
            onclick="document.getElementById('modal-disponibilidade-profissional-{{ $profissional->id }}').close()"
        >
            <x-icon name="close" class="size-4" />
        </button>
    </div>

    {{-- HORÁRIOS --}}
    <section class="mt-6">
        <div>
            <h3 class="font-semibold">
                Horários semanais
            </h3>

            <p class="mt-1 text-xs text-muted">
                Defina as faixas de atendimento deste profissional.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('profissionais.horarios.store', $profissional) }}"
            data-busy-form
            class="mt-5 grid gap-4 sm:grid-cols-3"
        >
            @csrf

            <input
                type="hidden"
                name="form_context"
                value="{{ $horarioContext }}"
            >

            <div>
                <label
                    for="dia-semana-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Dia
                </label>

                <select
                    id="dia-semana-{{ $profissional->id }}"
                    name="dia_semana"
                    required
                    class="admin-input"
                >
                    @foreach ($dias as $numero => $dia)
                        <option
                            value="{{ $numero }}"
                            @selected(
                                (string) (
                                    $hasHorarioErrors
                                        ? old('dia_semana', 1)
                                        : 1
                                ) === (string) $numero
                            )
                        >
                            {{ $dia }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="hora-inicio-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Início
                </label>

                <input
                    id="hora-inicio-{{ $profissional->id }}"
                    name="hora_inicio"
                    type="time"
                    step="900"
                    value="{{ $hasHorarioErrors ? old('hora_inicio') : '' }}"
                    required
                    class="admin-input"
                >

                @if ($hasHorarioErrors)
                    @error('hora_inicio')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div>
                <label
                    for="hora-fim-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Fim
                </label>

                <input
                    id="hora-fim-{{ $profissional->id }}"
                    name="hora_fim"
                    type="time"
                    step="900"
                    value="{{ $hasHorarioErrors ? old('hora_fim') : '' }}"
                    required
                    class="admin-input"
                >

                @if ($hasHorarioErrors)
                    @error('hora_fim')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div class="sm:col-span-3 flex justify-end">
                <button
                    type="submit"
                    class="admin-button"
                    data-busy-label
                >
                    Adicionar horário
                </button>
            </div>
        </form>

        <div class="mt-5 divide-y divide-slate-100">
            @forelse ($profissional->horarios as $horario)
                <div
                    class="flex flex-wrap items-center justify-between gap-3 py-3"
                >
                    <div class="flex items-center gap-3">
                        <x-icon
                            name="clock"
                            class="size-4 text-brand-dark"
                        />

                        <p class="text-sm">
                            <span class="font-medium">
                                {{ $dias[$horario->dia_semana] }}
                            </span>

                            <span class="ml-2 text-muted">
                                {{ substr($horario->hora_inicio, 0, 5) }}
                                –
                                {{ substr($horario->hora_fim, 0, 5) }}
                            </span>
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('profissionais.horarios.destroy', [$profissional, $horario]) }}"
                        data-busy-form
                        data-confirm="Remover este horário?"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="rounded-lg px-3 py-2 text-sm text-red-700 hover:bg-red-50"
                        >
                            Remover
                        </button>
                    </form>
                </div>
            @empty
                <p class="py-4 text-sm text-muted">
                    Nenhum horário cadastrado.
                </p>
            @endforelse
        </div>
    </section>

    {{-- BLOQUEIOS --}}
    <section class="mt-7 border-t border-slate-100 pt-6">
        <div>
            <h3 class="font-semibold">
                Bloqueios
            </h3>

            <p class="mt-1 text-xs text-muted">
                Cadastre períodos em que o profissional não poderá atender.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('profissionais.bloqueios.store', $profissional) }}"
            data-busy-form
            class="mt-5 grid gap-4 sm:grid-cols-2"
        >
            @csrf

            <input
                type="hidden"
                name="form_context"
                value="{{ $bloqueioContext }}"
            >

            <div>
                <label
                    for="bloqueio-inicio-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Início
                </label>

                <input
                    id="bloqueio-inicio-{{ $profissional->id }}"
                    name="inicio"
                    type="datetime-local"
                    value="{{ $hasBloqueioErrors ? old('inicio') : '' }}"
                    required
                    class="admin-input"
                >

                @if ($hasBloqueioErrors)
                    @error('inicio')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div>
                <label
                    for="bloqueio-fim-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Fim
                </label>

                <input
                    id="bloqueio-fim-{{ $profissional->id }}"
                    name="fim"
                    type="datetime-local"
                    value="{{ $hasBloqueioErrors ? old('fim') : '' }}"
                    required
                    class="admin-input"
                >
            </div>

            <div class="sm:col-span-2">
                <label
                    for="bloqueio-motivo-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Motivo
                    <span class="font-normal text-muted">
                        (opcional)
                    </span>
                </label>

                <input
                    id="bloqueio-motivo-{{ $profissional->id }}"
                    name="motivo"
                    value="{{ $hasBloqueioErrors ? old('motivo') : '' }}"
                    maxlength="255"
                    class="admin-input"
                >
            </div>

            <div class="sm:col-span-2 flex justify-end">
                <button
                    type="submit"
                    class="admin-button"
                    data-busy-label
                >
                    Adicionar bloqueio
                </button>
            </div>
        </form>

        <div class="mt-5 divide-y divide-slate-100">
            @forelse ($profissional->bloqueios as $bloqueio)
                <div
                    class="flex flex-wrap items-center justify-between gap-3 py-3"
                >
                    <div>
                        <p class="text-sm font-medium">
                            {{ $bloqueio->inicio->format('d/m/Y H:i') }}
                            até
                            {{ $bloqueio->fim->format('d/m/Y H:i') }}
                        </p>

                        @if ($bloqueio->motivo)
                            <p class="mt-1 text-xs text-muted">
                                {{ $bloqueio->motivo }}
                            </p>
                        @endif
                    </div>

                    <form
                        method="POST"
                        action="{{ route('profissionais.bloqueios.destroy', [$profissional, $bloqueio]) }}"
                        data-busy-form
                        data-confirm="Remover este bloqueio?"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="rounded-lg px-3 py-2 text-sm text-red-700 hover:bg-red-50"
                        >
                            Remover
                        </button>
                    </form>
                </div>
            @empty
                <p class="py-4 text-sm text-muted">
                    Nenhum bloqueio cadastrado.
                </p>
            @endforelse
        </div>
    </section>
</dialog>