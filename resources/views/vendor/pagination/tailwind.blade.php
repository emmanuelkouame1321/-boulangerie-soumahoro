{{-- Design System §37 : pagination obligatoire sur les grandes listes.
     Vue surchargée pour respecter les tokens du design system — la vue par
     défaut de Laravel utilise des classes grises hors charte.
     `$paginator` et `$elements` sont fournis par Laravel. --}}

@if ($paginator->hasPages())
    <nav
        role="navigation"
        aria-label="Pagination"
        class="flex flex-col items-center justify-between gap-3 border-t border-line px-5 py-3 sm:flex-row"
    >
        <p class="text-xs text-muted">
            Page <span class="numeric">{{ $paginator->currentPage() }}</span>
            sur <span class="numeric">{{ $paginator->lastPage() }}</span>
            <span class="hidden sm:inline">
                — <span class="numeric">{{ $paginator->total() }}</span> résultat(s)
            </span>
        </p>

        <ul class="flex flex-wrap items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm text-soft" aria-disabled="true">
                        Précédent
                    </span>
                @else
                    <a
                        href="{{ $paginator->previousPageUrl() }}"
                        rel="prev"
                        class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm text-ink transition hover:bg-subtle"
                    >
                        Précédent
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="inline-flex min-h-10 items-center px-2 text-sm text-soft" aria-hidden="true">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page === $paginator->currentPage())
                                <span
                                    aria-current="page"
                                    class="numeric inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg bg-brand-600 px-3 text-sm font-medium text-white"
                                >
                                    {{ $page }}
                                </span>
                            @else
                                <a
                                    href="{{ $url }}"
                                    aria-label="Page {{ $page }}"
                                    class="numeric inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg px-3 text-sm text-ink transition hover:bg-subtle"
                                >
                                    {{ $page }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a
                        href="{{ $paginator->nextPageUrl() }}"
                        rel="next"
                        class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm text-ink transition hover:bg-subtle"
                    >
                        Suivant
                    </a>
                @else
                    <span class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm text-soft" aria-disabled="true">
                        Suivant
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
