@extends('layouts.app')

@section('title', 'Employer plan payments')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Employer plan payments</h1>
            <div class="page-subtitle">Cheque checkout requests from the Hirevo employer dashboard</div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge text-bg-warning text-dark">{{ $pendingCount }} pending</span>
            <span class="badge text-bg-success">{{ $completedCount }} completed</span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <ul class="nav nav-pills mb-3 gap-2">
        <li class="nav-item">
            <a class="nav-link @if($status === '') active @endif"
               href="{{ route('admin.employer-plan-payments.index') }}">All</a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($status === 'pending') active @endif"
               href="{{ route('admin.employer-plan-payments.index', ['status' => 'pending']) }}">
                Pending
                @if($pendingCount > 0)
                    <span class="badge text-bg-light text-dark ms-1">{{ $pendingCount }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($status === 'completed') active @endif"
               href="{{ route('admin.employer-plan-payments.index', ['status' => 'completed']) }}">Completed</a>
        </li>
    </ul>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Cheque</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        @if($canComplete)
                            <th class="text-end">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @forelse($payments as $payment)
                    @php
                        $meta = $payment->meta ?? [];
                        $company = $meta['company_name'] ?? $payment->user?->referrerProfile?->company_name ?? $payment->user?->name ?? '—';
                        $planName = $meta['plan_name'] ?? ucfirst((string) ($meta['plan_key'] ?? '—'));
                        $baseAmount = (float) ($meta['base_amount'] ?? 0);
                        $gstAmount = (float) ($meta['gst_amount'] ?? 0);
                        $chequeDate = ! empty($meta['cheque_date'])
                            ? \Illuminate\Support\Carbon::parse($meta['cheque_date'])->format('d M Y')
                            : '—';
                        $isPending = $payment->status === \App\Models\Hirevo\HirevoPayment::STATUS_PENDING;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $company }}</div>
                            <div class="small text-muted">{{ $payment->user?->email ?? '—' }}</div>
                        </td>
                        <td>
                            <div>{{ $planName }}</div>
                            @if($baseAmount > 0)
                                <div class="small text-muted">Base ₹{{ number_format($baseAmount, 2) }} + GST ₹{{ number_format($gstAmount, 2) }}</div>
                            @endif
                        </td>
                        <td class="fw-semibold">₹{{ number_format((float) $payment->amount, 2) }}</td>
                        <td>
                            <div>#{{ $payment->payment_reference ?? '—' }}</div>
                            <div class="small text-muted">{{ $chequeDate }}</div>
                        </td>
                        <td>
                            @if($isPending)
                                <span class="badge text-bg-warning text-dark">Pending verification</span>
                            @elseif($payment->status === 'completed')
                                <span class="badge text-bg-success">Completed</span>
                            @else
                                <span class="badge text-bg-light text-capitalize">{{ $payment->status }}</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $payment->created_at?->format('d M Y, H:i') }}</td>
                        @if($canComplete)
                            <td class="text-end">
                                @if($isPending)
                                    <form method="POST"
                                          action="{{ route('admin.employer-plan-payments.complete', $payment) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Verify this cheque and activate the employer subscription?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Verify &amp; activate</button>
                                    </form>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canComplete ? 7 : 6 }}" class="text-center text-muted py-5">
                            No employer plan payments found for your access level.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $payments->links() }}</div>
@endsection
