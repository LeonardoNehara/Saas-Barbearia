@php
    $editContext = 'edit-profissional-' . $profissional->id;

    $hasEditErrors =
        old('form_context') === $editContext;

    $usuariosDisponiveis = $usuarios->filter(
        fn ($usuario) =>
            ! $usuario->profissional
            || $usuario->id === $profissional->user_id
    );
@endphp

<dialog
    id="modal-editar-profissional-{{ $profissional->id }}"
    class="admin-dialog"
    aria-labelledby="editar-profissional-title-{{ $profissional->id }}"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2
                id="editar-profissional-title-{{ $profissional->id }}"
                class="text-xl font-bold"
            >
                Editar profissional
            </h2>

            <p class="mt-1 text-sm text-muted">
                Atualize os dados do profissional.
            </p>
        </div>

        <button
            type="button"
            class="admin-secondary"
            aria-label="Fechar"
            onclick="document.getElementById('modal-editar-profissional-{{ $profissional->id }}').close()"
        >
            <x-icon name="close" class="size-4" />
        </button>
    </div>

    <form
        method="POST"
        action="{{ route('profissionais.update', $profissional) }}"
        data-busy-form
        class="mt-6"
    >
        @csrf
        @method('PUT')

        <input
            type="hidden"
            name="form_context"
            value="{{ $editContext }}"
        >

        <div class="grid gap-5 sm:grid-cols-2">

            <div class="sm:col-span-2">
                <label
                    for="edit-profissional-nome-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Nome completo *
                </label>

                <input
                    id="edit-profissional-nome-{{ $profissional->id }}"
                    name="nome"
                    value="{{ $hasEditErrors ? old('nome') : $profissional->nome }}"
                    maxlength="255"
                    required
                    class="admin-input"
                >

                @if ($hasEditErrors)
                    @error('nome')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div>
                <label
                    for="edit-profissional-telefone-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Telefone
                </label>

                <input
                    id="edit-profissional-telefone-{{ $profissional->id }}"
                    name="telefone"
                    type="tel"
                    value="{{ $hasEditErrors ? old('telefone') : $profissional->telefone }}"
                    maxlength="255"
                    class="admin-input"
                >
            </div>

            <div>
                <label
                    for="edit-profissional-email-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Email
                </label>

                <input
                    id="edit-profissional-email-{{ $profissional->id }}"
                    name="email"
                    type="email"
                    value="{{ $hasEditErrors ? old('email') : $profissional->email }}"
                    maxlength="255"
                    class="admin-input"
                >
            </div>

            <div class="sm:col-span-2">
                <label
                    for="edit-profissional-foto-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Endereço da foto
                </label>

                <input
                    id="edit-profissional-foto-{{ $profissional->id }}"
                    name="foto"
                    value="{{ $hasEditErrors ? old('foto') : $profissional->foto }}"
                    maxlength="255"
                    class="admin-input"
                >
            </div>

            <div class="sm:col-span-2">
                <label
                    for="edit-profissional-user-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Usuário vinculado
                    <span class="font-normal text-muted">
                        (opcional)
                    </span>
                </label>

                <select
                    id="edit-profissional-user-{{ $profissional->id }}"
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
                                (string) (
                                    $hasEditErrors
                                        ? old('user_id')
                                        : $profissional->user_id
                                ) === (string) $usuario->id
                            )
                        >
                            {{ $usuario->name }} · {{ $usuario->email }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label
                    for="edit-profissional-descricao-{{ $profissional->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Descrição
                </label>

                <textarea
                    id="edit-profissional-descricao-{{ $profissional->id }}"
                    name="descricao"
                    rows="4"
                    maxlength="10000"
                    class="admin-input resize-y"
                >{{ $hasEditErrors ? old('descricao') : $profissional->descricao }}</textarea>
            </div>
        </div>

        <div
            class="mt-7 flex justify-end gap-3 border-t border-slate-100 pt-5"
        >
            <button
                type="button"
                class="admin-secondary"
                onclick="document.getElementById('modal-editar-profissional-{{ $profissional->id }}').close()"
            >
                Cancelar
            </button>

            <button
                type="submit"
                class="admin-button"
                data-busy-label
            >
                Salvar alterações
            </button>
        </div>
    </form>
</dialog>