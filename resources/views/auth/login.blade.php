@extends('layouts.auth')

@section('title', 'Admin sign in')

@section('content')
    <div class="auth-page">
        <div class="auth-shell">
            <aside class="auth-brand-panel" aria-hidden="false">
                <div class="auth-brand-inner">
                    <a href="{{ url('/') }}" class="auth-logo-link">
                        <img src="{{ asset('images/hirevo-logo.svg') }}" alt="{{ config('app.name', 'Hirevoo') }}" class="auth-logo-img" width="200" height="44" loading="eager" decoding="async">
                    </a>
                    <h1 class="auth-brand-title">Sales CRM for your team</h1>
                    <p class="auth-brand-lead">
                        Manage company pipelines, candidate follow-ups, and meetings in one place.
                    </p>
                    <ul class="auth-features">
                        <li>
                            <i class="bi bi-buildings"></i>
                            <span>B2B employer pipeline with stages, kanban, and scheduling</span>
                        </li>
                        <li>
                            <i class="bi bi-person-workspace"></i>
                            <span>Talent sales — candidates, follow-ups, and team assignments</span>
                        </li>
                        <li>
                            <i class="bi bi-calendar-check"></i>
                            <span>Today's follow-ups and meetings on one schedule view</span>
                        </li>
                    </ul>
                </div>
                <p class="auth-brand-footer mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'Hirevoo') }}</p>
            </aside>

            <main class="auth-form-panel">
                <div>
                    <div class="auth-form-kicker">Staff access</div>
                    <h2 class="auth-form-title">Welcome back</h2>
                    <p class="auth-form-sub">Sign in with your admin email to open the CRM dashboard.</p>

                    @if ($errors->any())
                        <div class="auth-alert auth-alert-danger" role="alert">
                            <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1"></i>
                            <div>{{ $errors->first() }}</div>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="auth-alert auth-alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                    @endif
                    @if (session('status'))
                        <div class="auth-alert auth-alert-success" role="alert">
                            <i class="bi bi-check-circle-fill flex-shrink-0 mt-1"></i>
                            <div>{{ session('status') }}</div>
                        </div>
                    @endif
                    @if (session('info'))
                        <div class="auth-alert auth-alert-info" role="alert">
                            <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                            <div>{{ session('info') }}</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.store') }}" class="auth-form" novalidate>
                        @csrf

                        <div class="auth-field">
                            <label for="email">Email address</label>
                            <div class="auth-input-wrap">
                                <i class="bi bi-envelope field-icon" aria-hidden="true"></i>
                                <input id="email" name="email" type="email"
                                       value="{{ old('email') }}"
                                       class="@error('email') is-invalid @enderror"
                                       placeholder="you@company.com"
                                       autocomplete="email"
                                       required autofocus>
                            </div>
                            @error('email')
                                <div class="invalid-hint">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="password">Password</label>
                            <div class="auth-input-wrap has-toggle">
                                <i class="bi bi-lock field-icon" aria-hidden="true"></i>
                                <input id="password" name="password" type="password"
                                       class="@error('password') is-invalid @enderror"
                                       placeholder="Enter your password"
                                       autocomplete="current-password"
                                       required>
                                <button type="button" class="btn-toggle-pw" aria-label="Show password" data-toggle-password>
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-hint">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="auth-remember">
                            <input type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))>
                            <label for="remember">Keep me signed in on this device</label>
                        </div>

                        <button type="submit" class="btn-auth-submit">
                            <span>Sign in to CRM</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </main>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const btn = document.querySelector('[data-toggle-password]');
        const input = document.getElementById('password');
        if (!btn || !input) return;
        btn.addEventListener('click', function () {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !show);
                icon.classList.toggle('bi-eye-slash', show);
            }
        });
    })();
</script>
@endpush
