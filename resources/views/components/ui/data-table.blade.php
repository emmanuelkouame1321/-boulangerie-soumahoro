@props([
    'headers' => [],
])

@php
    // Tableau de données de la charte caisse. Les colonnes acceptent soit un
    // libellé simple, soit ['label' => ..., 'align' => 'right'].
    $columns = array_map(
        static fn ($header): array => is_array($header)
            ? ['label' => $header['label'] ?? '', 'align' => $header['align'] ?? 'left']
            : ['label' => (string) $header, 'align' => 'left'],
        $headers
    );
@endphp

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'w-full border-collapse text-[13px]']) }}>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th @class([
                        'bg-subtle px-3 py-2.5 text-[11px] font-bold tracking-wide whitespace-nowrap text-muted uppercase',
                        'text-left' => $column['align'] !== 'right',
                        'text-right' => $column['align'] === 'right',
                    ])>
                        {{ $column['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody class="[&>tr>td]:border-b [&>tr>td]:border-line [&>tr>td]:px-3 [&>tr>td]:py-3 [&>tr>td]:align-middle [&>tr:last-child>td]:border-b-0">
            {{ $slot }}
        </tbody>
    </table>
</div>
