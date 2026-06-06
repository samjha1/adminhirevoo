<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Job Portal Reports</title>
    <style>
        body { font-family: Inter, Arial, sans-serif; padding: 24px; color: #0f172a; }
        h1 { font-size: 1.5rem; margin-bottom: 4px; }
        .muted { color: #64748b; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; }
        th { background: #f8fafc; }
    </style>
</head>
<body>
    <h1>Job Portal Reports</h1>
    <div class="muted">Generated {{ now()->format('M j, Y H:i') }}</div>
    @php $r = $reports ?? []; @endphp
    @foreach(['companies', 'jobs', 'candidates', 'applications'] as $section)
        <h2 style="margin-top:24px;text-transform:capitalize">{{ $section }}</h2>
        <table>
            <thead><tr><th>Metric</th><th>Value</th></tr></thead>
            <tbody>
            @foreach($r[$section] ?? [] as $metric => $value)
                <tr>
                    <td>{{ str_replace('_', ' ', ucfirst($metric)) }}</td>
                    <td>{{ number_format($value) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
