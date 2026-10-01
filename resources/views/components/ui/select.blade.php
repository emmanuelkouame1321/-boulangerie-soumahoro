@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'help' => null,
    'required' => false,
    'placeholder' => null,
    'id' => null,
])

@php
    $fieldId = $id ?? 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $errorId = $fieldId.'-error';
    $helpId = $fieldId.'-help';

    $hasError = $errors->has($name);

    $describedBy = $hasError ? $errorId : ($help ? $helpId : null);

    $current = old($name, $selected instanceof \BackedEnum ? $selected->value : $selected);
    $current = $current === null ? null : (string) $current;

    $selectClasses = 'block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink transition '
        .'focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none '
        .'disabled:bg-subtle disabled:text-muted '
        .($hasError ? 'border-danger' : 'border-line-strong');
@endphp

<div>
    @if ($label)
        <label for="{{ $fieldId }}" class="mb-1.5 block text-xs font-bold text-ink">
            {{ $label }}
            @if ($required)
                <span class="text-danger" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->merge(['class' => $selectClasses]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" @selected($current !== null && (string) $value === $current)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1.5 flex items-start gap-1.5 text-sm text-danger">
            <x-ui.icon name="circle-alert" :size="16" class="mt-0.5 shrink-0" />
            <span>{{ $errors->first($name) }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $helpId }}" class="mt-1.5 text-xs text-muted">{{ $help }}</p>
    @endif
</div>
