import { Calendar } from '@fullcalendar/core';
import ptBr from '@fullcalendar/core/locales/pt-br';
import timeGridPlugin from '@fullcalendar/timegrid';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
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
const slotTrigger = find('#agenda-slot-trigger');
const slotPicker = find('#agenda-slot-picker');
const slotGroups = find('[data-slot-groups]');
const confirmSlot = find('[data-confirm-slot]');
let pendingSlot = '';
const save = find('[data-save]');
const formError = find('[data-form-error]');
const slotsMessage = find('[data-slots-message]');
const detailsMessage = find('[data-details-message]');
const cancel = find('[data-cancel]');
const csrf = form.elements._token.value;
const firstActiveProfessional = config.professionals.find((person) => person.active);
if (firstActiveProfessional) filter.value = String(firstActiveProfessional.id);
let saving = false;
let cancelling = false;
let slotRequest;
let detailsRequest;
let selectedDetails;
let preferredProfessional = '';
let preferredTime = '';
let feedVersion = 0;
let feedRequest;
let visibleEvents = [];
let todayEvents = [];
let nextAppointment;
let firstFree;
let feedReady = false;
let todayReady = false;
let loadedDay = siteNow().slice(0, 10);

const selectedPerson = () => config.professionals.find((person) => String(person.id) === filter.value);
const localStamp = (value) => value.slice(0, 19).replace(' ', 'T');
const stampTime = (value) => localStamp(value).slice(11, 16);
const minutes = (value) => Number(value.slice(0, 2)) * 60 + Number(value.slice(3, 5));
const timeString = (value) => `${String(Math.floor(value / 60)).padStart(2, '0')}:${String(value % 60).padStart(2, '0')}:00`;
const nextDay = (day) => new Date(Date.parse(`${day}T00:00:00Z`) + 86400000).toISOString().slice(0, 10);
const duration = (event) => Math.round((Date.parse(`${localStamp(event.end)}Z`) - Date.parse(`${localStamp(event.start)}Z`)) / 60000);
const activeEvents = (events) => events.filter((event) => event.display !== 'background' && event.extendedProps?.status !== 'cancelado');

function dayHours(day) {
    const weekday = new Date(`${day}T00:00:00Z`).getUTCDay();
    return (selectedPerson()?.hours || []).filter((hour) => hour.daysOfWeek.includes(weekday));
}

function minimumDuration() {
    const durations = config.services.filter((item) => item.professionals.some((person) => String(person.id) === filter.value)).map((item) => item.duration);
    return durations.length ? Math.min(...durations) : Infinity;
}

// Faixas livres são apenas uma indicação visual; o servidor confirma os horários por serviço.
function freePeriods(day, events) {
    if (!selectedPerson()?.active || day < siteNow().slice(0, 10)) return [];
    const now = siteNow();
    const occupied = events.filter((event) => event.extendedProps?.status !== 'cancelado')
        .filter((event) => localStamp(event.start) < `${nextDay(day)}T00:00:00` && localStamp(event.end) > `${day}T00:00:00`)
        .map((event) => [localStamp(event.start).slice(0, 10) < day ? 0 : minutes(stampTime(event.start)), localStamp(event.end).slice(0, 10) > day ? 1440 : minutes(stampTime(event.end))]);
    const result = [];
    for (const hour of dayHours(day)) {
        let ranges = [[minutes(hour.startTime), minutes(hour.endTime)]];
        const exclusions = [...occupied];
        if (hour.breakStart && hour.breakEnd) exclusions.push([minutes(hour.breakStart), minutes(hour.breakEnd)]);
        if (day === now.slice(0, 10)) exclusions.push([0, Math.ceil((minutes(now.slice(11, 16)) + Number(now.slice(17, 19)) / 60) / 15) * 15]);
        for (const [start, end] of exclusions) {
            ranges = ranges.flatMap(([from, to]) => end <= from || start >= to ? [[from, to]] : [[from, Math.min(start, to)], [Math.max(end, from), to]].filter(([a, b]) => a < b));
        }
        for (const [from, to] of ranges) {
            const start = Math.ceil(from / 15) * 15;
            if (to - start >= minimumDuration()) result.push({ start: `${day}T${timeString(start)}`, end: `${day}T${timeString(to)}` });
        }
    }
    return result.sort((a, b) => a.start.localeCompare(b.start));
}

