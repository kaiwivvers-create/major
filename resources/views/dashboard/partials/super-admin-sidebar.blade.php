@php
    $sidebarUser = $user ?? auth()->user();
    if (!empty($activePage)) {
        $active = $activePage;
    } elseif (request()->routeIs('dashboard.super-admin.checkins')) {
        $active = 'checkins';
    } elseif (request()->routeIs('dashboard.super-admin.weekly-journals')) {
        $active = 'journals';
    } elseif (request()->routeIs('dashboard.super-admin.completion')) {
        $active = 'completion';
    } elseif (request()->routeIs('dashboard.super-admin.mass-edit')) {
        $active = 'mass-edit';
    } elseif (request()->routeIs('dashboard.super-admin.companies*')) {
        $active = 'companies';
    } elseif (request()->routeIs('dashboard.super-admin.users*')) {
        $active = 'users';
    } elseif (request()->routeIs('dashboard.super-admin.permissions*')) {
        $active = 'permissions';
    } elseif (request()->routeIs('dashboard.super-admin.activities*')) {
        $active = 'activities';
    } elseif (request()->routeIs('dashboard.super-admin.database*')) {
        $active = 'database';
    } elseif (request()->routeIs('dashboard.super-admin.branding*')) {
        $active = 'branding';
    } else {
        $active = 'dashboard';
    }

    $avatarInitials = collect(explode(' ', trim($sidebarUser->name ?? 'U')))
        ->filter()
        ->map(function ($part) {
            return strtoupper(mb_substr($part, 0, 1));
        })
        ->take(2)
        ->implode('');

    $avatarUrl = $sidebarUser ? $sidebarUser->avatar_url : null;

    $avatarSource = !empty($avatarUrl)
        ? (\Illuminate\Support\Str::startsWith($avatarUrl, ['http://', 'https://'])
            ? $avatarUrl
            : \Illuminate\Support\Facades\Storage::url($avatarUrl))
        : null;

    $updatedAtTimestamp = null;
    if ($sidebarUser && !empty($sidebarUser->updated_at)) {
        $updatedAtTimestamp = $sidebarUser->updated_at->timestamp;
    }

    $avatarSourceWithVersion = $avatarSource
        ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($updatedAtTimestamp ?? time())
        : null;

    $isStrictSuperAdmin = ($sidebarUser && ($sidebarUser->role ?? null) === \App\Models\User::ROLE_SUPER_ADMIN);
@endphp

<aside class="sidebar">
    @include('dashboard.partials.sidebar-brand', ['suffix' => 'Super Admin'])

    <nav class="sidebar-nav" aria-label="Super admin menu">
        <a class="{{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard.super-admin') }}">Dashboard</a>
        <a class="{{ $active === 'checkins' ? 'active' : '' }}" href="{{ route('dashboard.super-admin.checkins') }}">All Check-ins</a>
        <a class="{{ $active === 'journals' ? 'active' : '' }}" href="{{ route('dashboard.super-admin.weekly-journals') }}">All Weekly Journals</a>
        <a class="{{ $active === 'completion' ? 'active' : '' }}" href="{{ route('dashboard.super-admin.completion') }}">All Completion Bars</a>
        <a class="{{ $active === 'mass-edit' ? 'active' : '' }}" href="{{ route('dashboard.super-admin.mass-edit') }}">Mass Edit</a>
        <a class="{{ $active === 'companies' ? 'active' : '' }}" href="{{ route('dashboard.super-admin.companies') }}">Companies</a>
        <a class="{{ $active === 'users' ? 'active' : '' }}" href="{{ route('dashboard.super-admin.users') }}">Users</a>
        @if ($isStrictSuperAdmin)
            <a class="{{ $active === 'permissions' ? 'active' : '' }}" href="{{ route('dashboard.super-admin.permissions') }}">Permissions</a>
            <a class="{{ $active === 'activities' ? 'active' : '' }}" href="{{ route('dashboard.super-admin.activities') }}">Activities</a>
            <a class="{{ $active === 'database' ? 'active' : '' }}" href="{{ route('dashboard.super-admin.database') }}">Database</a>
            <a class="{{ $active === 'branding' ? 'active' : '' }}" href="{{ route('dashboard.super-admin.branding') }}">Branding</a>
        @endif



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
            <div>
                <div class="profile-name">{{ $sidebarUser->name }}</div>
                <div class="profile-meta">NIS: {{ $sidebarUser->nis ?? '-' }} &middot; {{ strtoupper($sidebarUser->role) }}</div>
            </div>
            <span class="profile-arrow">></span>
        </button>
    </div>
</aside>

