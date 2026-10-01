@props([
    'name',
    'label',
    'value' => null,
    'unit' => null,
    'help' => null,
    'required' => false,
    'step' => '0.0001',
    'inputmode' => 'decimal',
    'id' => null,
])

@php
    // Saisie de quantité de production : champ numérique au pas décimal,
    // avec l'unité affichée en suffixe. Le calcul reste serveur.
    $fieldId = $id ?? 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $errorId = $fieldId.'-error';

    $hasError = $errors->has($name);

    $resolvedValue = old($name, $value);

    $inputClasses = 'block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink '
        .'placeholder:text-soft transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none '
        .($hasError ? 'border-danger' : 'border-line-strong');
@endphp

<div>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-xs font-bold text-ink">
        {{ $label }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="flex items-stretch gap-2">
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="number"
            step="{{ $step }}"
            inputmode="{{ $inputmode }}"
            value="{{ $resolvedValue }}"
            @if ($required) required @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
            {{ $attributes->merge(['class' => $inputClasses]) }}
        >

        @if ($unit)
            <span class="inline-flex items-center rounded-control border border-line-strong bg-subtle px-3 text-xs font-bold whitespace-nowrap text-muted">
                {{ $unit }}
            </span>
        @endif
    </div>

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1.5 flex items-start gap-1.5 text-sm text-danger">
            <x-ui.icon name="circle-alert" :size="16" class="mt-0.5 shrink-0" />
            <span>{{ $errors->first($name) }}</span>
        </p>
    @elseif ($help)
        <p class="mt-1.5 text-xs text-muted">{{ $help }}</p>
    @endif
</div>