function timelineEvents(events, start, end) {
    const now = siteNow();
    const result = events.map((event) => ({ ...event, classNames: [...(event.classNames || []), ...(localStamp(event.end) <= now ? ['agenda-past-event'] : [])] }));
    for (let day = start; day < end; day = nextDay(day)) {
        for (const hour of dayHours(day)) {
            if (hour.breakStart && hour.breakEnd) result.push({
                start: `${day}T${hour.breakStart}`, end: `${day}T${hour.breakEnd}`, title: 'Almoço / intervalo',
                display: 'background', classNames: ['agenda-break'], extendedProps: { kind: 'break' },
            });
        }
        for (const period of freePeriods(day, events)) result.push({
            ...period, title: 'Disponível', display: 'background', classNames: ['agenda-free'], extendedProps: { kind: 'free' },
        });
        if (day <= now.slice(0, 10)) result.push({
            start: `${day}T00:00:00`, end: day === now.slice(0, 10) ? now : `${nextDay(day)}T00:00:00`,
            display: 'background', classNames: ['agenda-past'], extendedProps: { kind: 'past' },
        });
    }
    return result;
}

function updateSummary() {
    if (!todayReady) return;
    const now = siteNow();
    const day = now.slice(0, 10);
    const appointments = activeEvents(todayEvents).filter((event) => localStamp(event.start).slice(0, 10) === day).sort((a, b) => a.start.localeCompare(b.start));
    const current = appointments.find((event) => localStamp(event.start) <= now && localStamp(event.end) > now);
    nextAppointment = appointments.find((event) => localStamp(event.start) > now);
    const free = freePeriods(day, todayEvents);
    firstFree = free[0];
    const totalMinutes = free.reduce((total, period) => total + duration(period), 0);
    find('[data-today-count]').textContent = `${appointments.length} atendimento${appointments.length === 1 ? '' : 's'}`;
    find('[data-first-free]').textContent = totalMinutes ? `${Math.floor(totalMinutes / 60) ? `${Math.floor(totalMinutes / 60)}h ` : ''}${totalMinutes % 60 ? `${totalMinutes % 60}min ` : ''}livres · a partir de ${stampTime(firstFree.start)}` : 'Sem horários livres hoje';
    find('[data-first-free]').disabled = !firstFree;
    const upcoming = find('[data-next-appointment]');
    upcoming.textContent = nextAppointment ? `Próximo: ${stampTime(nextAppointment.start)} · ${nextAppointment.extendedProps.cliente} — ${nextAppointment.extendedProps.servico || 'Serviço'}` : 'Nenhum próximo atendimento hoje';
    upcoming.disabled = !nextAppointment;
    const hourNow = now.slice(11, 19);
    const hours = dayHours(day);
    const atLunch = hours.some((hour) => hour.breakStart && hour.breakStart <= hourNow && hour.breakEnd > hourNow);
    const atWork = hours.some((hour) => hour.startTime <= hourNow && hour.endTime > hourNow);
    const blocked = todayEvents.some((event) => event.display === 'background' && localStamp(event.start) <= now && localStamp(event.end) > now);
    find('[data-current-state]').textContent = !selectedPerson()?.active ? 'Profissional inativo' : current ? `Agora: ${current.extendedProps.cliente} · previsto até ${stampTime(current.end)}` : atLunch ? 'Agora: intervalo de almoço' : blocked ? 'Agora: indisponível' : atWork ? 'Agora: sem atendimento' : 'Agora: fora do expediente';
}

function updateContext() {
    const person = selectedPerson();
    find('[data-avatar]').textContent = person?.name.slice(0, 1).toUpperCase() || '—';
    find('[data-new]').disabled = !person?.active;
    find('[data-calendar]').setAttribute('aria-label', person ? `Agenda de ${person.name}` : 'Agenda sem profissional');
}

function businessHours() {
    return selectedPerson()?.hours.length ? selectedPerson().hours.map(({ daysOfWeek, startTime, endTime }) => ({ daysOfWeek, startTime, endTime })) : { daysOfWeek: [] };
}

