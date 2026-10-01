@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'required' => false,
    'placeholder' => null,
    'autocomplete' => null,
    'autofocus' => false,
    'inputmode' => null,
    'step' => null,
    'id' => null,
])

@php
    // Le label est toujours visible. En cas d'erreur, le champ est marqué
    // invalide et le message associé pour les lecteurs d'écran.
    $fieldId = $id ?? 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $errorId = $fieldId.'-error';
    $helpId = $fieldId.'-help';

    $hasError = $errors->has($name);

    $describedBy = $hasError ? $errorId : ($help ? $helpId : null);

    $resolvedValue = $type === 'password' ? null : old($name, $value);

    $inputClasses = 'block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink '
        .'placeholder:text-soft transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none '
        .'disabled:bg-subtle disabled:text-muted '
        .($hasError ? 'border-danger' : 'border-line-strong');
@endphp

<div>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-xs font-bold text-ink">
        {{ $label }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
        @endif
    </label>

    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $resolvedValue }}"
        @if ($required) required @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($autofocus) autofocus @endif
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        @if ($step) step="{{ $step }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->merge(['class' => $inputClasses]) }}
    >

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1.5 flex items-start gap-1.5 text-sm text-danger">
            <x-ui.icon name="circle-alert" :size="16" class="mt-0.5 shrink-0" />
            <span>{{ $errors->first($name) }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $helpId }}" class="mt-1.5 text-xs text-muted">{{ $help }}</p>
    @endif
</div>
