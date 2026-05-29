@extends('layouts.app')

@section('title', 'Import Marketing Leads')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Import CSV</h1>
        <a href="{{ route('admin.standalone-leads.template') }}" class="btn btn-outline-secondary">Download template</a>
    </div>
    <div class="card shadow-soft">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.standalone-leads.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">CSV file</label>
                    <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                    <div class="form-text">Columns: name, phone, email, source, notes</div>
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>
        </div>
    </div>
@endsection
