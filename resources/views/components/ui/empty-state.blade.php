@props([
    'title' => 'Aucune donnée',
    'description' => null,
    'icon' => 'package',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-card border border-dashed border-line-strong bg-surface px-6 py-12 text-center']) }}>
    <div class="flex size-11 items-center justify-center rounded-full bg-subtle text-muted">
        <x-ui.icon :name="$icon" :size="20" />
    </div>

    <p class="mt-4 text-sm font-bold text-ink">{{ $title }}</p>

    @if ($description)
        <p class="mt-1 max-w-sm text-[13px] text-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
