@php
    $extraModules = [
        'student_dashboard' => ['route' => 'dashboard.student', 'label' => 'Student Dashboard'],
        'checkin' => ['route' => 'dashboard.student.checkin-page', 'label' => 'Check-in / Check-out'],
        'task_log' => ['route' => 'dashboard.student.task-log-page', 'label' => 'Today\'s Task Log'],
        'weekly_journal' => ['route' => 'dashboard.student.weekly-journal', 'label' => 'Student Weekly Journal'],
        'completion' => ['route' => 'dashboard.student.completion', 'label' => 'Completion Bar'],
        'student_data' => ['route' => 'dashboard.student.data-page', 'label' => 'Student Data'],

        'mentor_weekly_journal' => ['route' => 'dashboard.mentor.weekly-journal', 'label' => 'Mentor Dashboard'],
        'mentor_review_center' => ['route' => 'dashboard.mentor.review-center', 'label' => 'Mentor Review Center'],
        'mentor_company_settings' => ['route' => 'dashboard.mentor.company-settings', 'label' => 'Mentor Company Settings'],
        
        'kajur_dashboard' => ['route' => 'dashboard.kajur.dashboard', 'label' => 'Kajur Dashboard'],
        'kajur_weekly_journals' => ['route' => 'dashboard.kajur.weekly-journal', 'label' => 'Kajur Weekly Journals'],
        'kajur_daily_checkins' => ['route' => 'dashboard.kajur.daily-checkin', 'label' => 'Kajur Daily Check-ins'],
        'kajur_absence_report' => ['route' => 'dashboard.kajur.absence-report', 'label' => 'Kajur Absence Report'],
        
        'teacher_weekly_journal' => ['route' => 'dashboard.bindo.weekly-journal', 'label' => 'Teacher Dashboard'],
        'teacher_watchlist' => ['route' => 'dashboard.bindo.watchlist', 'label' => 'My Supervised Students'],
        'teacher_journal_audit' => ['route' => 'dashboard.bindo.journal-audit', 'label' => 'Journal Review & Audit'],
        'teacher_site_visits' => ['route' => 'dashboard.bindo.site-visits', 'label' => 'Site Visit Log'],
        'teacher_red_flags' => ['route' => 'dashboard.bindo.red-flags', 'label' => 'Red Flag System'],
        'teacher_contact_directory' => ['route' => 'dashboard.bindo.contact-directory', 'label' => 'Contact Directory'],
        
        'principal_dashboard' => ['route' => 'dashboard.principal.weekly-journal', 'label' => 'Principal Dashboard'],
        'principal_master_report' => ['route' => 'dashboard.principal.master-report-page', 'label' => 'Master Report'],
        'principal_attendance_alerts' => ['route' => 'dashboard.principal.attendance-alerts', 'label' => 'Attendance Alerts'],
        'principal_partner_companies' => ['route' => 'dashboard.principal.partner-companies', 'label' => 'Partner Companies'],
        'principal_journal_oversight' => ['route' => 'dashboard.principal.journal-oversight', 'label' => 'Weekly Journal Oversight'],
        'principal_school_performance' => ['route' => 'dashboard.principal.school-performance', 'label' => 'School Performance'],
        'principal_timeline' => ['route' => 'dashboard.principal.timeline', 'label' => 'Timeline / PKL Status'],
        
        'super_admin_dashboard' => ['route' => 'dashboard.super-admin', 'label' => 'Super Admin Dashboard'],
        'super_admin_branding' => ['route' => 'dashboard.super-admin.branding', 'label' => 'Branding'],
        'super_admin_checkins' => ['route' => 'dashboard.super-admin.checkins', 'label' => 'All Check-ins'],
        'super_admin_weekly_journals' => ['route' => 'dashboard.super-admin.weekly-journals', 'label' => 'All Weekly Journals'],
        'super_admin_completion' => ['route' => 'dashboard.super-admin.completion', 'label' => 'All Completion Bars'],
        'super_admin_activities' => ['route' => 'dashboard.super-admin.activities', 'label' => 'Activities'],
        'super_admin_mass_edit' => ['route' => 'dashboard.super-admin.mass-edit', 'label' => 'Mass Edit'],
        'super_admin_companies' => ['route' => 'dashboard.super-admin.companies', 'label' => 'Companies'],
        'super_admin_users' => ['route' => 'dashboard.super-admin.users', 'label' => 'Users'],
        'super_admin_permissions' => ['route' => 'dashboard.super-admin.permissions', 'label' => 'Permissions'],
        'super_admin_database' => ['route' => 'dashboard.super-admin.database', 'label' => 'Database'],
    ];

    $skipModules = $skipModules ?? [];
    $sidebarUser = $user ?? auth()->user();
@endphp

@foreach($extraModules as $moduleKey => $moduleInfo)
    @if(!in_array($moduleKey, $skipModules) && function_exists('user_can_access') && user_can_access($sidebarUser, $moduleKey, 'view'))
        <a class="{{ request()->routeIs($moduleInfo['route']) ? 'active' : '' }}" href="{{ \Illuminate\Support\Facades\Route::has($moduleInfo['route']) ? route($moduleInfo['route']) : '#' }}">
            {{ $moduleInfo['label'] }}
        </a>
    @endif
@endforeach

