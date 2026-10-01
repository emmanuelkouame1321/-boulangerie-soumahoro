@php
    use App\Domain\Sites\BakeryContext;

    $groups = \App\Support\Navigation::forUser(auth()->user());
    $context = app(BakeryContext::class);
    $currentSite = $context->current();
@endphp

<div
    x-data="{ open: false }"
    @toggle-sidebar.window="open = !open"
    @keydown.escape.window="open = false"
>
    {{-- Voile mobile --}}
    <div
        x-cloak
        x-show="open"
        @click="open = false"
        class="fixed inset-0 z-30 bg-ink/40 lg:hidden"
        aria-hidden="true"
    ></div>

    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col overflow-y-auto bg-surface transition-transform duration-200 lg:translate-x-0"
        aria-label="Navigation principale"
    >
        {{-- Identité — bandeau vert de la charte --}}
        <div class="mt-1.5 flex h-16 shrink-0 items-center gap-3 bg-brand-700 px-4 text-white">
            <div class="grid size-9 shrink-0 place-items-center rounded-[9px] bg-white text-sm font-extrabold text-brand-700">
                B
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm leading-tight font-bold">BakeryOS</p>
                <p class="truncate text-[11px] leading-tight text-brand-100">Gestion métier</p>
            </div>

            <button
                type="button"
                @click="open = false"
                class="ml-auto rounded-control p-2 text-brand-100 hover:bg-brand-800 hover:text-white lg:hidden"
                aria-label="Fermer le menu"
            >
                <x-ui.icon name="x" :size="18" />
            </button>
        </div>

        {{-- Site actif --}}
        <div class="mx-3 mt-3.5 rounded-control bg-context-600 px-3 py-2.5 text-white">
            <p class="text-[10px] font-extrabold tracking-wide uppercase text-context-100">Site actif</p>
            <p class="truncate text-[13px] font-bold">{{ $currentSite?->name ?? 'Toutes les boulangeries' }}</p>
            <p class="truncate text-[10px] text-context-100">{{ $currentSite?->code ?? 'Vue consolidée' }}</p>
        </div>

        {{-- Menu --}}
        <nav class="flex-1 py-2" aria-label="Modules">
            @foreach ($groups as $group)
                @if ($group['label'])
                    <p class="px-4 pt-4 pb-1 text-[11px] font-bold tracking-wider text-soft uppercase">
                        {{ $group['label'] }}
                    </p>
                @endif

                @foreach ($group['items'] as $item)
                    <a
                        href="{{ $item['href'] }}"
                        @if ($item['active']) aria-current="page" @endif
                        class="mx-2 my-0.5 flex min-h-[42px] w-[calc(100%-16px)] items-center gap-2.5 rounded-control px-3 text-[12px] font-medium uppercase transition
                            {{ $item['active']
                                ? 'bg-brand-500 font-bold text-white'
                                : 'bg-subtle text-ink hover:bg-line' }}"
                    >
                        <x-ui.icon :name="$item['icon']" :size="18" class="shrink-0" />
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="shrink-0 border-t border-line px-4 py-3">
            <p class="text-[11px] text-soft">Produire juste. Vendre mieux.</p>
        </div>
    </aside>
</div>
