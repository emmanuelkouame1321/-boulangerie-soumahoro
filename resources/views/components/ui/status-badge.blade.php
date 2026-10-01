@props([
    'status' => null,
    'label' => null,
    'tone' => null,
    'icon' => true,
])

@php
    use Illuminate\Support\Str;

    // Un composant unique pour tous les badges d'état (charte caisse).
    // Une couleur d'état n'est jamais utilisée seule : un libellé est
    // toujours affiché.
    $definition = null;

    if ($status !== null) {
        foreach (config('boulangerie.statuses', []) as $group) {
            if (isset($group[$status])) {
                $definition = $group[$status];
                break;
            }
        }
    }

    $resolvedTone = $tone ?? ($definition['tone'] ?? 'neutral');
    $resolvedLabel = $label ?? ($definition['label'] ?? ($status !== null ? Str::headline($status) : ''));

    $tones = [
        'success' => ['class' => 'bg-brand-50 text-brand-800', 'icon' => 'check'],
        'warning' => ['class' => 'bg-warning-bg text-warning', 'icon' => 'triangle-alert'],
        'danger' => ['class' => 'bg-danger-bg text-danger', 'icon' => 'triangle-alert'],
        'info' => ['class' => 'bg-context-600 text-white', 'icon' => 'info'],
        'neutral' => ['class' => 'bg-subtle text-muted', 'icon' => null],
    ];

    $toneConfig = $tones[$resolvedTone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex min-h-6 items-center gap-1.5 rounded-full px-2.5 text-[11px] font-bold tracking-wide whitespace-nowrap uppercase '.$toneConfig['class']]) }}>
    @if ($icon && $toneConfig['icon'])
        <x-ui.icon :name="$toneConfig['icon']" :size="12" class="shrink-0" />
    @endif
    {{ $resolvedLabel }}
</span>
