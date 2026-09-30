@props([
    'siteName' => 'Toutes les boulangeries',
    'siteMeta' => null,
])

{{-- Design System §11 et §12 : hauteur 64px, sticky, fond surface, bordure basse.
     Le contexte de boulangerie doit toujours être visible. --}}
<header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-surface px-4 sm:px-6">
    <button
        type="button"
        @click="$dispatch('toggle-sidebar')"
        class="rounded-lg p-2 text-muted hover:bg-subtle hover:text-ink lg:hidden"
        aria-label="Ouvrir le menu"
    >
        <x-ui.icon name="menu" :size="20" />
    </button>

    {{-- Sélecteur de boulangerie --}}
    <button
        type="button"
        class="flex min-w-0 items-center gap-2.5 rounded-lg border border-line px-3 py-2 text-left transition hover:bg-subtle"
        aria-haspopup="listbox"
        aria-label="Changer de boulangerie"
    >
        <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-700">
            <x-ui.icon name="building-2" :size="16" />
        </span>

        <span class="min-w-0">
            <span class="block truncate text-sm font-medium text-ink">{{ $siteName }}</span>
            @if ($siteMeta)
                <span class="block truncate text-xs text-muted">{{ $siteMeta }}</span>
            @endif
        </span>

        <x-ui.icon name="chevron-down" :size="16" class="ml-1 shrink-0 text-soft" />
    </button>

    <div class="flex-1"></div>

    {{-- Recherche globale (Design System §35) --}}
    <button
        type="button"
        class="hidden items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm text-muted transition hover:bg-subtle md:flex"
        aria-label="Recherche globale"
    >
        <x-ui.icon name="search" :size="16" />
        <span>Rechercher…</span>
        <kbd class="ml-4 rounded border border-line bg-subtle px-1.5 py-0.5 text-[11px] text-soft">Ctrl K</kbd>
    </button>

    <button
        type="button"
        class="relative rounded-lg p-2 text-muted transition hover:bg-subtle hover:text-ink"
        aria-label="Notifications"
    >
        <x-ui.icon name="bell" :size="20" />
    </button>

    {{-- Profil --}}
    <button
        type="button"
        class="flex items-center gap-2 rounded-lg p-1 transition hover:bg-subtle"
        aria-label="Profil utilisateur"
    >
        <span class="flex size-8 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-800">
            {{ strtoupper(\Illuminate\Support\Str::substr(auth()->user()?->name ?? 'Invité', 0, 1)) }}
        </span>
        <x-ui.icon name="chevron-down" :size="14" class="hidden text-soft sm:block" />
    </button>
</header>
