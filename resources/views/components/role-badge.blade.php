@props(['role'])
<span @class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium', 'bg-brand/10 text-brand-dark' => $role === 'admin', 'bg-blue-50 text-blue-700' => $role === 'barbeiro'])><x-icon :name="$role === 'admin' ? 'user' : 'scissors'" class="size-3.5" />{{ $role === 'admin' ? 'Administrador' : 'Barbeiro' }}</span>
