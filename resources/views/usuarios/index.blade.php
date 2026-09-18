@extends('layouts.admin')
@section('title', 'Usuários')
@section('breadcrumb')<li aria-current="page" class="font-semibold">Usuários</li>@endsection
@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-5">
        <div>
            <div class="flex items-center gap-3"><h1 class="text-3xl font-bold tracking-tight">Usuários</h1><span class="rounded-full bg-brand/10 px-3 py-1 text-xs font-medium text-brand-dark">{{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'registro' : 'registros' }}</span></div>
            <p class="mt-2 text-sm text-muted">Gerencie os usuários e acessos da sua barbearia.</p>
        </div>
        <button type="button" class="admin-button" onclick="document.getElementById('modal-novo-usuario').showModal()" ><x-icon name="plus" class="size-4" />Novo usuário</button>
    </div>

    <form method="GET" action="{{ route('usuarios.index') }}" data-busy-form class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
        <div class="min-w-48 flex-1">
            <label for="search" class="sr-only">Pesquisar por nome ou email</label>
            <div class="relative"><x-icon name="search" class="pointer-events-none absolute top-3 left-3 size-5 text-slate-400" /><input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="255" placeholder="Digite para pesquisar..." class="admin-input pl-10"></div>
        </div>
        <div class="min-w-36 flex-1 sm:flex-none"><label for="role" class="sr-only">Perfil</label><select id="role" name="role" class="admin-input"><option value="">Todos os perfis</option><option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Administrador</option><option value="barbeiro" @selected(($filters['role'] ?? '') === 'barbeiro')>Barbeiro</option></select></div>
        <div class="min-w-32 flex-1 sm:flex-none"><label for="active" class="sr-only">Situação</label><select id="active" name="active" class="admin-input"><option value="">Todas as situações</option><option value="1" @selected(($filters['active'] ?? '') === '1')>Ativo</option><option value="0" @selected(($filters['active'] ?? '') === '0')>Inativo</option></select></div>
        <button type="submit" class="admin-secondary" data-busy-label>Filtrar</button>
        @if (filled($filters['search'] ?? null) || filled($filters['role'] ?? null) || isset($filters['active']))<a href="{{ route('usuarios.index') }}" class="rounded-lg px-2 py-3 text-xs text-muted underline underline-offset-4">Limpar</a>@endif
        <x-per-page :value="$filters['per_page'] ?? 10" />
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="Lista de usuários">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">
                <caption class="sr-only">Usuários do seu estabelecimento</caption>
                <thead class="border-b border-slate-200 bg-slate-50/70 text-[10px] font-semibold tracking-wide text-slate-500 uppercase"><tr><th scope="col" class="px-5 py-4">ID</th><th scope="col" class="px-5 py-4">Nome / Email</th><th scope="col" class="px-5 py-4">Perfil</th><th scope="col" class="px-5 py-4">Situação</th><th scope="col" class="px-5 py-4 text-right">Ações</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($usuarios as $usuario)
                        <tr class="transition-colors hover:bg-slate-50/70">
                            <td class="px-5 py-5 text-xs text-muted">{{ $usuario->id }}</td>
                            <th scope="row" class="px-5 py-5 font-normal"><div class="flex items-center gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-slate-100 font-semibold text-slate-500" aria-hidden="true">{{ mb_strtoupper(mb_substr($usuario->name, 0, 1)) }}</span><div class="max-w-64"><p class="break-words font-semibold text-ink">{{ $usuario->name }} @if($usuario->is(auth()->user()))<span class="text-xs font-normal text-muted">(você)</span>@endif</p><p class="mt-1 break-all text-xs text-muted">{{ $usuario->email }}</p></div></div></th>
                            <td class="px-5 py-5"><x-role-badge :role="$usuario->role" /></td>
                            <td class="px-5 py-5"><x-status-badge :active="$usuario->active" /></td>
                            <td class="px-5 py-5"><div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    aria-label="Editar {{ $usuario->name }}"
                                    title="Editar usuário"
                                    class="rounded-lg p-3 text-muted hover:bg-slate-100 hover:text-brand-dark"
                                    onclick="document.getElementById('modal-editar-usuario-{{ $usuario->id }}').showModal()"
                                >
                                    <x-icon name="edit" class="size-4" />
                                </button>
                                @can('changeStatus', $usuario)
                                    <form method="POST" action="{{ route('usuarios.status', $usuario) }}" data-confirm="{{ $usuario->active ? 'Desativar' : 'Ativar' }} o acesso de {{ $usuario->name }}?" data-busy-form>
                                        @csrf @method('PATCH')<input type="hidden" name="active" value="{{ $usuario->active ? '0' : '1' }}">
                                        <button type="submit" aria-label="{{ $usuario->active ? 'Desativar' : 'Ativar' }} {{ $usuario->name }}" title="{{ $usuario->active ? 'Desativar' : 'Ativar' }} usuário" @class(['rounded-lg p-3 hover:bg-slate-100', 'text-red-700' => $usuario->active, 'text-emerald-700' => ! $usuario->active])><x-icon name="power" class="size-4" /></button>
                                    </form>
                                @endcan
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-16 text-center"><x-icon name="users" class="mx-auto mb-4 size-9 text-slate-300" /><p class="font-semibold">Nenhum usuário encontrado.</p><p class="mt-2 text-sm text-muted">Ajuste os filtros para encontrar os usuários da sua barbearia.</p><a href="{{ route('usuarios.index') }}" class="mt-4 inline-block text-sm text-brand-dark underline underline-offset-4">Limpar filtros</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$usuarios" />
    </section>
    <p class="mt-3 text-xs text-muted sm:hidden">Deslize a tabela para ver todas as colunas e ações.</p>
    @include('usuarios.partials.create-modal')
        @foreach ($usuarios as $usuario)
        @include('usuarios.partials.edit-modal', [
            'usuario' => $usuario,
        ])
    @endforeach
    @if (
        $errors->any()
        && old('form_context') === 'create-usuario'
    )
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document
                    .getElementById('modal-novo-usuario')
                    ?.showModal();
            });
        </script>
    @endif

    @if (
        $errors->any()
        && str_starts_with(
            old('form_context', ''),
            'edit-usuario-'
        )
    )
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const context = @json(old('form_context'));

                const usuarioId = context.replace(
                    'edit-usuario-',
                    ''
                );

                document
                    .getElementById(`modal-editar-usuario-${usuarioId}`)
                    ?.showModal();
            });
        </script>
    @endif
@endsection
