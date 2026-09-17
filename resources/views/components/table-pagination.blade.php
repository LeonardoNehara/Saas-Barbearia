@props(['paginator'])
<div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 px-5 py-4 text-xs text-muted">
    <p>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }} registros</p>
    <nav aria-label="Paginação" class="flex items-center gap-4">
        @if ($paginator->onFirstPage())<span aria-disabled="true" class="text-slate-400">Anterior</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded px-2 py-2 hover:text-brand-dark">Anterior</a>@endif
        <span class="sr-only">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded px-2 py-2 font-medium text-brand-dark">Próximo</a>@else<span aria-disabled="true" class="text-slate-400">Próximo</span>@endif
    </nav>
</div>
