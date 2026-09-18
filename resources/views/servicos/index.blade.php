@extends('layouts.admin')
@section('title', 'Serviços')
@section('breadcrumb')<li aria-current="page" class="font-semibold">Serviços</li>@endsection
@section('content')
    <section class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-7" aria-labelledby="servicos-title">
        <div class="mb-7 flex flex-wrap items-center justify-between gap-5">
            <div class="flex items-start gap-4">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand-dark"><x-icon name="scissors" class="size-6" /></span>
                <div><div class="flex flex-wrap items-center gap-3"><h1 id="servicos-title" class="text-2xl font-bold tracking-tight">Serviços</h1><span class="rounded-md bg-brand/10 px-2.5 py-1 text-xs font-medium text-brand-dark">{{ $servicos->total() }} {{ $servicos->total() === 1 ? 'serviço' : 'serviços' }}</span></div><p class="mt-2 text-sm text-muted">Cadastre e gerencie os serviços oferecidos pela sua barbearia.</p></div>
            </div>
            <button
                    type="button"
                    class="admin-button"
                    onclick="document.getElementById('modal-novo-servico').showModal()"
                >
                    <x-icon name="plus" class="size-4" />
                    Novo Serviço
                </button>
            </div>
        <form method="GET" action="{{ route('servicos.index') }}" data-busy-form class="mb-6 flex flex-wrap items-center gap-3">
            <div class="relative min-w-48 flex-1 sm:max-w-sm"><label for="search" class="sr-only">Pesquisar por nome ou descrição</label><x-icon name="search" class="pointer-events-none absolute top-3 left-3 size-5 text-slate-400" /><input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="255" placeholder="Digite para pesquisar..." class="admin-input bg-slate-50 pl-10"></div>
            <div><label for="active" class="sr-only">Status</label><select id="active" name="active" class="admin-input"><option value="">Todos os status</option><option value="1" @selected(($filters['active'] ?? '') === '1')>Ativos</option><option value="0" @selected(($filters['active'] ?? '') === '0')>Inativos</option></select></div>
            <button type="submit" class="admin-secondary" data-busy-label>Filtrar</button>
            @if (filled($filters['search'] ?? null) || isset($filters['active']))<a href="{{ route('servicos.index') }}" class="rounded-lg p-2 text-xs text-muted underline underline-offset-4">Limpar</a>@endif
            <x-per-page :value="$filters['per_page'] ?? 10" />
        </form>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <caption class="sr-only">Serviços do seu estabelecimento</caption>
                <thead class="bg-slate-50 text-[10px] font-semibold tracking-wide text-slate-500 uppercase"><tr><th scope="col" class="px-3 py-3">ID</th><th scope="col" class="px-3 py-3">Serviço</th><th scope="col" class="px-3 py-3">Duração</th><th scope="col" class="px-3 py-3">Preço</th><th scope="col" class="px-3 py-3">Status</th><th scope="col" class="px-3 py-3 text-right">Ações</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($servicos as $servico)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-3 py-5 text-xs text-muted">{{ $servico->id }}</td>
                            <th scope="row" class="px-3 py-5 font-normal"><div class="flex items-center gap-3"><span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand-dark"><x-icon name="scissors" class="size-5" /></span><div class="max-w-sm"><p class="break-words font-semibold text-ink">{{ $servico->nome }}</p>@if($servico->descricao)<p class="mt-1 line-clamp-2 break-words text-xs leading-5 text-muted">{{ $servico->descricao }}</p>@endif</div></div></th>
                            <td class="whitespace-nowrap px-3 py-5 text-xs text-muted"><span class="inline-flex items-center gap-2"><x-icon name="clock" class="size-4" />{{ $servico->duracao_minutos }} min</span></td>
                            <td class="whitespace-nowrap px-3 py-5 font-semibold">R$ {{ number_format((float) $servico->preco, 2, ',', '.') }}</td>
                            <td class="px-3 py-5"><x-status-badge :active="$servico->active" /></td>
                            <td class="px-3 py-5"><div class="flex justify-end gap-1">
                            <button
                                type="button"
                                aria-label="Editar {{ $servico->nome }}"
                                title="Editar serviço"
                                class="rounded-lg p-3 text-muted hover:bg-slate-100 hover:text-brand-dark"
                                onclick="document.getElementById('modal-editar-servico-{{ $servico->id }}').showModal()"
                            >
                                <x-icon name="edit" class="size-4" />
                            </button>
                            <form method="POST" action="{{ route('servicos.status', $servico) }}" data-busy-form data-confirm="{{ $servico->active ? 'Desativar' : 'Ativar' }} o serviço {{ $servico->nome }}?">@csrf @method('PATCH')<button type="submit" aria-label="{{ $servico->active ? 'Desativar' : 'Ativar' }} {{ $servico->nome }}" title="{{ $servico->active ? 'Desativar' : 'Ativar' }} serviço" @class(['rounded-lg p-3 hover:bg-slate-100 disabled:opacity-50', 'text-red-700' => $servico->active, 'text-emerald-700' => ! $servico->active])><x-icon name="power" class="size-4" /></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-16 text-center"><x-icon name="scissors" class="mx-auto mb-4 size-9 text-slate-300" /><p class="font-semibold">{{ $hasServices ? 'Nenhum resultado encontrado.' : 'Nenhum serviço cadastrado.' }}</p><p class="mt-2 text-sm text-muted">{{ $hasServices ? 'Ajuste a pesquisa e os filtros para encontrar seus serviços.' : 'Adicione os serviços que sua barbearia oferece.' }}</p><a href="{{ $hasServices ? route('servicos.index') : route('servicos.create') }}" class="mt-5 inline-flex rounded-lg px-3 py-2 text-sm font-medium text-brand-dark underline underline-offset-4">{{ $hasServices ? 'Limpar filtros' : 'Cadastrar primeiro serviço' }}</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="my-3 text-xs text-muted sm:hidden">Deslize a tabela para ver todas as colunas e ações.</p>
        <x-table-pagination :paginator="$servicos" />
    </section>
    @include('servicos.partials.create-modal')
    @foreach ($servicos as $servico)
        @include('servicos.partials.edit-modal', [
            'servico' => $servico,
            'profissionais' => $profissionais,
        ])
    @endforeach
    @if ($errors->any() && old('form_context') === 'create-servico')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document
                    .getElementById('modal-novo-servico')
                    ?.showModal();
            });
        </script>
    @endif
    @if (
        $errors->any()
        && str_starts_with(
            old('form_context', ''),
            'edit-servico-'
        )
    )
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const context = @json(old('form_context'));

                const id = context.replace(
                    'edit-servico-',
                    ''
                );

                document
                    .getElementById(`modal-editar-servico-${id}`)
                    ?.showModal();
            });
        </script>
    @endif
@endsection