function updateBounds(events = []) {
    const hours = selectedPerson()?.hours || [];
    const times = [...hours.flatMap((hour) => [minutes(hour.startTime), minutes(hour.endTime)]), ...events.filter((event) => event.display !== 'background').flatMap((event) => [minutes(stampTime(event.start)), localStamp(event.end).slice(0, 10) > localStamp(event.start).slice(0, 10) ? 1440 : minutes(stampTime(event.end))])];
    calendar.batchRendering(() => {
        calendar.setOption('slotMinTime', timeString(Math.max(0, Math.floor(Math.min(480, ...times) / 60) * 60 - 60)));
        calendar.setOption('slotMaxTime', timeString(Math.min(1440, Math.ceil(Math.max(1140, ...times) / 60) * 60 + 60)));
    });
}

function renderEvent(info) {
    const event = info.event;
    const kind = event.extendedProps.kind;
    const content = document.createElement('div');
    if (kind === 'past') return { domNodes: [] };
    if (event.display === 'background') {
        content.className = 'agenda-background-label';
        content.textContent = kind === 'free' ? 'Disponível' : `${event.startStr.slice(11, 16)} – ${event.endStr.slice(11, 16)} · ${event.title}`;
        if (kind === 'free') {
            const action = document.createElement('span');
            action.textContent = '+ Agendar';
            content.append(action);
        }
    } else {
        content.className = 'agenda-event-content';
        const fields = [
            ['agenda-event-time', `${event.startStr.slice(11, 16)}–${event.endStr.slice(11, 16)}`],
            ['agenda-event-client', event.extendedProps.cliente],
            ['agenda-event-service', event.extendedProps.servico || 'Serviço'],
            ['agenda-event-duration', `${duration({ start: event.startStr, end: event.endStr })} min${event.extendedProps.status === 'cancelado' ? ' · Cancelado' : ''}`],
        ];
        for (const [className, text] of fields) {
            const item = document.createElement('span');
            item.className = className;
            item.textContent = text;
            content.append(item);
        }
    }
    return { domNodes: [content] };
}

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

function updateSlotSelection() {
    slotTrigger.disabled = slot.disabled;
    find('[data-slot-label]').textContent = slot.value ? slot.value.slice(11, 16) : 'Escolher horário';
    save.disabled = saving || slot.disabled || !slot.value;
}

function updatePendingSlot() {
    slotGroups.querySelectorAll('button').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.value === pendingSlot));
    });
    confirmSlot.disabled = !pendingSlot;
}

function renderSlotChoices() {
    slotGroups.replaceChildren();
    find('[data-slot-date]').textContent = new Intl.DateTimeFormat('pt-BR', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    }).format(new Date(`${date.value}T12:00:00`));
    const periods = [
        { name: 'Manhã', start: 0, end: 12, icon: '☀' },
        { name: 'Tarde', start: 12, end: 18, icon: '☀' },
        { name: 'Noite', start: 18, end: 24, icon: '☾' },
    ];
    for (const period of periods) {
        const options = [...slot.options].filter((option) => {
            const hour = Number(option.value.slice(11, 13));
            return option.value && hour >= period.start && hour < period.end;
        });
        if (!options.length) continue;
        const section = document.createElement('section');
        const heading = document.createElement('h4');
        heading.textContent = `${period.icon}  ${period.name}`;
        const grid = document.createElement('div');
        grid.className = 'agenda-slot-grid';
        for (const option of options) {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.value = option.value;
            button.textContent = option.value.slice(11, 16);
            button.setAttribute('aria-label', option.textContent);
            button.addEventListener('click', () => {
                pendingSlot = option.value;
                updatePendingSlot();
            });
            grid.append(button);
        }
        section.append(heading, grid);
        slotGroups.append(section);
    }
}

function positionSlotPicker() {
    if (!slotPicker.matches(':popover-open')) return;
    const anchor = slotTrigger.getBoundingClientRect();
    const dialog = createDialog.getBoundingClientRect();
    const width = slotPicker.offsetWidth;
    const height = slotPicker.offsetHeight;
    const left = dialog.right + 12 + width <= window.innerWidth - 12
        ? dialog.right + 12
        : Math.max(12, Math.min(anchor.left, window.innerWidth - width - 12));
    slotPicker.style.left = `${left}px`;
    slotPicker.style.top = `${Math.max(12, Math.min(dialog.top + 24, window.innerHeight - height - 12))}px`;
}

