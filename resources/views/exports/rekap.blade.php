<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #18181b; font-size: 12px; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        p { margin: 0 0 14px; color: #52525b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d4d4d8; padding: 7px; text-align: left; }
        th { background: #f4f4f5; font-weight: bold; }
        .summary { margin-bottom: 14px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>Dicetak pada {{ now()->format('Y-m-d H:i') }}</p>

    @if (! empty($summary))
        <table class="summary">
            <tr>
                @foreach ($summary as $label => $value)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($summary as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        </table>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
