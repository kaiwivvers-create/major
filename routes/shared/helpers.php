<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

if (!function_exists('available_permission_modules')) {
    function available_permission_modules(): array
    {
        return [
            'student_dashboard' => 'Student Dashboard',
            'checkin' => 'Check-in / Check-out',
            'task_log' => 'Task Log',
            'mentor_weekly_journal' => 'Mentor Dashboard (Weekly Journal)',
            'mentor_review_center' => 'Mentor Review Center (Daily Scoring)',
            'mentor_company_settings' => 'Mentor Company Settings',
            'kajur_dashboard' => 'Kajur Dashboard',
            'kajur_weekly_journals' => 'Kajur Weekly Journals',
            'kajur_daily_checkins' => 'Kajur Daily Check-ins',
            'kajur_absence_report' => 'Kajur Absence Report',
            'teacher_weekly_journal' => 'Teacher Dashboard (School Mentor)',
            'teacher_watchlist' => 'Teacher - My Supervised Students',
            'teacher_journal_audit' => 'Teacher - Journal Review & Language Audit',
            'teacher_site_visits' => 'Teacher - Site Visit Log',
            'teacher_red_flags' => 'Teacher - Red Flag System',
            'teacher_contact_directory' => 'Teacher - Contact Directory',
            'principal_dashboard' => 'Principal Dashboard',
            'principal_master_report' => 'Principal Master Report',
            'principal_attendance_alerts' => 'Principal Attendance Alerts',
            'principal_partner_companies' => 'Principal Partner Companies',
            'principal_journal_oversight' => 'Principal Journal Oversight',
            'principal_school_performance' => 'Principal School Performance',
            'principal_timeline' => 'Principal Timeline / PKL Status',
            'weekly_journal' => 'Weekly Journal',
            'completion' => 'Completion Page',
            'student_data' => 'Student Data',
            'super_admin_dashboard' => 'Super Admin Dashboard',
            'super_admin_branding' => 'Super Admin Branding',
            'super_admin_checkins' => 'Super Admin Check-ins',
            'super_admin_weekly_journals' => 'Super Admin Weekly Journals',
            'super_admin_completion' => 'Super Admin Completion',
            'super_admin_activities' => 'Super Admin Activities',
            'super_admin_mass_edit' => 'Super Admin Mass Edit',
            'super_admin_companies' => 'Super Admin Companies',
            'super_admin_users' => 'Super Admin Users',
            'super_admin_permissions' => 'Super Admin Permissions',
            'super_admin_database' => 'Super Admin Database',
            'users_management' => 'Users Management (Legacy)',
        ];
    }
}

