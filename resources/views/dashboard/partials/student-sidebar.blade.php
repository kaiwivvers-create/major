@php
    $sidebarUser = $user ?? auth()->user();
    $active = $activePage
        ?? (request()->routeIs('dashboard.student.checkin-page')
            ? 'checkin'
            : (request()->routeIs('dashboard.student.task-log-page')
                ? 'tasklog'
                : (request()->routeIs('dashboard.student.weekly-journal')
                    ? 'journal'
                    : (request()->routeIs('dashboard.student.completion')
                        ? 'completion'
                        : (request()->routeIs('dashboard.student.data-page') ? 'studentdata' : 'dashboard')))));
    $dashboardUrl = route('dashboard.student');
    $taskHref = route('dashboard.student.task-log-page');
    $journalHref = route('dashboard.student.weekly-journal');
    $completionHref = route('dashboard.student.completion');
    $studentDataHref = route('dashboard.student.data-page');

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

    $studentProfileRow = \Illuminate\Support\Facades\Schema::hasTable('student_profiles')
        ? \Illuminate\Support\Facades\DB::table('student_profiles')
            ->where('student_id', $sidebarUser->id)
            ->first()
        : null;
    $studentDataComplete = $studentProfileRow
        && filled($sidebarUser->name)
        && filled(data_get($studentProfileRow, 'birth_place'))
        && filled(data_get($studentProfileRow, 'birth_date'))
        && filled(data_get($studentProfileRow, 'major_name'))
        && filled(data_get($studentProfileRow, 'address'))
        && filled(data_get($studentProfileRow, 'phone_number'))
        && filled(data_get($studentProfileRow, 'pkl_place_name'))
        && filled(data_get($studentProfileRow, 'pkl_place_address'))
        && filled(data_get($studentProfileRow, 'pkl_place_phone'))
        && filled(data_get($studentProfileRow, 'pkl_start_date'))
        && filled(data_get($studentProfileRow, 'pkl_end_date'))
        && filled(data_get($studentProfileRow, 'mentor_teacher_name'))
        && filled(data_get($studentProfileRow, 'school_supervisor_teacher_name'))
        && filled(data_get($studentProfileRow, 'company_instructor_position'));
@endphp

<style>
    .sidebar-nav a.needs-data {
        border-color: rgba(56, 189, 248, 0.95);
        box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2), 0 0 20px rgba(56, 189, 248, 0.45);
        animation: student-data-glow 1.2s ease-in-out infinite;
        font-weight: 700;
    }

    .student-data-badge {
        display: inline-block;
        margin-left: 6px;
        padding: 2px 7px;
        border-radius: 999px;
        border: 1px solid rgba(56, 189, 248, 0.65);
        background: rgba(56, 189, 248, 0.15);
        color: #bae6fd;
        font-size: 0.72rem;
        font-weight: 700;
        vertical-align: middle;
    }

    @keyframes student-data-glow {
        0%, 100% {
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.25), 0 0 18px rgba(56, 189, 248, 0.4);
        }
        50% {
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.5), 0 0 28px rgba(56, 189, 248, 0.8);
        }
    }
</style>

<aside class="sidebar">
    <div class="sidebar-brand">{{ config('app.name', 'Kips') }}</div>

    <nav class="sidebar-nav" aria-label="Student menu">
        <a class="{{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard.student') }}">Dashboard</a>
        <a class="{{ $active === 'checkin' ? 'active' : '' }}" href="{{ route('dashboard.student.checkin-page') }}">Check-in / Check-out</a>
        <a class="{{ $active === 'tasklog' ? 'active' : '' }}" href="{{ $taskHref }}">Today's Task Log</a>
        <a class="{{ $active === 'journal' ? 'active' : '' }}" href="{{ $journalHref }}">Weekly Journal</a>
        <a class="{{ $active === 'completion' ? 'active' : '' }}" href="{{ $completionHref }}">Completion Bar</a>
        <a class="{{ $active === 'studentdata' ? 'active' : '' }} {{ !$studentDataComplete ? 'needs-data' : '' }}" href="{{ $studentDataHref }}">
            Student Data
            @if (!$studentDataComplete)
                <span class="student-data-badge">Required</span>
            @endif
        </a>
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
