@extends('layouts.admin')
@section('title', 'Agenda da Barbearia')
@section('breadcrumb')<li aria-current="page" class="font-semibold">Agenda da Barbearia</li>@endsection
@section('content')
    <section data-agenda data-config="{{ json_encode($agendaConfig) }}" aria-labelledby="agenda-title">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div><h1 id="agenda-title" class="text-2xl font-bold tracking-tight">Agenda da Barbearia</h1><p class="mt-2 text-sm text-muted">Gerencie seus horários e profissionais de forma simples e rápida.</p></div>
            <button type="button" data-new class="admin-button"><x-icon name="plus" class="size-4" />Novo agendamento</button>
        </div>
        <div class="agenda-toolbar mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3">
            <div><label for="agenda-professional" class="sr-only">Filtrar profissional</label><select id="agenda-professional" class="admin-input"><option value="">Todos os profissionais</option>@foreach ($profissionais as $profissional)<option value="{{ $profissional->id }}">{{ $profissional->nome }}{{ $profissional->active ? '' : ' (inativo)' }}</option>@endforeach</select></div>
            <button type="button" data-refresh class="admin-secondary">Atualizar</button>
            <button type="button" data-view="listWeek" aria-pressed="false" class="admin-secondary">Ver em lista</button>
            <div class="flex flex-1 items-center justify-center gap-2">
                <button type="button" data-prev aria-label="Período anterior" class="admin-secondary">‹</button>
                <h2 data-period class="min-w-32 text-center text-sm font-semibold" aria-live="polite"></h2>
                <button type="button" data-next aria-label="Próximo período" class="admin-secondary">›</button>
            </div>
            <button type="button" data-today class="admin-secondary">Hoje</button>
            <div class="flex gap-1" aria-label="Visualização da agenda"><button type="button" data-view="timeGridDay" aria-pressed="false" class="admin-secondary">Dia</button><button type="button" data-view="timeGridWeek" aria-pressed="false" class="admin-secondary">Semana</button><button type="button" data-view="dayGridMonth" aria-pressed="false" class="admin-secondary">Mês</button></div>
        </div>
        <p data-agenda-message role="status" class="mb-3 text-sm text-muted">Carregando agenda…</p>
        <div data-calendar class="rounded-2xl border border-slate-200 bg-white p-2 sm:p-4"></div>
        <p class="mt-3 text-xs leading-5 text-muted">Horários de {{ $agendaConfig['timezone'] }}. Selecione um agendamento para consultar ou cancelar. Ao filtrar um profissional, os bloqueios aparecem em vermelho e os períodos fora da jornada em cinza.</p>
        <noscript><p class="mt-4 text-red-700">Ative o JavaScript para usar a agenda.</p></noscript>

        <dialog data-create-dialog aria-labelledby="create-title" class="agenda-dialog">
            <div class="flex items-center justify-between gap-4"><h2 id="create-title" class="text-xl font-bold">Novo agendamento</h2><button type="button" data-close aria-label="Fechar cadastro" class="admin-secondary"><x-icon name="close" class="size-4" /></button></div>
            <form data-create-form class="mt-6 space-y-4">
                @csrf
                @if ($servicos->isEmpty())<p class="text-sm text-muted">Cadastre um serviço ativo e vincule um profissional antes de criar um agendamento.</p>@endif
                <p data-form-error role="alert" class="text-sm text-red-700" hidden></p>
                <div><label for="agenda-service" class="mb-2 block text-sm font-medium">Serviço</label><select id="agenda-service" name="servico_id" class="admin-input" required><option value="">Selecione o serviço</option>@foreach ($servicos as $servico)<option value="{{ $servico->id }}">{{ $servico->nome }} · {{ $servico->duracao_minutos }} min · R$ {{ number_format((float) $servico->preco, 2, ',', '.') }}</option>@endforeach</select></div>
                <div><label for="create-professional" class="mb-2 block text-sm font-medium">Profissional</label><select id="create-professional" name="profissional_id" class="admin-input" required disabled><option value="">Selecione primeiro o serviço</option></select></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="agenda-date" class="mb-2 block text-sm font-medium">Data</label><input id="agenda-date" type="date" class="admin-input" min="{{ substr($agendaConfig['now'], 0, 10) }}" required></div>
                    <div><label for="agenda-slot" class="mb-2 block text-sm font-medium">Horário disponível</label><select id="agenda-slot" name="inicio" class="admin-input" required disabled><option value="">Selecione os dados acima</option></select></div>
                </div>
                <p data-slots-message role="status" class="text-sm text-muted"></p>
                <button type="button" data-retry-slots class="admin-secondary" hidden>Tentar consultar horários novamente</button>
                <div><label for="agenda-client" class="mb-2 block text-sm font-medium">Nome do cliente</label><input id="agenda-client" name="cliente_nome" autocomplete="name" maxlength="150" class="admin-input" required></div>
                <div><label for="agenda-phone" class="mb-2 block text-sm font-medium">Telefone</label><input id="agenda-phone" name="cliente_telefone" type="tel" autocomplete="tel" maxlength="20" class="admin-input" required></div>
                <div><label for="agenda-notes" class="mb-2 block text-sm font-medium">Observações <span class="font-normal text-muted">(opcional)</span></label><textarea id="agenda-notes" name="observacoes" rows="3" maxlength="2000" class="admin-input"></textarea></div>
                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-4"><button type="button" data-close class="admin-secondary">Voltar</button><button type="submit" data-save class="admin-button" disabled>Salvar agendamento</button></div>
            </form>
        </dialog>

        <dialog data-details-dialog aria-labelledby="details-title" class="agenda-dialog">
            <div class="flex items-center justify-between gap-4"><h2 id="details-title" class="text-xl font-bold">Detalhes do agendamento</h2><button type="button" data-close aria-label="Fechar detalhes" class="admin-secondary"><x-icon name="close" class="size-4" /></button></div>
            <p data-details-message role="status" class="mt-4 text-sm text-muted"></p>
            <dl data-details class="agenda-details mt-5"></dl>
            <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-4"><button type="button" data-close class="admin-secondary">Fechar</button><button type="button" data-cancel class="admin-secondary text-red-700" hidden>Cancelar agendamento</button></div>
        </dialog>
    </section>
@endsection
