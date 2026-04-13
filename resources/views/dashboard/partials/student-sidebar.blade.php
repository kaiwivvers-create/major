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
    $canViewDashboard = function_exists('user_can_access') ? user_can_access($sidebarUser, 'student_dashboard', 'view') : true;
    $canViewCheckin = function_exists('user_can_access') ? user_can_access($sidebarUser, 'checkin', 'view') : true;
    $canViewTaskLog = function_exists('user_can_access') ? user_can_access($sidebarUser, 'task_log', 'view') : true;
    $canViewWeeklyJournal = function_exists('user_can_access') ? user_can_access($sidebarUser, 'weekly_journal', 'view') : true;
    $canViewCompletion = function_exists('user_can_access') ? user_can_access($sidebarUser, 'completion', 'view') : true;
    $canViewStudentData = function_exists('user_can_access') ? user_can_access($sidebarUser, 'student_data', 'view') : true;

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
    $studentClassName = trim((string) data_get($studentProfileRow, 'class_name', ''));
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

        /* Themed scrollbar */
        * {
            scrollbar-width: thin;
            scrollbar-color: #38bdf8 #0f172a;
        }

        *::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        *::-webkit-scrollbar-track {
            background: #0f172a;
        }

        *::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #38bdf8, #2563eb);
            border: 2px solid #0f172a;
            border-radius: 999px;
        }

        *::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #67e8f9, #3b82f6);
        }
    </style>

<aside class="sidebar">
    <div class="sidebar-brand">{{ config('app.name', 'Kips') }}</div>

    <nav class="sidebar-nav" aria-label="Student menu">
        @if ($canViewDashboard)
            <a class="{{ $active === 'dashboard' ? 'active' : '' }}" href="{{ $dashboardUrl }}">Dashboard</a>
        @endif
        @if ($canViewCheckin)
            <a class="{{ $active === 'checkin' ? 'active' : '' }}" href="{{ route('dashboard.student.checkin-page') }}">Check-in / Check-out</a>
        @endif
        @if ($canViewTaskLog)
            <a class="{{ $active === 'tasklog' ? 'active' : '' }}" href="{{ $taskHref }}">Today's Task Log</a>
        @endif
        @if ($canViewWeeklyJournal)
            <a class="{{ $active === 'journal' ? 'active' : '' }}" href="{{ $journalHref }}">Weekly Journal</a>
        @endif
        @if ($canViewCompletion)
            <a class="{{ $active === 'completion' ? 'active' : '' }}" href="{{ $completionHref }}">Completion Bar</a>
        @endif
        @if ($canViewStudentData)
            <a class="{{ $active === 'studentdata' ? 'active' : '' }} {{ !$studentDataComplete ? 'needs-data' : '' }}" href="{{ $studentDataHref }}">
                Student Data
                @if (!$studentDataComplete)
                    <span class="student-data-badge">Required</span>
                @endif
            </a>
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
            <span>
                <div class="profile-name">{{ $sidebarUser->name }}</div>
                <div class="profile-meta">
                    NIS: {{ $sidebarUser->nis ?? '-' }}
                    @if ($studentClassName !== '')
                        &middot; Class: {{ $studentClassName }}
                    @endif
                    &middot; {{ strtoupper($sidebarUser->role) }}
                </div>
            </span>
            <span class="profile-arrow">></span>
        </button>
    </div>
</aside>
