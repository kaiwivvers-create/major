@php
    $sidebarUser = $user ?? auth()->user();
    $active = $activePage
        ?? (request()->routeIs('dashboard.student.checkin-page')
            ? 'checkin'
            : (request()->routeIs('dashboard.student.task-log-page')
                ? 'tasklog'
                : (request()->routeIs('dashboard.student.weekly-journal')
                    ? 'journal'
                    : (request()->routeIs('dashboard.student.completion') ? 'completion' : 'dashboard'))));
    $dashboardUrl = route('dashboard.student');
    $taskHref = route('dashboard.student.task-log-page');
    $journalHref = route('dashboard.student.weekly-journal');
    $completionHref = route('dashboard.student.completion');

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
    <div class="sidebar-brand">{{ config('app.name', 'Kips') }}</div>

    <nav class="sidebar-nav" aria-label="Student menu">
        <a class="{{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard.student') }}">Dashboard</a>
        <a class="{{ $active === 'checkin' ? 'active' : '' }}" href="{{ route('dashboard.student.checkin-page') }}">Check-in / Check-out</a>
        <a class="{{ $active === 'tasklog' ? 'active' : '' }}" href="{{ $taskHref }}">Today's Task Log</a>
        <a class="{{ $active === 'journal' ? 'active' : '' }}" href="{{ $journalHref }}">Weekly Journal</a>
        <a class="{{ $active === 'completion' ? 'active' : '' }}" href="{{ $completionHref }}">Completion Bar</a>
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
