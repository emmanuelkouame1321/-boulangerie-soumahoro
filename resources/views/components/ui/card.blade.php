@props([
    'title' => null,
    'subtitle' => null,
    'flush' => false,
])

{{-- Carte / panneau de la charte caisse : titre + sous-titre, actions à
     droite, corps bordé. Le slot `actions` porte les boutons du panneau. --}}
<div {{ $attributes->merge(['class' => 'rounded-card border border-line bg-surface shadow-card']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-3 border-b border-line-strong px-4 pt-4 pb-2">
            <div class="min-w-0">
                @if ($title)
                    <p class="text-[15px] font-bold text-ink">{{ $title }}</p>
                @endif
                @if ($subtitle)
                    <p class="text-[11px] text-soft">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $flush ? '' : 'p-4' }}">
        {{ $slot }}
    </div>
</div>
