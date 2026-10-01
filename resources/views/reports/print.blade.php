<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $label }} — {{ $from }} → {{ $to }}</title>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1F2933; margin: 32px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        p.meta { color: #52606D; font-size: 12px; margin: 0 0 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px; }
        th, td { border-bottom: 1px solid #E4E7EB; padding: 8px 10px; text-align: left; }
        th { background: #F5F7FA; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #52606D; }
        td.num, th.num { text-align: right; }
        .toolbar { margin-bottom: 20px; }
        @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>

    <h1>{{ $label }}</h1>
    <p class="meta">Du {{ $from }} au {{ $to }} — généré le {{ now()->format('d/m/Y H:i') }}</p>

    @if (count($tables['rows']) === 0)
        <p>Aucune donnée pour cette période.</p>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($tables['headers'] as $index => $header)
                        <th @class(['num' => $index > 0])>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($tables['rows'] as $row)
                    <tr>
                        @foreach ($row as $index => $value)
                            <td @class(['num' => $index > 0])>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
