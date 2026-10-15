@props([
    'headers' => [],
    'stack' => true,
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

    // Sous `md`, chaque ligne devient une carte (styles dans app.css). On ne
    // peut pas étiqueter le contenu du slot en Blade : le libellé de chaque
    // colonne est donc transmis en variable CSS, consommée par `td::before`.
    // La première cellule sert de titre et n'a pas besoin de libellé.
    $mobileLabels = [];

    foreach (array_slice($columns, 1) as $index => $column) {
        // Échappement CSS (chaîne entre apostrophes) puis HTML (attribut entre
        // guillemets) : le libellé finit tel quel dans `td::before { content }`.
        $label = str_replace(
            ['\\', "'", '&', '"'],
            ['\\\\', "\\'", '&amp;', '&quot;'],
            $column['label']
        );

        $mobileLabels[] = '--dt-label-'.($index + 2).": '{$label}'";
    }

    $mobileStyle = implode('; ', $mobileLabels);
@endphp

<div
    @class(['overflow-x-auto', 'data-card-table' => $stack])
    @if ($stack && $mobileStyle !== '') style="{!! $mobileStyle !!}" @endif
>
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
