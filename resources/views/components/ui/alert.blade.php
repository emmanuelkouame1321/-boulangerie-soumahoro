@props([
    'tone' => 'info',
    'title' => null,
])

@php
    $tones = [
        'info' => ['wrap' => 'border-context-100 bg-context-50', 'text' => 'text-context-700', 'icon' => 'info'],
        'success' => ['wrap' => 'border-brand-200 bg-brand-50', 'text' => 'text-brand-800', 'icon' => 'check-circle'],
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
            <p class="text-sm font-bold {{ $config['text'] }}">{{ $title }}</p>
        @endif

        <div class="text-[13px] text-ink/80 {{ $title ? 'mt-1' : '' }}">
            {{ $slot }}
        </div>
    </div>

    @isset($actions)
        <div class="shrink-0">{{ $actions }}</div>
    @endisset
</div>