slotPicker.addEventListener('toggle', (event) => {
    slotTrigger.setAttribute('aria-expanded', String(event.newState === 'open'));
    if (event.newState !== 'open') return;
    pendingSlot = slot.value;
    updatePendingSlot();
    positionSlotPicker();
    const buttons = [...slotGroups.querySelectorAll('button')];
    (buttons.find((button) => button.dataset.value === pendingSlot) || buttons[0])?.focus();
});
window.addEventListener('resize', positionSlotPicker);
find('[data-clear-slot]').addEventListener('click', () => {
    pendingSlot = '';
    slot.value = '';
    updatePendingSlot();
    updateSlotSelection();
});
confirmSlot.addEventListener('click', () => {
    if (!pendingSlot || slot.disabled) return;
    slot.value = pendingSlot;
    updateSlotSelection();
    slotPicker.hidePopover();
    slotTrigger.focus();
});

function resetSlots(text) {
    slotPicker.hidePopover();
    slotGroups.replaceChildren();
    pendingSlot = '';
    slotTrigger.disabled = true;
    find('[data-slot-label]').textContent = text;
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
        renderSlotChoices();
        updateSlotSelection();
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
    if (!item) {
        const person = selectedPerson();
        professional.replaceChildren(new Option(person?.name || 'Selecione o profissional', person?.id || ''));
        professional.disabled = true;
        loadSlots();
        return;
    }
    professional.replaceChildren(new Option(item?.professionals.length ? 'Selecione o profissional' : 'Nenhum profissional disponível', ''));
    for (const person of item?.professionals || []) professional.add(new Option(person.name, person.id));
    professional.disabled = !item?.professionals.length;
    if (item?.professionals.some((person) => String(person.id) === previous)) professional.value = previous;
    if (item?.professionals.length === 1) professional.value = String(item.professionals[0].id);
    loadSlots();
}

function openCreate(dateString = '') {
    if (!selectedPerson()?.active || createDialog.open) return;
    form.reset();
    showFormError();
    preferredProfessional = filter.value;
    preferredTime = dateString.includes('T') ? dateString.slice(11, 16) : '';
    date.value = dateString.slice(0, 10) || siteNow().slice(0, 10);
    date.min = siteNow().slice(0, 10);
    service.replaceChildren(new Option('Selecione o serviço', ''));
    for (const item of config.services.filter((item) => item.professionals.some((person) => String(person.id) === preferredProfessional))) {
        const price = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(item.price);
        service.add(new Option(`${item.name} · ${item.duration} min · ${price}`, item.id));
    }
    if (service.options.length === 2) service.selectedIndex = 1;
    updateProfessionals();
    if (service.options.length === 1) showFormError('Este profissional não possui serviços ativos vinculados. Vincule um serviço para agendar.');
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

let miniMonthDate = new Date(`${config.now.slice(0, 7)}-01T12:00:00Z`);
let miniMonthSelectedDate = config.now.slice(0, 10);
const miniMonthDays = find('[data-mini-month-days]');
const miniMonthFormatter = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric', timeZone: 'UTC' });
const miniDayFormatter = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'full', timeZone: 'UTC' });

function renderMiniMonth() {
    find('[data-mini-month-title]').textContent = miniMonthFormatter.format(miniMonthDate);
    const focusedDate = miniMonthDays.contains(document.activeElement) ? document.activeElement.dataset.date : null;
    miniMonthDays.replaceChildren();
    const firstDay = new Date(miniMonthDate);
    firstDay.setUTCDate(1 - firstDay.getUTCDay());
    const today = siteNow().slice(0, 10);
    for (let index = 0; index < 42; index++) {
        const day = new Date(firstDay);
        day.setUTCDate(firstDay.getUTCDate() + index);
        const dateString = day.toISOString().slice(0, 10);
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.date = dateString;
        button.textContent = String(day.getUTCDate());
        button.setAttribute('aria-label', miniDayFormatter.format(day));
        button.setAttribute('aria-pressed', String(dateString === miniMonthSelectedDate));
        if (dateString === today) button.setAttribute('aria-current', 'date');
        if (day.getUTCMonth() !== miniMonthDate.getUTCMonth()) button.classList.add('agenda-mini-month-outside');
        miniMonthDays.append(button);
    }
    if (focusedDate) miniMonthDays.querySelector(`[data-date="${focusedDate}"]`)?.focus({ preventScroll: true });
}

