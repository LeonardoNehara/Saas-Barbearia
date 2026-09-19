<dialog
    id="modal-disponibilidade-profissional-{{ $profissional->id }}"
    class="admin-dialog"
    aria-labelledby="disponibilidade-title-{{ $profissional->id }}"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2
                id="disponibilidade-title-{{ $profissional->id }}"
                class="text-xl font-bold"
            >
                Horários e intervalos
            </h2>

            <p class="mt-1 text-sm text-muted">
                {{ $profissional->nome }}
            </p>
        </div>

        <button
            type="button"
            class="admin-secondary"
            aria-label="Fechar"
            onclick="document.getElementById('modal-disponibilidade-profissional-{{ $profissional->id }}').close()"
        >
            <x-icon name="close" class="size-4" />
        </button>
    </div>

    @include('profissionais.partials.horarios')
</dialog>
