@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconAfter' => null,
    'loading' => false,
    'disabled' => false,
    'size' => 'md',
])

@php
    // Charte caisse : bouton en majuscules, rayon 4px, hauteur 44px.
    // Une seule action primaire par contexte.
    $variants = [
        'primary' => 'bg-brand-700 text-white border border-brand-700 hover:bg-brand-800 active:bg-brand-900',
        'secondary' => 'bg-surface text-ink border border-line-strong hover:bg-subtle',
        'soft' => 'bg-surface text-context-600 border border-context-600 hover:bg-context-50',
        'blue' => 'bg-context-600 text-white border border-context-600 hover:bg-context-700',
        'danger' => 'bg-surface text-danger border border-line-strong hover:bg-danger-bg',
        'ghost' => 'bg-transparent text-muted border border-transparent hover:bg-subtle hover:text-ink',
    ];

    $sizes = [
        'sm' => 'min-h-8 px-3 text-xs',
        'md' => 'min-h-11 px-4 text-[13px]',
    ];

    $base = 'inline-flex items-center justify-center gap-2 rounded-control font-medium uppercase tracking-wide transition '
        .'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 '
        .'disabled:cursor-not-allowed disabled:opacity-50';

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);

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

    @if ($iconAfter)
        <x-ui.icon :name="$iconAfter" :size="16" />
    @endif
</{{ $tag }}>