find('[data-mini-month-prev]').addEventListener('click', () => {
    miniMonthDate.setUTCMonth(miniMonthDate.getUTCMonth() - 1);
    renderMiniMonth();
});
find('[data-mini-month-next]').addEventListener('click', () => {
    miniMonthDate.setUTCMonth(miniMonthDate.getUTCMonth() + 1);
    renderMiniMonth();
});
miniMonthDays.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-date]');
    if (button) calendar.changeView('timeGridDay', button.dataset.date);
});

const calendar = new Calendar(find('[data-calendar]'), {
    plugins: [timeGridPlugin, dayGridPlugin, interactionPlugin],
    locale: ptBr,
    timeZone: config.timezone,
    initialView: 'timeGridDay',
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
    slotDuration: '00:15:00',
    slotLabelInterval: '00:15:00',
    scrollTime: `${String(Math.max(7, Number(siteNow().slice(11, 13)) - 1)).padStart(2, '0')}:00:00`,
    scrollTimeReset: false,
    businessHours: businessHours(),
    slotMinTime: '07:00:00',
    slotMaxTime: '20:00:00',
    height: Math.max(440, window.innerHeight - 285),
    expandRows: false,
    eventMinHeight: 22,
    slotEventOverlap: false,
    dayMaxEvents: true,
    displayEventEnd: true,
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    noEventsText: 'Nenhum agendamento neste período.',
    events: async (info, success) => {
        const version = ++feedVersion;
        feedRequest?.abort();
        const controller = new AbortController();
        feedRequest = controller;
        feedReady = false;
        todayReady = false;
        nextAppointment = null;
        firstFree = null;
        find('[data-first-free]').disabled = true;
        find('[data-next-appointment]').disabled = true;
        find('[data-today-count]').textContent = 'Consultando hoje…';
        find('[data-first-free]').textContent = 'Consultando tempo livre…';
        find('[data-next-appointment]').textContent = 'Consultando próximo atendimento…';
        find('[data-current-state]').textContent = '';
        find('[data-retry-feed]').hidden = true;
        if (!filter.value) {
            success([]);
            message.textContent = 'Cadastre um profissional para começar a organizar sua agenda.';
            find('[data-today-count]').textContent = 'Nenhum profissional cadastrado';
            find('[data-first-free]').textContent = 'Sem horários';
            find('[data-next-appointment]').textContent = 'Sem atendimentos';
            return;
        }
        const url = new URL(config.feedUrl);
        url.search = new URLSearchParams({ start: info.startStr.slice(0, 10), end: info.endStr.slice(0, 10), profissional_id: filter.value });
        try {
            const today = siteNow().slice(0, 10);
            const includesToday = info.startStr.slice(0, 10) <= today && info.endStr.slice(0, 10) > today;
            const todayUrl = new URL(config.feedUrl);
            todayUrl.search = new URLSearchParams({ start: today, end: nextDay(today), profissional_id: filter.value });
            const [events, summaryEvents] = await Promise.all([
                request(url, { signal: controller.signal }),
                includesToday ? Promise.resolve(null) : request(todayUrl, { signal: controller.signal }),
            ]);
            if (version !== feedVersion) { success([]); return; }
            visibleEvents = events;
            todayEvents = summaryEvents || events;
            loadedDay = today;
            feedReady = true;
            todayReady = true;
            updateBounds(events);
            success(timelineEvents(events, info.startStr.slice(0, 10), info.endStr.slice(0, 10)));
            updateSummary();
            const total = activeEvents(events).length;
            message.textContent = !selectedPerson()?.active ? 'Profissional inativo. Agenda disponível para consulta.' : total ? `${total} atendimento${total === 1 ? '' : 's'} no período exibido.` : 'Nenhum atendimento neste período.';
        } catch (error) {
            if (controller.signal.aborted) { success([]); return; }
            success([]);
            if (version === feedVersion) {
                message.textContent = errorText(error);
                find('[data-today-count]').textContent = 'Resumo indisponível';
                find('[data-first-free]').textContent = 'Disponibilidade não carregada';
                find('[data-next-appointment]').textContent = 'Próximo atendimento não carregado';
                find('[data-retry-feed]').hidden = false;
            }
        }
    },
    loading: (busy) => {
        find('[data-calendar]').setAttribute('aria-busy', String(busy));
        if (busy) message.textContent = 'Carregando agenda…';
    },
    datesSet: (info) => {
        miniMonthSelectedDate = info.view.calendar.getDate().toISOString().slice(0, 10);
        miniMonthDate = new Date(`${miniMonthSelectedDate.slice(0, 7)}-01T12:00:00Z`);
        renderMiniMonth();
        find('[data-period]').textContent = info.view.title;
        root.querySelectorAll('[data-view]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.view === info.view.type)));
    },
    dateClick: (info) => {
        if (!feedReady || !selectedPerson()?.active) return;
        if (info.allDay) {
            calendar.changeView('timeGridDay', info.dateStr);
            return;
        }
        const chosen = localStamp(info.dateStr);
        const fits = freePeriods(chosen.slice(0, 10), visibleEvents).some((period) => chosen >= period.start && chosen < period.end && duration({ start: chosen, end: period.end }) >= minimumDuration());
        if (fits) openCreate(info.dateStr);
        else message.textContent = 'Este horário não está disponível. Escolha uma faixa livre da agenda.';
    },
    eventClick: (info) => openDetails(info.event),
    eventContent: renderEvent,
    nowIndicatorContent: (info) => info.isAxis ? '' : `AGORA ${siteNow().slice(11, 16)}`,
    eventDidMount: (info) => {
        if (info.event.display !== 'background') {
            info.el.title = `${info.event.startStr.slice(11, 16)}–${info.event.endStr.slice(11, 16)} · ${info.event.title} · ${duration({ start: info.event.startStr, end: info.event.endStr })} min · ${info.event.extendedProps.status}`;
            info.el.setAttribute('aria-label', info.el.title);
        }
    },
});

calendar.render();
updateContext();
updateBounds();
find('[data-new]').addEventListener('click', () => openCreate(calendar.view.type === 'timeGridDay' ? calendar.getDate().toISOString().slice(0, 10) : ''));
find('[data-retry-feed]').addEventListener('click', () => calendar.refetchEvents());
find('[data-first-free]').addEventListener('click', () => { if (firstFree) openCreate(firstFree.start); });
find('[data-next-appointment]').addEventListener('click', () => { if (nextAppointment) openDetails(nextAppointment); });
find('[data-prev]').addEventListener('click', () => calendar.prev());
find('[data-next]').addEventListener('click', () => calendar.next());
find('[data-today]').addEventListener('click', () => calendar.gotoDate(siteNow().slice(0, 10)));
root.querySelectorAll('[data-view]').forEach((button) => button.addEventListener('click', () => calendar.changeView(button.dataset.view)));
filter.addEventListener('change', () => {
    updateContext();
    calendar.setOption('businessHours', businessHours());
    calendar.removeAllEvents();
    updateBounds();
    calendar.refetchEvents();
});

function tick() {
    find('[data-clock]').textContent = siteNow().slice(11, 16);
    find('[data-clock]').dateTime = siteNow();
    if (loadedDay !== siteNow().slice(0, 10)) {
        todayReady = false;
        calendar.refetchEvents();
    } else {
        updateSummary();
    }
}
tick();
setInterval(() => {
    if (!document.hidden && !saving && !cancelling && !find('[data-calendar]').matches('[aria-busy="true"]')) {
        tick();
        calendar.refetchEvents();
    }
}, 60000);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) { tick(); calendar.refetchEvents(); }
});
window.addEventListener('resize', () => calendar.setOption('height', Math.max(440, window.innerHeight - 285)));
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
createDialog.addEventListener('close', () => {
    slotRequest?.abort();
    slotPicker.hidePopover();
});
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
