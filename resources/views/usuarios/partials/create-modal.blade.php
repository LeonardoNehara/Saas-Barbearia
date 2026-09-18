<dialog id="modal-novo-usuario" class="admin-dialog" aria-labelledby="novo-usuario-title">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 id="novo-usuario-title" class="text-xl font-bold">Novo usuário</h2>
            <p class="mt-1 text-sm text-muted">
                Cadastre um novo acesso para sua barbearia.
            </p>
        </div>

        <button
            type="button"
            class="admin-secondary"
            aria-label="Fechar"
            onclick="document.getElementById('modal-novo-usuario').close()"
        >
            <x-icon name="close" class="size-4" />
        </button>
    </div>

    <form
        method="POST"
        action="{{ route('usuarios.store') }}"
        class="mt-6"
        data-busy-form
    >
        @csrf
        <input type="hidden" name="form_context" value="create-usuario">
        <div class="grid gap-5 sm:grid-cols-2">
            {{-- Nome --}}
            <div class="sm:col-span-2">
                <label for="create-name" class="mb-2 block text-sm font-medium">
                    Nome completo *
                </label>

                <input
                    id="create-name"
                    name="name"
                    value="{{ old('name') }}"
                    class="admin-input"
                    maxlength="255"
                    autocomplete="name"
                    required
                >

                @error('name')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="create-email" class="mb-2 block text-sm font-medium">
                    Email *
                </label>

                <input
                    id="create-email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    class="admin-input"
                    maxlength="255"
                    autocomplete="email"
                    required
                >

                @error('email')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            {{-- Perfil --}}
            <div>
                <label for="create-role" class="mb-2 block text-sm font-medium">
                    Perfil *
                </label>

                <select id="create-role" name="role" class="admin-input" required>
                    <option
                        value="barbeiro"
                        @selected(old('role', 'barbeiro') === 'barbeiro')
                    >
                        Barbeiro
                    </option>

                    <option
                        value="admin"
                        @selected(old('role') === 'admin')
                    >
                        Administrador
                    </option>
                </select>
            </div>

            {{-- Senha --}}
            <div class="border-t border-slate-100 pt-5 sm:col-span-2">
                <h3 class="font-semibold">Senha de acesso</h3>
                <p class="mt-1 text-xs text-muted">
                    Utilize pelo menos 8 caracteres.
                </p>
            </div>

           <div>
                <label for="create-password" class="mb-2 block text-sm font-medium">
                    Senha *
                </label>

                <div class="relative">
                    <input
                        id="create-password"
                        name="password"
                        type="password"
                        class="admin-input pr-12"
                        minlength="8"
                        maxlength="255"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        data-password-toggle
                        data-password-target="create-password"
                        aria-label="Mostrar senha"
                        aria-pressed="false"
                        class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-muted hover:text-ink"
                    >
                        <x-icon name="eye" class="size-5" />
                    </button>
                </div>

                @error('password')
                    <p class="mt-2 text-sm text-red-700">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="create-password-confirmation"
                    class="mb-2 block text-sm font-medium"
                >
                    Confirmar senha *
                </label>

                <div class="relative">
                    <input
                        id="create-password-confirmation"
                        name="password_confirmation"
                        type="password"
                        class="admin-input pr-12"
                        minlength="8"
                        maxlength="255"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        data-password-toggle
                        data-password-target="create-password-confirmation"
                        aria-label="Mostrar senha"
                        aria-pressed="false"
                        class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-muted hover:text-ink"
                    >
                        <x-icon name="eye" class="size-5" />
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-7 flex justify-end gap-3 border-t border-slate-100 pt-5">
            <button
                type="button"
                class="admin-secondary"
                onclick="document.getElementById('modal-novo-usuario').close()"
            >
                Cancelar
            </button>

            <button type="submit" class="admin-button" data-busy-label>
                Cadastrar usuário
            </button>
        </div>
    </form>
</dialog>