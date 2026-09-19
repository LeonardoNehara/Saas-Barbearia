const root = document.querySelector('[data-public-booking]');

if (root) {
    // =========================================================
    // ELEMENTOS
    // =========================================================

    const professionalsGrid = root.querySelector('[data-professionals-grid]');
    const datesContainer = root.querySelector('[data-dates]');
    const slotTrigger = root.querySelector('[data-slot-trigger]');
    const slotTriggerLabel = root.querySelector('[data-slot-trigger-label]');
    const slotDialog = document.getElementById('booking-slot-dialog');
    const slotDialogGroups = slotDialog.querySelector('[data-slot-dialog-groups]');
    const slotDialogDate = slotDialog.querySelector('[data-slot-dialog-date]');
    const confirmSlotButton = slotDialog.querySelector('[data-confirm-slot]');
    const clearSlotButton = slotDialog.querySelector('[data-clear-slot]');
    const slotsDescription = root.querySelector('[data-slot-description]');
    const summaryTitle = root.querySelector('[data-summary-title]');
    const summaryDescription = root.querySelector('[data-summary-description]');
    const confirmButton = root.querySelector('[data-confirm-booking]');
    const previousDateButton = root.querySelector('[data-date-prev]');
    const nextDateButton = root.querySelector('[data-date-next]');
    const showServicesButton = root.querySelector('[data-show-services]');

    const dialog = document.getElementById('booking-customer-dialog');
    const bookingForm = document.getElementById('booking-customer-form');
    const phoneInput = document.getElementById('cliente_telefone');

    const bookingError = dialog.querySelector('[data-booking-error]');
    const dialogSummary = dialog.querySelector('[data-dialog-summary]');
    const formWrapper = dialog.querySelector('[data-booking-form-wrapper]');
    const successWrapper = dialog.querySelector('[data-booking-success]');
    const successSummary = dialog.querySelector('[data-success-summary]');

    // =========================================================
    // ESTADO
    // =========================================================

    let availableSlots = [];
    let pendingSlot = null;  

    const state = {
        service: null,
        professional: null,
        date: null,
        slot: null,
        dateOffset: 0,
    };

    const DAYS_PER_PAGE = 6;

    const dayNames = [
        'Dom',
        'Seg',
        'Ter',
        'Qua',
        'Qui',
        'Sex',
        'Sáb',
    ];

    // =========================================================
    // HELPERS
    // =========================================================

    function money(value) {
        return Number(value).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        });
    }

    function dateFromString(value) {
        return new Date(`${value}T12:00:00`);
    }

    function dateToString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }

    function formattedFullDate(value) {
        return new Intl.DateTimeFormat('pt-BR', {
            weekday: 'long',
            day: '2-digit',
            month: '2-digit',
        }).format(dateFromString(value));
    }

    function resolveImage(path) {
        if (!path) {
            return root.dataset.fallbackImage;
        }

        if (
            path.startsWith('http://')
            || path.startsWith('https://')
            || path.startsWith('/')
        ) {
            return path;
        }

        return `/storage/${path}`;
    }

    function clearSlots(
        message = 'Escolher horário'
    ) {
        availableSlots = [];
        pendingSlot = null;
        state.slot = null;
        slotTrigger.disabled = true;
        slotTrigger.classList.remove(
            'has-value'
        );

        slotTriggerLabel.textContent =
            message;
        slotDialogGroups.innerHTML = '';
        confirmSlotButton.disabled = true;
    }

    function renderSlotDialog() {
        slotDialogGroups.innerHTML = '';

        if (!availableSlots.length) {
            slotDialogGroups.innerHTML = `
                <div class="booking-slot-dialog-empty">
                    Não existem horários disponíveis para esta data.
                </div>
            `;
            return;
        }

        const periods = [
            { name: 'Manhã', icon: '☀️', start: 0, end: 12 },
            { name: 'Tarde', icon: '☀️', start: 12, end: 18 },
            { name: 'Noite', icon: '🌙', start: 18, end: 24 },
        ];

        periods.forEach((period) => {
            const slots = availableSlots.filter((slot) => {
                const hour = Number(slot.inicio.slice(11, 13));
                return hour >= period.start && hour < period.end;
            });

            if (!slots.length) return;

            const section = document.createElement('section');
            const title = document.createElement('h3');
            const grid = document.createElement('div');

            section.className = 'booking-slot-period';
            title.className = 'booking-slot-period-title';
            title.textContent = `${period.icon} ${period.name}`;
            grid.className = 'booking-slot-modal-grid';

            slots.forEach((slot) => {
                const button = document.createElement('button');

                button.type = 'button';
                button.className = 'booking-slot-modal-option';
                button.textContent = slot.inicio.slice(11, 16);

                if (pendingSlot?.inicio === slot.inicio) {
                    button.classList.add('is-selected');
                }

                button.addEventListener('click', () => {
                    pendingSlot = slot;

                    slotDialogGroups
                        .querySelectorAll('.booking-slot-modal-option')
                        .forEach((item) => item.classList.remove('is-selected'));

                    button.classList.add('is-selected');
                    confirmSlotButton.disabled = false;
                });

                grid.appendChild(button);
            });

            section.append(title, grid);
            slotDialogGroups.appendChild(section);
        });
    }

    // =========================================================
    // PROGRESSO / RESUMO
    // =========================================================

    function updateProgress(currentStep) {
        root.querySelectorAll('[data-booking-step]').forEach((step) => {
            const number = Number(step.dataset.bookingStep);

            step.classList.toggle(
                'is-complete',
                number < currentStep
            );

            step.classList.toggle(
                'is-active',
                number === currentStep
            );
        });
    }

    function calculateCurrentStep() {
        if (!state.service) {
            return 1;
        }

        if (!state.professional) {
            return 2;
        }

        return 3;
    }

    function updateSummary() {
        const complete =
            state.service
            && state.professional
            && state.date
            && state.slot;

        confirmButton.disabled = !complete;

        if (!state.service) {
            summaryTitle.textContent = 'Monte seu agendamento';
            summaryDescription.textContent =
                'Escolha serviço, profissional, data e horário.';

            updateProgress(1);

            return;
        }

        if (!state.professional) {
            summaryTitle.textContent = state.service.name;
            summaryDescription.textContent =
                `${state.service.duration} min • ${money(state.service.price)}`;

            updateProgress(2);

            return;
        }

        if (!state.date || !state.slot) {
            summaryTitle.textContent =
                `${state.service.name} com ${state.professional.name}`;

            summaryDescription.textContent =
                `Escolha a data e o horário • ${state.service.duration} min • ${money(state.service.price)}`;

            updateProgress(3);

            return;
        }

        const time = state.slot.inicio.slice(11, 16);

        summaryTitle.textContent =
            `${state.service.name} com ${state.professional.name}`;

        summaryDescription.textContent =
            `${formattedFullDate(state.date)} às ${time} • ${state.service.duration} min • ${money(state.service.price)}`;

        updateProgress(3);
    }

    // =========================================================
    // SERVIÇOS
    // =========================================================

    function clearProfessionalSelection() {
        state.professional = null;
        state.date = null;
        state.slot = null;
        pendingSlot = null;
        availableSlots = [];

        slotTrigger.disabled = true;
        slotTrigger.classList.remove('has-value');
        slotTriggerLabel.textContent = 'Escolher horário';

        renderDates();

        slotsDescription.textContent = 'Selecione primeiro o serviço, profissional e data.';
    }

    async function selectService(card) {
        root.querySelectorAll('[data-service-card]').forEach((item) => {
            item.classList.remove('is-selected');
            item.setAttribute('aria-pressed', 'false');
        });

        card.classList.add('is-selected');
        card.setAttribute('aria-pressed', 'true');

        state.service = {
            id: Number(card.dataset.id),
            name: card.dataset.name,
            duration: Number(card.dataset.duration),
            price: Number(card.dataset.price),
        };

        clearProfessionalSelection();
        updateSummary();

        await loadProfessionals();
    }

    // =========================================================
    // PROFISSIONAIS
    // =========================================================

    async function loadProfessionals() {
        professionalsGrid.innerHTML = `
            <div class="booking-empty">
                Carregando profissionais...
            </div>
        `;

        const url = root.dataset.professionalsUrlTemplate.replace(
            '__SERVICO__',
            state.service.id
        );

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(
                    'Não foi possível carregar os profissionais.'
                );
            }

            const json = await response.json();

            renderProfessionals(json.data ?? []);
        } catch (error) {
            professionalsGrid.innerHTML = `
                <div class="booking-empty">
                    Não foi possível carregar os profissionais.
                    Tente novamente.
                </div>
            `;
        }
    }

    function renderProfessionals(professionals) {
        professionalsGrid.innerHTML = '';

        if (!professionals.length) {
            professionalsGrid.innerHTML = `
                <div class="booking-empty">
                    Nenhum profissional disponível
                    para este serviço.
                </div>
            `;

            return;
        }

        professionals.forEach((professional) => {
            const button = document.createElement('button');

            button.type = 'button';
            button.className = 'booking-professional-card';
            button.setAttribute('aria-pressed', 'false');

            const image = document.createElement('img');

            image.className = 'booking-professional-image';
            image.src = resolveImage(professional.foto);
            image.alt = professional.nome;

            image.addEventListener(
                'error',
                () => {
                    image.src = root.dataset.fallbackImage;
                },
                {
                    once: true,
                }
            );

            const name = document.createElement('strong');

            name.className = 'booking-professional-name';
            name.textContent = professional.nome;

            const description = document.createElement('span');

            description.className = 'booking-professional-description';
            description.textContent =
                professional.descricao
                || 'Profissional disponível para este serviço.';

            const check = document.createElement('span');

            check.className = 'booking-card-check';
            check.textContent = '✓';

            button.append(
                image,
                name,
                description,
                check
            );

            button.addEventListener('click', async () => {
                professionalsGrid
                    .querySelectorAll('.booking-professional-card')
                    .forEach((item) => {
                        item.classList.remove('is-selected');
                        item.setAttribute('aria-pressed', 'false');
                    });

                button.classList.add('is-selected');
                button.setAttribute('aria-pressed', 'true');

                state.professional = {
                    id: professional.id,
                    name: professional.nome,
                    photo: professional.foto,
                };

                state.slot = null;

                updateSummary();
                renderDates();

                if (state.date) {
                    await loadAvailability();
                }
            });

            professionalsGrid.appendChild(button);
        });
    }

    // =========================================================
    // DATAS
    // =========================================================

    function renderDates() {
        datesContainer.innerHTML = '';

        const initialDate = dateFromString(root.dataset.today);

        initialDate.setDate(
            initialDate.getDate() + state.dateOffset
        );

        for (let index = 0; index < DAYS_PER_PAGE; index++) {
            const date = new Date(initialDate);

            date.setDate(
                initialDate.getDate() + index
            );

            const dateString = dateToString(date);
            const button = document.createElement('button');

            button.type = 'button';
            button.className = 'booking-date';
            button.disabled = !state.professional;

            button.innerHTML = `
                <span class="booking-date-day">
                    ${dayNames[date.getDay()]}
                </span>

                <strong class="booking-date-number">
                    ${String(date.getDate()).padStart(2, '0')}
                </strong>
            `;

            if (state.date === dateString) {
                button.classList.add('is-selected');
            }

            button.addEventListener('click', async () => {
                state.date = dateString;
                state.slot = null;

                pendingSlot = null;

                slotTrigger.classList.remove(
                    'has-value'
                );

                slotTriggerLabel.textContent =
                    'Consultando horários...';

                renderDates();
                updateSummary();

                await loadAvailability();
            });

            datesContainer.appendChild(button);
        }

        previousDateButton.disabled =
            state.dateOffset === 0;
    }

    // =========================================================
    // DISPONIBILIDADE / HORÁRIOS
    // =========================================================

    async function loadAvailability() {
        if (
            !state.service
            || !state.professional
            || !state.date
        ) {
            return;
        }

        slotTrigger.disabled = true;

        slotTrigger.classList.remove(
            'has-value'
        );

        slotTriggerLabel.textContent =
            'Consultando horários...';

        slotsDescription.textContent =
            `Horários para ${formattedFullDate(state.date)}.`;

        const url = new URL(
            root.dataset.availabilityUrl,
            window.location.origin
        );

        url.searchParams.set(
            'profissional_id',
            state.professional.id
        );

        url.searchParams.set(
            'servico_id',
            state.service.id
        );

        url.searchParams.set(
            'data',
            state.date
        );

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error();
            }

            const json = await response.json();

            renderSlots(json.data ?? []);
        } catch (error) {
            clearSlots(
                'Não foi possível consultar os horários.'
            );
        }
    }

    function renderSlots(slots) {
        availableSlots = slots;
        pendingSlot = null;
        state.slot = null;

        slotTrigger.classList.remove('has-value');
        confirmSlotButton.disabled = true;

        if (!slots.length) {
            slotTrigger.disabled = true;
            slotTriggerLabel.textContent = 'Nenhum horário disponível';
            return;
        }

        slotTrigger.disabled = false;
        slotTriggerLabel.textContent = 'Escolher horário';

        renderSlotDialog();
    }

    // =========================================================
    // CONFIRMAÇÃO
    // =========================================================

    function openConfirmation() {
        if (
            !state.service
            || !state.professional
            || !state.date
            || !state.slot
        ) {
            return;
        }

        const time = state.slot.inicio.slice(11, 16);

        dialogSummary.innerHTML = `
            <strong>
                ${state.service.name}
            </strong>
            <br>
            ${state.professional.name}
            <br>
            ${formattedFullDate(state.date)} às ${time}
            <br>
            ${state.service.duration} min • ${money(state.service.price)}
        `;

        bookingError.hidden = true;

        updateProgress(4);

        dialog.showModal();

        setTimeout(() => {
            document
                .getElementById('cliente_nome')
                .focus();
        }, 100);
    }

    function closeConfirmation() {
        dialog.close();

        updateProgress(
            calculateCurrentStep()
        );
    }

    // =========================================================
    // SALVAR AGENDAMENTO
    // =========================================================

    async function submitBooking(event) {
        event.preventDefault();

        bookingError.hidden = true;

        const submitButton = dialog.querySelector(
            '[data-submit-booking]'
        );

        submitButton.disabled = true;
        submitButton.textContent = 'Confirmando...';

        const formData = new FormData(bookingForm);

        const payload = {
            profissional_id: state.professional.id,
            servico_id: state.service.id,
            cliente_nome: formData.get('cliente_nome'),

            cliente_telefone: String(
                formData.get('cliente_telefone')
            ).replace(/\D/g, ''),

            inicio: state.slot.inicio,
            observacoes: formData.get('observacoes') || null,
        };

        try {
            const response = await fetch(
                root.dataset.storeUrl,
                {
                    method: 'POST',

                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },

                    body: JSON.stringify(payload),
                }
            );

            const json = await response.json();

            if (!response.ok) {
                const errors = json.errors ?? {};

                const firstError = Object
                    .values(errors)
                    .flat()
                    .at(0);

                throw new Error(
                    firstError
                    || json.message
                    || 'Não foi possível realizar o agendamento.'
                );
            }

            const time = state.slot.inicio.slice(11, 16);

            successSummary.innerHTML = `
                <strong>
                    ${state.service.name}
                </strong>
                <br>
                ${state.professional.name}
                <br>
                ${formattedFullDate(state.date)} às ${time}
                <br>
                ${money(state.service.price)}
            `;

            formWrapper.hidden = true;
            successWrapper.hidden = false;

            updateProgress(4);
        } catch (error) {
            bookingError.textContent = error.message;
            bookingError.hidden = false;
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Confirmar agendamento';
        }
    }

    // =========================================================
    // MÁSCARA TELEFONE
    // =========================================================

    function maskPhone(event) {
        let value = event.target.value
            .replace(/\D/g, '')
            .slice(0, 11);

        if (value.length > 10) {
            value = value.replace(
                /^(\d{2})(\d{5})(\d{4})$/,
                '($1) $2-$3'
            );
        } else if (value.length > 6) {
            value = value.replace(
                /^(\d{2})(\d{4})(\d{0,4})$/,
                '($1) $2-$3'
            );
        } else if (value.length > 2) {
            value = value.replace(
                /^(\d{2})(\d+)/,
                '($1) $2'
            );
        } else if (value.length) {
            value = value.replace(
                /^(\d*)/,
                '($1'
            );
        }

        event.target.value = value;
    }

    // =========================================================
    // EVENTOS
    // =========================================================

    root.querySelectorAll('[data-service-card]').forEach((card) => {
        card.addEventListener('click', () => {
            selectService(card);
        });
    });

    previousDateButton.addEventListener('click', () => {
        state.dateOffset = Math.max(
            0,
            state.dateOffset - DAYS_PER_PAGE
        );

        renderDates();
    });

    nextDateButton.addEventListener('click', () => {
        state.dateOffset += DAYS_PER_PAGE;

        renderDates();
    });

    confirmButton.addEventListener(
        'click',
        openConfirmation
    );

    dialog
        .querySelectorAll('[data-close-booking-dialog]')
        .forEach((button) => {
            button.addEventListener(
                'click',
                closeConfirmation
            );
        });

    bookingForm.addEventListener(
        'submit',
        submitBooking
    );

    phoneInput.addEventListener(
        'input',
        maskPhone
    );

    dialog
        .querySelector('[data-new-booking]')
        .addEventListener('click', () => {
            window.location.reload();
        });

    dialog.addEventListener('cancel', () => {
        updateProgress(
            calculateCurrentStep()
        );
    });

    if (showServicesButton) {
        showServicesButton.addEventListener('click', () => {
            root
                .querySelectorAll('.is-extra-service')
                .forEach((service) => {
                    service.hidden = false;
                });

            showServicesButton.hidden = true;
        });
    }

    // =========================================================
    // INICIALIZAÇÃO
    // =========================================================

    renderDates();
    updateSummary();
}

slotTrigger.addEventListener('click', () => {
    if (slotTrigger.disabled || !availableSlots.length) return;

    pendingSlot = state.slot;

    slotDialogDate.textContent = new Intl.DateTimeFormat('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(dateFromString(state.date));

    renderSlotDialog();
    confirmSlotButton.disabled = !pendingSlot;
    slotDialog.showModal();
});

slotDialog
    .querySelectorAll(
        '[data-close-slot-dialog]'
    )
    .forEach(
        (button) => {

            button.addEventListener(
                'click',
                () => {
                    slotDialog.close();
                }
            );
        }
    );

slotDialog.querySelectorAll('[data-close-slot-dialog]').forEach((button) => {
    button.addEventListener('click', () => slotDialog.close());
});

confirmSlotButton.addEventListener('click', () => {
    if (!pendingSlot) return;

    state.slot = pendingSlot;

    const time = state.slot.inicio.slice(11, 16);

    slotTriggerLabel.textContent = time;
    slotTrigger.classList.add('has-value');

    slotDialog.close();
    updateSummary();
    slotTrigger.focus();
});