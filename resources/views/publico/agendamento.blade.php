<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Agendar horário | {{ $estabelecimento->nome }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="booking-page">

<main
    class="booking-wrapper"
    data-public-booking
    data-today="{{ now($estabelecimento->timezone)->toDateString() }}"
    data-professionals-url-template="{{ route('publico.servicos.profissionais', [
        'estabelecimento' => $estabelecimento,
        'servico' => '__SERVICO__'
    ]) }}"
    data-availability-url="{{ route('publico.disponibilidade', $estabelecimento) }}"
    data-store-url="{{ route('publico.agendamentos.store', $estabelecimento) }}"
    data-fallback-image="{{ asset('images/barbershop.jpg') }}"
>
    <section class="booking-container">

        {{-- PROGRESSO --}}
        <nav class="booking-progress" aria-label="Etapas do agendamento">
            <div class="booking-step is-active" data-booking-step="1">
                <span class="booking-step-number">1</span>
                <span class="booking-step-label">Serviço</span>
            </div>

            <span class="booking-step-line"></span>

            <div class="booking-step" data-booking-step="2">
                <span class="booking-step-number">2</span>
                <span class="booking-step-label">Profissional</span>
            </div>

            <span class="booking-step-line"></span>

            <div class="booking-step" data-booking-step="3">
                <span class="booking-step-number">3</span>
                <span class="booking-step-label">Data e Horário</span>
            </div>

            <span class="booking-step-line"></span>

            <div class="booking-step" data-booking-step="4">
                <span class="booking-step-number">4</span>
                <span class="booking-step-label">Confirmação</span>
            </div>
        </nav>

        <div class="booking-separator"></div>

        {{-- SERVIÇOS --}}
        <section class="booking-section" aria-labelledby="booking-services-title">
            <div class="booking-section-header">
                <div>
                    <h1 id="booking-services-title" class="booking-section-title">
                        Escolha o serviço
                    </h1>

                    <p class="booking-section-description">
                        Selecione o serviço que deseja realizar.
                    </p>
                </div>

                @if ($servicos->count() > 6)
                    <button type="button" class="booking-show-all" data-show-services>
                        Ver todos os serviços <span>›</span>
                    </button>
                @endif
            </div>

            <div class="booking-services-grid">
                @forelse ($servicos as $servico)
                    <button
                        type="button"
                        @class([
                            'booking-service-card',
                            'is-extra-service' => $loop->index >= 6,
                        ])
                        @if ($loop->index >= 6) hidden @endif
                        data-service-card
                        data-id="{{ $servico->id }}"
                        data-name="{{ $servico->nome }}"
                        data-duration="{{ $servico->duracao_minutos }}"
                        data-price="{{ $servico->preco }}"
                        aria-pressed="false"
                    >
                        <img
                            src="{{ asset('images/barbershop.jpg') }}"
                            alt=""
                            class="booking-service-image"
                        >

                        <span class="booking-service-info">
                            <strong class="booking-service-name">
                                {{ $servico->nome }}
                            </strong>

                            <span class="booking-service-duration">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 2"></path>
                                </svg>

                                {{ $servico->duracao_minutos }} min
                            </span>

                            <strong class="booking-service-price">
                                R$ {{ number_format((float) $servico->preco, 2, ',', '.') }}
                            </strong>
                        </span>

                        <span class="booking-card-check">✓</span>
                    </button>
                @empty
                    <div class="booking-empty">
                        Nenhum serviço disponível no momento.
                    </div>
                @endforelse
            </div>
        </section>

        {{-- PROFISSIONAIS --}}
        <section
            class="booking-section"
            id="booking-professionals"
            aria-labelledby="booking-professionals-title"
        >
            <div class="booking-section-header">
                <div>
                    <h2 id="booking-professionals-title" class="booking-section-title">
                        Escolha o barbeiro
                    </h2>

                    <p class="booking-section-description">
                        Veja os profissionais disponíveis.
                    </p>
                </div>
            </div>

            <div class="booking-professionals-grid" data-professionals-grid>
                <div class="booking-empty">
                    Escolha um serviço para visualizar os profissionais disponíveis.
                </div>
            </div>
        </section>

        {{-- DATA + HORÁRIO --}}
        <section class="booking-schedule-grid">

            {{-- DATA --}}
            <div class="booking-schedule-column">
                <div class="booking-section-header">
                    <div>
                        <h2 class="booking-section-title">
                            Escolha a data
                        </h2>

                        <p class="booking-section-description">
                            Selecione o dia que deseja agendar.
                        </p>
                    </div>
                </div>

                <div class="booking-date-navigation">
                    <button
                        type="button"
                        class="booking-date-arrow"
                        data-date-prev
                        aria-label="Dias anteriores"
                    >
                        ‹
                    </button>

                    <div class="booking-dates" data-dates></div>

                    <button
                        type="button"
                        class="booking-date-arrow"
                        data-date-next
                        aria-label="Próximos dias"
                    >
                        ›
                    </button>
                </div>
            </div>

            {{-- SELEÇÃO DE HORÁRIO --}}
            <dialog
                id="booking-slot-dialog"
                class="booking-slot-dialog"
                aria-labelledby="booking-slot-dialog-title"
            >
                <div class="booking-slot-dialog-content">

                    {{-- CABEÇALHO --}}
                    <header class="booking-slot-dialog-header">

                        <span class="booking-slot-dialog-icon">
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v5l3 2"></path>
                            </svg>
                        </span>

                        <div class="booking-slot-dialog-heading">
                            <h2 id="booking-slot-dialog-title">
                                Horários disponíveis
                            </h2>

                            <p>
                                Selecione um horário para o atendimento.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="booking-slot-dialog-close"
                            data-close-slot-dialog
                            aria-label="Fechar horários"
                        >
                            ×
                        </button>

                    </header>


                    {{-- DATA --}}
                    <div class="booking-slot-dialog-date">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <rect
                                x="4"
                                y="5"
                                width="16"
                                height="15"
                                rx="2"
                            ></rect>

                            <path d="M8 3v4M16 3v4M4 10h16"></path>
                        </svg>

                        <span data-slot-dialog-date>
                            —
                        </span>

                    </div>


                    {{-- HORÁRIOS --}}
                    <div
                        class="booking-slot-dialog-groups"
                        data-slot-dialog-groups
                    ></div>


                    {{-- RODAPÉ --}}
                    <footer class="booking-slot-dialog-footer">

                        <button
                            type="button"
                            class="booking-slot-clear"
                            data-clear-slot
                        >
                            Limpar seleção
                        </button>

                        <button
                            type="button"
                            class="booking-slot-confirm"
                            data-confirm-slot
                            disabled
                        >
                            Confirmar horário
                        </button>

                    </footer>

                </div>
            </dialog>
        </section>

        {{-- RESUMO --}}
        <footer class="booking-summary">
            <div class="booking-summary-main">
                <div class="booking-summary-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="4" y="5" width="16" height="15" rx="2"></rect>
                        <path d="M8 3v4M16 3v4M4 10h16"></path>
                    </svg>
                </div>

                <div>
                    <strong class="booking-summary-title" data-summary-title>
                        Monte seu agendamento
                    </strong>

                    <p class="booking-summary-description" data-summary-description>
                        Escolha serviço, profissional, data e horário.
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="booking-confirm-button"
                data-confirm-booking
                disabled
            >
                Confirmar Agendamento <span>→</span>
            </button>
        </footer>

    </section>
