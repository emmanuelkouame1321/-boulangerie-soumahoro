@php
    // Le sélecteur de boulangerie est un composant critique : le contexte de
    // site doit toujours être visible et son changement explicite.
    $label = $current?->name ?? 'Toutes les boulangeries';
    $meta = $current
        ? $current->code
        : 'Vue consolidée';
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        @click="open = !open"
        class="flex min-w-0 items-center gap-2 rounded-control bg-context-600 px-3 py-2 text-left text-white transition hover:bg-context-700"
        aria-haspopup="listbox"
        :aria-expanded="open ? 'true' : 'false'"
        aria-label="Changer de boulangerie"
    >
        <x-ui.icon name="building-2" :size="16" class="shrink-0" />

        <span class="min-w-0 hidden sm:block">
            <span class="block truncate text-[13px] font-bold">{{ $label }}</span>
            <span class="block truncate text-[10px] text-context-100">{{ $meta }}</span>
        </span>

        <x-ui.icon name="chevron-down" :size="14" class="ml-0.5 shrink-0" />
    </button>

    <div
        x-cloak
        x-show="open"
        @click.outside="open = false"
        class="absolute left-0 z-30 mt-2 w-72 overflow-hidden rounded-card border border-line bg-surface shadow-elevated"
    >
        <ul role="listbox" aria-label="Boulangeries disponibles" class="max-h-80 overflow-y-auto py-1">
            @if ($allowsAllSites)
                <li>
                    <button
                        type="button"
                        role="option"
                        wire:click="select(0)"
                        aria-selected="{{ $current === null ? 'true' : 'false' }}"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-subtle
                            {{ $current === null ? 'bg-context-50 font-medium text-context-700' : 'text-ink' }}"
                    >
                        <x-ui.icon name="building-2" :size="16" class="shrink-0 text-soft" />
                        <span class="min-w-0">
                            <span class="block truncate">Toutes les boulangeries</span>
                            <span class="block text-xs text-muted">Vision consolidée</span>
                        </span>
                    </button>
                </li>

                <li class="my-1 border-t border-line" role="presentation"></li>
            @endif

            @forelse ($bakeries as $bakery)
                <li>
                    <button
                        type="button"
                        role="option"
                        wire:click="select({{ $bakery->id }})"
                        aria-selected="{{ $current?->id === $bakery->id ? 'true' : 'false' }}"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-subtle
                            {{ $current?->id === $bakery->id ? 'bg-context-50 font-medium text-context-700' : 'text-ink' }}"
                    >
                        <x-ui.icon name="building-2" :size="16" class="shrink-0 text-soft" />

                        <span class="min-w-0 flex-1">
                            <span class="block truncate">{{ $bakery->name }}</span>
                            <span class="block truncate text-xs text-muted">{{ $bakery->code }}</span>
                        </span>

                        <x-ui.status-badge :tone="$bakery->status->tone()" :label="$bakery->status->label()" :icon="false" />
                    </button>
                </li>
            @empty
                <li class="px-4 py-3 text-sm text-muted">
                    Aucune boulangerie ne vous est affectée.
                </li>
            @endforelse
        </ul>
    </div>
</div>
