@props([
    'name',
    'title' => null,
    'subtitle' => null,
    'maxWidth' => 'max-w-2xl',
])

{{-- Modale nommée, ouverte via `$dispatch('open-modal', 'nom')`.
     Le contenu passe par le slot par défaut, les actions par `footer`. --}}
<div
    x-data="{ open: false }"
    @open-modal.window="if (($event.detail && $event.detail.name ? $event.detail.name : $event.detail) === '{{ $name }}') open = true"
    @keydown.escape.window="open = false"
    x-cloak
    x-show="open"
    class="fixed inset-0 z-[80] flex items-center justify-center bg-ink/35 p-5"
    role="dialog"
    aria-modal="true"
>
    <div @click="open = false" class="absolute inset-0" aria-hidden="true"></div>

    <div
        x-show="open"
        x-transition
        class="relative max-h-[90vh] w-full {{ $maxWidth }} overflow-auto rounded-modal border border-line bg-surface shadow-elevated"
    >
        <div class="flex items-start justify-between gap-3 border-b border-line px-4 py-4">
            <div class="min-w-0">
                @if ($title)
                    <p class="text-[15px] font-bold text-ink">{{ $title }}</p>
                @endif
                @if ($subtitle)
                    <p class="text-[11px] text-soft">{{ $subtitle }}</p>
                @endif
            </div>

            <button type="button" @click="open = false" class="rounded-control p-1.5 text-muted hover:bg-subtle" aria-label="Fermer">
                <x-ui.icon name="x" :size="18" />
            </button>
        </div>

        <div class="p-4">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex justify-end gap-2 border-t border-line px-4 py-3.5">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
