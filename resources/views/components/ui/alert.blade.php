@props([
    'tone' => 'info',
    'title' => null,
])

@php
    // Design System §27 : Information / Attention / Critique.
    // Le ton ne suffit jamais : icône + libellé explicite.
    $tones = [
        'info' => ['wrap' => 'border-info/20 bg-info-bg', 'text' => 'text-info', 'icon' => 'info'],
        'success' => ['wrap' => 'border-success/20 bg-success-bg', 'text' => 'text-success', 'icon' => 'check-circle'],
        'warning' => ['wrap' => 'border-warning/20 bg-warning-bg', 'text' => 'text-warning', 'icon' => 'triangle-alert'],
        'danger' => ['wrap' => 'border-danger/20 bg-danger-bg', 'text' => 'text-danger', 'icon' => 'circle-alert'],
    ];

    $config = $tones[$tone] ?? $tones['info'];
@endphp

<div
    role="{{ $tone === 'danger' ? 'alert' : 'status' }}"
    {{ $attributes->merge(['class' => 'flex gap-3 rounded-card border p-4 '.$config['wrap']]) }}
>
    <x-ui.icon :name="$config['icon']" :size="18" class="mt-0.5 shrink-0 {{ $config['text'] }}" />

    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="text-sm font-semibold {{ $config['text'] }}">{{ $title }}</p>
        @endif

        <div class="text-sm text-ink/80 {{ $title ? 'mt-1' : '' }}">
            {{ $slot }}
        </div>
    </div>

    @isset($actions)
        <div class="shrink-0">{{ $actions }}</div>
    @endisset
</div>
