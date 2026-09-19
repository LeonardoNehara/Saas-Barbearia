@extends('layouts.admin')
@section('title', 'Horários e intervalos')
@section('breadcrumb')<li><a href="{{ route('profissionais.index') }}" class="text-muted">Profissionais</a></li><li aria-hidden="true">/</li><li aria-current="page" class="font-semibold">Disponibilidade</li>@endsection
@section('content')
    @php($dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'])
    <div class="mb-7 flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Horários e intervalos</h1><p class="mt-2 text-sm text-muted">{{ $profissional->nome }} · Horário local: {{ auth()->user()->estabelecimento->timezone }}</p></div><a href="{{ route('profissionais.edit', $profissional) }}" class="admin-secondary">Voltar ao profissional</a></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
        @include('profissionais.partials.horarios')
    </div>
@endsection
