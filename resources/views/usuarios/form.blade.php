@extends('layouts.admin')
@section('title', $usuario ? 'Editar usuário' : 'Novo usuário')
@section('breadcrumb')<li><a href="{{ route('usuarios.index') }}" class="text-muted hover:text-brand-dark">Usuários</a></li><li aria-hidden="true" class="text-slate-400">/</li><li aria-current="page" class="font-semibold">{{ $usuario ? 'Editar' : 'Novo usuário' }}</li>@endsection
@section('content')
    <div class="mb-7"><h1 class="text-3xl font-bold tracking-tight">{{ $usuario ? 'Editar usuário' : 'Novo usuário' }}</h1><p class="mt-2 text-sm text-muted">{{ $usuario ? 'Atualize os dados e o perfil de acesso.' : 'Cadastre um novo acesso para sua barbearia.' }}</p></div>
    <form method="POST" action="{{ $usuario ? route('usuarios.update', $usuario) : route('usuarios.store') }}" data-busy-form class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 sm:p-8">
        @csrf
        @if ($usuario) @method('PUT') @endif
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="sm:col-span-2"><label for="name" class="mb-2 block text-sm font-medium">Nome completo <span class="text-muted">*</span></label><input id="name" name="name" value="{{ old('name', $usuario?->name) }}" required maxlength="255" autocomplete="name" class="admin-input" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>@error('name')<p id="name-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="email" class="mb-2 block text-sm font-medium">Email <span class="text-muted">*</span></label><input id="email" name="email" type="email" value="{{ old('email', $usuario?->email) }}" required maxlength="255" autocomplete="email" class="admin-input" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>@error('email')<p id="email-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="role" class="mb-2 block text-sm font-medium">Perfil <span class="text-muted">*</span></label><select id="role" name="role" required class="admin-input" @error('role') aria-invalid="true" aria-describedby="role-error" @enderror>
                @if (! $usuario?->is(auth()->user()))<option value="barbeiro" @selected(old('role', $usuario?->role ?? 'barbeiro') === 'barbeiro')>Barbeiro</option>@endif
                <option value="admin" @selected(old('role', $usuario?->role) === 'admin')>Administrador</option>
            </select>@error('role')<p id="role-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div class="border-t border-slate-100 pt-6 sm:col-span-2"><h2 class="font-semibold">Senha de acesso</h2><p id="password-help" class="mt-1 text-xs leading-6 text-muted">{{ $usuario ? 'Deixe os campos vazios para manter a senha atual.' : 'Utilize pelo menos 8 caracteres. O usuário será cadastrado como ativo.' }}</p></div>
            <div><label for="password" class="mb-2 block text-sm font-medium">{{ $usuario ? 'Nova senha' : 'Senha' }}</label><input id="password" name="password" type="password" @required(! $usuario) minlength="8" maxlength="255" autocomplete="new-password" class="admin-input" aria-describedby="password-help @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>@error('password')<p id="password-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation" class="mb-2 block text-sm font-medium">Confirmar senha</label><input id="password_confirmation" name="password_confirmation" type="password" @required(! $usuario) minlength="8" maxlength="255" autocomplete="new-password" class="admin-input"></div>
        </div>
        <div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-6"><a href="{{ route('usuarios.index') }}" class="admin-secondary">Cancelar</a><button type="submit" class="admin-button" data-busy-label>{{ $usuario ? 'Salvar alterações' : 'Cadastrar usuário' }}</button></div>
    </form>
@endsection
