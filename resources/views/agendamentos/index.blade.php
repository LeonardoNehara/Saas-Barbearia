@extends('layouts.admin')
@section('title', 'Agenda da Barbearia')
@section('breadcrumb')<li aria-current="page" class="font-semibold">Agenda da Barbearia</li>@endsection
@section('content')
    <section data-agenda data-config="{{ json_encode($agendaConfig) }}" aria-labelledby="agenda-title">
        <div class="agenda-heading">
            <div>
                <div class="flex items-center gap-3">
                    <button type="button" data-open-menu aria-label="Abrir menu" aria-controls="mobile-menu" aria-expanded="false" class="shrink-0 rounded-lg p-1 text-muted hover:bg-slate-100"><x-icon name="menu" class="size-5" /></button>
                    <h1 id="agenda-title">Agenda da Barbearia</h1>
                </div>
                <p>Acompanhe o dia, organize os próximos atendimentos.</p>
            </div>
            <span class="agenda-clock"><span aria-hidden="true"></span>Agora <time data-clock></time></span>
        </div>
        <div class="agenda-layout">
        <aside class="agenda-mini-month" aria-label="Calendário mensal">
            <div class="agenda-mini-month-heading">
                <h2 id="agenda-mini-month-title" data-mini-month-title aria-live="polite"></h2>
                <button type="button" data-mini-month-prev aria-label="Mês anterior">‹</button>
                <button type="button" data-mini-month-next aria-label="Próximo mês">›</button>
            </div>
            <div class="agenda-mini-month-weekdays" aria-hidden="true"><span>D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span>S</span></div>
            <div data-mini-month-days class="agenda-mini-month-days" role="group" aria-labelledby="agenda-mini-month-title"></div>
        </aside>
        <div class="agenda-workspace">
        <div class="agenda-toolbar">
            <div class="agenda-person">
                <span class="agenda-avatar" data-avatar aria-hidden="true"></span>
                <div><label for="agenda-professional">Agenda de</label><select id="agenda-professional" class="admin-input" @disabled($profissionais->isEmpty())>@forelse ($profissionais as $profissional)<option value="{{ $profissional->id }}">{{ $profissional->nome }}{{ $profissional->active ? '' : ' (inativo)' }}</option>@empty<option value="">Nenhum profissional</option>@endforelse</select></div>
            </div>
            <div class="agenda-navigation">
                <button type="button" data-prev aria-label="Período anterior" class="admin-secondary">‹</button>
                <h2 data-period aria-live="polite"></h2>
                <button type="button" data-next aria-label="Próximo período" class="admin-secondary">›</button>
                <button type="button" data-today class="admin-secondary">Hoje</button>
            </div>
            <div class="agenda-views" aria-label="Visualização da agenda"><button type="button" data-view="timeGridDay" aria-pressed="true">Dia</button><button type="button" data-view="timeGridWeek" aria-pressed="false">Semana</button><button type="button" data-view="dayGridMonth" aria-pressed="false">Mês</button></div>
            <button type="button" data-new class="admin-button"><x-icon name="plus" class="size-4" />Novo agendamento</button>
        </div>
        <div class="agenda-summary" aria-label="Resumo de hoje" aria-live="polite">
            <strong>Hoje</strong><span data-today-count>— atendimentos</span>
            <button type="button" data-first-free class="agenda-free-link" disabled>Consultando tempo livre…</button>
            <span data-current-state></span>
            <button type="button" data-next-appointment class="agenda-next-appointment" disabled>Consultando próximo atendimento…</button>
        </div>
        <div class="agenda-feedback"><p data-agenda-message role="status">Carregando agenda…</p><button type="button" data-retry-feed hidden>Tentar novamente</button></div>
        <div data-calendar></div>
        <div class="agenda-legend" aria-label="Legenda da agenda">
            <span><i class="legend-free"></i>Disponível</span><span><i class="legend-booked"></i>Agendado</span><span><i class="legend-past"></i>Passado</span><span><i class="legend-break"></i>Almoço / intervalo</span><span><i class="legend-closed"></i>Fora do expediente</span>
            <span class="agenda-timezone">{{ $agendaConfig['timezone'] }}</span>
        </div>
        </div>
        </div>
        <p class="agenda-help">Clique em um horário livre para agendar ou em um atendimento para ver os detalhes. O tempo disponível depende da duração do serviço.</p>
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
                    <div>
                        <label for="agenda-slot-trigger" class="mb-2 block text-sm font-medium">Horário</label>
                        <select id="agenda-slot" name="inicio" hidden disabled><option value="">Selecione os dados acima</option></select>
                        <button id="agenda-slot-trigger" type="button" class="admin-input agenda-slot-trigger" popovertarget="agenda-slot-picker" aria-haspopup="dialog" aria-describedby="agenda-slots-message" disabled><x-icon name="clock" class="size-4" /><span data-slot-label>Escolher horário</span><span aria-hidden="true">›</span></button>
                        <div id="agenda-slot-picker" popover="auto" role="dialog" aria-labelledby="agenda-slot-picker-title" class="agenda-slot-picker">
                            <div class="agenda-slot-picker-heading">
                                <span class="agenda-slot-picker-icon"><x-icon name="clock" class="size-5" /></span>
                                <div><h3 id="agenda-slot-picker-title">Horários disponíveis</h3><p>Selecione um horário para o atendimento.</p></div>
                                <button type="button" popovertarget="agenda-slot-picker" popovertargetaction="hide" aria-label="Fechar horários" class="agenda-slot-close"><x-icon name="close" class="size-4" /></button>
                            </div>
                            <p class="agenda-slot-date"><x-icon name="calendar" class="size-4" /><span data-slot-date></span></p>
                            <div data-slot-groups class="agenda-slot-groups"></div>
                            <div class="agenda-slot-picker-footer"><button type="button" data-clear-slot class="agenda-slot-clear">Limpar seleção</button><button type="button" data-confirm-slot class="admin-button" disabled>Confirmar horário</button></div>
                        </div>
                    </div>
                </div>
                <p id="agenda-slots-message" data-slots-message role="status" class="text-sm text-muted"></p>
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
