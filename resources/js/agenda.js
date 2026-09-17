import { Calendar } from '@fullcalendar/core';
import ptBr from '@fullcalendar/core/locales/pt-br';
import timeGridPlugin from '@fullcalendar/timegrid';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import '../css/agenda.css';

const root = document.querySelector('[data-agenda]');
const config = JSON.parse(root.dataset.config);
const find = (selector) => root.querySelector(selector);
const message = find('[data-agenda-message]');
const filter = find('#agenda-professional');
const createDialog = find('[data-create-dialog]');
const detailsDialog = find('[data-details-dialog]');
const form = find('[data-create-form]');
const service = form.elements.servico_id;
const professional = form.elements.profissional_id;
const date = find('#agenda-date');
const slot = form.elements.inicio;
const save = find('[data-save]');
const formError = find('[data-form-error]');
const slotsMessage = find('[data-slots-message]');
const detailsMessage = find('[data-details-message]');
const cancel = find('[data-cancel]');
const csrf = form.elements._token.value;
let saving = false;
let cancelling = false;
let slotRequest;
let detailsRequest;
let selectedDetails;
let preferredProfessional = '';
let preferredTime = '';
let feedVersion = 0;

async function request(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...options.headers },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || response.redirected) {
        const statusMessages = {
            401: 'Sua sessão expirou. Entre novamente no sistema.',
            403: 'Você não tem permissão para esta ação.',
            404: 'O registro não está mais disponível.',
            419: 'Sua sessão expirou. Atualize a página e entre novamente.',
            429: 'Muitas solicitações. Aguarde um momento e tente novamente.',
        };
        throw new Error(statusMessages[response.status] || Object.values(data.errors || {}).flat().join(' ') || (response.status === 422 ? data.message : '') || 'Não foi possível concluir a solicitação. Tente novamente.');
    }
    return data;
}

function errorText(error) {
    return error instanceof TypeError ? 'Falha de conexão. Verifique sua conexão e tente novamente.' : error.message;
}

function showFormError(text = '') {
    formError.textContent = text;
    formError.hidden = !text;
}

function resetSlots(text) {
    slot.replaceChildren(new Option(text, ''));
    slot.disabled = true;
    save.disabled = true;
}

async function loadSlots() {
    slotRequest?.abort();
    resetSlots('Selecione os dados acima');
    slotsMessage.textContent = '';
    find('[data-retry-slots]').hidden = true;
    if (!service.value || !professional.value || !date.value) return;
    const controller = new AbortController();
    slotRequest = controller;
    resetSlots('Carregando horários…');
    slotsMessage.textContent = 'Consultando horários disponíveis…';
    const url = new URL(config.availabilityUrl);
    url.search = new URLSearchParams({ servico_id: service.value, profissional_id: professional.value, data: date.value });
    try {
        const result = await request(url, { signal: controller.signal });
        if (controller.signal.aborted) return;
        slot.replaceChildren(new Option(result.data.length ? 'Selecione o horário' : 'Nenhum horário disponível', ''));
        for (const item of result.data) {
            slot.add(new Option(`${item.inicio.slice(11, 16)} – ${item.fim.slice(11, 16)}`, item.inicio));
        }
        slot.disabled = !result.data.length;
        save.disabled = saving || !result.data.length;
        const preferred = result.data.find((item) => item.inicio.slice(11, 16) === preferredTime);
        if (preferred) slot.value = preferred.inicio;
        slotsMessage.textContent = result.data.length ? 'A disponibilidade será conferida novamente ao salvar.' : 'Sem horários disponíveis. Escolha outra data ou profissional.';
        if (preferredTime && !preferred && result.data.length) slotsMessage.textContent = 'O horário selecionado não está disponível. Escolha um dos horários livres.';
    } catch (error) {
        if (controller.signal.aborted) return;
        resetSlots('Não foi possível carregar');
        slotsMessage.textContent = errorText(error);
        find('[data-retry-slots]').hidden = false;
    }
}

