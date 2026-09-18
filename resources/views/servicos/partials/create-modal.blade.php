@php
    $hasCreateErrors =
        old('form_context') === 'create-servico';
@endphp
<dialog
    id="modal-novo-servico"
    class="admin-dialog"
    aria-labelledby="novo-servico-title"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 id="novo-servico-title" class="text-xl font-bold">
                Novo serviço
            </h2>

            <p class="mt-1 text-sm text-muted">
                Cadastre um serviço oferecido pela sua barbearia.
            </p>
        </div>

        <button
            type="button"
            class="admin-secondary"
            aria-label="Fechar"
            onclick="document.getElementById('modal-novo-servico').close()"
        >
            <x-icon name="close" class="size-4" />
        </button>
    </div>

    <form
        method="POST"
        action="{{ route('servicos.store') }}"
        data-busy-form
        class="mt-6"
    >
        @csrf

        <input type="hidden" name="form_context" value="create-servico">

        <div class="grid gap-5 sm:grid-cols-2">

            <div class="sm:col-span-2">
                <label for="create-servico-nome" class="mb-2 block text-sm font-medium">
                    Nome do serviço *
                </label>

                <input
                    id="create-servico-nome"
                    name="nome"
                    value="{{ old('nome') }}"
                    required
                    maxlength="255"
                    class="admin-input"
                >

                @error('nome')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="create-servico-descricao" class="mb-2 block text-sm font-medium">
                    Descrição
                    <span class="font-normal text-muted">(opcional)</span>
                </label>

                <textarea
                    id="create-servico-descricao"
                    name="descricao"
                    rows="3"
                    maxlength="10000"
                    class="admin-input resize-y"
                >{{ old('descricao') }}</textarea>

                @error('descricao')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="create-servico-duracao" class="mb-2 block text-sm font-medium">
                    Duração em minutos *
                </label>

                <input
                    id="create-servico-duracao"
                    name="duracao_minutos"
                    type="number"
                    min="5"
                    max="480"
                    step="1"
                    value="{{ old('duracao_minutos') }}"
                    required
                    class="admin-input"
                >

                @error('duracao_minutos')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="create-servico-preco" class="mb-2 block text-sm font-medium">
                    Preço (R$) *
                </label>

                <input
                    id="create-servico-preco"
                    name="preco"
                    type="number"
                    inputmode="decimal"
                    min="0"
                    max="99999999.99"
                    step="0.01"
                    value="{{ old('preco') }}"
                    required
                    class="admin-input"
                >

                @error('preco')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-7 flex justify-end gap-3 border-t border-slate-100 pt-5">
            <button
                type="button"
                class="admin-secondary"
                onclick="document.getElementById('modal-novo-servico').close()"
            >
                Cancelar
            </button>

            <button type="submit" class="admin-button" data-busy-label>
                Cadastrar serviço
            </button>
        </div>
    </form>
</dialog>