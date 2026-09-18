@php
    $editContext = 'edit-usuario-' . $usuario->id;
    $hasEditErrors = old('form_context') === $editContext;
@endphp

<dialog
    id="modal-editar-usuario-{{ $usuario->id }}"
    class="admin-dialog"
    aria-labelledby="editar-usuario-title-{{ $usuario->id }}"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2
                id="editar-usuario-title-{{ $usuario->id }}"
                class="text-xl font-bold"
            >
                Editar usuário
            </h2>

            <p class="mt-1 text-sm text-muted">
                Atualize os dados e o perfil de acesso.
            </p>
        </div>

        <button
            type="button"
            class="admin-secondary"
            onclick="document.getElementById('modal-editar-usuario-{{ $usuario->id }}').close()"
        >
            <x-icon name="close" class="size-4" />
        </button>
    </div>

    <form
        method="POST"
        action="{{ route('usuarios.update', $usuario) }}"
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
                    for="edit-user-name-{{ $usuario->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Nome completo *
                </label>

                <input
                    id="edit-user-name-{{ $usuario->id }}"
                    name="name"
                    value="{{ $hasEditErrors ? old('name') : $usuario->name }}"
                    required
                    maxlength="255"
                    class="admin-input"
                >

                @if ($hasEditErrors)
                    @error('name')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div>
                <label
                    for="edit-user-email-{{ $usuario->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Email *
                </label>

                <input
                    id="edit-user-email-{{ $usuario->id }}"
                    name="email"
                    type="email"
                    value="{{ $hasEditErrors ? old('email') : $usuario->email }}"
                    required
                    maxlength="255"
                    class="admin-input"
                >
            </div>

            <div>
                <label
                    for="edit-user-role-{{ $usuario->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Perfil *
                </label>

                <select
                    id="edit-user-role-{{ $usuario->id }}"
                    name="role"
                    required
                    class="admin-input"
                >
                    @if (! $usuario->is(auth()->user()))
                        <option
                            value="barbeiro"
                            @selected(
                                ($hasEditErrors ? old('role') : $usuario->role)
                                === 'barbeiro'
                            )
                        >
                            Barbeiro
                        </option>
                    @endif

                    <option
                        value="admin"
                        @selected(
                            ($hasEditErrors ? old('role') : $usuario->role)
                            === 'admin'
                        )
                    >
                        Administrador
                    </option>
                </select>
            </div>

            <div class="border-t border-slate-100 pt-5 sm:col-span-2">
                <h3 class="font-semibold">
                    Alterar senha
                </h3>

                <p class="mt-1 text-xs text-muted">
                    Deixe em branco para manter a senha atual.
                </p>
            </div>

            <div>
                <label
                    for="edit-user-password-{{ $usuario->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Nova senha
                </label>

                <div class="relative">
                    <input
                        id="edit-user-password-{{ $usuario->id }}"
                        name="password"
                        type="password"
                        minlength="8"
                        maxlength="255"
                        autocomplete="new-password"
                        class="admin-input pr-12"
                    >

                    <button
                        type="button"
                        data-password-toggle
                        data-password-target="edit-user-password-{{ $usuario->id }}"
                        aria-label="Mostrar senha"
                        aria-pressed="false"
                        class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-muted hover:text-ink"
                    >
                        <x-icon name="eye" class="size-5" />
                    </button>
                </div>

                @if ($hasEditErrors)
                    @error('password')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>

            <div>
                <label
                    for="edit-user-password-{{ $usuario->id }}"
                    class="mb-2 block text-sm font-medium"
                >
                    Nova senha
                </label>

                <div class="relative">
                    <input
                        id="edit-user-password-{{ $usuario->id }}"
                        name="password"
                        type="password"
                        minlength="8"
                        maxlength="255"
                        autocomplete="new-password"
                        class="admin-input pr-12"
                    >

                    <button
                        type="button"
                        data-password-toggle
                        data-password-target="edit-user-password-{{ $usuario->id }}"
                        aria-label="Mostrar senha"
                        aria-pressed="false"
                        class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-muted hover:text-ink"
                    >
                        <x-icon name="eye" class="size-5" />
                    </button>
                </div>

                @if ($hasEditErrors)
                    @error('password')
                        <p class="mt-2 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </div>
        </div>

        <div class="mt-7 flex justify-end gap-3 border-t border-slate-100 pt-5">
            <button
                type="button"
                class="admin-secondary"
                onclick="document.getElementById('modal-editar-usuario-{{ $usuario->id }}').close()"
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