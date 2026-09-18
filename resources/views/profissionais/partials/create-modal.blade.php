@php
    $hasCreateErrors =
        old('form_context') === 'create-profissional';

    $usuariosDisponiveis = $usuarios->filter(
        fn ($usuario) => ! $usuario->profissional
    );
@endphp

<dialog
    id="modal-novo-profissional"
    class="admin-dialog"
    aria-labelledby="novo-profissional-title"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2
                id="novo-profissional-title"
                class="text-xl font-bold"
            >
                Novo profissional
            </h2>

            <p class="mt-1 text-sm text-muted">
                Cadastre um profissional da sua barbearia.
            </p>
        </div>

        <button
            type="button"
            class="admin-secondary"
            aria-label="Fechar"
            onclick="document.getElementById('modal-novo-profissional').close()"
        >
            <x-icon name="close" class="size-4" />
        </button>
    </div>

    <form
        method="POST"
        action="{{ route('profissionais.store') }}"
        data-busy-form
        class="mt-6"
    >
        @csrf

        <input
            type="hidden"
            name="form_context"
            value="create-profissional"
        >

        <div class="grid gap-5 sm:grid-cols-2">

            <div class="sm:col-span-2">
                <label
                    for="create-profissional-nome"
                    class="mb-2 block text-sm font-medium"
                >
                    Nome completo *
                </label>

                <input
                    id="create-profissional-nome"
                    name="nome"
                    value="{{ $hasCreateErrors ? old('nome') : '' }}"
                    maxlength="255"
                    required
                    class="admin-input"
                >

                @if ($hasCreateErrors)
                    @error('nome')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div>
                <label
                    for="create-profissional-telefone"
                    class="mb-2 block text-sm font-medium"
                >
                    Telefone
                </label>

                <input
                    id="create-profissional-telefone"
                    name="telefone"
                    type="tel"
                    value="{{ $hasCreateErrors ? old('telefone') : '' }}"
                    maxlength="255"
                    class="admin-input"
                >

                @if ($hasCreateErrors)
                    @error('telefone')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div>
                <label
                    for="create-profissional-email"
                    class="mb-2 block text-sm font-medium"
                >
                    Email
                </label>

                <input
                    id="create-profissional-email"
                    name="email"
                    type="email"
                    value="{{ $hasCreateErrors ? old('email') : '' }}"
                    maxlength="255"
                    class="admin-input"
                >

                @if ($hasCreateErrors)
                    @error('email')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div class="sm:col-span-2">
                <label
                    for="create-profissional-foto"
                    class="mb-2 block text-sm font-medium"
                >
                    Endereço da foto
                </label>

                <input
                    id="create-profissional-foto"
                    name="foto"
                    value="{{ $hasCreateErrors ? old('foto') : '' }}"
                    maxlength="255"
                    class="admin-input"
                >

                @if ($hasCreateErrors)
                    @error('foto')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div class="sm:col-span-2">
                <label
                    for="create-profissional-user"
                    class="mb-2 block text-sm font-medium"
                >
                    Usuário vinculado
                    <span class="font-normal text-muted">
                        (opcional)
                    </span>
                </label>

                <select
                    id="create-profissional-user"
                    name="user_id"
                    class="admin-input"
                >
                    <option value="">
                        Sem vínculo com usuário
                    </option>

                    @foreach ($usuariosDisponiveis as $usuario)
                        <option
                            value="{{ $usuario->id }}"
                            @selected(
                                $hasCreateErrors
                                && (string) old('user_id') === (string) $usuario->id
                            )
                        >
                            {{ $usuario->name }} · {{ $usuario->email }}
                        </option>
                    @endforeach
                </select>

                @if ($hasCreateErrors)
                    @error('user_id')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div class="sm:col-span-2">
                <label
                    for="create-profissional-descricao"
                    class="mb-2 block text-sm font-medium"
                >
                    Descrição
                </label>

                <textarea
                    id="create-profissional-descricao"
                    name="descricao"
                    rows="4"
                    maxlength="10000"
                    class="admin-input resize-y"
                >{{ $hasCreateErrors ? old('descricao') : '' }}</textarea>

                @if ($hasCreateErrors)
                    @error('descricao')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>
        </div>

        <div
            class="mt-7 flex justify-end gap-3 border-t border-slate-100 pt-5"
        >
            <button
                type="button"
                class="admin-secondary"
                onclick="document.getElementById('modal-novo-profissional').close()"
            >
                Cancelar
            </button>

            <button
                type="submit"
                class="admin-button"
                data-busy-label
            >
                Cadastrar profissional
            </button>
        </div>
    </form>
</dialog>