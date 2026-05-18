@php
    $sidebarUser = $user ?? auth()->user();
    $active = $activePage ?? '';

    // Student Permissions
    $canStudentDashboard = function_exists('user_can_access') ? user_can_access($sidebarUser, 'student_dashboard', 'view') : true;
    $canCheckin = function_exists('user_can_access') ? user_can_access($sidebarUser, 'checkin', 'view') : true;
    $canTaskLog = function_exists('user_can_access') ? user_can_access($sidebarUser, 'task_log', 'view') : true;
    $canWeeklyJournal = function_exists('user_can_access') ? user_can_access($sidebarUser, 'weekly_journal', 'view') : true;
    $canCompletion = function_exists('user_can_access') ? user_can_access($sidebarUser, 'completion', 'view') : true;
    $canStudentData = function_exists('user_can_access') ? user_can_access($sidebarUser, 'student_data', 'view') : true;

    // Teacher Permissions
    $canTeacherDashboard = function_exists('teacher_access') ? teacher_access($sidebarUser, 'teacher_weekly_journal', 'view') : true;
    $canWatchlist = function_exists('teacher_access') ? teacher_access($sidebarUser, 'teacher_watchlist', 'view') : true;
    $canJournalAudit = function_exists('teacher_access') ? teacher_access($sidebarUser, 'teacher_journal_audit', 'view') : true;
    $canSiteVisits = function_exists('teacher_access') ? teacher_access($sidebarUser, 'teacher_site_visits', 'view') : true;
    $canRedFlags = function_exists('teacher_access') ? teacher_access($sidebarUser, 'teacher_red_flags', 'view') : true;
    $canContactDirectory = function_exists('teacher_access') ? teacher_access($sidebarUser, 'teacher_contact_directory', 'view') : true;

    // Mentor Permissions
    $canMentorDashboard = function_exists('user_can_access') ? user_can_access($sidebarUser, 'mentor_weekly_journal', 'view') : true;
    $canMentorReviewCenter = function_exists('user_can_access') ? user_can_access($sidebarUser, 'mentor_review_center', 'view') : true;
    $canMentorCompanySettings = function_exists('user_can_access') ? user_can_access($sidebarUser, 'mentor_company_settings', 'view') : true;

    // Kajur Permissions
    $canKajurDashboard = function_exists('user_can_access') ? user_can_access($sidebarUser, 'kajur_dashboard', 'view') : true;
    $canKajurWeeklyJournals = function_exists('user_can_access') ? user_can_access($sidebarUser, 'kajur_weekly_journals', 'view') : true;
    $canKajurDailyCheckins = function_exists('user_can_access') ? user_can_access($sidebarUser, 'kajur_daily_checkins', 'view') : true;
    $canKajurAbsenceReport = function_exists('user_can_access') ? user_can_access($sidebarUser, 'kajur_absence_report', 'view') : true;

    // Principal Permissions
    $canPrincipalDashboard = function_exists('user_can_access') ? user_can_access($sidebarUser, 'principal_dashboard', 'view') : true;
    $canPrincipalMasterReport = function_exists('user_can_access') ? user_can_access($sidebarUser, 'principal_master_report', 'view') : true;
    $canPrincipalAttendanceAlerts = function_exists('user_can_access') ? user_can_access($sidebarUser, 'principal_attendance_alerts', 'view') : true;
    $canPrincipalPartnerCompanies = function_exists('user_can_access') ? user_can_access($sidebarUser, 'principal_partner_companies', 'view') : true;
    $canPrincipalJournalOversight = function_exists('user_can_access') ? user_can_access($sidebarUser, 'principal_journal_oversight', 'view') : true;
    $canPrincipalSchoolPerformance = function_exists('user_can_access') ? user_can_access($sidebarUser, 'principal_school_performance', 'view') : true;
    $canPrincipalTimeline = function_exists('user_can_access') ? user_can_access($sidebarUser, 'principal_timeline', 'view') : true;

    // Super Admin Permissions
    $isSuperAdmin = ($sidebarUser && ($sidebarUser->role ?? null) === \App\Models\User::ROLE_SUPER_ADMIN);

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
    
    .sidebar-section-title {
        color: #94a3b8;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 1rem 0 0.5rem 1rem;
    }
</style>

