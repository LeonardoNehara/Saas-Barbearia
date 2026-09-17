<header class="flex min-h-20 items-center justify-between gap-3 border-b border-slate-200/70 bg-white px-4 py-4 sm:px-8 lg:px-9">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" data-open-menu aria-label="Abrir menu" aria-controls="mobile-menu" aria-expanded="false" class="rounded-lg p-2 text-muted hover:bg-slate-100 lg:hidden"><x-icon name="menu" class="size-5" /></button>
        <nav aria-label="Localização" class="text-xs sm:text-sm"><ol class="flex flex-wrap items-center gap-2"><li class="text-muted">Painel</li><li aria-hidden="true" class="text-slate-400">/</li>@yield('breadcrumb', '')</ol></nav>
    </div>
    <div class="flex shrink-0 items-center gap-3 sm:gap-5">
        <div class="flex items-center gap-3">
            <span class="flex size-9 items-center justify-center rounded-full bg-brand/15 text-sm font-semibold text-brand-dark" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <div class="hidden max-w-48 sm:block"><p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p><p class="text-[11px] text-muted">{{ auth()->user()->isAdmin() ? 'Administrador' : 'Barbeiro' }}</p></div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex min-h-10 items-center gap-2 rounded-lg px-2 text-xs text-muted hover:bg-slate-100 hover:text-ink"><x-icon name="logout" class="size-4" /><span>Sair</span></button></form>
    </div>
</header>