if (!function_exists('user_permissions_payload')) {
    function user_permissions_payload(mixed $user): array
    {
        $payload = data_get($user, 'permissions_json');
        if (is_array($payload)) {
            return $payload;
        }
        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}

if (!function_exists('permissions_storage_ready')) {
    function permissions_storage_ready(): bool
    {
        return Schema::hasTable('users') && Schema::hasColumn('users', 'permissions_json');
    }
}

if (!function_exists('permission_module_recommended_roles')) {
    function permission_module_recommended_roles(): array
    {
        return [
            'student_dashboard' => [User::ROLE_STUDENT],
            'checkin' => [User::ROLE_STUDENT, User::ROLE_KAJUR, User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
            'task_log' => [User::ROLE_STUDENT],
            'weekly_journal' => [User::ROLE_STUDENT, User::ROLE_MENTOR, User::ROLE_TEACHER, User::ROLE_KAJUR, User::ROLE_PRINCIPAL],
            'completion' => [User::ROLE_STUDENT, User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
            'student_data' => [User::ROLE_STUDENT, User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],

            'mentor_weekly_journal' => [User::ROLE_MENTOR],
            'mentor_review_center' => [User::ROLE_MENTOR],
            'mentor_company_settings' => [User::ROLE_MENTOR],

            'kajur_dashboard' => [User::ROLE_KAJUR],
            'kajur_weekly_journals' => [User::ROLE_KAJUR],
            'kajur_daily_checkins' => [User::ROLE_KAJUR],
            'kajur_absence_report' => [User::ROLE_KAJUR],

            'teacher_weekly_journal' => [User::ROLE_TEACHER],
            'teacher_watchlist' => [User::ROLE_TEACHER],
            'teacher_journal_audit' => [User::ROLE_TEACHER],
            'teacher_site_visits' => [User::ROLE_TEACHER],
            'teacher_red_flags' => [User::ROLE_TEACHER],
            'teacher_contact_directory' => [User::ROLE_TEACHER],

            'principal_dashboard' => [User::ROLE_PRINCIPAL],
            'principal_master_report' => [User::ROLE_PRINCIPAL],
            'principal_attendance_alerts' => [User::ROLE_PRINCIPAL],
            'principal_partner_companies' => [User::ROLE_PRINCIPAL],
            'principal_journal_oversight' => [User::ROLE_PRINCIPAL],
            'principal_school_performance' => [User::ROLE_PRINCIPAL],
            'principal_timeline' => [User::ROLE_PRINCIPAL],

            'super_admin_dashboard' => [User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
            'super_admin_branding' => [User::ROLE_SUPER_ADMIN],
            'super_admin_checkins' => [User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
            'super_admin_weekly_journals' => [User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
            'super_admin_completion' => [User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
            'super_admin_activities' => [User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
            'super_admin_mass_edit' => [User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
            'super_admin_companies' => [User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
            'super_admin_users' => [User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
            'super_admin_permissions' => [User::ROLE_SUPER_ADMIN],
            'super_admin_database' => [User::ROLE_SUPER_ADMIN],
            'users_management' => [User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN],
        ];
    }
}

if (!function_exists('is_admin_like_role')) {
    function is_admin_like_role(mixed $user): bool
    {
        $role = (string) data_get($user, 'role');
        return in_array($role, [User::ROLE_SUPER_ADMIN, User::ROLE_KESISWAAN], true);
    }
}

if (!function_exists('is_super_admin_role')) {
    function is_super_admin_role(mixed $user): bool
    {
        return (string) data_get($user, 'role') === User::ROLE_SUPER_ADMIN;
    }
}

if (!function_exists('role_permissions_storage_ready')) {
    function role_permissions_storage_ready(): bool
    {
        return Schema::hasTable('role_permissions')
            && Schema::hasColumn('role_permissions', 'role')
            && Schema::hasColumn('role_permissions', 'permissions_json');
    }
}

if (!function_exists('role_permissions_payload')) {
    function role_permissions_payload(string $role): array
    {
        if (!role_permissions_storage_ready()) {
            return [];
        }

        static $cache = [];
        if (array_key_exists($role, $cache)) {
            return $cache[$role];
        }

        $raw = DB::table('role_permissions')
            ->where('role', $role)
            ->value('permissions_json');

        if (is_array($raw)) {
            $cache[$role] = $raw;
            return $cache[$role];
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $cache[$role] = is_array($decoded) ? $decoded : [];
            return $cache[$role];
        }

        $defaultModules = permission_modules_for_role($role);
        if (!empty($defaultModules)) {
            $defaults = [];
            foreach (array_keys($defaultModules) as $module) {
                $defaults[$module] = [
                    'view' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => true,
                ];
            }
            $cache[$role] = $defaults;
            return $cache[$role];
        }

        $cache[$role] = [];
        return $cache[$role];
    }
}

if (!function_exists('normalize_permissions_payload')) {
    function normalize_permissions_payload(array $permissionsPayload): array
    {
        $normalized = [];
        $actions = ['view', 'create', 'update', 'delete'];

        foreach ($permissionsPayload as $module => $modulePermissions) {
            if (!is_array($modulePermissions)) {
                $modulePermissions = [];
            }

            $row = [];
            foreach ($actions as $action) {
                $row[$action] = (bool) data_get($modulePermissions, $action, false);
            }

            if ($row['create'] || $row['update'] || $row['delete']) {
                $row['view'] = true;
            }

            $normalized[$module] = $row;
        }

        return $normalized;
    }
}

if (!function_exists('user_can_access')) {
    function user_can_access(mixed $user, string $module, string $action = 'view'): bool
    {
        if (!$user) {
            return false;
        }
        if (is_super_admin_role($user)) {
            return true;
        }

        if (!permissions_storage_ready() && !role_permissions_storage_ready()) {
            return true;
        }

        if (!array_key_exists($module, available_permission_modules())) {
            return false;
        }

        $allowedByUser = null;
        $userPermissions = user_permissions_payload($user);
        if (!empty($userPermissions)) {
            $modulePermissions = data_get($userPermissions, $module, null);
            if (is_array($modulePermissions)) {
                $allowedByUser = (bool) data_get($modulePermissions, $action, false);
            }
        }

        $allowedByRole = null;
        $permissions = role_permissions_payload((string) ($user->role ?? ''));
        if (!empty($permissions)) {
            $modulePermissions = data_get($permissions, $module, null);
            if (is_array($modulePermissions)) {
                $allowedByRole = (bool) data_get($modulePermissions, $action, false);
            }
        }

        if ($allowedByUser === true || $allowedByRole === true) {
            return true;
        }

        return false;
    }
}

if (!function_exists('teacher_dashboard_modules')) {
    function teacher_dashboard_modules(): array
    {
        return [
            'teacher_weekly_journal',
            'teacher_watchlist',
            'teacher_journal_audit',
            'teacher_site_visits',
            'teacher_red_flags',
            'teacher_contact_directory',
        ];
    }
}

if (!function_exists('permission_module_defined_for_user')) {
    function permission_module_defined_for_user(mixed $user, string $module): bool
    {
        $userPermissions = user_permissions_payload($user);
        if (array_key_exists($module, $userPermissions)) {
            return true;
        }

        $rolePermissions = role_permissions_payload((string) ($user->role ?? ''));
        return array_key_exists($module, $rolePermissions);
    }
}

if (!function_exists('teacher_access')) {
    function teacher_access(mixed $user, string $module, string $action = 'view'): bool
    {
        if (user_can_access($user, $module, $action)) {
            return true;
        }

        $teacherModules = teacher_dashboard_modules();
        if (
            in_array($module, $teacherModules, true)
            && $module !== 'teacher_weekly_journal'
            && !permission_module_defined_for_user($user, $module)
        ) {
            return user_can_access($user, 'teacher_weekly_journal', $action);
        }

        return false;
    }
}

if (!function_exists('teacher_has_dashboard_access')) {
    function teacher_has_dashboard_access(mixed $user, string $action = 'view'): bool
    {
        foreach (teacher_dashboard_modules() as $module) {
            if (teacher_access($user, $module, $action)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('require_user_permission')) {
    function require_user_permission(mixed $user, string $module, string $action = 'view'): void
    {
        abort_unless(user_can_access($user, $module, $action), 403, 'You do not have permission for this action.');
    }
}

if (!function_exists('get_chatbot_response')) {
    function get_chatbot_response(string $message, $user): array
    {
        $query = mb_strtolower(trim($message));
        
        if (Str::contains($query, ['help', 'feature', 'how to', 'what'])) {
            return [
                'message' => "I can explain the features! Students use me to check-in and log tasks. Mentors score your work. Admins manage the whole system and view attendance graphs.",
                'type' => 'info'
            ];
        }

        if (Str::contains($query, ['report', 'absence', 'summary'])) {
            if (!in_array($user->role, ['kajur', 'principal', 'super_admin'])) {
                return ['message' => "I'm sorry, only administrators can access absence reports.", 'type' => 'error'];
            }
            $today = now('Asia/Jakarta')->toDateString();
            $total = DB::table('users')->where('role', 'student')->count();
            $present = DB::table('attendances')->whereDate('attendance_date', $today)->whereNotNull('check_in_at')->count();
            return [
                'message' => "Attendance Report for {$today}: Total students: {$total}. Present today: {$present}. Alpha: " . ($total - $present),
                'type' => 'report'
            ];
        }

        return ['message' => "I didn't quite catch that. Try asking 'What are the features?' or 'Absence report summary'.", 'type' => 'default'];
    }
}

if (!function_exists('mentor_supervised_student_ids')) {
    function mentor_supervised_student_ids(mixed $user)
    {
        if (!$user || ($user->role ?? null) !== User::ROLE_MENTOR) {
            return collect();
        }

        $studentIds = collect();
        if (Schema::hasTable('student_profiles')) {
            $mentorName = strtoupper(trim((string) $user->name));
            $studentIds = $studentIds->merge(
                DB::table('student_profiles as sp')
                    ->join('users as s', 's.id', '=', 'sp.student_id')
                    ->where('s.role', User::ROLE_STUDENT)
                    ->whereRaw('UPPER(TRIM(COALESCE(sp.mentor_teacher_name, ""))) = ?', [$mentorName])
                    ->pluck('sp.student_id')
            );

            if (
                Schema::hasTable('partner_companies')
                && Schema::hasColumn('users', 'partner_company_id')
                && !empty($user->partner_company_id)
            ) {
                $mentorCompany = DB::table('partner_companies')
                    ->where('id', $user->partner_company_id)
                    ->first(['name', 'address']);

                $companyName = trim((string) data_get($mentorCompany, 'name', ''));
                $companyAddress = trim((string) data_get($mentorCompany, 'address', '-')) ?: '-';
                if ($companyName !== '') {
                    $studentIds = $studentIds->merge(
                        DB::table('student_profiles as sp')
                            ->join('users as s', 's.id', '=', 'sp.student_id')
                            ->where('s.role', User::ROLE_STUDENT)
                            ->whereRaw('TRIM(COALESCE(sp.pkl_place_name, "")) = ?', [$companyName])
                            ->whereRaw("COALESCE(NULLIF(TRIM(sp.pkl_place_address), ''), '-') = ?", [$companyAddress])
                            ->pluck('sp.student_id')
                    );
                }
            }
        }

        if (Schema::hasTable('weekly_journals')) {
            $studentIds = $studentIds->merge(
                DB::table('weekly_journals')
                    ->where('mentor_id', $user->id)
                    ->distinct()
                    ->pluck('student_id')
            );
        }

        return $studentIds
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }
}

if (!function_exists('school_supervisor_assignment_storage_ready')) {
    function school_supervisor_assignment_storage_ready(): bool
    {
        return Schema::hasTable('student_school_supervisors')
            && Schema::hasColumn('student_school_supervisors', 'student_id')
            && Schema::hasColumn('student_school_supervisors', 'teacher_id');
    }
}

if (!function_exists('student_school_supervisor_ids')) {
    function student_school_supervisor_ids(int $studentId)
    {
        if (!school_supervisor_assignment_storage_ready()) {
            return collect();
        }

        return DB::table('student_school_supervisors')
            ->where('student_id', $studentId)
            ->pluck('teacher_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }
}

if (!function_exists('teacher_supervised_student_ids')) {
    function teacher_supervised_student_ids(mixed $user)
    {
        if (!$user || ($user->role ?? null) !== User::ROLE_TEACHER) {
            return collect();
        }

        $studentIds = collect();
        $hasAssignmentRows = false;
        if (school_supervisor_assignment_storage_ready()) {
            $assignedRows = DB::table('student_school_supervisors as sss')
                ->join('users as s', 's.id', '=', 'sss.student_id')
                ->where('sss.teacher_id', $user->id)
                ->where('s.role', User::ROLE_STUDENT)
                ->pluck('sss.student_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
            if ($assignedRows->isNotEmpty()) {
                $studentIds = $studentIds->merge($assignedRows);
                $hasAssignmentRows = true;
            }
        }

        if (Schema::hasTable('student_profiles')) {
            $teacherName = strtoupper(trim((string) $user->name));
            if (!$hasAssignmentRows) {
                $studentIds = $studentIds->merge(
                    DB::table('student_profiles as sp')
                        ->join('users as s', 's.id', '=', 'sp.student_id')
                        ->where('s.role', User::ROLE_STUDENT)
                        ->whereRaw('UPPER(TRIM(COALESCE(sp.school_supervisor_teacher_name, ""))) = ?', [$teacherName])
                        ->pluck('sp.student_id')
                );
            }
        }

        if (Schema::hasTable('weekly_journals')) {
            $studentIds = $studentIds->merge(
                DB::table('weekly_journals')
                    ->where('bindo_id', $user->id)
                    ->distinct()
                    ->pluck('student_id')
            );
        }

        $teacherClassScope = Schema::hasColumn('users', 'teacher_class_name')
            ? trim((string) ($user->teacher_class_name ?? ''))
            : '';
        if (!$hasAssignmentRows && $teacherClassScope !== '' && strtoupper($teacherClassScope) !== 'ALL' && Schema::hasTable('student_profiles')) {
            $classStudentIds = DB::table('student_profiles')
                ->whereRaw('TRIM(COALESCE(class_name, "")) = ?', [$teacherClassScope])
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            if ($studentIds->isEmpty()) {
                $studentIds = $classStudentIds;
            } else {
                $studentIds = $studentIds->intersect($classStudentIds);
            }
        }

        return $studentIds
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }
}

if (!function_exists('normalize_whatsapp_number')) {
    function normalize_whatsapp_number(?string $phone): ?string
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?: '';
        if ($digits === '') {
            return null;
        }

        if (Str::startsWith($digits, '0')) {
            $digits = '62' . ltrim($digits, '0');
        } elseif (Str::startsWith($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits !== '' ? 'https://wa.me/' . $digits : null;
    }
}

if (!function_exists('attendance_checkin_cutoff_time')) {
    function attendance_checkin_cutoff_time(): string
    {
        $raw = trim((string) env('ATTENDANCE_CHECKIN_CUTOFF', '08:00'));
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $raw)) {
            return '08:00:00';
        }
        return strlen($raw) === 5 ? $raw . ':00' : $raw;
    }
}

if (!function_exists('attendance_deadline_for_date')) {
    function attendance_deadline_for_date(string $date): Carbon
    {
        return Carbon::parse($date . ' ' . attendance_checkin_cutoff_time(), 'Asia/Jakarta');
    }
}

if (!function_exists('attendance_late_minutes')) {
    function attendance_late_minutes(Carbon $checkInAt, string $attendanceDate): int
    {
        $deadline = attendance_deadline_for_date($attendanceDate);
        return $checkInAt->greaterThan($deadline)
            ? max(0, $deadline->diffInMinutes($checkInAt))
            : 0;
    }
}

if (!function_exists('haversine_distance_meters')) {
    function haversine_distance_meters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $earthRadius * $angle;
    }
}

if (!function_exists('attendance_calendar_storage_ready')) {
    function attendance_calendar_storage_ready(): bool
    {
        return Schema::hasTable('attendance_calendar_exceptions');
    }
}

if (!function_exists('attendance_alert_storage_ready')) {
    function attendance_alert_storage_ready(): bool
    {
        return Schema::hasTable('attendance_alert_notifications');
    }
}

if (!function_exists('attendance_is_non_working_day')) {
    function attendance_is_non_working_day(string $date, ?string $majorName = null, ?string $className = null): bool
    {
        if (!attendance_calendar_storage_ready()) {
            return false;
        }

        $major = strtoupper(trim((string) $majorName));
        $class = trim((string) $className);

        return DB::table('attendance_calendar_exceptions')
            ->whereDate('exception_date', $date)
            ->where(function ($query) use ($major, $class) {
                $query->where(function ($global) {
                    $global->whereNull('major_name')->whereNull('class_name');
                });
                if ($major !== '') {
                    $query->orWhere(function ($majorOnly) use ($major) {
                        $majorOnly->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$major])
                            ->whereNull('class_name');
                    });
                }
                if ($major !== '' && $class !== '') {
                    $query->orWhere(function ($majorAndClass) use ($major, $class) {
                        $majorAndClass->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$major])
                            ->whereRaw('TRIM(COALESCE(class_name, "")) = ?', [$class]);
                    });
                }
            })
            ->exists();
    }
}

if (!function_exists('attendance_fetch_recent_alerts')) {
    function attendance_fetch_recent_alerts(mixed $user, int $limit = 12, bool $includeRead = true)
    {
        if (!$user || !attendance_alert_storage_ready()) {
            return collect();
        }

        $query = DB::table('attendance_alert_notifications')
            ->where(function ($query) use ($user) {
                $query->where('recipient_role', (string) ($user->role ?? ''))
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('recipient_user_id')
                            ->orWhere('recipient_user_id', $user->id);
                    });
            })
            ->orderByDesc('alert_date')
            ->orderByDesc('id');

        if (!$includeRead) {
            $query->where('is_read', false);
        }

        return $query->limit($limit)->get();
    }
}

if (!function_exists('attendance_unread_alert_count')) {
    function attendance_unread_alert_count(mixed $user): int
    {
        if (!$user || !attendance_alert_storage_ready()) {
            return 0;
        }

        return (int) DB::table('attendance_alert_notifications')
            ->where(function ($query) use ($user) {
                $query->where('recipient_role', (string) ($user->role ?? ''))
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('recipient_user_id')
                            ->orWhere('recipient_user_id', $user->id);
                    });
            })
            ->where('is_read', false)
            ->count();
    }
}

if (!function_exists('attendance_push_notification')) {
    function attendance_push_notification(
        string $alertType,
        string $message,
        array $targets,
        ?string $majorName = null,
        ?string $className = null,
        ?string $alertDate = null
    ): void {
        if (!attendance_alert_storage_ready()) {
            return;
        }

        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $alertDate)
            ? (string) $alertDate
            : now('Asia/Jakarta')->toDateString();
        $major = strtoupper(trim((string) $majorName)) ?: null;
        $class = trim((string) $className) ?: null;

        foreach ($targets as $target) {
            $role = trim((string) data_get($target, 'role'));
            if ($role === '') {
                continue;
            }

            $recipientUserId = data_get($target, 'user_id');
            $recipientUserId = is_null($recipientUserId) ? null : (int) $recipientUserId;

            DB::table('attendance_alert_notifications')->updateOrInsert(
                [
                    'alert_date' => $date,
                    'recipient_role' => $role,
                    'recipient_user_id' => $recipientUserId,
                    'alert_type' => $alertType,
                    'major_name' => $major,
                    'class_name' => $class,
                ],
                [
                    'message' => trim($message),
                    'is_read' => false,
                    'read_at' => null,
                    'updated_at' => now('Asia/Jakarta'),
                    'created_at' => now('Asia/Jakarta'),
                ]
            );
        }
    }
}

if (!function_exists('attendance_notification_targets_for_student')) {
    function attendance_notification_targets_for_student(int $studentId): array
    {
        $majorName = null;
        $className = null;
        if (
            Schema::hasTable('student_profiles')
            && Schema::hasColumn('student_profiles', 'major_name')
            && Schema::hasColumn('student_profiles', 'class_name')
        ) {
            $profile = DB::table('student_profiles')
                ->where('student_id', $studentId)
                ->first(['major_name', 'class_name']);
            $majorName = strtoupper(trim((string) data_get($profile, 'major_name'))) ?: null;
            $className = trim((string) data_get($profile, 'class_name')) ?: null;
        }

        $targets = collect([
            ['role' => User::ROLE_PRINCIPAL, 'user_id' => null],
        ]);

        $kajurIds = collect();
        if (Schema::hasTable('users')) {
            $kajurQuery = DB::table('users')
                ->where('role', User::ROLE_KAJUR)
                ->whereNotNull('id');

            if ($majorName && Schema::hasColumn('users', 'kajur_major_name')) {
                $kajurQuery->whereRaw('UPPER(TRIM(COALESCE(kajur_major_name, ""))) = ?', [$majorName]);
            }

            $kajurIds = $kajurQuery
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
        }

        if ($kajurIds->isEmpty()) {
            $targets->push(['role' => User::ROLE_KAJUR, 'user_id' => null]);
        } else {
            foreach ($kajurIds as $kajurId) {
                $targets->push(['role' => User::ROLE_KAJUR, 'user_id' => $kajurId]);
            }
        }

        return [
            'major_name' => $majorName,
            'class_name' => $className,
            'targets' => $targets->all(),
        ];
    }
}

if (!function_exists('user_activity_storage_ready')) {
    function user_activity_storage_ready(): bool
    {
        return Schema::hasTable('user_activity_logs');
    }
}

if (!function_exists('deleted_user_archive_storage_ready')) {
    function deleted_user_archive_storage_ready(): bool
    {
        return Schema::hasTable('deleted_user_archives');
    }
}

if (!function_exists('implementation_timeline_storage_ready')) {
    function implementation_timeline_storage_ready(): bool
    {
        return Schema::hasTable('implementation_timeline_statuses');
    }
}

if (!function_exists('build_implementation_timeline_payload')) {
    function build_implementation_timeline_payload(?string $today = null, ?string $major = null): array
    {
        $timelineStart = null;
        $timelineEnd = null;
        $timelineWeeks = collect();
        $timelineStatus = 'No timeline available';

        if (!Schema::hasTable('student_profiles')) {
            return [
                'timelineStart' => $timelineStart,
                'timelineEnd' => $timelineEnd,
                'timelineWeeks' => $timelineWeeks,
                'timelineStatus' => $timelineStatus,
            ];
        }

        $timelineRangeQuery = DB::table('student_profiles')
            ->whereNotNull('pkl_start_date')
            ->whereNotNull('pkl_end_date');

        $majorFilter = strtoupper(trim((string) $major));
        if ($majorFilter !== '' && $majorFilter !== 'ALL') {
            $timelineRangeQuery->whereRaw('UPPER(COALESCE(major_name, "")) = ?', [$majorFilter]);
        }

        $timelineRange = $timelineRangeQuery
            ->selectRaw('MIN(pkl_start_date) as min_start, MAX(pkl_end_date) as max_end')
            ->first();

        $minStart = data_get($timelineRange, 'min_start');
        $maxEnd = data_get($timelineRange, 'max_end');
        if (empty($minStart) || empty($maxEnd)) {
            return [
                'timelineStart' => $timelineStart,
                'timelineEnd' => $timelineEnd,
                'timelineWeeks' => $timelineWeeks,
                'timelineStatus' => $timelineStatus,
            ];
        }

        $timelineStart = Carbon::parse($minStart, 'Asia/Jakarta')->toDateString();
        $timelineEnd = Carbon::parse($maxEnd, 'Asia/Jakarta')->toDateString();
        $cursor = Carbon::parse($timelineStart, 'Asia/Jakarta')->startOfDay();
        $end = Carbon::parse($timelineEnd, 'Asia/Jakarta')->startOfDay();
        $todayDate = $today ?: now('Asia/Jakarta')->toDateString();

        $overrideByStart = collect();
        if (implementation_timeline_storage_ready()) {
            $overrideByStart = DB::table('implementation_timeline_statuses')
                ->whereDate('week_start', '>=', $timelineStart)
                ->whereDate('week_start', '<=', $timelineEnd)
                ->get(['week_start', 'status_label'])
                ->keyBy(fn ($row) => Carbon::parse($row->week_start, 'Asia/Jakarta')->toDateString());
        }

        $weekNumber = 1;
        while ($cursor->lessThanOrEqualTo($end)) {
            $weekStart = $cursor->copy();
            $weekEnd = $cursor->copy()->addDays(6);
            if ($weekEnd->greaterThan($end)) {
                $weekEnd = $end->copy();
            }

            $statusType = 'upcoming';
            if ($todayDate >= $weekStart->toDateString() && $todayDate <= $weekEnd->toDateString()) {
                $statusType = 'current';
            } elseif ($todayDate > $weekEnd->toDateString()) {
                $statusType = 'done';
            }

            $weekStartKey = $weekStart->toDateString();
            $overrideLabel = trim((string) data_get($overrideByStart->get($weekStartKey), 'status_label', ''));
            $statusLabel = $overrideLabel !== '' ? $overrideLabel : ucfirst($statusType);

            $timelineWeeks->push([
                'week' => $weekNumber,
                'start' => $weekStartKey,
                'end' => $weekEnd->toDateString(),
                'status_type' => $statusType,
                'status_label' => $statusLabel,
            ]);

            $weekNumber++;
            $cursor->addDays(7);
        }

        $currentWeek = $timelineWeeks->first(function ($week) use ($todayDate) {
            return $todayDate >= $week['start'] && $todayDate <= $week['end'];
        });

        if ($currentWeek) {
            $timelineStatus = 'Week ' . $currentWeek['week'] . ' - ' . $currentWeek['status_label'];
        } elseif ($timelineWeeks->isNotEmpty() && $todayDate < $timelineWeeks->first()['start']) {
            $firstWeek = $timelineWeeks->first();
            $timelineStatus = 'Before Week 1 - ' . $firstWeek['status_label'];
        } elseif ($timelineWeeks->isNotEmpty()) {
            $lastWeek = $timelineWeeks->last();
            $timelineStatus = 'Week ' . $lastWeek['week'] . ' - ' . $lastWeek['status_label'];
        }

        return [
            'timelineStart' => $timelineStart,
            'timelineEnd' => $timelineEnd,
            'timelineWeeks' => $timelineWeeks,
            'timelineStatus' => $timelineStatus,
        ];
    }
}

if (!function_exists('permission_modules_for_role')) {
    function permission_modules_for_role(string $role): array
    {
        $all = available_permission_modules();

        if ($role === User::ROLE_STUDENT) {
            return array_intersect_key($all, array_flip([
                'student_dashboard',
                'checkin',
                'task_log',
                'weekly_journal',
                'completion',
                'student_data',
            ]));
        }

        if ($role === User::ROLE_MENTOR) {
            return array_intersect_key($all, array_flip([
                'mentor_weekly_journal',
                'mentor_review_center',
                'mentor_company_settings',
            ]));
        }

        if ($role === User::ROLE_KAJUR) {
            return array_intersect_key($all, array_flip([
                'kajur_dashboard',
                'kajur_weekly_journals',
                'kajur_daily_checkins',
                'kajur_absence_report',
            ]));
        }

        if ($role === User::ROLE_TEACHER) {
            return array_intersect_key($all, array_flip([
                'teacher_weekly_journal',
                'teacher_watchlist',
                'teacher_journal_audit',
                'teacher_site_visits',
                'teacher_red_flags',
                'teacher_contact_directory',
            ]));
        }

        if ($role === User::ROLE_PRINCIPAL) {
            return array_intersect_key($all, array_flip([
                'principal_dashboard',
                'principal_master_report',
                'principal_attendance_alerts',
                'principal_partner_companies',
                'principal_journal_oversight',
                'principal_school_performance',
                'principal_timeline',
            ]));
        }

        if ($role === User::ROLE_SUPER_ADMIN) {
            return array_intersect_key($all, array_flip([
                'super_admin_dashboard',
                'super_admin_branding',
                'super_admin_checkins',
                'super_admin_weekly_journals',
                'super_admin_completion',
                'super_admin_activities',
                'super_admin_mass_edit',
                'super_admin_companies',
                'super_admin_users',
                'super_admin_permissions',
                'super_admin_database',
            ]));
        }

        if ($role === User::ROLE_KESISWAAN) {
            return array_intersect_key($all, array_flip([
                'super_admin_dashboard',
                'super_admin_checkins',
                'super_admin_weekly_journals',
                'super_admin_completion',
                'super_admin_activities',
                'super_admin_mass_edit',
                'super_admin_companies',
                'super_admin_users',
            ]));
        }

        return $all;
    }
}

if (!function_exists('database_backup_directory')) {
    function database_backup_directory(): string
    {
        return storage_path('app/private/database-backups');
    }
}

if (!function_exists('database_backup_log_path')) {
    function database_backup_log_path(): string
    {
        return database_backup_directory() . DIRECTORY_SEPARATOR . 'backup-log.json';
    }
}

if (!function_exists('database_backup_ensure_directory')) {
    function database_backup_ensure_directory(): void
    {
        $directory = database_backup_directory();
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
    }
}

if (!function_exists('database_backup_table_names')) {
    function database_backup_table_names(): array
    {
        try {
            $tables = Schema::getTableListing();
        } catch (\Throwable $e) {
            return [];
        }

        $currentDatabase = trim((string) DB::getDatabaseName());

        return collect($tables)
            ->map(function ($table) use ($currentDatabase) {
                $raw = trim((string) $table);
                if ($raw === '') {
                    return null;
                }

                if (str_contains($raw, '.')) {
                    [$schema, $name] = array_pad(explode('.', $raw, 2), 2, '');
                    $schema = trim((string) $schema);
                    $name = trim((string) $name);

                    if ($schema === '' || $name === '') {
                        return null;
                    }

                    if ($currentDatabase !== '' && strcasecmp($schema, $currentDatabase) !== 0) {
                        return null;
                    }

                    return $name;
                }

                return $raw;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}

if (!function_exists('database_backup_normalize_table_name')) {
    function database_backup_normalize_table_name(string $table): ?string
    {
        $raw = trim((string) $table);
        if ($raw === '') {
            return null;
        }

        if (!str_contains($raw, '.')) {
            return $raw;
        }

        [$schema, $name] = array_pad(explode('.', $raw, 2), 2, '');
        $schema = trim((string) $schema);
        $name = trim((string) $name);
        $currentDatabase = trim((string) DB::getDatabaseName());

        if ($schema === '' || $name === '') {
            return null;
        }

        if ($currentDatabase !== '' && strcasecmp($schema, $currentDatabase) !== 0) {
            return null;
        }

        return $name;
    }
}

if (!function_exists('database_backup_resolve_tables')) {
    function database_backup_resolve_tables(array $selectedTables = []): array
    {
        $available = database_backup_table_names();
        if (empty($selectedTables)) {
            return $available;
        }

        $selected = collect($selectedTables)
            ->map(fn ($table) => database_backup_normalize_table_name((string) $table))
            ->filter()
            ->values()
            ->all();

        return collect($available)
            ->filter(fn ($table) => in_array($table, $selected, true))
            ->values()
            ->all();
    }
}

if (!function_exists('database_backup_payload_for_tables')) {
    function database_backup_payload_for_tables(array $tables, string $action, $user = null): array
    {
        $payload = [
            'meta' => [
                'action' => $action,
                'generated_at' => now('Asia/Jakarta')->toIso8601String(),
                'generated_by_user_id' => data_get($user, 'id'),
                'generated_by_name' => data_get($user, 'name'),
                'connection' => config('database.default'),
                'app_env' => config('app.env'),
            ],
            'tables' => [],
            'table_counts' => [],
        ];

        foreach ($tables as $table) {
            $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->values()->all();
            $payload['tables'][$table] = $rows;
            $payload['table_counts'][$table] = count($rows);
        }

        return $payload;
    }
}

if (!function_exists('database_backup_logs')) {
    function database_backup_logs(): array
    {
        $logPath = database_backup_log_path();
        if (!is_file($logPath)) {
            return [];
        }

        $decoded = json_decode((string) @file_get_contents($logPath), true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)->values()->all();
    }
}

if (!function_exists('database_backup_append_log')) {
    function database_backup_append_log(array $entry): void
    {
        database_backup_ensure_directory();
        $existing = collect(database_backup_logs());
        $record = array_merge([
            'timestamp' => now('Asia/Jakarta')->toIso8601String(),
            'status' => 'info',
            'action' => 'unknown',
            'message' => '',
            'file_name' => null,
            'counts' => [],
            'performed_by' => null,
        ], $entry);

        $payload = $existing
            ->prepend($record)
            ->take(300)
            ->values()
            ->all();

        @file_put_contents(
            database_backup_log_path(),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}

if (!function_exists('database_sql_escape_identifier')) {
    function database_sql_escape_identifier(string $identifier): string
    {
        $raw = trim($identifier);
        return '`' . str_replace('`', '``', $raw) . '`';
    }
}

if (!function_exists('database_sql_quote_value')) {
    function database_sql_quote_value(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $pdo = DB::connection()->getPdo();
        return $pdo->quote((string) $value);
    }
}

if (!function_exists('database_sql_dump_for_tables')) {
    function database_sql_dump_for_tables(array $tables): array
    {
        $lines = [];
        $counts = [];
        $now = now('Asia/Jakarta')->toDateTimeString();

        $lines[] = '-- major database export';
        $lines[] = '-- generated at: ' . $now . ' WIB';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = '';

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $tableName = database_sql_escape_identifier($table);
            $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->values()->all();
            $counts[$table] = count($rows);

            $lines[] = '--';
            $lines[] = '-- table: ' . $table;
            $lines[] = '-- rows: ' . $counts[$table];
            $lines[] = 'TRUNCATE TABLE ' . $tableName . ';';

            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $columnList = implode(', ', array_map(fn ($column) => database_sql_escape_identifier((string) $column), $columns));

                foreach (array_chunk($rows, 200) as $chunk) {
                    $valueRows = [];
                    foreach ($chunk as $row) {
                        $values = [];
                        foreach ($columns as $column) {
                            $values[] = database_sql_quote_value($row[$column] ?? null);
                        }
                        $valueRows[] = '(' . implode(', ', $values) . ')';
                    }

                    $lines[] = 'INSERT INTO ' . $tableName . ' (' . $columnList . ') VALUES';
                    $lines[] = implode(",\n", $valueRows) . ';';
                }
            }

            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = '';

        return [
            'sql' => implode("\n", $lines),
            'counts' => $counts,
        ];
    }
}

if (!function_exists('database_sql_split_statements')) {
    function database_sql_split_statements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $prev = $i > 0 ? $sql[$i - 1] : '';

            if ($ch === "'" && !$inDouble && !$inBacktick && $prev !== '\\') {
                $inSingle = !$inSingle;
            } elseif ($ch === '"' && !$inSingle && !$inBacktick && $prev !== '\\') {
                $inDouble = !$inDouble;
            } elseif ($ch === '`' && !$inSingle && !$inDouble) {
                $inBacktick = !$inBacktick;
            }

            if ($ch === ';' && !$inSingle && !$inDouble && !$inBacktick) {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $ch;
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }
}

if (!function_exists('log_user_activity')) {
    function log_user_activity(mixed $actor, string $action, ?string $subjectType = null, $subjectId = null, ?string $description = null, array $metadata = [], bool $canRevert = false): void
    {
        if (!user_activity_storage_ready()) {
            return;
        }

        try {
            DB::table('user_activity_logs')->insert([
                'actor_user_id' => data_get($actor, 'id'),
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'description' => $description,
                'metadata' => empty($metadata) ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE),
                'can_revert' => $canRevert,
                'created_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]);
        } catch (\Throwable $e) {
        }
    }
}

if (!function_exists('build_principal_dashboard_payload')) {
    function build_principal_dashboard_payload(string $weekStart, string $weekEnd, string $today): array
    {
        $seasonStart = Carbon::parse($today, 'Asia/Jakarta')->subDays(29)->toDateString();

        $rows = DB::table('weekly_journals as wj')
            ->join('users as s', 's.id', '=', 'wj.student_id')
            ->leftJoin('users as m', 'm.id', '=', 'wj.mentor_id')
            ->leftJoin('users as k', 'k.id', '=', 'wj.kajur_id')
            ->leftJoin('users as b', 'b.id', '=', 'wj.bindo_id')
            ->whereDate('wj.week_start_date', $weekStart)
            ->whereDate('wj.week_end_date', $weekEnd)
            ->select(
                'wj.id',
                'wj.learning_notes',
                'wj.student_mentor_notes',
                'wj.mentor_is_correct',
                'wj.missing_info_notes',
                'wj.kajur_notes',
                'wj.bindo_notes',
                'wj.status',
                's.name as student_name',
                's.nis as student_nis',
                'm.name as mentor_name',
                'k.name as kajur_name',
                'b.name as bindo_name'
            )
            ->orderBy('s.name')
            ->get();

        $totalStudentsInSchool = DB::table('users')
            ->where('role', User::ROLE_STUDENT)
            ->count();

        $totalStudentsPlaced = 0;
        $topIndustryPartners = collect();
        $placementRows = collect();
        if (Schema::hasTable('student_profiles')) {
            $totalStudentsPlaced = DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->where('u.role', User::ROLE_STUDENT)
                ->whereRaw('TRIM(COALESCE(sp.pkl_place_name, "")) <> ""')
                ->distinct('sp.student_id')
                ->count('sp.student_id');

            $topIndustryPartners = DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->where('u.role', User::ROLE_STUDENT)
                ->whereRaw('TRIM(COALESCE(sp.pkl_place_name, "")) <> ""')
                ->groupBy('sp.pkl_place_name', 'sp.pkl_place_address')
                ->selectRaw("
                    COALESCE(NULLIF(TRIM(sp.pkl_place_name), ''), 'Unknown Company') as company_name,
                    COALESCE(NULLIF(TRIM(sp.pkl_place_address), ''), '-') as company_address,
                    COUNT(DISTINCT sp.student_id) as total_students
                ")
                ->orderByDesc('total_students')
                ->orderBy('company_name')
                ->limit(5)
                ->get();

            $placementRows = DB::table('users as u')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 'u.id')
                ->where('u.role', User::ROLE_STUDENT)
                ->selectRaw("
                    u.name as student_name,
                    u.nis as student_nis,
                    COALESCE(NULLIF(TRIM(sp.major_name), ''), '-') as major_name,
                    COALESCE(NULLIF(TRIM(sp.class_name), ''), '-') as class_name,
                    COALESCE(NULLIF(TRIM(sp.pkl_place_name), ''), '-') as company_name,
                    COALESCE(NULLIF(TRIM(sp.pkl_place_address), ''), '-') as company_address,
                    sp.pkl_start_date,
                    sp.pkl_end_date
                ")
                ->orderBy('u.name')
                ->get();
        }

        $departmentLabels = [
            'RPL' => 'RPL',
            'BDP' => 'BDP',
            'AKL' => 'AKL',
        ];
        $departmentStudentCounts = [
            'RPL' => 0,
            'BDP' => 0,
            'AKL' => 0,
        ];
        $departmentCheckedDays = [
            'RPL' => 0,
            'BDP' => 0,
            'AKL' => 0,
        ];

        $majorToDepartment = static function (?string $major): ?string {
            $normalized = strtoupper(trim((string) $major));
            if ($normalized === '') {
                return null;
            }
            if (Str::contains($normalized, 'RPL')) {
                return 'RPL';
            }
            if (Str::contains($normalized, 'BDP')) {
                return 'BDP';
            }
            if (Str::contains($normalized, 'AKL')) {
                return 'AKL';
            }
            return null;
        };

        if (Schema::hasTable('student_profiles')) {
            $studentsByMajor = DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->where('u.role', User::ROLE_STUDENT)
                ->groupByRaw('UPPER(TRIM(COALESCE(sp.major_name, "")))')
                ->selectRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) as major_key, COUNT(DISTINCT sp.student_id) as total_students')
                ->get();

            foreach ($studentsByMajor as $row) {
                $departmentKey = $majorToDepartment($row->major_key);
                if ($departmentKey === null) {
                    continue;
                }
                $departmentStudentCounts[$departmentKey] += (int) ($row->total_students ?? 0);
            }
        }

        if (Schema::hasTable('attendances') && Schema::hasTable('student_profiles')) {
            $attendanceByMajor = DB::table('attendances as a')
                ->join('users as u', 'u.id', '=', 'a.student_id')
                ->join('student_profiles as sp', 'sp.student_id', '=', 'a.student_id')
                ->where('u.role', User::ROLE_STUDENT)
                ->whereNotNull('a.check_in_at')
                ->whereDate('a.attendance_date', '>=', $seasonStart)
                ->whereDate('a.attendance_date', '<=', $today)
                ->groupByRaw('UPPER(TRIM(COALESCE(sp.major_name, "")))')
                ->selectRaw("UPPER(TRIM(COALESCE(sp.major_name, ''))) as major_key, COUNT(DISTINCT CONCAT(a.student_id, '|', a.attendance_date)) as checked_days")
                ->get();

            foreach ($attendanceByMajor as $row) {
                $departmentKey = $majorToDepartment($row->major_key);
                if ($departmentKey === null) {
                    continue;
                }
                $departmentCheckedDays[$departmentKey] += (int) ($row->checked_days ?? 0);
            }
        }

        $departmentAttendance = collect($departmentLabels)
            ->map(function (string $label, string $key) use ($departmentStudentCounts, $departmentCheckedDays) {
                $students = (int) ($departmentStudentCounts[$key] ?? 0);
                $checkedDays = (int) ($departmentCheckedDays[$key] ?? 0);
                $expectedDays = $students * 30;
                $rate = $expectedDays > 0 ? round(($checkedDays / $expectedDays) * 100, 1) : 0.0;

                return [
                    'key' => $key,
                    'label' => $label,
                    'students' => $students,
                    'checked_days' => $checkedDays,
                    'rate' => $rate,
                ];
            })
            ->values();

        $mouTracker = collect();
        if (Schema::hasTable('partner_companies')) {
            $mouColumn = collect(['mou_expiry_date', 'mou_expires_at', 'mou_end_date', 'contract_end_date'])
                ->first(fn ($column) => Schema::hasColumn('partner_companies', $column));

            $inferredByCompany = collect();
            if (Schema::hasTable('student_profiles')) {
                $inferredByCompany = DB::table('student_profiles')
                    ->whereRaw('TRIM(COALESCE(pkl_place_name, "")) <> ""')
                    ->groupBy('pkl_place_name', 'pkl_place_address')
                    ->selectRaw("
                        COALESCE(NULLIF(TRIM(pkl_place_name), ''), 'Unknown Company') as company_name,
                        COALESCE(NULLIF(TRIM(pkl_place_address), ''), '-') as company_address,
                        MAX(pkl_end_date) as inferred_expiry
                    ")
                    ->get()
                    ->keyBy(fn ($row) => trim((string) $row->company_name) . '||' . trim((string) ($row->company_address ?? '-')));
            }

            $mouTracker = DB::table('partner_companies')
                ->where('is_active', true)
                ->select('name', 'address', 'contact_person', 'contact_phone')
                ->when($mouColumn, fn ($query) => $query->addSelect(DB::raw($mouColumn . ' as mou_expiry_date')))
                ->orderBy('name')
                ->get()
                ->map(function ($row) use ($inferredByCompany, $mouColumn) {
                    $companyName = trim((string) ($row->name ?? 'Unknown Company')) ?: 'Unknown Company';
                    $companyAddress = trim((string) ($row->address ?? '-')) ?: '-';
                    $lookupKey = $companyName . '||' . $companyAddress;
                    $inferredExpiry = data_get($inferredByCompany->get($lookupKey), 'inferred_expiry');
                    $explicitExpiry = $mouColumn ? data_get($row, 'mou_expiry_date') : null;
                    $finalExpiry = $explicitExpiry ?: $inferredExpiry;

                    return [
                        'company_name' => $companyName,
                        'company_address' => $companyAddress,
                        'contact_person' => trim((string) ($row->contact_person ?? '')) ?: '-',
                        'contact_phone' => trim((string) ($row->contact_phone ?? '')) ?: '-',
                        'expiry_date' => $finalExpiry,
                        'expiry_source' => $explicitExpiry ? 'MOU' : ($inferredExpiry ? 'Inferred from PKL end date' : 'Not set'),
                    ];
                })
                ->sortBy([
                    fn ($row) => empty($row['expiry_date']) ? 1 : 0,
                    fn ($row) => (string) ($row['expiry_date'] ?? '9999-12-31'),
                    fn ($row) => strtolower((string) $row['company_name']),
                ])
                ->values();
        }

        return [
            'rows' => $rows,
            'totalStudentsInSchool' => $totalStudentsInSchool,
            'totalStudentsPlaced' => $totalStudentsPlaced,
            'topIndustryPartners' => $topIndustryPartners,
            'departmentAttendance' => $departmentAttendance,
            'mouTracker' => $mouTracker,
            'placementRows' => $placementRows,
            'seasonStart' => $seasonStart,
            'today' => $today,
        ];
    }
}
