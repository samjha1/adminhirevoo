@extends('layouts.app')

@section('title', 'Payments')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Payments</h1>
            <div class="page-subtitle">Revenue and transactions</div>
        </div>
        <div class="text-end">
            <div class="text-muted small">Completed revenue</div>
            <div class="fw-bold fs-4">INR {{ number_format($revenue, 2) }}</div>
            @if(auth('admin')->user()?->canPermission('employer_payments.view'))
                <a href="{{ route('admin.employer-plan-payments.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                    Employer plan cheques
                </a>
            @endif
        </div>
    </div>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>User</th><th>Type</th><th>Amount</th><th>Status</th><th>Reference</th><th>Created</th></tr></thead>
                <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->user?->name ?? '—' }}</td>
                        <td class="text-capitalize">{{ str_replace('_', ' ', $payment->type) }}</td>
                        <td>{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                        <td><span class="badge text-bg-light text-capitalize">{{ $payment->status }}</span></td>
                        <td class="text-muted">{{ $payment->payment_reference ?? '—' }}</td>
                        <td class="text-muted">{{ $payment->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No payments found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $payments->links() }}</div>
@endsection

