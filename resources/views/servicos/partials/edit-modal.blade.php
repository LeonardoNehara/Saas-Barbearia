@php
    $editContext = 'edit-servico-' . $servico->id;
    $hasEditErrors = old('form_context') === $editContext;

    $selectedProfissionais = $hasEditErrors && old('profissionais') !== null
        ? old('profissionais')
        : $servico->profissionais->modelKeys();
@endphp

<dialog
    id="modal-editar-servico-{{ $servico->id }}"
    class="admin-dialog"
    aria-labelledby="editar-servico-title-{{ $servico->id }}"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2
                id="editar-servico-title-{{ $servico->id }}"
                class="text-xl font-bold"
            >
                Editar serviço
            </h2>

            <p class="mt-1 text-sm text-muted">
                Atualize os dados e profissionais deste serviço.
            </p>
        </div>

        <button
            type="button"
            class="admin-secondary"
            aria-label="Fechar"
            onclick="document.getElementById('modal-editar-servico-{{ $servico->id }}').close()"
        >
            <x-icon name="close" class="size-4" />
        </button>
    </div>

    {{-- Dados principais --}}
    <div id="dados-servico-{{ $servico->id }}">
        <form
            method="POST"
            action="{{ route('servicos.update', $servico) }}"
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
                        for="edit-nome-{{ $servico->id }}"
                        class="mb-2 block text-sm font-medium"
                    >
                        Nome do serviço *
                    </label>
                    <input
                        id="edit-nome-{{ $servico->id }}"
                        name="nome"
                        value="{{ $hasEditErrors ? old('nome') : $servico->nome }}"
                        required
                        maxlength="255"
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
                <div class="sm:col-span-2">
                    <label
                        for="edit-descricao-{{ $servico->id }}"
                        class="mb-2 block text-sm font-medium"
                    >
                        Descrição
                        <span class="font-normal text-muted">
                            (opcional)
                        </span>
                    </label>
                    <textarea
                        id="edit-descricao-{{ $servico->id }}"
                        name="descricao"
                        rows="3"
                        maxlength="10000"
                        class="admin-input resize-y"
                    >{{ $hasEditErrors ? old('descricao') : $servico->descricao }}</textarea>
                    @if ($hasEditErrors)
                        @error('descricao')
                            <p class="mt-2 text-sm text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    @endif
                </div>
                <div>
                    <label
                        for="edit-duracao-{{ $servico->id }}"
                        class="mb-2 block text-sm font-medium"
                    >
                        Duração em minutos *
                    </label>
                    <input
                        id="edit-duracao-{{ $servico->id }}"
                        name="duracao_minutos"
                        type="number"
                        min="5"
                        max="480"
                        step="1"
                        value="{{ $hasEditErrors ? old('duracao_minutos') : $servico->duracao_minutos }}"
                        required
                        class="admin-input"
                    >
                    @if ($hasEditErrors)
                        @error('duracao_minutos')
                            <p class="mt-2 text-sm text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    @endif
                </div>
                <div>
                    <label
                        for="edit-preco-{{ $servico->id }}"
                        class="mb-2 block text-sm font-medium"
                    >
                        Preço (R$) *
                    </label>
                    <input
                        id="edit-preco-{{ $servico->id }}"
                        name="preco"
                        type="number"
                        inputmode="decimal"
                        min="0"
                        max="99999999.99"
                        step="0.01"
                        value="{{ $hasEditErrors ? old('preco') : $servico->preco }}"
                        required
                        class="admin-input"
                    >
                    @if ($hasEditErrors)
                        @error('preco')
                            <p class="mt-2 text-sm text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    @endif
                </div>
            </div>
            <div class="mt-6 flex items-center justify-between gap-3">

                <button
                    type="button"
                    class="admin-secondary"
                    onclick="
                        document.getElementById('dados-servico-{{ $servico->id }}').classList.add('hidden');
                        document.getElementById('profissionais-servico-{{ $servico->id }}').classList.remove('hidden');
                    "
                >
                    <x-icon name="users" class="size-4" />
                    Profissionais
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
    </div>

    {{-- Profissionais --}}
    <div id="profissionais-servico-{{ $servico->id }}" class="hidden">
        <form
            method="POST"
            action="{{ route('servicos.profissionais', $servico) }}"
            data-busy-form
            class="mt-6 border-t border-slate-100 pt-6"
        >
            @csrf
            @method('PUT')
            <input
                type="hidden"
                name="form_context"
                value="{{ $editContext }}"
            >
            <input
                type="hidden"
                name="profissionais_present"
                value="1"
            >
            <fieldset>
                <legend class="font-semibold">
                    Profissionais que realizam este serviço
                </legend>
                <p class="mt-1 text-xs text-muted">
                    Selecione os profissionais disponíveis.
                </p>
                <div class="mt-4 grid max-h-56 gap-3 overflow-y-auto sm:grid-cols-2">
                    @forelse ($profissionais as $profissional)
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"
                        >
                            <input
                                type="checkbox"
                                name="profissionais[]"
                                value="{{ $profissional->id }}"
                                @checked(
                                    in_array(
                                        $profissional->id,
                                        $selectedProfissionais
                                    )
                                )
                                class="size-4 accent-brand-dark"
                            >
                            <span class="min-w-0 flex-1 text-sm">
                                {{ $profissional->nome }}
                            </span>
                            <x-status-badge
                                :active="$profissional->active"
                            />
                        </label>
                    @empty
                        <p class="text-sm text-muted sm:col-span-2">
                            Nenhum profissional cadastrado.
                        </p>
                    @endforelse
                </div>
            </fieldset>
           <div class="mt-5 flex items-center justify-between gap-3">

                <button
                    type="button"
                    class="admin-secondary"
                    onclick="
                        document.getElementById('profissionais-servico-{{ $servico->id }}').classList.add('hidden');
                        document.getElementById('dados-servico-{{ $servico->id }}').classList.remove('hidden');
                    "
                >
                    ← Voltar
                </button>

                <button
                    type="submit"
                    class="admin-button"
                    data-busy-label
                >
                    Salvar profissionais
                </button>

            </div>
        </form>
    </div>

    <div class="mt-6 border-t border-slate-100 pt-5">
        <button
            type="button"
            class="admin-secondary w-full"
            onclick="document.getElementById('modal-editar-servico-{{ $servico->id }}').close()"
        >
            Fechar
        </button>
    </div>
</dialog>