function updateProfessionals() {
    const previous = professional.value || preferredProfessional;
    const item = config.services.find((item) => String(item.id) === service.value);
    professional.replaceChildren(new Option(item?.professionals.length ? 'Selecione o profissional' : 'Nenhum profissional disponível', ''));
    for (const person of item?.professionals || []) professional.add(new Option(person.name, person.id));
    professional.disabled = !item?.professionals.length;
    if (item?.professionals.some((person) => String(person.id) === previous)) professional.value = previous;
    if (item?.professionals.length === 1) professional.value = String(item.professionals[0].id);
    loadSlots();
}

function openCreate(dateString = '') {
    form.reset();
    showFormError();
    preferredProfessional = filter.value;
    preferredTime = dateString.includes('T') ? dateString.slice(11, 16) : '';
    date.value = dateString.slice(0, 10) || siteNow().slice(0, 10);
    date.min = siteNow().slice(0, 10);
    updateProfessionals();
    createDialog.showModal();
}

// O banco armazena horários locais; o calendário usa os mesmos valores sem conversão do navegador.
function siteNow() {
    const parts = new Intl.DateTimeFormat('sv-SE', {
        timeZone: config.timezone, year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
    }).formatToParts(new Date());
    const value = Object.fromEntries(parts.map((part) => [part.type, part.value]));
    return `${value.year}-${value.month}-${value.day}T${value.hour}:${value.minute}:${value.second}`;
}

async function openDetails(event) {
    if (!event.extendedProps.detailsUrl) return;
    detailsRequest?.abort();
    const controller = new AbortController();
    detailsRequest = controller;
    selectedDetails = null;
    cancel.hidden = true;
    find('[data-details]').replaceChildren();
    detailsMessage.textContent = 'Carregando detalhes…';
    detailsDialog.showModal();
    try {
        const url = new URL(event.extendedProps.detailsUrl);
        url.searchParams.set('calendar', '1');
        const result = await request(url, { signal: controller.signal });
        if (controller.signal.aborted) return;
        selectedDetails = result.extendedProps;
        const labels = { cliente: 'Cliente', telefone: 'Telefone', servico: 'Serviço', profissional: 'Profissional', periodo: 'Horário', status: 'Situação', observacoes: 'Observações' };
        for (const [key, label] of Object.entries(labels)) {
            const term = document.createElement('dt');
            term.textContent = label;
            const description = document.createElement('dd');
            description.textContent = key === 'status' ? ({ agendado: 'Agendado', cancelado: 'Cancelado' }[selectedDetails[key]] || selectedDetails[key]) : selectedDetails[key] || 'Não informado';
            find('[data-details]').append(term, description);
        }
        cancel.hidden = selectedDetails.status === 'cancelado';
        detailsMessage.textContent = '';
    } catch (error) {
        if (!controller.signal.aborted) detailsMessage.textContent = errorText(error);
    }
}

