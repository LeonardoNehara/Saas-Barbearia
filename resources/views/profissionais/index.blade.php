@extends('layouts.admin')
@section('title', 'Profissionais')
@section('breadcrumb')<li aria-current="page" class="font-semibold">
    Profissionais</li>@endsection
@section('content')
    @php($dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'])
    <section class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-7" aria-labelledby="profissionais-title">
        <div class="mb-7 flex flex-wrap items-center justify-between gap-5">
            <div class="flex items-start gap-4"><span
                    class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-brand-dark"><x-icon
                        name="user" class="size-7" /></span>
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 id="profissionais-title" class="text-2xl font-bold tracking-tight sm:text-3xl">Profissionais
                        </h1><span
                            class="rounded-lg bg-brand/10 px-3 py-1 text-xs font-medium text-brand-dark">{{ $profissionais->total() }}
                            {{ $profissionais->total() === 1 ? 'profissional' : 'profissionais' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-muted">Gerencie os profissionais da sua barbearia, seus serviços e horários
                        de atendimento.</p>
                </div>
            </div>
            <button
                type="button"
                class="admin-button"
                onclick="document.getElementById('modal-novo-profissional').showModal()"
            >
                <x-icon name="plus" class="size-4" />
                Novo Profissional
            </button>
        </div>
        <form method="GET" action="{{ route('profissionais.index') }}" data-busy-form
            class="mb-6 flex flex-wrap items-center gap-3">
            <div class="relative min-w-48 flex-1"><label for="search" class="sr-only">Pesquisar nome ou
                    email</label><x-icon name="search"
                    class="pointer-events-none absolute top-3 left-3 size-5 text-slate-400" /><input id="search"
                    name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="255"
                    placeholder="Digite para pesquisar..." class="admin-input pl-10"></div>
            <div><label for="active" class="sr-only">Status</label><select id="active" name="active"
                    class="admin-input">
                    <option value="">Todos os status</option>
                    <option value="1" @selected(($filters['active'] ?? '') === '1')>Ativos</option>
                    <option value="0" @selected(($filters['active'] ?? '') === '0')>Inativos</option>
                </select></div>
            <div class="max-w-full"><label for="servico_id" class="sr-only">Serviço realizado</label><select id="servico_id"
                    name="servico_id" class="admin-input max-w-60">
                    <option value="">Todos os serviços</option>
                    @foreach ($servicos as $servico)
                        <option value="{{ $servico->id }}" @selected((string) ($filters['servico_id'] ?? '') === (string) $servico->id)>{{ $servico->nome }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="admin-secondary" data-busy-label>Filtrar</button>
            @if (filled($filters['search'] ?? null) || isset($filters['active']) || filled($filters['servico_id'] ?? null))
                <a href="{{ route('profissionais.index') }}" class="rounded p-2 text-xs text-muted underline">Limpar</a>
            @endif
            <x-per-page :value="$filters['per_page'] ?? 10" />
        </form>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <caption class="sr-only">Profissionais do seu estabelecimento</caption>
                <thead class="bg-slate-50 text-[10px] font-semibold tracking-wide text-slate-500 uppercase">
                    <tr>
                        @foreach (['ID', 'Profissional', 'Serviços', 'Horários', 'Status', 'Ações'] as $heading)
                            <th scope="col" class="px-3 py-4">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($profissionais as $profissional)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-3 py-5 text-xs text-muted">{{ $profissional->id }}</td>
                            <th scope="row" class="px-3 py-5 font-normal">
                                <div class="flex items-center gap-3"><x-profissional-avatar :profissional="$profissional" />
                                    <div class="max-w-48">
                                        <p class="break-words font-semibold text-ink">{{ $profissional->nome }}</p>
                                        <p class="mt-1 break-all text-xs text-muted">
                                            {{ $profissional->email ?: $profissional->telefone }}</p>
                                    </div>
                                </div>
                            </th>
                            <td class="max-w-56 px-3 py-5">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($profissional->servicos->take(3) as $servico)
                                        <span
                                        class="rounded-md bg-brand/10 px-2 py-1 text-xs text-brand-dark">{{ $servico->nome }}</span>@empty<span
                                            class="text-xs text-muted">Sem serviços vinculados</span>
                                    @endforelse
                                </div>
                                @if ($profissional->servicos->count() > 3)
                                    <details class="mt-2 text-xs">
                                        <summary class="cursor-pointer rounded text-muted">
                                            +{{ $profissional->servicos->count() - 3 }} serviços</summary>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($profissional->servicos->skip(3) as $servico)
                                                <span
                                                    class="rounded-md bg-brand/10 px-2 py-1 text-brand-dark">{{ $servico->nome }}</span>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </td>
                            <td class="px-3 py-5 text-xs text-muted">
                                @forelse($profissional->horarios->take(2) as $horario)
                                    <p class="mb-1 whitespace-nowrap">{{ $dias[$horario->dia_semana] }} ·
                                        {{ substr($horario->hora_inicio, 0, 5) }}–{{ substr($horario->hora_fim, 0, 5) }}
                                </p>@empty<p>Sem horários definidos</p>
                                    @endforelse @if ($profissional->horarios->count() > 2)
                                        <button
                                            type="button"
                                            class="inline-block rounded py-1 text-brand-dark underline"
                                            onclick="document.getElementById('modal-disponibilidade-profissional-{{ $profissional->id }}').showModal()"
                                        >
                                            +{{ $profissional->horarios->count() - 2 }} faixas
                                        </button>
                                    @endif
                            </td>
                            <td class="px-3 py-5"><x-status-badge :active="$profissional->active" /></td>
                            <td class="px-3 py-5">
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        aria-label="Editar {{ $profissional->nome }}"
                                        title="Editar profissional"
                                        class="rounded-lg border border-slate-200 p-2.5 text-muted hover:bg-slate-100"
                                        onclick="document.getElementById('modal-editar-profissional-{{ $profissional->id }}').showModal()"
                                    >
                                        <x-icon name="edit" class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        aria-label="Gerenciar horários e bloqueios de {{ $profissional->nome }}"
                                        title="Horários e bloqueios"
                                        class="rounded-lg border border-slate-200 p-2.5 text-muted hover:bg-slate-100"
                                        onclick="document.getElementById('modal-disponibilidade-profissional-{{ $profissional->id }}').showModal()"
                                    >
                                        <x-icon name="calendar" class="size-4" />
                                    </button>
                                    <form method="POST" action="{{ route('profissionais.status', $profissional) }}"
                                        data-busy-form
                                        data-confirm="{{ $profissional->active ? 'Desativar' : 'Ativar' }} {{ $profissional->nome }}?">
                                        @csrf @method('PATCH')<button type="submit"
                                            aria-label="{{ $profissional->active ? 'Desativar' : 'Ativar' }} {{ $profissional->nome }}"
                                            title="{{ $profissional->active ? 'Desativar' : 'Ativar' }} profissional"
                                            class="rounded-lg border border-slate-200 p-2.5 text-muted hover:bg-slate-100 disabled:opacity-50"><x-icon
                                                name="power" class="size-4" /></button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center"><x-icon name="users"
                                    class="mx-auto mb-4 size-9 text-slate-300" />
                                <p class="font-semibold">
                                    {{ $hasProfessionals ? 'Nenhum profissional encontrado para os filtros selecionados.' : 'Nenhum profissional cadastrado.' }}
                                </p><a
                                    href="{{ $hasProfessionals ? route('profissionais.index') : route('profissionais.create') }}"
                                    class="mt-5 inline-flex rounded-lg px-3 py-2 text-sm font-medium text-brand-dark underline">{{ $hasProfessionals ? 'Limpar filtros' : 'Cadastrar primeiro profissional' }}</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="my-3 text-xs text-muted sm:hidden">Deslize a tabela para ver os horários e as ações.</p>
        <x-table-pagination :paginator="$profissionais" />
    </section>
    @include('profissionais.partials.create-modal')
    @foreach ($profissionais as $profissional)
        @include('profissionais.partials.edit-modal', [
            'profissional' => $profissional,
            'usuarios' => $usuarios,
        ])
    @endforeach
    @foreach ($profissionais as $profissional)
        @include('profissionais.partials.disponibilidade-modal', [
            'profissional' => $profissional,
        ])
    @endforeach
    @if (
        $errors->any()
        && old('form_context') === 'create-profissional'
    )
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document
                    .getElementById('modal-novo-profissional')
                    ?.showModal();
            });
        </script>
    @endif
    @if (
        $errors->any()
        && str_starts_with(
            old('form_context', ''),
            'edit-profissional-'
        )
    )
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const context = @json(old('form_context'));

                const profissionalId = context.replace(
                    'edit-profissional-',
                    ''
                );

                document
                    .getElementById(
                        `modal-editar-profissional-${profissionalId}`
                    )
                    ?.showModal();
            });
        </script>
    @endif
    @if (
        $errors->any()
        && (
            str_starts_with(old('form_context', ''), 'horario-profissional-')
            || str_starts_with(old('form_context', ''), 'bloqueio-profissional-')
        )
    )
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const context = @json(old('form_context'));

                const profissionalId = context
                    .replace('horario-profissional-', '')
                    .replace('bloqueio-profissional-', '');

                document
                    .getElementById(
                        `modal-disponibilidade-profissional-${profissionalId}`
                    )
                    ?.showModal();
            });
        </script>
    @endif
@endsection
