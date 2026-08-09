@props(['name', 'size' => 24])

<svg
    {{ $attributes->merge([
        'width' => $size,
        'height' => $size,
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'xmlns' => 'http://www.w3.org/2000/svg',
        'aria-hidden' => 'true',
    ]) }}
>
    @switch($name)
        @case('drop')
            <path d="M12 2.75S5.75 9.08 5.75 14.08a6.25 6.25 0 0 0 12.5 0C18.25 9.08 12 2.75 12 2.75Z" fill="currentColor"/>
            <path d="M9 15.25a3.15 3.15 0 0 0 3 2.25" stroke="white" stroke-width="1.7" stroke-linecap="round"/>
            @break
        @case('heart')
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/>
            <path d="M7 3v4M17 3v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="m8.5 15 2 2 4.5-4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('card')
            <rect x="2.5" y="4" width="19" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/>
            <circle cx="8" cy="11" r="2.2" stroke="currentColor" stroke-width="1.6"/>
            <path d="M5 16c.8-1.4 1.8-2.1 3-2.1s2.2.7 3 2.1M14 10h4M14 14h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            @break
        @case('shield')
            <path d="M12 3 4.5 6v5.1c0 4.65 3.2 8.05 7.5 9.9 4.3-1.85 7.5-5.25 7.5-9.9V6L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="m8.75 12 2.15 2.15 4.4-4.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/>
            <path d="M4.5 21c.65-4.15 3.15-6.25 7.5-6.25s6.85 2.1 7.5 6.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            @break
        @case('phone')
            <path d="M7.25 3h2.2l1.1 4.1-1.8 1.5a16.5 16.5 0 0 0 6.65 6.65l1.5-1.8 4.1 1.1v2.2A4.25 4.25 0 0 1 16.75 21C9.16 21 3 14.84 3 7.25A4.25 4.25 0 0 1 7.25 3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('map')
            <path d="M20 10c0 5.5-8 11-8 11s-8-5.5-8-11a8 8 0 1 1 16 0Z" stroke="currentColor" stroke-width="1.8"/>
            <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.8"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
            <path d="M12 7v5l3.5 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            @break
        @case('search')
            <circle cx="10.5" cy="10.5" r="6.5" stroke="currentColor" stroke-width="1.8"/>
            <path d="m15.5 15.5 4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            @break
        @case('check')
            <path d="m5 12.5 4.3 4.3L19 7.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('arrow')
            <path d="M5 12h14M14 7l5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            @break
        @case('sun')
            <circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.8"/>
            <path d="M12 2.5v2M12 19.5v2M21.5 12h-2M4.5 12h-2M18.72 5.28l-1.42 1.42M6.7 17.3l-1.42 1.42M18.72 18.72l-1.42-1.42M6.7 6.7 5.28 5.28" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            @break
        @case('moon')
            <path d="M20.3 15.2A8.55 8.55 0 0 1 8.8 3.7 8.6 8.6 0 1 0 20.3 15.2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('globe')
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
            <path d="M3.5 12h17M12 3c2.4 2.45 3.6 5.45 3.6 9S14.4 18.55 12 21c-2.4-2.45-3.6-5.45-3.6-9S9.6 5.45 12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            @break
        @case('chevron')
            <path d="m7.5 9.5 4.5 4.5 4.5-4.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('info')
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
            <path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            @break
        @case('bell')
            <path d="M6.5 10a5.5 5.5 0 0 1 11 0v3.1l1.7 2.4H4.8l1.7-2.4V10Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9.8 18.3a2.35 2.35 0 0 0 4.4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            @break
        @default
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
    @endswitch
</svg>
