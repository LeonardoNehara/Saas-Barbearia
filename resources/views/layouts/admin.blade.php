<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111211">
    <title>@yield('title', 'Painel') | Agenda Barbearia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell bg-slate-50 font-sans text-slate-800 antialiased">
    @php($agendaWorkspace = request()->routeIs('agendamentos.index'))
    <a href="#conteudo" class="sr-only z-50 rounded bg-white p-3 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Ir para o conteúdo</a>
    <div @class(['min-h-svh', 'lg:grid lg:grid-cols-[240px_minmax(0,1fr)]' => ! $agendaWorkspace])>
        @unless ($agendaWorkspace)
        <aside class="login-story hidden flex-col px-4 py-7 text-white lg:sticky lg:top-0 lg:flex lg:h-svh" aria-label="Menu principal">
            <x-sidebar />
        </aside>
        @endunless
        <div class="flex min-w-0 flex-col">
            <x-admin-header :always-show-menu="$agendaWorkspace" />
            <main id="conteudo" tabindex="-1" class="w-full flex-1 px-4 py-7 outline-none sm:px-8 sm:py-10 lg:px-9">
                <div @class(['mx-auto', 'max-w-[1440px]' => ! $agendaWorkspace])>
                    @if (session('status'))
                        <div role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <p class="font-semibold">Confira os dados informados.</p>
                            <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @yield('content')
                </div>
            </main>
            <footer class="flex flex-wrap justify-between gap-2 border-t border-slate-200/70 px-4 py-6 text-xs text-muted sm:px-8 lg:px-9"><span>Agenda Barbearia</span><span>Seu negócio, bem organizado.</span></footer>
        </div>
    </div>
    <dialog id="mobile-menu" aria-label="Menu principal" @if ($agendaWorkspace) data-desktop-menu @endif @class(['admin-menu m-0 h-svh max-h-none w-[min(85vw,300px)] max-w-none border-0 bg-transparent p-0 text-white backdrop:bg-black/50', 'lg:hidden' => ! $agendaWorkspace])>
        <div class="login-story flex min-h-full flex-col px-5 py-6">
            <button type="button" data-close-menu aria-label="Fechar menu" class="mb-5 ml-auto rounded-lg p-2 hover:bg-white/10"><x-icon name="close" class="size-5" /></button>
            <x-sidebar />
        </div>
    </dialog>
</body>
</html>
