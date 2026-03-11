@props([
    'name' => 'arrow-right',
    'class' => '',
])

@php
    $icons = [
        'arrow-right' => '<path d="M4 10h12M10 4l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'mail' => '<path d="M3.5 5.5h13v9h-13v-9zm0 0L10 10l6.5-4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'mail-solid' => '<path d="M3.5 5.25A1.75 1.75 0 0 0 1.75 7v6A1.75 1.75 0 0 0 3.5 14.75h13A1.75 1.75 0 0 0 18.25 13V7a1.75 1.75 0 0 0-1.75-1.75h-13zm.32 1.8L10 10.7l6.18-3.65a.75.75 0 0 0-.68-.45h-11a.75.75 0 0 0-.68.45z" fill="currentColor"/>',
        'send' => '<path d="M3 10l13-6-3.4 12-3.1-4.1L6 14l-3-4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'trash' => '<path d="M7 2.5h6a2.5 2.5 0 0 1 2.5 2.5V6H4.5V5A2.5 2.5 0 0 1 7 2.5z" fill="currentColor"/><path d="M3 6h14v2H3z" fill="currentColor"/><path d="M5 8h10l-1 9H6L5 8z" fill="currentColor"/><rect x="7.25" y="10" width="1.7" height="6" rx="0.85" fill="#000" opacity="0.35"/><rect x="9.65" y="10" width="1.7" height="6" rx="0.85" fill="#000" opacity="0.35"/><rect x="12.05" y="10" width="1.7" height="6" rx="0.85" fill="#000" opacity="0.35"/>',
        'eye' => '<path d="M2.5 10s2.8-4.5 7.5-4.5 7.5 4.5 7.5 4.5-2.8 4.5-7.5 4.5S2.5 10 2.5 10zm7.5 2.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" fill="none" stroke="currentColor" stroke-width="1.5"/>',
        'pencil' => '<path d="M4 14.8h2.4L14 7.2 11.8 5 4.2 12.6V15zM10.9 6l2.2 2.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'reply' => '<path d="M8 6L3 10l5 4M4 10h9a4 4 0 0 1 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    $path = $icons[$name] ?? $icons['arrow-right'];
@endphp

<svg viewBox="0 0 20 20" aria-hidden="true" {!! $attributes->merge(['class' => trim((string) $class)])->toHtml() !!}>
    {!! $path !!}
</svg>






