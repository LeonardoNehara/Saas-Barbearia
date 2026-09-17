<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111211">
    <title>Entrar | Agenda Barbearia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-ink antialiased">
    <main class="grid min-h-svh lg:grid-cols-2">
        <section class="login-story relative isolate hidden min-h-svh flex-col justify-between overflow-hidden px-[9%] py-14 text-white lg:flex" aria-label="Agenda Barbearia">
            <x-brand />
            <div class="my-16">
                <p class="mb-7 flex items-center gap-3 text-[11px] font-semibold text-brand"><span class="h-px w-5 bg-brand"></span>MAIS TEMPO PARA O QUE IMPORTA</p>
                <h1 class="text-[clamp(1.75rem,2.9vw,3rem)] font-bold leading-[1.35] tracking-tight">Seu talento no corte.<br><span class="text-brand">Sua agenda em dia.</span></h1>
                <p class="mt-7 max-w-lg text-sm leading-7 text-slate-300">Organize os horários, cuide da equipe e deixe cada atendimento no seu lugar.</p>
                <div class="mt-7 grid grid-cols-3 gap-3">
                    @foreach (['calendar' => 'Fácil organização', 'users' => 'Equipe produtiva', 'trend' => 'Seu negócio mais forte'] as $icon => $label)
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-5">
                            <span class="mb-3 flex size-8 items-center justify-center rounded-full bg-white/5 text-brand"><x-icon :name="$icon" class="size-4" /></span>
                            <p class="text-[11px] font-medium leading-5">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <p class="text-xs leading-6 text-slate-400">“Um bom atendimento começa com organização.”</p>
        </section>

        <section class="flex min-h-svh flex-col px-6 py-8 sm:px-12 sm:py-12 lg:px-[9%] lg:py-14" aria-labelledby="login-heading">
            <header class="flex items-center justify-between gap-4 lg:justify-end">
                <x-brand class="lg:hidden" />
                <p class="flex items-center gap-3 text-[9px] font-semibold text-muted sm:text-[10px]"><span class="h-px w-4 bg-brand"></span>GESTÃO DA BARBEARIA</p>
            </header>

            <div class="my-auto w-full max-w-[440px] py-16 lg:py-20">
                <p class="mb-3 text-[11px] font-semibold text-brand-dark">BEM-VINDO DE VOLTA</p>
                <h2 id="login-heading" class="max-w-[430px] text-[clamp(2rem,2.8vw,2.5rem)] font-bold leading-[1.18] tracking-tight">Vamos organizar o seu<br class="hidden sm:block"> dia?</h2>
                <p class="mt-3 text-sm leading-6 text-muted">Entre com seu email e senha para acessar o sistema.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-9" data-login-form>
                    @csrf
                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium">Email</label>
                        <div class="relative">
                            <x-icon name="user" class="pointer-events-none absolute top-3.5 left-4 size-5 text-muted" />
                            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="seu@email.com" required maxlength="255" class="login-input @error('email') border-red-600 @enderror" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        </div>
                        @error('email')<p id="email-error" class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="mt-5">
                        <label for="password" class="mb-2 block text-sm font-medium">Senha</label>
                        <div class="relative">
                            <x-icon name="lock" class="pointer-events-none absolute top-3.5 left-4 size-5 text-muted" />
                            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required class="login-input pr-14 @error('password') border-red-600 @enderror" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                            <button type="button" data-password-toggle hidden aria-label="Mostrar senha" aria-controls="password" aria-pressed="false" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-muted hover:text-ink focus-visible:outline-2 focus-visible:outline-brand-dark"><x-icon name="eye" class="size-5" /></button>
                        </div>
                        @error('password')<p id="password-error" class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="relative mt-9 flex min-h-12 w-full items-center justify-center rounded-xl bg-brand-button px-12 py-3 text-sm font-semibold text-white shadow-md shadow-brand/20 transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-dark disabled:cursor-wait disabled:opacity-70">
                        <span data-submit-label role="status" aria-live="polite">Entrar no sistema</span><x-icon name="arrow" class="absolute right-5 size-5" />
                    </button>
                </form>
                <p class="mt-5 text-center text-xs leading-6 text-muted underline decoration-slate-300 underline-offset-2">Precisa de acesso? Fale com o responsável pela barbearia.</p>
            </div>

            <footer class="flex flex-wrap justify-between gap-3 text-[11px] text-muted"><span>Agenda Barbearia</span><span>Seu negócio, bem organizado.</span></footer>
        </section>
    </main>
</body>
</html>
