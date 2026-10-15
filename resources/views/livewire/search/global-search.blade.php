{{-- Palette de recherche globale — ouverte depuis la topbar via
     `$dispatch('open-global-search')`. Le panneau est monté à la racine de la
     coquille pour échapper au contexte d'empilement de la topbar collante. --}}
<div
    x-data="{
        open: false,
        show() {
            this.open = true
            this.$nextTick(() => {
                const input = this.$refs.term
                if (input) input.focus()
            })
        },
        close() {
            this.open = false
            if (this.$wire.term !== '') this.$wire.clear()
        },
    }"
    x-on:open-global-search.window="show()"
    x-on:keydown.ctrl.k.window.prevent="show()"
    x-on:keydown.meta.k.window.prevent="show()"
    x-on:keydown.escape.window="close()"
>
    @if ($term === '')
        <p class="sr-only" aria-live="polite">Saisissez au moins {{ $minimumLength }} caractères pour lancer la recherche.</p>
    @else
        <p class="sr-only" aria-live="polite">
            {{ array_sum(array_map(static fn (array $group): int => count($group['items']), $groups)) }} résultat(s) pour « {{ $term }} ».
        </p>
    @endif

    {{-- Visibilité par classe et non par `x-show` : Alpine diffère le passage
         à l'état visible (`setTimeout`), ce qui rendait impossible le focus
         immédiat du champ. Ici la bascule est synchrone, le `x-cloak` couvre
         l'état d'avant initialisation. --}}
    <div
        x-cloak
        x-bind:class="open ? 'flex' : 'hidden'"
        class="fixed inset-0 z-[90] items-start justify-center bg-ink/40 p-3 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-label="Recherche globale"
    >
        <div x-on:click="close()" class="absolute inset-0" aria-hidden="true"></div>

        <div class="relative mt-2 w-full max-w-2xl overflow-hidden rounded-modal border border-line bg-surface shadow-elevated sm:mt-14">
            <div class="flex items-center gap-2.5 border-b border-line px-4 py-3">
                <x-ui.icon name="search" :size="18" class="shrink-0 text-soft" />

                <input
                    x-ref="term"
                    type="search"
                    wire:model.live.debounce.300ms="term"
                    placeholder="Produit, client, vente, référence…"
                    aria-label="Terme de recherche globale"
                    autocomplete="off"
                    class="w-full border-0 bg-transparent p-0 text-sm text-ink transition placeholder:text-soft focus:outline-none"
                >

                <button
                    type="button"
                    x-on:click="close()"
                    class="shrink-0 rounded-control p-1.5 text-muted transition hover:bg-subtle"
                    aria-label="Fermer la recherche"
                >
                    <x-ui.icon name="x" :size="18" />
                </button>
            </div>

            <div class="max-h-[70vh] overflow-y-auto" role="region" aria-label="Résultats de recherche">
                @if (mb_strlen($term) < $minimumLength)
                    <x-ui.empty-state
                        icon="search"
                        title="Recherche globale"
                        description="Saisissez au moins {{ $minimumLength }} caractères — produits, clients, grossistes, fournisseurs, employés, ventes, transferts, pertes, achats et production."
                    />
                @elseif ($groups === [])
                    <x-ui.empty-state
                        icon="search"
                        title="Aucun résultat"
                        description="Aucun élément ne correspond à « {{ $term }} » sur les périmètres auxquels vous avez accès."
                    />
                @else
                    <div class="pb-2">
                        @foreach ($groups as $group)
                            <p class="label-caps flex items-center gap-2 px-4 pt-4 pb-1.5 text-soft">
                                <x-ui.icon :name="$group['icon']" :size="14" />
                                {{ $group['label'] }}
                                <span class="ml-auto numeric text-[10px]">{{ count($group['items']) }}</span>
                            </p>

                            <ul>
                                @foreach ($group['items'] as $item)
                                    <li>
                                        <a
                                            href="{{ $item['url'] }}"
                                            class="flex items-center gap-3 px-4 py-2.5 transition hover:bg-subtle"
                                        >
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[13px] font-medium text-ink">{{ $item['title'] }}</span>

                                                @if ($item['subtitle'] !== '')
                                                    <span class="block truncate text-xs text-muted">{{ $item['subtitle'] }}</span>
                                                @endif
                                            </span>

                                            @if ($item['meta'] !== '')
                                                <span class="shrink-0 text-xs text-soft">{{ $item['meta'] }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-line px-4 py-2.5">
                <p class="text-[11px] text-soft">Ctrl K pour ouvrir · Échap pour fermer</p>

                <button
                    type="button"
                    x-on:click="close()"
                    class="rounded-control border border-line-strong px-3 py-1.5 text-[12px] font-medium text-ink transition hover:bg-subtle"
                >
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>
