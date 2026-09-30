@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

{{-- Design System §13 : breadcrumb optionnel, H1, description, actions à droite.
     Une seule action principale porte le style primaire. --}}
<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    @if (! empty($breadcrumbs))
        <nav aria-label="Fil d’Ariane" class="mb-2">
            <ol class="flex flex-wrap items-center gap-1 text-xs text-muted">
                @foreach ($breadcrumbs as $crumb)
                    <li class="flex items-center gap-1">
                        @if (! $loop->first)
                            <x-ui.icon name="chevron-right" :size="12" class="text-soft" />
                        @endif

                        @if (! empty($crumb['href']) && ! $loop->last)
                            <a href="{{ $crumb['href'] }}" class="hover:text-ink">{{ $crumb['label'] }}</a>
                        @else
                            <span @class(['text-ink font-medium' => $loop->last])>{{ $crumb['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl leading-tight font-semibold text-ink">{{ $title }}</h1>

            @if ($description)
                <p class="mt-1 text-sm text-muted">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
