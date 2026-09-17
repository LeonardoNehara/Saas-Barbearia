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
        <a href="{{ route('profissionais.index') }}" @class(['admin-nav', 'admin-nav-active' => request()->routeIs('profissionais.*')])><x-icon name="scissors" class="size-5" />Profissionais</a>
    @endcan
</nav>
@if (auth()->user()->estabelecimento)
    <div class="mt-auto pt-10">
        <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand/15 text-brand"><x-icon name="store" class="size-5" /></span>
            <div class="min-w-0"><p class="break-words text-sm font-medium">{{ auth()->user()->estabelecimento->nome }}</p><p class="mt-1 text-[11px] text-brand">Seu estabelecimento</p></div>
        </div>
    </div>
@endif
