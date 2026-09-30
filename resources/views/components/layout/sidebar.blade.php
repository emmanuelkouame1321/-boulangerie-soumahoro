@php
    // Design System §10 : sidebar 248px, fixe desktop, drawer mobile.
    // Les groupes non autorisés ne sont jamais affichés (§10.1).
    $groups = \App\Support\Navigation::forUser(auth()->user());
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
        class="fixed inset-y-0 left-0 z-40 flex w-[248px] flex-col border-r border-brand-900 bg-brand-900 text-brand-50 transition-transform duration-200 lg:translate-x-0"
        aria-label="Navigation principale"
    >
        {{-- Identité --}}
        <div class="flex h-16 shrink-0 items-center gap-3 px-5">
            <div class="flex size-9 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">
                BS
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm leading-tight font-semibold">Boulangerie</p>
                <p class="truncate text-xs leading-tight text-brand-300">SOUMAHORO</p>
            </div>

            <button
                type="button"
                @click="open = false"
                class="ml-auto rounded-lg p-2 text-brand-200 hover:bg-brand-800 hover:text-white lg:hidden"
                aria-label="Fermer le menu"
            >
                <x-ui.icon name="x" :size="18" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-6">
            @foreach ($groups as $group)
                <div class="{{ $group['label'] ? 'mt-5' : 'mt-1' }}">
                    @if ($group['label'])
                        <p class="px-2 pb-2 text-[11px] font-semibold tracking-wider text-brand-400 uppercase">
                            {{ $group['label'] }}
                        </p>
                    @endif

                    <ul class="space-y-0.5">
                        @foreach ($group['items'] as $item)
                            <li>
                                <a
                                    href="{{ $item['href'] }}"
                                    @if ($item['active']) aria-current="page" @endif
                                    class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition
                                        {{ $item['active']
                                            ? 'bg-brand-50 font-medium text-brand-800'
                                            : 'text-brand-100 hover:bg-brand-800 hover:text-white' }}"
                                >
                                    @if ($item['active'])
                                        {{-- Indicateur vertical 3px (Design System §10.2) --}}
                                        <span class="absolute inset-y-1.5 left-0 w-[3px] rounded-full bg-brand-600" aria-hidden="true"></span>
                                    @endif

                                    <x-ui.icon
                                        :name="$item['icon']"
                                        :size="18"
                                        @class([
                                            'shrink-0',
                                            'text-brand-600' => $item['active'],
                                            'text-brand-300 group-hover:text-white' => ! $item['active'],
                                        ])
                                    />

                                    <span class="truncate">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="shrink-0 border-t border-brand-800 px-5 py-3">
            <p class="text-[11px] text-brand-400">Produire juste. Vendre mieux.</p>
        </div>
    </aside>
</div>
