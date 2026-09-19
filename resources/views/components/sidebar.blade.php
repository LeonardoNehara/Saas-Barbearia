<x-brand class="px-2" />
<nav class="mt-10 space-y-2" aria-label="Navegação da aplicação">
    <a href="{{ route('agendamentos.index') }}" @class(['admin-nav', 'admin-nav-active' => request()->routeIs('agendamentos.*')]) @if(request()->routeIs('agendamentos.*')) aria-current="page" @endif><x-icon name="calendar" class="size-5" />Agenda</a>
    @can('viewAny', \App\Models\User::class)
        <a href="{{ route('usuarios.index') }}" @class(['admin-nav', 'admin-nav-active' => request()->routeIs('usuarios.*')]) @if(request()->routeIs('usuarios.*')) aria-current="page" @endif><x-icon name="users" class="size-5" />Usuários</a>
    @endcan
    @can('viewAny', \App\Models\Servico::class)
        <a href="{{ route('servicos.index') }}" @class(['admin-nav', 'admin-nav-active' => request()->routeIs('servicos.*')]) @if(request()->routeIs('servicos.*')) aria-current="page" @endif><x-icon name="scissors" class="size-5" />Serviços</a>
    @endcan
    @can('viewAny', \App\Models\Profissional::class)
        <a href="{{ route('profissionais.index') }}" @class(['admin-nav', 'admin-nav-active' => request()->routeIs('profissionais.*')]) @if(request()->routeIs('profissionais.*')) aria-current="page" @endif><x-icon name="user" class="size-5" />Profissionais</a>
    @endcan
</nav>
<div class="mt-auto space-y-3 pt-10">

    <div class="rounded-xl border border-white/10 bg-white/5 p-3">

        <div class="flex items-center gap-3">
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-full
                       border border-brand/40 bg-brand/15 text-sm font-semibold text-brand"
            >
                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </span>

            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-white">
                    {{ auth()->user()->name }}
                </p>

                <p class="mt-0.5 text-[11px] text-white/60">
                    {{ auth()->user()->isAdmin() ? 'Administrador' : 'Barbeiro' }}
                </p>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('logout') }}"
            class="mt-3"
        >
            @csrf

            <button
                type="submit"
                class="flex w-full items-center gap-2 rounded-lg border border-brand/40
                       px-3 py-2 text-sm text-brand transition
                       hover:bg-brand/10"
            >
                <x-icon name="logout" class="size-4" />

                <span>Sair</span>
            </button>
        </form>

    </div>

</div>
