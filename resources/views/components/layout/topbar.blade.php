<header class="sticky top-1.5 z-20 flex h-16 items-center gap-3 border-b border-line bg-surface px-4 sm:px-6">
    <button
        type="button"
        @click="$dispatch('toggle-sidebar')"
        class="rounded-control p-2 text-muted hover:bg-subtle hover:text-ink lg:hidden"
        aria-label="Ouvrir le menu"
    >
        <x-ui.icon name="menu" :size="20" />
    </button>

    <button
        type="button"
        onclick="window.toast('Recherche globale ouverte')"
        class="flex items-center gap-2 rounded-control border border-line-strong px-3 py-2 text-[13px] text-muted transition hover:bg-subtle"
        aria-label="Recherche globale"
    >
        <x-ui.icon name="search" :size="16" />
        <span class="hidden sm:inline">Recherche</span>
    </button>

    <livewire:sites.site-switcher />

    <div class="flex-1"></div>

    <button
        type="button"
        onclick="window.toast('Centre d’alertes')"
        class="relative rounded-control border border-line-strong p-2 text-muted transition hover:bg-subtle hover:text-ink"
        aria-label="Notifications"
    >
        <x-ui.icon name="bell" :size="18" />
        <span class="absolute -top-1.5 -right-1.5 grid size-4 place-items-center rounded-full bg-danger text-[10px] font-bold text-white">7</span>
    </button>

    @auth
        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
            <button
                type="button"
                @click="open = !open"
                class="flex items-center gap-2 rounded-control border border-line-strong p-1 pr-2.5 transition hover:bg-subtle"
                :aria-expanded="open ? 'true' : 'false'"
                aria-haspopup="menu"
                aria-label="Profil utilisateur"
            >
                <span class="grid size-7 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-800">
                    {{ strtoupper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
                </span>
                <span class="hidden max-w-32 truncate text-[13px] font-medium text-ink sm:block">{{ auth()->user()->name }}</span>
                <x-ui.icon name="chevron-down" :size="14" class="hidden text-soft sm:block" />
            </button>

            <div
                x-cloak
                x-show="open"
                @click.outside="open = false"
                class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-card border border-line bg-surface shadow-elevated"
                role="menu"
            >
                <div class="border-b border-line px-4 py-3">
                    <p class="truncate text-[13px] font-medium text-ink">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-muted">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        role="menuitem"
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-[13px] text-ink transition hover:bg-subtle"
                    >
                        <x-ui.icon name="log-out" :size="16" class="text-soft" />
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    @endauth
</header>
