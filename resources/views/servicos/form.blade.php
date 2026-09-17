@extends('layouts.admin')
@section('title', $servico ? 'Editar serviço' : 'Novo serviço')
@section('breadcrumb')<li><a href="{{ route('servicos.index') }}" class="text-muted hover:text-brand-dark">Serviços</a></li><li aria-hidden="true" class="text-slate-400">/</li><li aria-current="page" class="font-semibold">{{ $servico ? 'Editar' : 'Novo serviço' }}</li>@endsection
@section('content')
    <div class="mb-7"><h1 class="text-3xl font-bold tracking-tight">{{ $servico ? 'Editar serviço' : 'Novo serviço' }}</h1><p class="mt-2 text-sm text-muted">Defina os detalhes do atendimento oferecido pela sua barbearia.</p></div>
    <div class="max-w-3xl space-y-6">
        <form method="POST" action="{{ $servico ? route('servicos.update', $servico) : route('servicos.store') }}" data-busy-form class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-8">
            @csrf @if($servico) @method('PUT') @endif
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2"><label for="nome" class="mb-2 block text-sm font-medium">Nome do serviço *</label><input id="nome" name="nome" value="{{ old('nome', $servico?->nome) }}" required maxlength="255" class="admin-input" @error('nome') aria-invalid="true" aria-describedby="nome-error" @enderror>@error('nome')<p id="nome-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label for="descricao" class="mb-2 block text-sm font-medium">Descrição <span class="font-normal text-muted">(opcional)</span></label><textarea id="descricao" name="descricao" rows="4" maxlength="10000" class="admin-input resize-y" @error('descricao') aria-invalid="true" aria-describedby="descricao-error" @enderror>{{ old('descricao', $servico?->descricao) }}</textarea>@error('descricao')<p id="descricao-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="duracao_minutos" class="mb-2 block text-sm font-medium">Duração em minutos *</label><input id="duracao_minutos" name="duracao_minutos" type="number" min="5" max="480" step="1" value="{{ old('duracao_minutos', $servico?->duracao_minutos) }}" required class="admin-input" aria-describedby="duracao-help @error('duracao_minutos') duracao-error @enderror" @error('duracao_minutos') aria-invalid="true" @enderror><p id="duracao-help" class="mt-2 text-xs text-muted">De 5 a 480 minutos.</p>@error('duracao_minutos')<p id="duracao-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="preco" class="mb-2 block text-sm font-medium">Preço (R$) *</label><input id="preco" name="preco" type="number" inputmode="decimal" min="0" max="99999999.99" step="0.01" value="{{ old('preco', $servico?->preco) }}" required class="admin-input" @error('preco') aria-invalid="true" aria-describedby="preco-error" @enderror>@error('preco')<p id="preco-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            </div>
            @if (! $servico)<p class="mt-6 text-xs leading-6 text-muted">O serviço será cadastrado como ativo. Na próxima etapa, selecione os profissionais que realizam este serviço.</p>@endif
            <div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-6"><a href="{{ route('servicos.index') }}" class="admin-secondary">Voltar para serviços</a><button type="submit" class="admin-button" data-busy-label>{{ $servico ? 'Salvar alterações' : 'Cadastrar serviço' }}</button></div>
        </form>
        @if ($servico)
            <form method="POST" action="{{ route('servicos.profissionais', $servico) }}" data-busy-form class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-8">
                @csrf @method('PUT')
                <input type="hidden" name="profissionais_present" value="1">
                <fieldset><legend class="text-lg font-semibold">Profissionais que realizam este serviço</legend><p class="mt-2 text-sm leading-6 text-muted">Selecione os profissionais da sua barbearia. Desmarque todos para remover os vínculos.</p>
                    @php($selected = old('profissionais', $servico->profissionais->modelKeys()))
                    <div class="mt-5 grid max-h-80 gap-3 overflow-y-auto sm:grid-cols-2">
                        @forelse ($profissionais as $profissional)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 hover:bg-slate-50"><input type="checkbox" name="profissionais[]" value="{{ $profissional->id }}" @checked(is_array($selected) && in_array($profissional->id, $selected)) class="size-4 shrink-0 accent-brand-dark"><span class="min-w-0 flex-1 break-words text-sm">{{ $profissional->nome }}</span><x-status-badge :active="$profissional->active" /></label>
                        @empty
                            <p class="py-3 text-sm text-muted sm:col-span-2">Nenhum profissional cadastrado neste estabelecimento.</p>
                        @endforelse
                    </div>
                </fieldset>
                <div class="mt-6 flex justify-end"><button type="submit" class="admin-button" data-busy-label>Salvar profissionais</button></div>
            </form>
        @endif
    </div>
@endsection