</main>

{{-- DADOS DO CLIENTE --}}
<dialog id="booking-customer-dialog" class="booking-dialog">
    <div data-booking-form-wrapper>

        <div class="booking-dialog-heading">
            <div>
                <span class="booking-dialog-eyebrow">
                    Última etapa
                </span>

                <h2>Confirme seu agendamento</h2>

                <p>
                    Informe seus dados para finalizar.
                </p>
            </div>

            <button
                type="button"
                class="booking-dialog-close"
                data-close-booking-dialog
                aria-label="Fechar"
            >
                ×
            </button>
        </div>

        <div class="booking-dialog-summary" data-dialog-summary></div>

        <form id="booking-customer-form" class="booking-customer-form">

            <div class="booking-field">
                <label for="cliente_nome">Nome completo</label>

                <input
                    id="cliente_nome"
                    name="cliente_nome"
                    type="text"
                    maxlength="150"
                    autocomplete="name"
                    required
                    placeholder="Digite seu nome"
                >
            </div>

            <div class="booking-field">
                <label for="cliente_telefone">WhatsApp</label>

                <input
                    id="cliente_telefone"
                    name="cliente_telefone"
                    type="tel"
                    maxlength="15"
                    autocomplete="tel"
                    required
                    placeholder="(44) 99999-9999"
                >
            </div>

            <div class="booking-field">
                <label for="observacoes">
                    Observação <span>opcional</span>
                </label>

                <textarea
                    id="observacoes"
                    name="observacoes"
                    maxlength="2000"
                    rows="3"
                    placeholder="Alguma observação para o profissional?"
                ></textarea>
            </div>

            <div
                class="booking-form-error"
                data-booking-error
                hidden
            ></div>

            <div class="booking-dialog-actions">
                <button
                    type="button"
                    class="booking-dialog-secondary"
                    data-close-booking-dialog
                >
                    Voltar
                </button>

                <button
                    type="submit"
                    class="booking-dialog-primary"
                    data-submit-booking
                >
                    Confirmar agendamento
                </button>
            </div>
        </form>
    </div>

    {{-- SUCESSO --}}
    <div class="booking-success" data-booking-success hidden>
        <div class="booking-success-icon">✓</div>

        <h2>Agendamento confirmado!</h2>

        <p>
            Seu horário foi reservado com sucesso.
        </p>

        <div class="booking-success-summary" data-success-summary></div>

        <button
            type="button"
            class="booking-dialog-primary"
            data-new-booking
        >
            Fazer outro agendamento
        </button>
    </div>
</dialog>

</body>
</html>