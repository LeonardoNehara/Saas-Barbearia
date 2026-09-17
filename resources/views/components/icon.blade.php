@props(['name'])
<svg {{ $attributes->class(['shrink-0']) }} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16"/>
            @break
        @case('close')
            <path d="m6 6 12 12M6 18 18 6"/>
            @break
        @case('search')
            <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>
            @break
        @case('plus')
            <circle cx="12" cy="12" r="9"/><path d="M8 12h8m-4-4v8"/>
            @break
        @case('edit')
            <path d="M12 5H5v14h14v-7M15 4l5 5m-11 6 4-1L22 5l-4-4-9 9-1 5Z"/>
            @break
        @case('power')
            <path d="M12 2v10m-5-7a9 9 0 1 0 10 0"/>
            @break
        @case('logout')
            <path d="M9 3H4v18h5m5-14 5 5-5 5M9 12h10"/>
            @break
        @case('briefcase')
            <rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V3h8v4M3 12a21 21 0 0 0 18 0m-9 0v4"/>
            @break
        @case('store')
            <path d="M3 10 5 3h14l2 7M4 10v11h16V10M9 21v-7h6v7M3 10h18"/>
            @break
        @case('scissors')
            <circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="m8.1 8.1 12 12M8.1 15.9 20 4M14 14l-3-3"/>
            @break
        @case('calendar')
            <rect x="4" y="5" width="16" height="16" rx="2"/><path d="M16 3v4M8 3v4M4 11h16M8 15h2m4 0h2"/>
            @break
        @case('users')
            <path d="M15 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m18 0v-2a4 4 0 0 0-3-3.87M15 3.13a4 4 0 0 1 0 7.75"/><circle cx="9" cy="7" r="4"/>
            @break
        @case('trend')
            <path d="m3 17 6-6 4 4 8-10m-6 0h6v6"/>
            @break
        @case('user')
            <circle cx="12" cy="7" r="4"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/>
            @break
        @case('lock')
            <rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>
            @break
        @case('eye')
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
            @break
        @case('arrow')
            <path d="M4 12h16m-6-6 6 6-6 6"/>
            @break
    @endswitch
</svg>
