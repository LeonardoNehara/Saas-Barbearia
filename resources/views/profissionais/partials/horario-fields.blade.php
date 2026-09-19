@php
    $formId = 'horario-' . $profissional->id . '-' . ($horario?->id ?? 'novo');
    $hasErrors = old('form_context') === 'horario-profissional-' . $profissional->id
        && (string) old('horario_id', '') === (string) ($horario?->id ?? '');
@endphp
<input type="hidden" name="form_context" value="horario-profissional-{{ $profissional->id }}">
<input type="hidden" name="horario_id" value="{{ $horario?->id }}">
<div>
    <label for="{{ $formId }}-dia" class="mb-2 block text-sm font-medium">Dia</label>
    <select id="{{ $formId }}-dia" name="dia_semana" required class="admin-input">
        @foreach ($dias as $numero => $dia)
            <option value="{{ $numero }}" @selected((string) ($hasErrors ? old('dia_semana') : ($horario?->dia_semana ?? 1)) === (string) $numero)>{{ $dia }}</option>
        @endforeach
    </select>
    @if ($hasErrors)
        @error('dia_semana')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
    @endif
</div>
@foreach (['hora_inicio' => 'Início', 'hora_fim' => 'Fim'] as $field => $label)
    <div>
        <label for="{{ $formId }}-{{ $field }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
        <input id="{{ $formId }}-{{ $field }}" name="{{ $field }}" type="time" required class="admin-input" value="{{ $hasErrors ? old($field) : substr($horario?->$field ?? '', 0, 5) }}">
        @if ($hasErrors)
            @error($field)<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        @endif
    </div>
@endforeach
<fieldset class="sm:col-span-3">
    <legend class="text-sm font-medium">Horário de almoço / intervalo <span class="font-normal text-muted">(opcional)</span></legend>
    <p class="mt-1 text-xs text-muted">Deixe os dois campos vazios para atender sem intervalo. Para remover um intervalo, limpe ambos e salve.</p>
    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        @foreach (['intervalo_inicio' => 'Início do intervalo', 'intervalo_fim' => 'Fim do intervalo'] as $field => $label)
            <div>
                <label for="{{ $formId }}-{{ $field }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
                <input id="{{ $formId }}-{{ $field }}" name="{{ $field }}" type="time" class="admin-input" value="{{ $hasErrors ? old($field) : substr($horario?->$field ?? '', 0, 5) }}">
                @if ($hasErrors)
                    @error($field)<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                @endif
            </div>
        @endforeach
    </div>
</fieldset>
