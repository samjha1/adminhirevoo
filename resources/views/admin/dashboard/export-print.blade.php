<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard export</title>
    <style>body{font-family:system-ui,sans-serif;padding:24px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}</style>
</head>
<body>
    <h1>Executive dashboard</h1>
    <p>{{ ($period ?? null)?->label() ?? '' }}</p>
    <h2>Summary</h2>
    <table>
        <tr><th></th><th>Talent</th><th>Company</th><th>Combined</th></tr>
        @foreach(['totalLeads','meetings','closed','revenue'] as $k)
            <tr>
                <td>{{ $k }}</td>
                <td>{{ $summary['talent'][$k] ?? 0 }}</td>
                <td>{{ $summary['company'][$k] ?? 0 }}</td>
                <td>{{ $summary['combined'][$k] ?? 0 }}</td>
            </tr>
        @endforeach
    </table>
    <p><small>Print this page to PDF from your browser.</small></p>
</body>
</html>
