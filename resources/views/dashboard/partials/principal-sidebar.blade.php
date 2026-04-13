@php
    $sidebarUser = $user ?? auth()->user();
    $active = $activePage
        ?? (request()->routeIs('dashboard.principal.master-report-page')
            ? 'master-report'
            : (request()->routeIs('dashboard.principal.attendance-alerts')
                ? 'attendance-alerts'
                : (request()->routeIs('dashboard.principal.partner-companies')
                    ? 'partner-companies'
                    : (request()->routeIs('dashboard.principal.journal-oversight')
                        ? 'journal-oversight'
                        : (request()->routeIs('dashboard.principal.school-performance')
                            ? 'school-performance'
                            : (request()->routeIs('dashboard.principal.timeline')
                                ? 'timeline'
                                : 'dashboard'))))));

    $avatarInitials = collect(explode(' ', trim($sidebarUser->name ?? 'U')))
        ->filter()
        ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');

    $avatarSource = !empty($sidebarUser?->avatar_url)
        ? (\Illuminate\Support\Str::startsWith($sidebarUser->avatar_url, ['http://', 'https://'])
            ? $sidebarUser->avatar_url
            : \Illuminate\Support\Facades\Storage::url($sidebarUser->avatar_url))
        : null;

    $avatarSourceWithVersion = $avatarSource
        ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($sidebarUser->updated_at?->timestamp ?? time())
        : null;
@endphp

<aside class="sidebar">
    <div class="sidebar-brand">{{ config('app.name', 'Kips') }} - Principal</div>

    <nav class="sidebar-nav" aria-label="Principal menu">
        <a class="{{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard.principal.weekly-journal') }}">Principal Dashboard</a>
        <a class="{{ $active === 'master-report' ? 'active' : '' }}" href="{{ route('dashboard.principal.master-report-page') }}">Master Report</a>
        <a class="{{ $active === 'attendance-alerts' ? 'active' : '' }}" href="{{ route('dashboard.principal.attendance-alerts') }}">Attendance Alerts</a>
        <a class="{{ $active === 'partner-companies' ? 'active' : '' }}" href="{{ route('dashboard.principal.partner-companies') }}">Partner Companies</a>
        <a class="{{ $active === 'journal-oversight' ? 'active' : '' }}" href="{{ route('dashboard.principal.journal-oversight') }}">Weekly Journal Oversight</a>
        <a class="{{ $active === 'school-performance' ? 'active' : '' }}" href="{{ route('dashboard.principal.school-performance') }}">School Performance</a>
        <a class="{{ $active === 'timeline' ? 'active' : '' }}" href="{{ route('dashboard.principal.timeline') }}">Timeline / PKL Status</a>
        <a href="{{ url('/') }}">Back to Home</a>
    </nav>

    <div class="sidebar-profile">
        <button type="button" class="profile-trigger" id="open-profile-modal" aria-label="Open profile modal">
            <span class="profile-avatar">
                @if (!empty($avatarSourceWithVersion))
                    <img src="{{ $avatarSourceWithVersion }}" alt="Profile picture" onerror="this.style.display='none'; this.parentElement.textContent='{{ $avatarInitials }}';">
                @else
                    {{ $avatarInitials }}
                @endif
            </span>
            <span>
                <div class="profile-name">{{ $sidebarUser->name }}</div>
                <div class="profile-meta">NIS: {{ $sidebarUser->nis ?? '-' }} &middot; {{ strtoupper($sidebarUser->role) }}</div>
            </span>
            <span class="profile-arrow">></span>
        </button>
    </div>
</aside>
