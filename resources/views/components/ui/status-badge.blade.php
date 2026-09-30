@props([
    'status' => 'neutral',
    'label' => null,
    'tone' => null,
    'icon' => true,
])

@php
    use Illuminate\Support\Str;

    // Design System §18 : un composant unique StatusBadge.
    // §53 : une couleur d'état n'est jamais utilisée seule — un libellé
    // est toujours affiché.
    $definition = null;

    foreach (config('boulangerie.statuses', []) as $group) {
        if (isset($group[$status])) {
            $definition = $group[$status];
            break;
        }
    }

    $resolvedTone = $tone ?? ($definition['tone'] ?? 'neutral');
    $resolvedLabel = $label ?? ($definition['label'] ?? Str::headline($status));

    $tones = [
        'success' => ['class' => 'bg-success-bg text-success', 'icon' => 'check'],
        'warning' => ['class' => 'bg-warning-bg text-warning', 'icon' => 'triangle-alert'],
        'danger' => ['class' => 'bg-danger-bg text-danger', 'icon' => 'triangle-alert'],
        'info' => ['class' => 'bg-info-bg text-info', 'icon' => 'info'],
        'neutral' => ['class' => 'bg-neutral-bg text-neutral', 'icon' => null],
    ];

    $toneConfig = $tones[$resolvedTone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium '.$toneConfig['class']]) }}>
    @if ($icon && $toneConfig['icon'])
        <x-ui.icon :name="$toneConfig['icon']" :size="14" class="shrink-0" />
    @endif
    {{ $resolvedLabel }}
</span>
