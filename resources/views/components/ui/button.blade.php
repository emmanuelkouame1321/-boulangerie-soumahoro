@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'loading' => false,
    'disabled' => false,
])

@php
    // Design System §19 et §20 : états default/hover/focus/active/disabled/loading.
    // Une seule action primaire par contexte.
    $variants = [
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800',
        'secondary' => 'bg-surface text-ink border border-line hover:bg-subtle',
        'ghost' => 'text-muted hover:bg-subtle hover:text-ink',
        'danger' => 'bg-danger text-white hover:brightness-95 active:brightness-90',
    ];

    $base = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition '
        .'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 '
        .'disabled:cursor-not-allowed disabled:opacity-50';

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);

    $isDisabled = $disabled || $loading;
    $tag = $href && ! $isDisabled ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($tag === 'a') href="{{ $href }}" @else type="{{ $type }}" @disabled($isDisabled) @endif
    @if ($isDisabled) aria-disabled="true" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if ($loading)
        <x-ui.icon name="loader" :size="16" class="animate-spin" />
    @elseif ($icon)
        <x-ui.icon :name="$icon" :size="16" />
    @endif

    {{ $slot }}
</{{ $tag }}>
