@extends('layouts.admin')
@section('title', 'Horários e bloqueios')
@section('breadcrumb')<li><a href="{{ route('profissionais.index') }}" class="text-muted">Profissionais</a></li><li aria-hidden="true">/</li><li aria-current="page" class="font-semibold">Disponibilidade</li>@endsection
@section('content')
    @php($dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'])
    <div class="mb-7 flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Horários e bloqueios</h1><p class="mt-2 text-sm text-muted">{{ $profissional->nome }} · Horário local: {{ auth()->user()->estabelecimento->timezone }}</p></div><a href="{{ route('profissionais.edit', $profissional) }}" class="admin-secondary">Voltar ao profissional</a></div>
    <div class="space-y-7">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7" aria-labelledby="horarios-title">
            <h2 id="horarios-title" class="text-xl font-semibold">Horários semanais</h2><p class="mt-2 text-sm text-muted">Adicione faixas de atendimento. Para alterar uma faixa, remova-a e cadastre o novo horário.</p>
            <form method="POST" action="{{ route('profissionais.horarios.store', $profissional) }}" data-busy-form class="my-6 grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @csrf
                <div><label for="dia_semana" class="mb-2 block text-sm font-medium">Dia da semana</label><select id="dia_semana" name="dia_semana" required class="admin-input">@foreach($dias as $number => $dia)<option value="{{ $number }}" @selected((string) old('dia_semana', 1) === (string) $number)>{{ $dia }}</option>@endforeach</select></div>
                <div><label for="hora_inicio" class="mb-2 block text-sm font-medium">Hora inicial</label><input id="hora_inicio" name="hora_inicio" type="time" value="{{ old('hora_inicio') }}" required class="admin-input" @error('hora_inicio') aria-invalid="true" @enderror></div>
                <div><label for="hora_fim" class="mb-2 block text-sm font-medium">Hora final</label><input id="hora_fim" name="hora_fim" type="time" value="{{ old('hora_fim') }}" required class="admin-input" @error('hora_fim') aria-invalid="true" @enderror></div>
                <button type="submit" class="admin-button" data-busy-label>Adicionar horário</button>
            </form>
            <ul class="divide-y divide-slate-100">
                @forelse($horarios as $horario)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-4"><div class="flex items-center gap-3"><x-icon name="clock" class="size-5 text-brand-dark" /><p class="text-sm"><span class="font-medium">{{ $dias[$horario->dia_semana] }}</span><span class="ml-3 text-muted">{{ substr($horario->hora_inicio, 0, 5) }}–{{ substr($horario->hora_fim, 0, 5) }}</span></p></div><form method="POST" action="{{ route('profissionais.horarios.destroy', [$profissional, $horario]) }}" data-busy-form data-confirm="Remover esta faixa de atendimento?">@csrf @method('DELETE')<button type="submit" class="rounded-lg px-3 py-2 text-sm text-red-700 hover:bg-red-50" data-busy-label aria-label="Remover horário de {{ $dias[$horario->dia_semana] }} às {{ substr($horario->hora_inicio, 0, 5) }}">Remover</button></form></li>
                @empty
                    <li class="py-8 text-center text-sm text-muted">Nenhum horário cadastrado.</li>
                @endforelse
            </ul>
        </section>
        <section id="bloqueios" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-5 sm:p-7" aria-labelledby="bloqueios-title">
            <h2 id="bloqueios-title" class="text-xl font-semibold">Bloqueios de disponibilidade</h2><p class="mt-2 text-sm text-muted">Informe os períodos em que o profissional não poderá atender.</p>
            <form method="POST" action="{{ route('profissionais.bloqueios.store', $profissional) }}" data-busy-form class="my-6 grid items-end gap-4 sm:grid-cols-2">
                @csrf
                <div class="min-w-0"><label for="inicio" class="mb-2 block text-sm font-medium">Início</label><input id="inicio" name="inicio" type="datetime-local" value="{{ old('inicio') }}" required class="admin-input min-w-0" @error('inicio') aria-invalid="true" @enderror></div>
                <div class="min-w-0"><label for="fim" class="mb-2 block text-sm font-medium">Fim</label><input id="fim" name="fim" type="datetime-local" value="{{ old('fim') }}" required class="admin-input min-w-0" @error('fim') aria-invalid="true" @enderror></div>
                <div><label for="motivo" class="mb-2 block text-sm font-medium">Motivo <span class="font-normal text-muted">(opcional)</span></label><input id="motivo" name="motivo" value="{{ old('motivo') }}" maxlength="255" class="admin-input"></div>
                <button type="submit" class="admin-button sm:justify-self-end" data-busy-label>Adicionar bloqueio</button>
            </form>
            <ul class="divide-y divide-slate-100">
                @forelse($bloqueios as $bloqueio)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-4"><div class="min-w-0"><p class="text-sm font-medium">{{ $bloqueio->inicio->format('d/m/Y H:i') }} até {{ $bloqueio->fim->format('d/m/Y H:i') }}</p>@if($bloqueio->motivo)<p class="mt-1 break-words text-sm text-muted">{{ $bloqueio->motivo }}</p>@endif</div><form method="POST" action="{{ route('profissionais.bloqueios.destroy', [$profissional, $bloqueio]) }}" data-busy-form data-confirm="Remover este bloqueio de disponibilidade?">@csrf @method('DELETE')<button type="submit" class="rounded-lg px-3 py-2 text-sm text-red-700 hover:bg-red-50" data-busy-label aria-label="Remover bloqueio de {{ $bloqueio->inicio->format('d/m/Y H:i') }}">Remover</button></form></li>
                @empty
                    <li class="py-8 text-center text-sm text-muted">Nenhum bloqueio cadastrado.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
