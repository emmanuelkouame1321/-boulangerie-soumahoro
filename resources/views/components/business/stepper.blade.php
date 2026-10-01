@props([
    'steps' => [],
    'current' => 1,
])

@php
    // Barre d'étapes de l'assistant de production (Design System §22 et
    // §43). L'étape active est soulignée ; les étapes franchies sont
    // marquées. Aucune couleur codée en dur : uniquement les tokens.
@endphp

<ol {{ $attributes->merge(['class' => 'flex gap-1']) }}>
    @foreach ($steps as $index => $step)
        @php
            $number = $index + 1;
            $isDone = $number < $current;
            $isActive = $number === $current;
        @endphp

        <li @class([
            'flex-1 border-b-2 pb-2 text-xs',
            'border-brand-500 font-bold text-brand-800' => $isActive,
            'border-brand-300 text-brand-700' => $isDone,
            'border-line text-muted' => ! $isActive && ! $isDone,
        ])>
            <span class="numeric mr-1">{{ $number }}.</span>{{ $step }}
        </li>
    @endforeach
</ol>
