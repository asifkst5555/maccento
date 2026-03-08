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
        'trash' => '<path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'eye' => '<path d="M2.5 10s2.8-4.5 7.5-4.5 7.5 4.5 7.5 4.5-2.8 4.5-7.5 4.5S2.5 10 2.5 10zm7.5 2.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" fill="none" stroke="currentColor" stroke-width="1.5"/>',
        'pencil' => '<path d="M4 14.8h2.4L14 7.2 11.8 5 4.2 12.6V15zM10.9 6l2.2 2.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    $path = $icons[$name] ?? $icons['arrow-right'];
@endphp

<svg viewBox="0 0 20 20" aria-hidden="true" {!! $attributes->merge(['class' => trim((string) $class)])->toHtml() !!}>
    {!! $path !!}
</svg>