<aside class="sidebar">
    @include('dashboard.partials.sidebar-brand', ['suffix' => ucfirst(str_replace('_', ' ', $sidebarUser->role ?? 'User'))])

    <nav class="sidebar-nav" aria-label="Main menu">
        @if ($canStudentDashboard || $canCheckin || $canTaskLog || $canWeeklyJournal || $canCompletion || $canStudentData)
            <div class="sidebar-section-title">Student</div>
            @if ($canStudentDashboard)
                <a class="{{ request()->routeIs('dashboard.student') ? 'active' : '' }}" href="{{ route('dashboard.student') }}">Dashboard</a>
            @endif
            @if ($canCheckin)
                <a class="{{ request()->routeIs('dashboard.student.checkin-page') ? 'active' : '' }}" href="{{ route('dashboard.student.checkin-page') }}">Check-in / Check-out</a>
            @endif
            @if ($canTaskLog)
                <a class="{{ request()->routeIs('dashboard.student.task-log-page') ? 'active' : '' }}" href="{{ route('dashboard.student.task-log-page') }}">Today's Task Log</a>
            @endif
            @if ($canWeeklyJournal)
                <a class="{{ request()->routeIs('dashboard.student.weekly-journal') ? 'active' : '' }}" href="{{ route('dashboard.student.weekly-journal') }}">Weekly Journal</a>
            @endif
            @if ($canCompletion)
                <a class="{{ request()->routeIs('dashboard.student.completion') ? 'active' : '' }}" href="{{ route('dashboard.student.completion') }}">Completion Bar</a>
            @endif
            @if ($canStudentData)
                <a class="{{ request()->routeIs('dashboard.student.data-page') ? 'active' : '' }} {{ !$studentDataComplete ? 'needs-data' : '' }}" href="{{ route('dashboard.student.data-page') }}">
                    Student Data
                    @if (!$studentDataComplete)
                        <span class="student-data-badge">Required</span>
                    @endif
                </a>
            @endif
        @endif

        @if ($canTeacherDashboard || $canWatchlist || $canJournalAudit || $canSiteVisits || $canRedFlags || $canContactDirectory)
            <div class="sidebar-section-title">Teacher</div>
            @if ($canTeacherDashboard)
                <a class="{{ request()->routeIs('dashboard.bindo.weekly-journal') ? 'active' : '' }}" href="{{ route('dashboard.bindo.weekly-journal') }}">Teacher Dashboard</a>
            @endif
            @if ($canWatchlist)
                <a class="{{ request()->routeIs('dashboard.bindo.watchlist') ? 'active' : '' }}" href="{{ route('dashboard.bindo.watchlist') }}">My Supervised Students</a>
            @endif
            @if ($canJournalAudit)
                <a class="{{ request()->routeIs('dashboard.bindo.journal-audit') ? 'active' : '' }}" href="{{ route('dashboard.bindo.journal-audit') }}">Journal Review & Language Audit</a>
            @endif
            @if ($canSiteVisits)
                <a class="{{ request()->routeIs('dashboard.bindo.site-visits') ? 'active' : '' }}" href="{{ route('dashboard.bindo.site-visits') }}">Site Visit Log</a>
            @endif
            @if ($canRedFlags)
                <a class="{{ request()->routeIs('dashboard.bindo.red-flags') ? 'active' : '' }}" href="{{ route('dashboard.bindo.red-flags') }}">Red Flag System</a>
            @endif
            @if ($canContactDirectory)
                <a class="{{ request()->routeIs('dashboard.bindo.contact-directory') ? 'active' : '' }}" href="{{ route('dashboard.bindo.contact-directory') }}">Contact Directory</a>
            @endif
        @endif

        @if ($canMentorDashboard || $canMentorReviewCenter || $canMentorCompanySettings)
            <div class="sidebar-section-title">Mentor</div>
            @if ($canMentorDashboard)
                <a class="{{ request()->routeIs('dashboard.mentor.weekly-journal') ? 'active' : '' }}" href="{{ route('dashboard.mentor.weekly-journal') }}">Mentor Dashboard</a>
            @endif
            @if ($canMentorReviewCenter)
                <a class="{{ request()->routeIs('dashboard.mentor.review-center') ? 'active' : '' }}" href="{{ route('dashboard.mentor.review-center') }}">Mentor Review Center</a>
            @endif
            @if ($canMentorCompanySettings)
                <a class="{{ request()->routeIs('dashboard.mentor.company-settings') ? 'active' : '' }}" href="{{ route('dashboard.mentor.company-settings') }}">Company Settings</a>
            @endif
        @endif

        @if ($canKajurDashboard || $canKajurWeeklyJournals || $canKajurDailyCheckins || $canKajurAbsenceReport)
            <div class="sidebar-section-title">Kajur</div>
            @if ($canKajurDashboard)
                <a class="{{ request()->routeIs('dashboard.kajur.dashboard') ? 'active' : '' }}" href="{{ route('dashboard.kajur.dashboard') }}">Kajur Dashboard</a>
            @endif
            @if ($canKajurWeeklyJournals)
                <a class="{{ request()->routeIs('dashboard.kajur.weekly-journal') ? 'active' : '' }}" href="{{ route('dashboard.kajur.weekly-journal') }}">Weekly Journals</a>
            @endif
            @if ($canKajurDailyCheckins)
                <a class="{{ request()->routeIs('dashboard.kajur.daily-checkin') ? 'active' : '' }}" href="{{ route('dashboard.kajur.daily-checkin') }}">Daily Check-ins</a>
            @endif
            @if ($canKajurAbsenceReport)
                <a class="{{ request()->routeIs('dashboard.kajur.absence-report') ? 'active' : '' }}" href="{{ route('dashboard.kajur.absence-report') }}">Absence Report</a>
            @endif
        @endif

        @if ($canPrincipalDashboard || $canPrincipalMasterReport || $canPrincipalAttendanceAlerts || $canPrincipalPartnerCompanies || $canPrincipalJournalOversight || $canPrincipalSchoolPerformance || $canPrincipalTimeline)
            <div class="sidebar-section-title">Principal</div>
            @if ($canPrincipalDashboard)
                <a class="{{ request()->routeIs('dashboard.principal.weekly-journal') ? 'active' : '' }}" href="{{ route('dashboard.principal.weekly-journal') }}">Principal Dashboard</a>
            @endif
            @if ($canPrincipalMasterReport)
                <a class="{{ request()->routeIs('dashboard.principal.master-report-page') ? 'active' : '' }}" href="{{ route('dashboard.principal.master-report-page') }}">Master Report</a>
            @endif
            @if ($canPrincipalAttendanceAlerts)
                <a class="{{ request()->routeIs('dashboard.principal.attendance-alerts') ? 'active' : '' }}" href="{{ route('dashboard.principal.attendance-alerts') }}">Attendance Alerts</a>
            @endif
            @if ($canPrincipalPartnerCompanies)
                <a class="{{ request()->routeIs('dashboard.principal.partner-companies') ? 'active' : '' }}" href="{{ route('dashboard.principal.partner-companies') }}">Partner Companies</a>
            @endif
            @if ($canPrincipalJournalOversight)
                <a class="{{ request()->routeIs('dashboard.principal.journal-oversight') ? 'active' : '' }}" href="{{ route('dashboard.principal.journal-oversight') }}">Journal Oversight</a>
            @endif
            @if ($canPrincipalSchoolPerformance)
                <a class="{{ request()->routeIs('dashboard.principal.school-performance') ? 'active' : '' }}" href="{{ route('dashboard.principal.school-performance') }}">School Performance</a>
            @endif
            @if ($canPrincipalTimeline)
                <a class="{{ request()->routeIs('dashboard.principal.timeline') ? 'active' : '' }}" href="{{ route('dashboard.principal.timeline') }}">Timeline / PKL Status</a>
            @endif
        @endif
        
        @if ($isSuperAdmin)
            <div class="sidebar-section-title">Super Admin</div>
            <a class="{{ request()->routeIs('dashboard.super-admin') ? 'active' : '' }}" href="{{ route('dashboard.super-admin') }}">Dashboard</a>
            <a class="{{ request()->routeIs('dashboard.super-admin.checkins') ? 'active' : '' }}" href="{{ route('dashboard.super-admin.checkins') }}">All Check-ins</a>
            <a class="{{ request()->routeIs('dashboard.super-admin.weekly-journals') ? 'active' : '' }}" href="{{ route('dashboard.super-admin.weekly-journals') }}">All Weekly Journals</a>
            <a class="{{ request()->routeIs('dashboard.super-admin.completion') ? 'active' : '' }}" href="{{ route('dashboard.super-admin.completion') }}">All Completion Bars</a>
            <a class="{{ request()->routeIs('dashboard.super-admin.mass-edit') ? 'active' : '' }}" href="{{ route('dashboard.super-admin.mass-edit') }}">Mass Edit</a>
            <a class="{{ request()->routeIs('dashboard.super-admin.companies') ? 'active' : '' }}" href="{{ route('dashboard.super-admin.companies') }}">Companies</a>
            <a class="{{ request()->routeIs('dashboard.super-admin.users*') ? 'active' : '' }}" href="{{ route('dashboard.super-admin.users') }}">Users</a>
            <a class="{{ request()->routeIs('dashboard.super-admin.permissions') ? 'active' : '' }}" href="{{ route('dashboard.super-admin.permissions') }}">Permissions</a>
            <a class="{{ request()->routeIs('dashboard.super-admin.activities') ? 'active' : '' }}" href="{{ route('dashboard.super-admin.activities') }}">Activities</a>
            <a class="{{ request()->routeIs('dashboard.super-admin.database') ? 'active' : '' }}" href="{{ route('dashboard.super-admin.database') }}">Database</a>
            <a class="{{ request()->routeIs('dashboard.super-admin.branding') ? 'active' : '' }}" href="{{ route('dashboard.super-admin.branding') }}">Branding</a>
        @endif

        <a href="{{ url('/') }}" style="margin-top: 1rem;">Back to Home</a>
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

