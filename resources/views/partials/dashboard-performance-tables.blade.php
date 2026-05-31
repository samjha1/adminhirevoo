@php
    $teamTables = $teamTables ?? [];
@endphp
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card shadow-soft">
            <div class="card-header bg-white fw-semibold">Team performance</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Team</th><th class="text-end">Leads</th><th class="text-end">Meetings</th><th class="text-end">Closures</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    @foreach($teamTables['byTeam'] ?? [] as $row)
                        <tr>
                            <td>{{ $row['team'] }}</td>
                            <td class="text-end">{{ number_format($row['leads']) }}</td>
                            <td class="text-end">{{ number_format($row['meetings']) }}</td>
                            <td class="text-end">{{ number_format($row['closures']) }}</td>
                            <td class="text-end">₹{{ number_format($row['revenue'], 0) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-soft h-100">
            <div class="card-header bg-white fw-semibold">Manager performance</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Manager</th><th>Team</th><th class="text-end">Leads</th><th class="text-end">Meetings</th><th class="text-end">Closures</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    @forelse($teamTables['byManager'] ?? [] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="small text-muted">{{ $row['team'] }}</td>
                            <td class="text-end">{{ number_format($row['leads']) }}</td>
                            <td class="text-end">{{ number_format($row['meetings']) }}</td>
                            <td class="text-end">{{ number_format($row['closures']) }}</td>
                            <td class="text-end">₹{{ number_format($row['revenue'], 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center py-3">No managers</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-soft h-100">
            <div class="card-header bg-white fw-semibold">Employee performance</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Employee</th><th>Manager</th><th class="text-end">Leads</th><th class="text-end">Meetings</th><th class="text-end">Closures</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    @forelse($teamTables['byEmployee'] ?? [] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="small text-muted">{{ $row['manager'] }}</td>
                            <td class="text-end">{{ number_format($row['leads']) }}</td>
                            <td class="text-end">{{ number_format($row['meetings']) }}</td>
                            <td class="text-end">{{ number_format($row['closures']) }}</td>
                            <td class="text-end">₹{{ number_format($row['revenue'], 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center py-3">No employees</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
