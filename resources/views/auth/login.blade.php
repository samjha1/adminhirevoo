@extends('layouts.app')

@section('title', 'Admin Login')

@section('content')
    <div class="row justify-content-center align-items-center" style="min-height: calc(100vh - 140px);">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-soft">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="kpi-icon primary"><i class="bi bi-shield-lock"></i></div>
                        <div>
                            <div class="fw-bold">Admin login</div>
                            <div class="text-muted small">Sign in to manage employers</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.login.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input name="email" type="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror" required autofocus>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input name="password" type="password"
                                   class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>

                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sign in
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