const calendar = new Calendar(find('[data-calendar]'), {
    plugins: [timeGridPlugin, dayGridPlugin, interactionPlugin, listPlugin],
    locale: ptBr,
    timeZone: config.timezone,
    initialView: window.matchMedia('(max-width: 767px)').matches ? 'timeGridDay' : 'timeGridWeek',
    initialDate: config.now,
    now: siteNow,
    headerToolbar: false,
    firstDay: 1,
    allDaySlot: false,
    editable: false,
    eventStartEditable: false,
    eventDurationEditable: false,
    eventInteractive: true,
    nowIndicator: true,
    slotDuration: '00:30:00',
    scrollTime: `${config.now.slice(11, 13)}:00:00`,
    height: 720,
    dayMaxEvents: true,
    displayEventEnd: true,
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    noEventsText: 'Nenhum agendamento neste período.',
    events: async (info, success, failure) => {
        const version = ++feedVersion;
        const url = new URL(config.feedUrl);
        url.search = new URLSearchParams({ start: info.startStr.slice(0, 10), end: info.endStr.slice(0, 10), profissional_id: filter.value });
        try {
            const events = await request(url);
            success(events);
            if (version === feedVersion) {
                const total = events.filter((event) => event.display !== 'background').length;
                message.textContent = total ? `${total} agendamento(s) neste período.` : 'Nenhum agendamento neste período. Use “Novo agendamento” para adicionar.';
            }
        } catch (error) {
            failure(error);
            if (version === feedVersion) message.textContent = errorText(error);
        }
    },
    loading: (busy) => {
        find('[data-calendar]').setAttribute('aria-busy', String(busy));
        if (busy) message.textContent = 'Carregando agenda…';
    },
    datesSet: (info) => {
        find('[data-period]').textContent = info.view.title;
        root.querySelectorAll('[data-view]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.view === info.view.type)));
    },
    dateClick: (info) => openCreate(info.dateStr),
    eventClick: (info) => openDetails(info.event),
    eventDidMount: (info) => {
        if (info.event.display !== 'background') {
            info.el.title = `${info.event.title} · ${info.event.extendedProps.profissional} · ${info.event.extendedProps.status}`;
            info.el.setAttribute('aria-label', info.el.title);
        }
    },
});

calendar.render();
find('[data-new]').addEventListener('click', () => openCreate());
find('[data-refresh]').addEventListener('click', () => calendar.refetchEvents());
find('[data-prev]').addEventListener('click', () => calendar.prev());
find('[data-next]').addEventListener('click', () => calendar.next());
find('[data-today]').addEventListener('click', () => calendar.today());
root.querySelectorAll('[data-view]').forEach((button) => button.addEventListener('click', () => calendar.changeView(button.dataset.view)));
filter.addEventListener('change', () => {
    const person = config.professionals.find((item) => String(item.id) === filter.value);
    calendar.setOption('businessHours', person ? (person.hours.length ? person.hours : { daysOfWeek: [] }) : false);
    calendar.refetchEvents();
});
service.addEventListener('change', updateProfessionals);
professional.addEventListener('change', loadSlots);
find('[data-retry-slots]').addEventListener('click', loadSlots);
date.addEventListener('change', () => { preferredTime = ''; loadSlots(); });
root.querySelectorAll('[data-close]').forEach((button) => button.addEventListener('click', () => {
    if (!saving && !cancelling) button.closest('dialog').close();
}));
for (const dialog of [createDialog, detailsDialog]) {
    dialog.addEventListener('cancel', (event) => { if (saving || cancelling) event.preventDefault(); });
}
createDialog.addEventListener('close', () => slotRequest?.abort());
detailsDialog.addEventListener('close', () => detailsRequest?.abort());

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (saving || slot.disabled || !slot.value) return;
    const payload = Object.fromEntries(new FormData(form));
    saving = true;
    showFormError();
    form.setAttribute('aria-busy', 'true');
    const controls = [...form.querySelectorAll('input, select, textarea, button')];
    const previous = controls.map((control) => control.disabled);
    controls.forEach((control) => { control.disabled = true; });
    save.textContent = 'Salvando…';
    let succeeded = false;
    try {
        await request(config.storeUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        succeeded = true;
        if (filter.value && filter.value !== payload.profissional_id) {
            filter.value = payload.profissional_id;
            filter.dispatchEvent(new Event('change'));
        }
        calendar.gotoDate(date.value);
        calendar.refetchEvents();
        createDialog.close();
    } catch (error) {
        showFormError(errorText(error));
    } finally {
        saving = false;
        controls.forEach((control, index) => { control.disabled = previous[index]; });
        save.textContent = 'Salvar agendamento';
        form.removeAttribute('aria-busy');
        if (!succeeded) { preferredTime = ''; await loadSlots(); }
    }
});

cancel.addEventListener('click', async () => {
    if (cancelling || !selectedDetails || !window.confirm(`Cancelar o agendamento de ${selectedDetails.cliente}? Esta ação não pode ser desfeita.`)) return;
    cancelling = true;
    cancel.disabled = true;
    cancel.textContent = 'Cancelando…';
    detailsMessage.textContent = '';
    try {
        await request(selectedDetails.cancelUrl, { method: 'PATCH' });
        detailsDialog.close();
        calendar.refetchEvents();
    } catch (error) {
        detailsMessage.textContent = errorText(error);
        calendar.refetchEvents();
    } finally {
        cancelling = false;
        cancel.disabled = false;
        cancel.textContent = 'Cancelar agendamento';
    }
});
