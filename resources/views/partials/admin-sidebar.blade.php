@php use App\Support\AdminHomeResolver; @endphp
<aside class="sidebar d-none d-lg-flex flex-column" aria-label="Sidebar">
    <div class="sidebar-brand">
        <a href="{{ AdminHomeResolver::urlFor(auth('admin')->user()) }}" class="sidebar-brand-link">
            <span class="sidebar-brand-mark">
                <i class="bi bi-shield-lock"></i>
            </span>
            <span class="sidebar-brand-text">
                <span class="sidebar-brand-title">Hirevoo</span>
                <span class="sidebar-brand-sub">CRM Admin</span>
            </span>
        </a>
    </div>
    <div class="sidebar-scroll flex-grow-1">
        @include('partials.admin-sidebar-nav')
    </div>
</aside>
