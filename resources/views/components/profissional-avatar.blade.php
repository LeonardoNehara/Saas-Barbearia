@props(['profissional'])
<span class="relative flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand/10 text-sm font-semibold text-brand-dark" aria-hidden="true">
    {{ mb_strtoupper(mb_substr($profissional->nome, 0, 1)) }}
    @if ($profissional->foto)
        <img src="{{ preg_match('~^https?://~i', $profissional->foto) ? $profissional->foto : asset(ltrim($profissional->foto, '/')) }}" alt="" loading="lazy" referrerpolicy="no-referrer" data-profissional-foto class="absolute inset-0 size-full object-cover">
    @endif
</span>
