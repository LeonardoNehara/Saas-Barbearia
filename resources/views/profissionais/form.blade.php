@extends('layouts.admin')
@section('title', $profissional ? 'Editar profissional' : 'Novo profissional')
@section('breadcrumb')<li><a href="{{ route('profissionais.index') }}" class="text-muted">Profissionais</a></li><li aria-hidden="true">/</li><li aria-current="page" class="font-semibold">{{ $profissional ? 'Editar' : 'Novo profissional' }}</li>@endsection
@section('content')
    <div class="mb-7"><h1 class="text-3xl font-bold tracking-tight">{{ $profissional ? 'Editar profissional' : 'Novo profissional' }}</h1><p class="mt-2 text-sm text-muted">Dados do profissional e vínculo com o acesso à sua barbearia.</p></div>
    <div class="max-w-3xl space-y-6">
        <form method="POST" action="{{ $profissional ? route('profissionais.update', $profissional) : route('profissionais.store') }}" data-busy-form class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-8">
            @csrf @if($profissional) @method('PUT') @endif
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach(['nome' => 'Nome completo *', 'telefone' => 'Telefone', 'email' => 'Email', 'foto' => 'Endereço da foto'] as $field => $label)
                    <div><label for="{{ $field }}" class="mb-2 block text-sm font-medium">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'telefone' ? 'tel' : 'text') }}" value="{{ old($field, $profissional?->{$field}) }}" maxlength="255" @required($field === 'nome') class="admin-input" @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>@error($field)<p id="{{ $field }}-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                @endforeach
                <div class="sm:col-span-2"><label for="user_id" class="mb-2 block text-sm font-medium">Usuário vinculado <span class="font-normal text-muted">(opcional)</span></label><select id="user_id" name="user_id" class="admin-input" @error('user_id') aria-invalid="true" aria-describedby="user-error" @enderror><option value="">Sem vínculo com usuário</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected((string) old('user_id', $profissional?->user_id) === (string) $usuario->id)>{{ $usuario->name }} · {{ $usuario->email }}</option>@endforeach</select>@error('user_id')<p id="user-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label for="descricao" class="mb-2 block text-sm font-medium">Descrição</label><textarea id="descricao" name="descricao" rows="4" maxlength="10000" class="admin-input resize-y" @error('descricao') aria-invalid="true" aria-describedby="descricao-error" @enderror>{{ old('descricao', $profissional?->descricao) }}</textarea>@error('descricao')<p id="descricao-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            </div>
            <div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-6"><a href="{{ route('profissionais.index') }}" class="admin-secondary">Voltar</a><button type="submit" class="admin-button" data-busy-label>{{ $profissional ? 'Salvar alterações' : 'Cadastrar profissional' }}</button></div>
        </form>
        @if($profissional)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-8"><h2 class="text-lg font-semibold">Serviços realizados</h2><p class="mt-2 text-sm leading-6 text-muted">Abra um serviço e selecione este profissional em “Profissionais que realizam este serviço”.</p><div class="mt-4 flex flex-wrap gap-2">@forelse($profissional->servicos as $servico)<a href="{{ route('servicos.edit', $servico) }}" class="rounded-lg bg-brand/10 px-3 py-2 text-sm text-brand-dark hover:bg-brand/20">{{ $servico->nome }}</a>@empty<p class="text-sm text-muted">Nenhum serviço vinculado.</p>@endforelse</div><a href="{{ route('servicos.index') }}" class="admin-secondary mt-5">Selecionar serviços</a></section>
            <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:p-8"><div><h2 class="text-lg font-semibold">Disponibilidade</h2><p class="mt-2 text-sm text-muted">Configure horários semanais e períodos de bloqueio.</p></div><a href="{{ route('profissionais.horarios.index', $profissional) }}" class="admin-button"><x-icon name="calendar" class="size-4" />Gerenciar horários</a></section>
        @else
            <p class="text-sm text-muted">Após cadastrar, configure os serviços, horários e bloqueios deste profissional.</p>
        @endif
    </div>
@endsection
