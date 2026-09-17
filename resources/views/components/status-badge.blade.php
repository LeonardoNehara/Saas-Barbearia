@props(['active'])
<span @class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-50 text-emerald-700' => $active, 'bg-red-50 text-red-700' => ! $active])><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ $active ? 'Ativo' : 'Inativo' }}</span>
