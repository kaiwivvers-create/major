<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

if (!function_exists('available_permission_modules')) {
    function available_permission_modules(): array
    {
        return [
            'student_dashboard' => 'Student Dashboard',
            'checkin' => 'Check-in / Check-out',
            'task_log' => 'Task Log',
            'mentor_dashboard' => 'Mentor Dashboard',
            'kajur_dashboard' => 'Kajur Dashboard',
            'weekly_journal' => 'Weekly Journal',
            'mentor_review_center' => 'Mentor Review Center (Daily Scoring)',
            'completion' => 'Completion Page',
            'student_data' => 'Student Data',
            'super_admin_dashboard' => 'Super Admin Dashboard',
            'users_management' => 'Users Management',
        ];
    }
}

if (!function_exists('user_permissions_payload')) {
    function user_permissions_payload($user): array
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

        $cache[$role] = [];
        return $cache[$role];
    }
}

if (!function_exists('user_can_access')) {
    function user_can_access($user, string $module, string $action = 'view'): bool
    {
        if (!$user) {
            return false;
        }
        if (($user->role ?? null) === User::ROLE_SUPER_ADMIN) {
            return true;
        }

        $fallbackMap = [
            'mentor_review_center' => 'weekly_journal',
            'mentor_dashboard' => 'weekly_journal',
            'kajur_dashboard' => 'weekly_journal',
        ];
        $fallbackModule = $fallbackMap[$module] ?? null;

        $userPermissions = user_permissions_payload($user);
        if (!empty($userPermissions)) {
            $userRole = (string) ($user->role ?? '');
            if ($userRole !== '' && $userRole !== User::ROLE_SUPER_ADMIN) {
                $roleModules = array_keys(permission_modules_for_role($userRole));
                $permissionActions = ['view', 'create', 'update', 'delete'];
                $hasRoleSpecificKeys = false;
                $hasAnyRolePermission = false;

                foreach ($roleModules as $roleModule) {
                    if (!array_key_exists($roleModule, $userPermissions)) {
                        continue;
                    }
                    $hasRoleSpecificKeys = true;
                    $modulePermissions = data_get($userPermissions, $roleModule);
                    if (!is_array($modulePermissions)) {
                        continue;
                    }
                    foreach ($permissionActions as $permissionAction) {
                        if ((bool) data_get($modulePermissions, $permissionAction, false)) {
                            $hasAnyRolePermission = true;
                            break 2;
                        }
                    }
                }

                // If user-level payload is stale or all-deny, fall back to role-level permissions.
                if (!$hasRoleSpecificKeys || !$hasAnyRolePermission) {
                    $userPermissions = [];
                }
            }
        }

        if (!empty($userPermissions)) {
            $modulePermissions = data_get($userPermissions, $module, null);
            if (is_array($modulePermissions)) {
                return (bool) data_get($modulePermissions, $action, false);
            }
            if ($fallbackModule !== null) {
                $fallbackPermissions = data_get($userPermissions, $fallbackModule, null);
                if (is_array($fallbackPermissions)) {
                    return (bool) data_get($fallbackPermissions, $action, false);
                }
            }
            return false;
        }

        $permissions = role_permissions_payload((string) ($user->role ?? ''));
        if (empty($permissions)) {
            return true;
        }
        $modulePermissions = data_get($permissions, $module, null);
        if (is_array($modulePermissions)) {
            return (bool) data_get($modulePermissions, $action, false);
        }
        if ($fallbackModule !== null) {
            $fallbackPermissions = data_get($permissions, $fallbackModule, null);
            if (is_array($fallbackPermissions)) {
                return (bool) data_get($fallbackPermissions, $action, false);
            }
        }
        return false;
    }
}

if (!function_exists('require_user_permission')) {
    function require_user_permission($user, string $module, string $action = 'view'): void
    {
        abort_unless(user_can_access($user, $module, $action), 403, 'You do not have permission for this action.');
    }
}

if (!function_exists('mentor_supervised_student_ids')) {
    function mentor_supervised_student_ids($user)
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

if (!function_exists('teacher_supervised_student_ids')) {
    function teacher_supervised_student_ids($user)
    {
        if (!$user || ($user->role ?? null) !== User::ROLE_TEACHER) {
            return collect();
        }

        $studentIds = collect();
        if (Schema::hasTable('student_profiles')) {
            $teacherName = strtoupper(trim((string) $user->name));
            $studentIds = $studentIds->merge(
                DB::table('student_profiles as sp')
                    ->join('users as s', 's.id', '=', 'sp.student_id')
                    ->where('s.role', User::ROLE_STUDENT)
                    ->whereRaw('UPPER(TRIM(COALESCE(sp.school_supervisor_teacher_name, ""))) = ?', [$teacherName])
                    ->pluck('sp.student_id')
            );
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
        if ($teacherClassScope !== '' && strtoupper($teacherClassScope) !== 'ALL' && Schema::hasTable('student_profiles')) {
            $classStudentIds = DB::table('student_profiles')
                ->whereRaw('TRIM(COALESCE(class_name, "")) = ?', [$teacherClassScope])
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            $studentIds = $studentIds->intersect($classStudentIds);
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
    function attendance_fetch_recent_alerts($user, int $limit = 12)
    {
        if (!$user || !attendance_alert_storage_ready()) {
            return collect();
        }

        return DB::table('attendance_alert_notifications')
            ->where(function ($query) use ($user) {
                $query->where('recipient_role', (string) ($user->role ?? ''))
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('recipient_user_id')
                            ->orWhere('recipient_user_id', $user->id);
                    });
            })
            ->orderByDesc('alert_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
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
                'mentor_dashboard',
                'weekly_journal',
                'mentor_review_center',
            ]));
        }

        if ($role === User::ROLE_KAJUR) {
            return array_intersect_key($all, array_flip([
                'kajur_dashboard',
                'weekly_journal',
            ]));
        }

        if (in_array($role, [User::ROLE_TEACHER, User::ROLE_PRINCIPAL], true)) {
            return array_intersect_key($all, array_flip([
                'weekly_journal',
            ]));
        }

        if ($role === User::ROLE_SUPER_ADMIN) {
            return array_intersect_key($all, array_flip([
                'super_admin_dashboard',
                'users_management',
            ]));
        }

        return $all;
    }
}

if (!function_exists('log_user_activity')) {
    function log_user_activity($actor, string $action, ?string $subjectType = null, $subjectId = null, ?string $description = null, array $metadata = [], bool $canRevert = false): void
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
            // Ignore activity log failures so core business flow still works.
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
 
Route::get('/', function () {
    $schoolPhoto = null;
    $landingCompanies = collect();

    if (Schema::hasTable('student_profiles')) {
        $landingCompanies = DB::table('student_profiles as sp')
            ->join('users as u', 'u.id', '=', 'sp.student_id')
            ->where('u.role', User::ROLE_STUDENT)
            ->whereNotNull('sp.pkl_place_name')
            ->where('sp.pkl_place_name', '<>', '')
            ->groupBy('sp.pkl_place_name', 'sp.pkl_place_address')
            ->selectRaw("
                sp.pkl_place_name as company_name,
                sp.pkl_place_address as company_address,
                COUNT(DISTINCT sp.student_id) as total_students
            ")
            ->orderBy('sp.pkl_place_name')
            ->limit(12)
            ->get();

        $landingCompanies = $landingCompanies->map(function ($row) {
            $row->company_name = trim((string) ($row->company_name ?? ''));
            $row->company_address = trim((string) ($row->company_address ?? '')) ?: '-';
            return $row;
        });

        if (Schema::hasTable('partner_companies') && $landingCompanies->isNotEmpty()) {
            $partnerRows = DB::table('partner_companies')
                ->select('name', 'address', 'logo_url', 'contact_person', 'contact_phone', 'contact_email', 'website_url')
                ->get();

            $partnerByKey = $partnerRows->keyBy(function ($row) {
                $name = trim((string) ($row->name ?? ''));
                $address = trim((string) ($row->address ?? '-'));
                if ($address === '') {
                    $address = '-';
                }
                return mb_strtolower($name . '||' . $address);
            });

            $landingCompanies = $landingCompanies->map(function ($row) use ($partnerByKey) {
                $name = trim((string) ($row->company_name ?? ''));
                $address = trim((string) ($row->company_address ?? '-'));
                if ($address === '') {
                    $address = '-';
                }
                $key = mb_strtolower($name . '||' . $address);
                $partner = $partnerByKey->get($key);

                $row->logo_url = $partner->logo_url ?? null;
                $row->contact_person = $partner->contact_person ?? null;
                $row->contact_phone = $partner->contact_phone ?? null;
                $row->contact_email = $partner->contact_email ?? null;
                $row->website_url = $partner->website_url ?? null;
                return $row;
            });
        } else {
            $landingCompanies = $landingCompanies->map(function ($row) {
                $row->logo_url = null;
                $row->contact_person = null;
                $row->contact_phone = null;
                $row->contact_email = null;
                $row->website_url = null;
                return $row;
            });
        }
    }

    if ($landingCompanies->isEmpty()) {
        $landingCompanies = collect([
            (object) ['company_name' => 'Nusa Byte Teknologi', 'company_address' => 'Jl. Melati Raya No. 18, Tangerang Selatan, Banten 15413', 'logo_url' => null, 'contact_person' => null, 'contact_phone' => null, 'contact_email' => null, 'website_url' => null, 'total_students' => 0],
            (object) ['company_name' => 'Arunika Global Solusi', 'company_address' => 'Jl. Cendana Timur No. 27, Bekasi, Jawa Barat 17113', 'logo_url' => null, 'contact_person' => null, 'contact_phone' => null, 'contact_email' => null, 'website_url' => null, 'total_students' => 0],
            (object) ['company_name' => 'Sagara Prime Industries', 'company_address' => 'Jl. Kenanga Barat No. 5, Jakarta Selatan, DKI Jakarta 12940', 'logo_url' => null, 'contact_person' => null, 'contact_phone' => null, 'contact_email' => null, 'website_url' => null, 'total_students' => 0],
            (object) ['company_name' => 'Vektor Kreasi Digital', 'company_address' => 'Jl. Anggrek Utama No. 44, Bandung, Jawa Barat 40115', 'logo_url' => null, 'contact_person' => null, 'contact_phone' => null, 'contact_email' => null, 'website_url' => null, 'total_students' => 0],
        ]);
    }

    return view('welcome', [
        'schoolPhoto' => $schoolPhoto,
        'landingCompanies' => $landingCompanies,
    ]);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'nis' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'nis' => 'The provided credentials do not match our records.',
        ])->onlyInput('nis');
    })->name('login.store');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();

        if ($user->role === 'student') {
            return redirect()->route('dashboard.student');
        }
        if ($user->role === 'mentor') {
            if (user_can_access($user, 'mentor_dashboard', 'view')) {
                return redirect()->route('dashboard.mentor.weekly-journal');
            }
            if (user_can_access($user, 'weekly_journal', 'view')) {
                return redirect()->route('dashboard.mentor.weekly-journal');
            }
            if (user_can_access($user, 'mentor_review_center', 'view')) {
                return redirect()->route('dashboard.mentor.review-center');
            }
            return redirect('/')->withErrors(['permissions' => 'Your mentor account does not have dashboard access yet. Please contact Super Admin.']);
        }
        if ($user->role === 'kajur') {
            return redirect()->route('dashboard.kajur.dashboard');
        }
        if ($user->role === 'teacher') {
            return redirect()->route('dashboard.bindo.weekly-journal');
        }
        if ($user->role === 'principal') {
            return redirect()->route('dashboard.principal.weekly-journal');
        }
        if ($user->role === 'super_admin') {
            return redirect()->route('dashboard.super-admin');
        }

        return redirect('/')->with('status', 'Dashboard for your role is not available yet.');
    })->name('dashboard');

    Route::get('/dashboard/student', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'student_dashboard', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $todayAttendance = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();
        $studentProfile = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles')->where('student_id', $user->id)->first(['major_name', 'class_name'])
            : null;
        $studentMajorName = data_get($studentProfile, 'major_name');
        $studentClassName = data_get($studentProfile, 'class_name');
        $todayExcuse = Schema::hasTable('attendance_excuses')
            ? DB::table('attendance_excuses')
                ->where('student_id', $user->id)
                ->whereDate('attendance_date', $today)
                ->first()
            : null;

        $todayLog = DB::table('daily_logs')
            ->where('student_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $weeklyJournal = DB::table('weekly_journals')
            ->where('student_id', $user->id)
            ->whereDate('week_start_date', $weekStart)
            ->whereDate('week_end_date', $weekEnd)
            ->first();

        $profile = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles')
                ->where('student_id', $user->id)
                ->first(['pkl_start_date', 'pkl_end_date', 'major_name', 'class_name'])
            : null;

        $pklStartDate = data_get($profile, 'pkl_start_date');
        $pklEndDate = data_get($profile, 'pkl_end_date');
        $studentMajorName = data_get($profile, 'major_name');
        $studentClassName = data_get($profile, 'class_name');
        $hasPklRange = !empty($pklStartDate) && !empty($pklEndDate);

        if ($hasPklRange) {
            $completedDays = DB::table('attendances')
                ->where('student_id', $user->id)
                ->whereNotNull('check_out_at')
                ->whereBetween('attendance_date', [$pklStartDate, $pklEndDate])
                ->whereRaw('DAYOFWEEK(attendance_date) NOT IN (1, 6, 7)')
                ->count();

            $targetDays = 0;
            $cursor = Carbon::parse($pklStartDate, 'Asia/Jakarta')->startOfDay();
            $endCursor = Carbon::parse($pklEndDate, 'Asia/Jakarta')->startOfDay();
            while ($cursor->lessThanOrEqualTo($endCursor)) {
                if (!in_array($cursor->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY], true)) {
                    $targetDays++;
                }
                $cursor->addDay();
            }
        } else {
            $completedDays = DB::table('attendances')
                ->where('student_id', $user->id)
                ->whereNotNull('check_out_at')
                ->count();
            $targetDays = 90;
        }

        $progressPercent = $targetDays > 0
            ? min(100, (int) round(($completedDays / $targetDays) * 100))
            : 0;

        $attendanceAlerts = collect();
        $checkInDeadline = attendance_deadline_for_date($today);
        $isNonWorkingDay = attendance_is_non_working_day($today, $studentMajorName, $studentClassName);
        if (empty(data_get($todayAttendance, 'check_in_at')) && !$isNonWorkingDay && $wibNow->greaterThan($checkInDeadline)) {
            if (($todayExcuse->status ?? null) === 'approved') {
                $attendanceAlerts->push([
                    'type' => 'info',
                    'message' => 'Today is marked as excused (' . strtoupper((string) ($todayExcuse->absence_type ?? '-')) . ').',
                ]);
            } elseif (($todayExcuse->status ?? null) === 'pending') {
                $attendanceAlerts->push([
                    'type' => 'warn',
                    'message' => 'You missed check-in deadline (' . $checkInDeadline->format('H:i') . ' WIB). Your absence request is still pending.',
                ]);
            } else {
                $attendanceAlerts->push([
                    'type' => 'error',
                    'message' => 'Check-in deadline (' . $checkInDeadline->format('H:i') . ' WIB) has passed and no approved absence request was found.',
                ]);
            }
        } elseif ($isNonWorkingDay) {
            $attendanceAlerts->push([
                'type' => 'info',
                'message' => 'Today is configured as non-working day in attendance calendar.',
            ]);
        }

        if (($todayAttendance->status ?? null) === 'late') {
            $lateMinutes = (int) ($todayAttendance->late_minutes ?? 0);
            $attendanceAlerts->push([
                'type' => 'warn',
                'message' => 'You checked in late by ' . $lateMinutes . ' minute(s).',
            ]);
        }

        return view('dashboard.student', [
            'todayAttendance' => $todayAttendance,
            'todayExcuse' => $todayExcuse,
            'todayLog' => $todayLog,
            'weeklyJournal' => $weeklyJournal,
            'completedDays' => $completedDays,
            'targetDays' => $targetDays,
            'progressPercent' => $progressPercent,
            'attendanceAlerts' => $attendanceAlerts,
            'checkInCutoffTime' => attendance_checkin_cutoff_time(),
        ]);
    })->name('dashboard.student');

    Route::get('/dashboard/student/checkin', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'checkin', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $profile = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles')
                ->where('student_id', $user->id)
                ->first()
            : null;
        $studentMajorName = data_get($profile, 'major_name');
        $studentClassName = data_get($profile, 'class_name');

        $todayAttendance = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $attendanceHistory = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereNotNull('check_in_at')
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_at')
            ->limit(120)
            ->get();

        $todayExcuse = Schema::hasTable('attendance_excuses')
            ? DB::table('attendance_excuses')
                ->where('student_id', $user->id)
                ->whereDate('attendance_date', $today)
                ->first()
            : null;

        $recentExcuses = Schema::hasTable('attendance_excuses')
            ? DB::table('attendance_excuses')
                ->where('student_id', $user->id)
                ->orderByDesc('attendance_date')
                ->limit(14)
                ->get()
            : collect();

        $checkInDeadline = attendance_deadline_for_date($today);
        $attendanceAlerts = collect();
        $isNonWorkingDay = attendance_is_non_working_day($today, $studentMajorName, $studentClassName);
        if (empty(data_get($todayAttendance, 'check_in_at')) && !$isNonWorkingDay && $wibNow->greaterThan($checkInDeadline)) {
            if (($todayExcuse->status ?? null) === 'approved') {
                $attendanceAlerts->push('Today is marked as excused (' . strtoupper((string) ($todayExcuse->absence_type ?? '-')) . ').');
            } elseif (($todayExcuse->status ?? null) === 'pending') {
                $attendanceAlerts->push('You missed check-in deadline (' . $checkInDeadline->format('H:i') . ' WIB). Your absence request is pending.');
            } else {
                $attendanceAlerts->push('You missed check-in deadline (' . $checkInDeadline->format('H:i') . ' WIB). Submit absence request (sick/permit) now.');
            }
        } elseif ($isNonWorkingDay) {
            $attendanceAlerts->push('Today is configured as non-working day in attendance calendar.');
        }

        return view('dashboard.student-checkin', [
            'todayAttendance' => $todayAttendance,
            'attendanceHistory' => $attendanceHistory,
            'todayExcuse' => $todayExcuse,
            'recentExcuses' => $recentExcuses,
            'attendanceAlerts' => $attendanceAlerts,
            'today' => $today,
            'wibNow' => $wibNow,
            'checkInCutoffTime' => attendance_checkin_cutoff_time(),
        ]);
    })->name('dashboard.student.checkin-page');

    Route::get('/dashboard/student/task-log', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'task_log', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();

        $todayAttendance = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $todayLog = DB::table('daily_logs')
            ->where('student_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        return view('dashboard.student-task-log', [
            'todayAttendance' => $todayAttendance,
            'todayLog' => $todayLog,
            'today' => $today,
            'wibNow' => $wibNow,
        ]);
    })->name('dashboard.student.task-log-page');

    Route::get('/dashboard/student/completion', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'completion', 'view');

        $profile = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles')
                ->where('student_id', $user->id)
                ->first(['pkl_start_date', 'pkl_end_date'])
            : null;

        $pklStartDate = data_get($profile, 'pkl_start_date');
        $pklEndDate = data_get($profile, 'pkl_end_date');
        $hasPklRange = !empty($pklStartDate) && !empty($pklEndDate);

        if ($hasPklRange) {
            $completedDays = DB::table('attendances')
                ->where('student_id', $user->id)
                ->whereNotNull('check_out_at')
                ->whereBetween('attendance_date', [$pklStartDate, $pklEndDate])
                ->whereRaw('DAYOFWEEK(attendance_date) NOT IN (1, 6, 7)')
                ->count();

            $targetDays = 0;
            $cursor = Carbon::parse($pklStartDate, 'Asia/Jakarta')->startOfDay();
            $endCursor = Carbon::parse($pklEndDate, 'Asia/Jakarta')->startOfDay();
            while ($cursor->lessThanOrEqualTo($endCursor)) {
                if (!in_array($cursor->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY], true)) {
                    $targetDays++;
                }
                $cursor->addDay();
            }
        } else {
            $completedDays = DB::table('attendances')
                ->where('student_id', $user->id)
                ->whereNotNull('check_out_at')
                ->count();
            $targetDays = 90;
        }

        $progressPercent = $targetDays > 0
            ? min(100, (int) round(($completedDays / $targetDays) * 100))
            : 0;

        $rows = DB::table('attendances as a')
            ->leftJoin('daily_logs as d', function ($join) {
                $join->on('d.student_id', '=', 'a.student_id')
                    ->on('d.work_date', '=', 'a.attendance_date');
            })
            ->where('a.student_id', $user->id)
            ->whereNotNull('a.check_in_at')
            ->select(
                'a.id',
                'a.attendance_date',
                'a.check_in_at',
                'a.check_out_at',
                'a.status',
                'a.ip_address',
                'a.latitude',
                'a.longitude',
                'd.planned_today',
                'd.work_realization',
                'd.assigned_work',
                'd.field_problems',
                'd.notes',
                'd.score_smile',
                'd.score_friendliness',
                'd.score_appearance',
                'd.score_communication',
                'd.score_work_realization'
            )
            ->orderByDesc('a.attendance_date')
            ->orderByDesc('a.check_in_at')
            ->limit(180)
            ->get();

        return view('dashboard.student-completion', [
            'rows' => $rows,
            'completedDays' => $completedDays,
            'targetDays' => $targetDays,
            'progressPercent' => $progressPercent,
        ]);
    })->name('dashboard.student.completion');

    Route::get('/dashboard/student/data', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'student_data', 'view');

        $profile = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles')
                ->where('student_id', $user->id)
                ->first()
            : null;

        $profileIsComplete = $profile
            && filled($user->name)
            && filled(data_get($profile, 'birth_place'))
            && filled(data_get($profile, 'birth_date'))
            && filled(data_get($profile, 'major_name'))
            && filled(data_get($profile, 'address'))
            && filled(data_get($profile, 'phone_number'))
            && filled(data_get($profile, 'pkl_place_name'))
            && filled(data_get($profile, 'pkl_place_address'))
            && filled(data_get($profile, 'pkl_place_phone'))
            && filled(data_get($profile, 'pkl_start_date'))
            && filled(data_get($profile, 'pkl_end_date'))
            && filled(data_get($profile, 'mentor_teacher_name'))
            && filled(data_get($profile, 'school_supervisor_teacher_name'))
            && filled(data_get($profile, 'company_instructor_position'));

        return view('dashboard.student-data', [
            'profile' => $profile,
            'profileIsComplete' => $profileIsComplete,
        ]);
    })->name('dashboard.student.data-page');

    Route::post('/dashboard/student/data', function (Request $request) {
        $user = $request->user();
        require_user_permission($user, 'student_data', 'update');
        abort(403, 'Only admin can edit student data.');

        if (!Schema::hasTable('student_profiles')) {
            return back()->withErrors(['student_data' => 'Student data table is not ready yet. Please run database migrations first.']);
        }

        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'birth_place' => ['required', 'string', 'max:120'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'major_name' => ['required', 'in:RPL,BDP,AKL'],
            'class_name' => ['nullable', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:2000'],
            'pkl_place_name' => ['required', 'string', 'max:150'],
            'pkl_place_address' => ['required', 'string', 'max:2000'],
            'pkl_place_phone' => ['required', 'string', 'max:30'],
            'pkl_start_date' => ['required', 'date', 'after_or_equal:today'],
            'pkl_end_date' => ['required', 'date', 'after_or_equal:pkl_start_date'],
            'mentor_teacher_name' => ['required', 'string', 'max:150'],
            'school_supervisor_teacher_name' => ['required', 'string', 'max:150'],
            'company_instructor_position' => ['required', 'string', 'max:150'],
        ]);

        $wibNow = Carbon::now('Asia/Jakarta');
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'name' => $validated['student_name'],
                'updated_at' => $wibNow,
            ]);

        DB::table('student_profiles')->updateOrInsert(
            ['student_id' => $user->id],
            [
                'birth_place' => $validated['birth_place'],
                'birth_date' => $validated['birth_date'],
                'major_name' => $validated['major_name'],
                'class_name' => $validated['class_name'] ?? null,
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
                'pkl_place_name' => $validated['pkl_place_name'],
                'pkl_place_address' => $validated['pkl_place_address'],
                'pkl_place_phone' => $validated['pkl_place_phone'],
                'pkl_start_date' => $validated['pkl_start_date'],
                'pkl_end_date' => $validated['pkl_end_date'],
                'mentor_teacher_name' => $validated['mentor_teacher_name'],
                'school_supervisor_teacher_name' => $validated['school_supervisor_teacher_name'],
                'company_instructor_position' => $validated['company_instructor_position'],
                'updated_at' => $wibNow,
                'created_at' => $wibNow,
            ]
        );

        return back()->with('status', 'Student data saved.');
    })->name('dashboard.student.data.save');
    Route::post('/dashboard/student/task-log', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'task_log', 'create');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();

        $validated = $request->validate([
            'planned_today' => ['required', 'string', 'max:5000'],
            'work_realization' => ['required', 'string', 'max:5000'],
            'assigned_work' => ['nullable', 'string', 'max:5000'],
            'field_problems' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::table('daily_logs')->updateOrInsert(
            [
                'student_id' => $user->id,
                'work_date' => $today,
            ],
            [
                'title' => Str::limit($validated['planned_today'], 150, ''),
                'description' => $validated['work_realization'],
                'planned_today' => $validated['planned_today'],
                'work_realization' => $validated['work_realization'],
                'assigned_work' => $validated['assigned_work'] ?? null,
                'field_problems' => $validated['field_problems'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'updated_at' => $wibNow,
                'created_at' => $wibNow,
            ]
        );

        return back()->with('status', 'Today\'s task log saved.');
    })->name('dashboard.student.task-log');

    Route::post('/dashboard/student/absence-request', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_STUDENT, 403);
        require_user_permission($user, 'checkin', 'create');

        if (!Schema::hasTable('attendance_excuses')) {
            return back()->withErrors(['absence_request' => 'Absence request table is not ready. Run migrations first.']);
        }

        $wibNow = now('Asia/Jakarta');
        $today = $wibNow->toDateString();

        $validated = $request->validate([
            'absence_type' => ['required', 'in:sick,permit'],
            'reason' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'attachment_crop_data' => ['nullable', 'string'],
        ]);

        $todayAttendance = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();
        if (!empty(data_get($todayAttendance, 'check_in_at'))) {
            return back()->withErrors(['absence_request' => 'You already checked in today. Absence request is not needed.']);
        }

        $existing = DB::table('attendance_excuses')
            ->where('student_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existing) {
            $editableUntil = !empty($existing->created_at)
                ? Carbon::parse($existing->created_at, 'Asia/Jakarta')->addMinutes(30)
                : null;
            $stillEditable = ($existing->status ?? null) === 'pending'
                && $editableUntil
                && $wibNow->lessThanOrEqualTo($editableUntil);

            if (!$stillEditable) {
                return back()->withErrors([
                    'absence_request' => 'Today\'s request can only be edited within 30 minutes after submission while still pending.',
                ]);
            }
        }

        $attachmentPath = data_get($existing, 'attachment_path');
        if (!empty($validated['attachment_crop_data'])) {
            $base64 = (string) $validated['attachment_crop_data'];
            $prefixes = ['data:image/png;base64,', 'data:image/jpeg;base64,', 'data:image/jpg;base64,'];
            $matchedPrefix = collect($prefixes)->first(fn ($prefix) => Str::startsWith($base64, $prefix));

            if (!$matchedPrefix) {
                return back()->withErrors(['attachment_crop_data' => 'Invalid cropped attachment image.']);
            }

            $decoded = base64_decode(substr($base64, strlen($matchedPrefix)), true);
            if ($decoded === false) {
                return back()->withErrors(['attachment_crop_data' => 'Could not process cropped attachment image.']);
            }

            $ext = Str::contains($matchedPrefix, 'png') ? 'png' : 'jpg';
            $storedPath = 'absence-proofs/' . Str::uuid() . '.' . $ext;
            Storage::disk('public')->put($storedPath, $decoded);
            $attachmentPath = $storedPath;

            if (!empty($existing?->attachment_path) && !Str::startsWith((string) $existing->attachment_path, ['http://', 'https://'])) {
                Storage::disk('public')->delete((string) $existing->attachment_path);
            }
        } elseif ($request->hasFile('attachment')) {
            $uploadedFile = $request->file('attachment');
            $storedPath = $uploadedFile->store('absence-proofs', 'public');
            if ($storedPath === false) {
                return back()->withErrors(['absence_request' => 'Could not upload attachment.']);
            }
            $attachmentPath = $storedPath;
            if (!empty($existing?->attachment_path) && !Str::startsWith((string) $existing->attachment_path, ['http://', 'https://'])) {
                Storage::disk('public')->delete((string) $existing->attachment_path);
            }
        }

        $payload = [
            'absence_type' => $validated['absence_type'],
            'reason' => trim((string) $validated['reason']),
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'rejection_notes' => null,
            'updated_at' => $wibNow,
        ];

        if ($existing) {
            DB::table('attendance_excuses')
                ->where('id', $existing->id)
                ->update($payload);
        } else {
            $payload['student_id'] = $user->id;
            $payload['attendance_date'] = $today;
            $payload['created_at'] = $wibNow;
            DB::table('attendance_excuses')->insert($payload);
        }

        return back()->with('status', 'Absence request submitted and waiting for approval.');
    })->name('dashboard.student.absence-request');

    Route::post('/dashboard/student/check-in', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'checkin', 'create');

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'selfie_data' => ['required', 'string'],
        ]);

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $attendance = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($attendance && $attendance->check_in_at) {
            return back()->with('status', 'You are already checked in for today.');
        }

        if (Schema::hasTable('attendance_excuses')) {
            $todayExcuse = DB::table('attendance_excuses')
                ->where('student_id', $user->id)
                ->whereDate('attendance_date', $today)
                ->first();
            if (in_array((string) ($todayExcuse->status ?? ''), ['pending', 'approved'], true)) {
                return back()->withErrors([
                    'attendance' => 'You already have an absence request for today (' . strtoupper((string) ($todayExcuse->status ?? '')) . '). Resolve it before check-in.',
                ]);
            }
        }

        $selfieData = $validated['selfie_data'];
        $prefixes = ['data:image/jpeg;base64,', 'data:image/jpg;base64,', 'data:image/png;base64,'];
        $matchedPrefix = collect($prefixes)->first(fn ($prefix) => Str::startsWith($selfieData, $prefix));

        if (!$matchedPrefix) {
            return back()->withErrors(['selfie_data' => 'Invalid selfie image format.']);
        }

        $decoded = base64_decode(substr($selfieData, strlen($matchedPrefix)), true);
        if ($decoded === false) {
            return back()->withErrors(['selfie_data' => 'Could not process selfie image.']);
        }

        $ext = Str::contains($matchedPrefix, 'png') ? 'png' : 'jpg';
        $photoPath = 'checkins/' . Str::uuid() . '.' . $ext;
        Storage::disk('public')->put($photoPath, $decoded);

        $geofenceDistanceMeters = null;
        $geofenceRadiusMeters = null;
        $hasPartnerGeo = Schema::hasTable('partner_companies')
            && Schema::hasColumn('partner_companies', 'office_latitude')
            && Schema::hasColumn('partner_companies', 'office_longitude')
            && Schema::hasColumn('partner_companies', 'geofence_radius_meters');
        if ($hasPartnerGeo && Schema::hasTable('student_profiles')) {
            $studentProfile = DB::table('student_profiles')
                ->where('student_id', $user->id)
                ->first(['pkl_place_name', 'pkl_place_address']);

            $companyName = trim((string) data_get($studentProfile, 'pkl_place_name', ''));
            $companyAddress = trim((string) data_get($studentProfile, 'pkl_place_address', '-')) ?: '-';
            if ($companyName !== '') {
                $companyProfile = DB::table('partner_companies')
                    ->whereRaw('TRIM(name) = ?', [$companyName])
                    ->whereRaw("COALESCE(NULLIF(TRIM(address), ''), '-') = ?", [$companyAddress])
                    ->first(['office_latitude', 'office_longitude', 'geofence_radius_meters']);

                if (
                    $companyProfile
                    && !is_null($companyProfile->office_latitude)
                    && !is_null($companyProfile->office_longitude)
                    && !is_null($companyProfile->geofence_radius_meters)
                ) {
                    $geofenceDistanceMeters = haversine_distance_meters(
                        (float) $validated['latitude'],
                        (float) $validated['longitude'],
                        (float) $companyProfile->office_latitude,
                        (float) $companyProfile->office_longitude
                    );
                    $geofenceRadiusMeters = (int) $companyProfile->geofence_radius_meters;

                    if ($geofenceRadiusMeters > 0 && $geofenceDistanceMeters > $geofenceRadiusMeters) {
                        return back()->withErrors([
                            'attendance' => 'Check-in denied: you are outside company geofence. Distance '
                                . (int) round($geofenceDistanceMeters)
                                . 'm, allowed radius '
                                . $geofenceRadiusMeters
                                . 'm.',
                        ]);
                    }
                }
            }
        }

        $lateMinutes = attendance_late_minutes($wibNow, $today);
        $attendanceStatus = $lateMinutes > 0 ? 'late' : 'pending';

        $upsertPayload = [
            'check_in_at' => $wibNow,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'ip_address' => $request->ip(),
            'photo_path' => $photoPath,
            'status' => $attendanceStatus,
            'updated_at' => $wibNow,
            'created_at' => $wibNow,
        ];
        if (Schema::hasColumn('attendances', 'late_minutes')) {
            $upsertPayload['late_minutes'] = $lateMinutes;
        }
        if (Schema::hasColumn('attendances', 'geofence_distance_meters')) {
            $upsertPayload['geofence_distance_meters'] = $geofenceDistanceMeters;
        }
        if (Schema::hasColumn('attendances', 'geofence_radius_meters')) {
            $upsertPayload['geofence_radius_meters'] = $geofenceRadiusMeters;
        }

        DB::table('attendances')->updateOrInsert(
            [
                'student_id' => $user->id,
                'attendance_date' => $today,
            ],
            $upsertPayload
        );

        if ($lateMinutes > 0) {
            return back()->with('status', 'Check-in successful, but marked LATE (' . $lateMinutes . ' min).');
        }

        return back()->with('status', 'Check-in successful.');
    })->name('dashboard.student.check-in');

    Route::post('/dashboard/student/check-out', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'checkin', 'update');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();

        $attendance = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in_at) {
            return back()->withErrors(['attendance' => 'You need to check in first.']);
        }

        if ($attendance->check_out_at) {
            return back()->with('status', 'You already checked out today.');
        }

        $todayLog = DB::table('daily_logs')
            ->where('student_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $workRealization = data_get($todayLog, 'work_realization') ?? data_get($todayLog, 'description', '');
        if (!$todayLog || empty(trim((string) $workRealization))) {
            return back()->withErrors(['task_log' => 'Please fill today\'s task log before check-out.']);
        }

        DB::table('attendances')
            ->where('id', $attendance->id)
            ->update([
                'check_out_at' => $wibNow,
                'status' => ($attendance->status ?? null) === 'late' ? 'late' : 'present',
                'updated_at' => $wibNow,
            ]);

        return back()->with('status', 'Check-out successful. Great work today.');
    })->name('dashboard.student.check-out');

    Route::post('/dashboard/student/profile', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:50', 'unique:users,nis,' . $user->id],
            'avatar_crop_data' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->nis = $validated['nis'];

        if (!empty($validated['avatar_crop_data'])) {
            $base64 = $validated['avatar_crop_data'];
            $prefix = 'data:image/png;base64,';

            if (!Str::startsWith($base64, $prefix)) {
                return back()->withErrors(['avatar_crop_data' => 'Invalid cropped image format.']);
            }

            $decoded = base64_decode(substr($base64, strlen($prefix)), true);
            if ($decoded === false) {
                return back()->withErrors(['avatar_crop_data' => 'Could not process cropped image.']);
            }

            if (!empty($user->avatar_url) && !Str::startsWith($user->avatar_url, ['http://', 'https://'])) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            $path = 'avatars/' . Str::uuid() . '.png';
            Storage::disk('public')->put($path, $decoded);
            $user->avatar_url = $path;
        }

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return back()->with('status', 'Profile updated successfully.');
    })->name('dashboard.student.profile');

    Route::post('/dashboard/profile', function (Request $request) {
        $user = $request->user();
        abort_unless($user && $user->role !== User::ROLE_SUPER_ADMIN, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:50', 'unique:users,nis,' . $user->id],
            'avatar_crop_data' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->nis = $validated['nis'];

        if (!empty($validated['avatar_crop_data'])) {
            $base64 = $validated['avatar_crop_data'];
            $prefix = 'data:image/png;base64,';

            if (!Str::startsWith($base64, $prefix)) {
                return back()->withErrors(['avatar_crop_data' => 'Invalid cropped image format.']);
            }

            $decoded = base64_decode(substr($base64, strlen($prefix)), true);
            if ($decoded === false) {
                return back()->withErrors(['avatar_crop_data' => 'Could not process cropped image.']);
            }

            if (!empty($user->avatar_url) && !Str::startsWith($user->avatar_url, ['http://', 'https://'])) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            $path = 'avatars/' . Str::uuid() . '.png';
            Storage::disk('public')->put($path, $decoded);
            $user->avatar_url = $path;
        }

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return back()->with('status', 'Profile updated successfully.');
    })->name('dashboard.profile.update');

    Route::get('/dashboard/student/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'weekly_journal', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $weeklyJournal = DB::table('weekly_journals')
            ->where('student_id', $user->id)
            ->whereDate('week_start_date', $weekStart)
            ->whereDate('week_end_date', $weekEnd)
            ->first();

        return view('dashboard.student-weekly-journal', [
            'weeklyJournal' => $weeklyJournal,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'wibNow' => $wibNow,
        ]);
    })->name('dashboard.student.weekly-journal');

    Route::post('/dashboard/student/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'weekly_journal', 'create');

        $wibNow = Carbon::now('Asia/Jakarta');
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $validated = $request->validate([
            'learning_notes' => ['required', 'string', 'max:7000'],
            'student_mentor_notes' => ['required', 'string', 'max:7000'],
        ]);

        DB::table('weekly_journals')->updateOrInsert(
            [
                'student_id' => $user->id,
                'week_start_date' => $weekStart,
                'week_end_date' => $weekEnd,
            ],
            [
                'learning_notes' => $validated['learning_notes'],
                'student_mentor_notes' => $validated['student_mentor_notes'],
                'status' => 'submitted',
                'updated_at' => $wibNow,
                'created_at' => $wibNow,
            ]
        );

        return back()->with('status', 'Weekly journal submitted for validation.');
    })->name('dashboard.student.weekly-journal.save');

    Route::get('/dashboard/mentor/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_dashboard', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $supervisedStudentIds = mentor_supervised_student_ids($user);

        $activeStudentIds = collect();
        if (Schema::hasTable('student_profiles') && $supervisedStudentIds->isNotEmpty()) {
            $activeStudentIds = DB::table('student_profiles')
                ->whereIn('student_id', $supervisedStudentIds)
                ->whereNotNull('pkl_start_date')
                ->whereNotNull('pkl_end_date')
                ->whereDate('pkl_start_date', '<=', $today)
                ->whereDate('pkl_end_date', '>=', $today)
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        $todayCheckInByStudent = Schema::hasTable('attendances') && $supervisedStudentIds->isNotEmpty()
            ? DB::table('attendances')
                ->whereIn('student_id', $supervisedStudentIds)
                ->whereDate('attendance_date', $today)
                ->whereNotNull('check_in_at')
                ->select('student_id', 'check_in_at')
                ->get()
                ->keyBy(fn ($row) => (int) $row->student_id)
            : collect();

        $todaysCheckins = $todayCheckInByStudent->count();

        $pendingJournals = Schema::hasTable('weekly_journals') && $supervisedStudentIds->isNotEmpty()
            ? DB::table('weekly_journals')
                ->whereIn('student_id', $supervisedStudentIds)
                ->whereDate('week_start_date', $weekStart)
                ->whereDate('week_end_date', $weekEnd)
                ->whereNull('mentor_is_correct')
                ->whereIn('status', ['submitted', 'needs_revision'])
                ->count()
            : 0;

        $presenceRows = collect();
        if (Schema::hasTable('student_profiles') && $activeStudentIds->isNotEmpty()) {
            $presenceRows = DB::table('users as s')
                ->join('student_profiles as sp', 'sp.student_id', '=', 's.id')
                ->leftJoin('attendances as a', function ($join) use ($today) {
                    $join->on('a.student_id', '=', 's.id')
                        ->whereDate('a.attendance_date', '=', $today);
                })
                ->whereIn('s.id', $activeStudentIds)
                ->select(
                    's.id as student_id',
                    's.name as student_name',
                    's.nis as student_nis',
                    'sp.phone_number',
                    'sp.major_name',
                    'sp.class_name',
                    'sp.pkl_place_name',
                    'sp.pkl_place_address',
                    'a.check_in_at',
                    'a.latitude',
                    'a.longitude',
                    'a.photo_path'
                )
                ->orderBy('s.name')
                ->get();
        }

        $hasDailyLogMentorReviewColumns = Schema::hasTable('daily_logs')
            && Schema::hasColumn('daily_logs', 'mentor_review_status')
            && Schema::hasColumn('daily_logs', 'mentor_revision_notes')
            && Schema::hasColumn('daily_logs', 'mentor_reviewed_at');

        $dailyLogsQuery = DB::table('daily_logs as dl')
            ->join('users as s', 's.id', '=', 'dl.student_id')
            ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->whereIn('dl.student_id', $supervisedStudentIds->isNotEmpty() ? $supervisedStudentIds : [-1])
            ->whereDate('dl.work_date', '>=', $weekStart)
            ->whereDate('dl.work_date', '<=', $weekEnd)
            ->select(
                'dl.id',
                'dl.student_id',
                'dl.work_date',
                'dl.title',
                'dl.description',
                'dl.planned_today',
                'dl.work_realization',
                'dl.assigned_work',
                'dl.field_problems',
                'dl.notes',
                's.name as student_name',
                's.nis as student_nis',
                'sp.major_name'
            );

        if ($hasDailyLogMentorReviewColumns) {
            $dailyLogsQuery->addSelect(
                'dl.mentor_review_status',
                'dl.mentor_revision_notes',
                'dl.mentor_reviewed_at'
            );
        } else {
            $dailyLogsQuery->selectRaw('NULL as mentor_review_status, NULL as mentor_revision_notes, NULL as mentor_reviewed_at');
        }

        $dailyValidationRows = $dailyLogsQuery
            ->orderByDesc('dl.work_date')
            ->orderBy('s.name')
            ->limit(60)
            ->get();

        $hasWeeklyMentorFeedbackColumns = Schema::hasTable('weekly_journals')
            && Schema::hasColumn('weekly_journals', 'mentor_feedback_summary')
            && Schema::hasColumn('weekly_journals', 'mentor_attitude_rating')
            && Schema::hasColumn('weekly_journals', 'mentor_skill_rating');

        $rows = DB::table('weekly_journals as wj')
            ->join('users as s', 's.id', '=', 'wj.student_id')
            ->whereIn('wj.student_id', $supervisedStudentIds->isNotEmpty() ? $supervisedStudentIds : [-1])
            ->whereDate('wj.week_start_date', $weekStart)
            ->whereDate('wj.week_end_date', $weekEnd)
            ->select(
                'wj.id',
                'wj.student_id',
                'wj.week_start_date',
                'wj.week_end_date',
                'wj.learning_notes',
                'wj.student_mentor_notes',
                'wj.mentor_is_correct',
                'wj.missing_info_notes',
                'wj.status',
                'wj.mentor_reviewed_at',
                's.name as student_name',
                's.nis as student_nis'
            )
            ->orderBy('s.name')
            ->get();

        if ($hasWeeklyMentorFeedbackColumns) {
            $weeklyExtras = DB::table('weekly_journals')
                ->whereIn('id', $rows->pluck('id'))
                ->get([
                    'id',
                    'mentor_feedback_summary',
                    'mentor_attitude_rating',
                    'mentor_skill_rating',
                ])
                ->keyBy('id');
            $rows = $rows->map(function ($row) use ($weeklyExtras) {
                $extra = $weeklyExtras->get($row->id);
                $row->mentor_feedback_summary = data_get($extra, 'mentor_feedback_summary');
                $row->mentor_attitude_rating = data_get($extra, 'mentor_attitude_rating');
                $row->mentor_skill_rating = data_get($extra, 'mentor_skill_rating');
                return $row;
            });
        } else {
            $rows = $rows->map(function ($row) {
                $row->mentor_feedback_summary = null;
                $row->mentor_attitude_rating = null;
                $row->mentor_skill_rating = null;
                return $row;
            });
        }

        $companySummary = Schema::hasTable('student_profiles') && $activeStudentIds->isNotEmpty()
            ? DB::table('student_profiles')
                ->whereIn('student_id', $activeStudentIds)
                ->whereNotNull('pkl_place_name')
                ->where('pkl_place_name', '<>', '')
                ->groupBy('pkl_place_name', 'pkl_place_address')
                ->selectRaw('pkl_place_name as company_name, COALESCE(NULLIF(TRIM(pkl_place_address), \'\'), \'-\') as company_address, COUNT(*) as total')
                ->orderByDesc('total')
                ->first()
            : null;

        $partnerHasGeoColumns = Schema::hasTable('partner_companies')
            && Schema::hasColumn('partner_companies', 'office_latitude')
            && Schema::hasColumn('partner_companies', 'office_longitude')
            && Schema::hasColumn('partner_companies', 'geofence_radius_meters');

        $companyProfile = null;
        if (Schema::hasTable('partner_companies') && Schema::hasColumn('users', 'partner_company_id') && !empty($user->partner_company_id)) {
            $companyProfile = DB::table('partner_companies')
                ->where('id', $user->partner_company_id)
                ->first();
        }
        if (!$companyProfile && Schema::hasTable('partner_companies')) {
            $mentorCompanyNameFromSummary = trim((string) data_get($companySummary, 'company_name', ''));
            $mentorCompanyAddressFromSummary = trim((string) data_get($companySummary, 'company_address', '-')) ?: '-';
            if ($mentorCompanyNameFromSummary !== '') {
                $companyProfile = DB::table('partner_companies')
                    ->whereRaw('TRIM(name) = ?', [$mentorCompanyNameFromSummary])
                    ->whereRaw("COALESCE(NULLIF(TRIM(address), ''), '-') = ?", [$mentorCompanyAddressFromSummary])
                    ->first();
            }
        }
        $mentorCompanyName = trim((string) data_get($companyProfile, 'name', data_get($companySummary, 'company_name', '')));
        $mentorCompanyAddress = trim((string) data_get($companyProfile, 'address', data_get($companySummary, 'company_address', '-'))) ?: '-';
        $absenceRows = collect();
        if (Schema::hasTable('attendance_excuses') && $supervisedStudentIds->isNotEmpty()) {
            $absenceRows = DB::table('attendance_excuses as ae')
                ->join('users as s', 's.id', '=', 'ae.student_id')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 'ae.student_id')
                ->whereIn('ae.student_id', $supervisedStudentIds)
                ->whereDate('ae.attendance_date', $today)
                ->orderBy('s.name')
                ->get([
                    'ae.attendance_date',
                    'ae.absence_type',
                    'ae.status',
                    'ae.reason',
                    's.name as student_name',
                    's.nis as student_nis',
                    DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                ]);
        }
        $recentAlerts = attendance_fetch_recent_alerts($user, 8);

        return view('dashboard.mentor-weekly-journal', [
            'today' => $today,
            'rows' => $rows,
            'dailyValidationRows' => $dailyValidationRows,
            'presenceRows' => $presenceRows,
            'totalActiveStudents' => $activeStudentIds->count(),
            'todaysCheckins' => $todaysCheckins,
            'pendingJournals' => $pendingJournals,
            'mentorCompanyName' => $mentorCompanyName,
            'mentorCompanyAddress' => $mentorCompanyAddress,
            'companyProfile' => $companyProfile,
            'partnerHasGeoColumns' => $partnerHasGeoColumns,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'absenceRows' => $absenceRows,
            'recentAlerts' => $recentAlerts,
        ]);
    })->name('dashboard.mentor.weekly-journal');

    Route::post('/dashboard/mentor/daily-log/{log}/review', function (Request $request, int $log) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_review_center', 'update');

        $validated = $request->validate([
            'action' => ['required', 'in:approve,revise'],
            'revision_notes' => [Rule::requiredIf(fn () => $request->input('action') === 'revise'), 'nullable', 'string', 'max:7000'],
        ]);

        if (!Schema::hasTable('daily_logs')) {
            return back()->withErrors(['mentor_daily_log' => 'Daily logs table is not ready.']);
        }

        $supervisedStudentIds = mentor_supervised_student_ids($user);
        if ($supervisedStudentIds->isEmpty()) {
            abort(403);
        }

        $dailyLog = DB::table('daily_logs as dl')
            ->where('dl.id', $log)
            ->whereIn('dl.student_id', $supervisedStudentIds)
            ->select('dl.id')
            ->first();

        abort_unless($dailyLog, 403);

        $payload = [
            'score_mentor_id' => $user->id,
            'scored_at' => now('Asia/Jakarta'),
            'updated_at' => now('Asia/Jakarta'),
        ];
        if (Schema::hasColumn('daily_logs', 'mentor_review_status')) {
            $payload['mentor_review_status'] = $validated['action'] === 'approve' ? 'approved' : 'revise';
        }
        if (Schema::hasColumn('daily_logs', 'mentor_revision_notes')) {
            $payload['mentor_revision_notes'] = $validated['action'] === 'approve' ? null : trim((string) ($validated['revision_notes'] ?? ''));
        }
        if (Schema::hasColumn('daily_logs', 'mentor_reviewed_at')) {
            $payload['mentor_reviewed_at'] = now('Asia/Jakarta');
        }

        DB::table('daily_logs')
            ->where('id', $log)
            ->update($payload);

        return back()->with('status', $validated['action'] === 'approve' ? 'Daily log approved.' : 'Daily log marked for revision.');
    })->name('dashboard.mentor.daily-log.review');

    Route::get('/dashboard/mentor/review-center', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_review_center', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $supervisedStudentIds = mentor_supervised_student_ids($user);

        $dailyScoreRows = DB::table('daily_logs as dl')
            ->join('users as s', 's.id', '=', 'dl.student_id')
            ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->leftJoin('attendances as a', function ($join) {
                $join->on('a.student_id', '=', 'dl.student_id')
                    ->on('a.attendance_date', '=', 'dl.work_date');
            })
            ->whereIn('dl.student_id', $supervisedStudentIds->isNotEmpty() ? $supervisedStudentIds : [-1])
            ->whereDate('dl.work_date', '>=', $weekStart)
            ->whereDate('dl.work_date', '<=', $weekEnd)
            ->select(
                'dl.id',
                'dl.student_id',
                'dl.work_date',
                'dl.title',
                'dl.description',
                'dl.planned_today',
                'dl.work_realization',
                'dl.score_smile',
                'dl.score_friendliness',
                'dl.score_appearance',
                'dl.score_communication',
                'dl.score_work_realization',
                'dl.scored_at',
                's.name as student_name',
                's.nis as student_nis',
                'sp.major_name',
                'a.check_in_at',
                'a.check_out_at'
            )
            ->orderByDesc('dl.work_date')
            ->orderBy('s.name')
            ->get()
            ->map(function ($row) {
                $isCompleted = !empty($row->check_in_at)
                    && !empty($row->check_out_at)
                    && trim((string) ($row->work_realization ?? '')) !== '';
                $row->is_completed = $isCompleted;
                return $row;
            });

        $weeklyRows = DB::table('weekly_journals as wj')
            ->join('users as s', 's.id', '=', 'wj.student_id')
            ->whereIn('wj.student_id', $supervisedStudentIds->isNotEmpty() ? $supervisedStudentIds : [-1])
            ->whereDate('wj.week_start_date', $weekStart)
            ->whereDate('wj.week_end_date', $weekEnd)
            ->select(
                'wj.id',
                'wj.student_id',
                'wj.learning_notes',
                'wj.student_mentor_notes',
                'wj.mentor_is_correct',
                'wj.missing_info_notes',
                'wj.mentor_feedback_summary',
                'wj.mentor_attitude_rating',
                'wj.mentor_skill_rating',
                'wj.status',
                's.name as student_name',
                's.nis as student_nis'
            )
            ->orderBy('s.name')
            ->get();

        return view('dashboard.mentor-review-center', [
            'today' => $today,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'dailyScoreRows' => $dailyScoreRows,
            'weeklyRows' => $weeklyRows,
        ]);
    })->name('dashboard.mentor.review-center');

    Route::post('/dashboard/mentor/daily-scoring/{dailyLog}', function (Request $request, int $dailyLog) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_review_center', 'update');

        $validated = $request->validate([
            'score_smile' => ['required', 'integer', 'between:1,5'],
            'score_friendliness' => ['required', 'integer', 'between:1,5'],
            'score_appearance' => ['required', 'integer', 'between:1,5'],
            'score_communication' => ['required', 'integer', 'between:1,5'],
            'score_work_realization' => ['required', 'integer', 'between:1,5'],
        ]);

        if (!Schema::hasTable('daily_logs') || !Schema::hasTable('attendances')) {
            return back()->withErrors(['daily_scoring' => 'Required tables are not ready. Run migrations first.']);
        }

        $supervisedStudentIds = mentor_supervised_student_ids($user);
        if ($supervisedStudentIds->isEmpty()) {
            abort(403);
        }

        $row = DB::table('daily_logs as dl')
            ->leftJoin('attendances as a', function ($join) {
                $join->on('a.student_id', '=', 'dl.student_id')
                    ->on('a.attendance_date', '=', 'dl.work_date');
            })
            ->where('dl.id', $dailyLog)
            ->whereIn('dl.student_id', $supervisedStudentIds)
            ->select(
                'dl.id',
                'dl.student_id',
                'dl.work_date',
                'dl.work_realization',
                'a.check_in_at',
                'a.check_out_at'
            )
            ->first();

        abort_unless($row, 403);

        $isCompleted = !empty($row->check_in_at)
            && !empty($row->check_out_at)
            && trim((string) ($row->work_realization ?? '')) !== '';

        if (!$isCompleted) {
            return back()->withErrors(['daily_scoring' => 'Daily scoring is allowed only after student check-out and completed work realization.']);
        }

        DB::table('daily_logs')
            ->where('id', $dailyLog)
            ->update([
                'score_smile' => (int) $validated['score_smile'],
                'score_friendliness' => (int) $validated['score_friendliness'],
                'score_appearance' => (int) $validated['score_appearance'],
                'score_communication' => (int) $validated['score_communication'],
                'score_work_realization' => (int) $validated['score_work_realization'],
                'score_mentor_id' => $user->id,
                'scored_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]);

        return back()->with('status', 'Daily score saved.');
    })->name('dashboard.mentor.daily-scoring.save');

    Route::post('/dashboard/mentor/weekly-journal/{journal}', function (Request $request, int $journal) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'weekly_journal', 'update');

        $validated = $request->validate([
            'mentor_is_correct' => ['required', 'in:1,0'],
            'missing_info_notes' => ['nullable', 'string', 'max:7000'],
            'mentor_feedback_summary' => ['nullable', 'string', 'max:7000'],
            'mentor_attitude_rating' => ['nullable', 'integer', 'between:1,5'],
            'mentor_skill_rating' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $wibNow = Carbon::now('Asia/Jakarta');
        $isCorrect = $validated['mentor_is_correct'] === '1';

        $payload = [
            'mentor_id' => $user->id,
            'mentor_is_correct' => $isCorrect,
            'missing_info_notes' => $isCorrect ? null : ($validated['missing_info_notes'] ?? null),
            'status' => $isCorrect ? 'approved' : 'needs_revision',
            'mentor_reviewed_at' => $wibNow,
            'updated_at' => $wibNow,
        ];
        if (Schema::hasColumn('weekly_journals', 'mentor_feedback_summary')) {
            $payload['mentor_feedback_summary'] = trim((string) ($validated['mentor_feedback_summary'] ?? '')) ?: null;
        }
        if (Schema::hasColumn('weekly_journals', 'mentor_attitude_rating')) {
            $payload['mentor_attitude_rating'] = $validated['mentor_attitude_rating'] ?? null;
        }
        if (Schema::hasColumn('weekly_journals', 'mentor_skill_rating')) {
            $payload['mentor_skill_rating'] = $validated['mentor_skill_rating'] ?? null;
        }

        DB::table('weekly_journals')
            ->where('id', $journal)
            ->update($payload);

        return back()->with('status', 'Mentor validation updated.');
    })->name('dashboard.mentor.weekly-journal.review');

    Route::get('/dashboard/mentor/company-settings-page', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_dashboard', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();

        $supervisedStudentIds = mentor_supervised_student_ids($user);

        $activeStudentIds = collect();
        if (Schema::hasTable('student_profiles') && $supervisedStudentIds->isNotEmpty()) {
            $activeStudentIds = DB::table('student_profiles')
                ->whereIn('student_id', $supervisedStudentIds)
                ->whereNotNull('pkl_start_date')
                ->whereNotNull('pkl_end_date')
                ->whereDate('pkl_start_date', '<=', $today)
                ->whereDate('pkl_end_date', '>=', $today)
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        $companySummary = Schema::hasTable('student_profiles') && $activeStudentIds->isNotEmpty()
            ? DB::table('student_profiles')
                ->whereIn('student_id', $activeStudentIds)
                ->whereNotNull('pkl_place_name')
                ->where('pkl_place_name', '<>', '')
                ->groupBy('pkl_place_name', 'pkl_place_address')
                ->selectRaw('pkl_place_name as company_name, COALESCE(NULLIF(TRIM(pkl_place_address), \'\'), \'-\') as company_address, COUNT(*) as total')
                ->orderByDesc('total')
                ->first()
            : null;

        $partnerHasGeoColumns = Schema::hasTable('partner_companies')
            && Schema::hasColumn('partner_companies', 'office_latitude')
            && Schema::hasColumn('partner_companies', 'office_longitude')
            && Schema::hasColumn('partner_companies', 'geofence_radius_meters');

        $companyProfile = null;
        if (Schema::hasTable('partner_companies') && Schema::hasColumn('users', 'partner_company_id') && !empty($user->partner_company_id)) {
            $companyProfile = DB::table('partner_companies')
                ->where('id', $user->partner_company_id)
                ->first();
        }
        if (!$companyProfile && Schema::hasTable('partner_companies')) {
            $mentorCompanyNameFromSummary = trim((string) data_get($companySummary, 'company_name', ''));
            $mentorCompanyAddressFromSummary = trim((string) data_get($companySummary, 'company_address', '-')) ?: '-';
            if ($mentorCompanyNameFromSummary !== '') {
                $companyProfile = DB::table('partner_companies')
                    ->whereRaw('TRIM(name) = ?', [$mentorCompanyNameFromSummary])
                    ->whereRaw("COALESCE(NULLIF(TRIM(address), ''), '-') = ?", [$mentorCompanyAddressFromSummary])
                    ->first();
            }
        }

        $mentorCompanyName = trim((string) data_get($companyProfile, 'name', data_get($companySummary, 'company_name', '')));
        $mentorCompanyAddress = trim((string) data_get($companyProfile, 'address', data_get($companySummary, 'company_address', '-'))) ?: '-';

        return view('dashboard.mentor-company-settings', [
            'today' => $today,
            'mentorCompanyName' => $mentorCompanyName,
            'mentorCompanyAddress' => $mentorCompanyAddress,
            'companyProfile' => $companyProfile,
            'partnerHasGeoColumns' => $partnerHasGeoColumns,
        ]);
    })->name('dashboard.mentor.company-settings-page');

    Route::post('/dashboard/mentor/company-settings', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_dashboard', 'update');

        if (!Schema::hasTable('partner_companies')) {
            return back()->withErrors(['mentor_company' => 'Company profile table is not ready yet.']);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_address' => ['required', 'string', 'max:2000'],
            'office_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'office_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'logo_crop_data' => ['nullable', 'string'],
        ]);

        $name = trim((string) $validated['company_name']);
        $address = trim((string) $validated['company_address']) ?: '-';

        $existing = DB::table('partner_companies')
            ->whereRaw('TRIM(name) = ?', [$name])
            ->whereRaw("COALESCE(NULLIF(TRIM(address), ''), '-') = ?", [$address])
            ->first();

        $payload = [
            'name' => $name,
            'address' => $address,
            'updated_at' => now('Asia/Jakarta'),
        ];
        $companyId = null;

        if (!empty($validated['logo_crop_data'])) {
            $base64 = (string) $validated['logo_crop_data'];
            $prefix = 'data:image/png;base64,';
            if (!Str::startsWith($base64, $prefix)) {
                return back()->withErrors(['logo_crop_data' => 'Invalid logo image format. Please crop and submit again.']);
            }

            $decoded = base64_decode(substr($base64, strlen($prefix)), true);
            if ($decoded === false) {
                return back()->withErrors(['logo_crop_data' => 'Could not process cropped logo image.']);
            }

            $fileName = 'company-logo-' . now('Asia/Jakarta')->format('YmdHis') . '-' . Str::random(8) . '.png';
            $filePath = 'company-logos/' . $fileName;
            Storage::disk('public')->put($filePath, $decoded);
            $payload['logo_url'] = $filePath;
        }

        if (Schema::hasColumn('partner_companies', 'office_latitude')) {
            $payload['office_latitude'] = $validated['office_latitude'] ?? null;
        }
        if (Schema::hasColumn('partner_companies', 'office_longitude')) {
            $payload['office_longitude'] = $validated['office_longitude'] ?? null;
        }
        if (Schema::hasColumn('partner_companies', 'geofence_radius_meters')) {
            $payload['geofence_radius_meters'] = $validated['geofence_radius_meters'] ?? null;
        }

        if ($existing) {
            if (
                array_key_exists('logo_url', $payload)
                && !empty($existing->logo_url)
                && !Str::startsWith((string) $existing->logo_url, ['http://', 'https://'])
                && $existing->logo_url !== $payload['logo_url']
            ) {
                Storage::disk('public')->delete((string) $existing->logo_url);
            }
            DB::table('partner_companies')
                ->where('id', $existing->id)
                ->update($payload);
            $companyId = (int) $existing->id;
        } else {
            $payload['is_active'] = true;
            $payload['created_at'] = now('Asia/Jakarta');
            DB::table('partner_companies')->insert($payload);
            $companyId = (int) DB::table('partner_companies')
                ->where('name', $name)
                ->where('address', $address)
                ->value('id');
        }

        if (Schema::hasColumn('users', 'partner_company_id') && !empty($companyId)) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'partner_company_id' => $companyId,
                    'updated_at' => now('Asia/Jakarta'),
                ]);
        }

        return back()->with('status', 'Company proximity settings updated.');
    })->name('dashboard.mentor.company-settings');

    Route::get('/dashboard/kajur', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'kajur_dashboard', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $selectedDateInput = trim((string) $request->query('date', $today));
        $selectedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateInput) ? $selectedDateInput : $today;
        $defaultWeekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekStartInput = trim((string) $request->query('week_start', $defaultWeekStart));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStartInput)) {
            $weekStartDate = Carbon::parse($weekStartInput, 'Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        } else {
            $weekStartDate = Carbon::parse($defaultWeekStart, 'Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        }
        $weekStart = $weekStartDate->toDateString();
        $weekEnd = $weekStartDate->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $monitoringStart = $wibNow->copy()->subDays(14)->toDateString();
        $mapStart = $wibNow->copy()->subDays(29)->toDateString();

        $majorOptions = collect(['RPL', 'BDP', 'AKL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->where('major_name', '<>', '')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->values();
        }
        if ($majorOptions->isEmpty()) {
            $majorOptions = collect(['RPL']);
        }

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        if ($managedMajor === '' || !$majorOptions->contains($managedMajor)) {
            $managedMajor = (string) $majorOptions->first();
        }

        $selectedMajor = strtoupper(trim((string) $request->query('major', $managedMajor)));
        if ($selectedMajor === '' || !$majorOptions->contains($selectedMajor)) {
            $selectedMajor = $managedMajor;
        }

        $hasStudentProfiles = Schema::hasTable('student_profiles');
        $studentsBase = DB::table('users as s')
            ->where('s.role', User::ROLE_STUDENT);
        if ($hasStudentProfiles) {
            $studentsBase->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id');
            if ($selectedMajor !== 'ALL') {
                $studentsBase->whereRaw('UPPER(COALESCE(sp.major_name, "")) = ?', [$selectedMajor]);
            }
        }

        $classOptions = collect(['ALL']);
        if ($hasStudentProfiles) {
            $dynamicClasses = (clone $studentsBase)
                ->whereNotNull('sp.class_name')
                ->whereRaw('TRIM(sp.class_name) <> ""')
                ->distinct()
                ->pluck('sp.class_name')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->sort()
                ->values();
            $classOptions = $classOptions->merge($dynamicClasses)->unique()->values();
        }

        $selectedClass = trim((string) $request->query('class', 'ALL'));
        if ($selectedClass === '' || !$classOptions->contains($selectedClass)) {
            $selectedClass = 'ALL';
        }
        $effectiveClassScope = $selectedClass === 'ALL' ? null : $selectedClass;
        $isSelectedDateNonWorking = attendance_is_non_working_day($selectedDate, $managedMajor, $effectiveClassScope);
        $calendarException = null;
        if (attendance_calendar_storage_ready()) {
            $calendarException = DB::table('attendance_calendar_exceptions')
                ->whereDate('exception_date', $selectedDate)
                ->where(function ($query) use ($managedMajor, $effectiveClassScope) {
                    $query->where(function ($global) {
                        $global->whereNull('major_name')->whereNull('class_name');
                    })->orWhere(function ($majorOnly) use ($managedMajor) {
                        $majorOnly->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$managedMajor])
                            ->whereNull('class_name');
                    });
                    if ($effectiveClassScope !== null) {
                        $query->orWhere(function ($majorAndClass) use ($managedMajor, $effectiveClassScope) {
                            $majorAndClass->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$managedMajor])
                                ->whereRaw('TRIM(COALESCE(class_name, "")) = ?', [$effectiveClassScope]);
                        });
                    }
                })
                ->orderByDesc('id')
                ->first();
        }
        $students = (clone $studentsBase)
            ->when($hasStudentProfiles && $selectedClass !== 'ALL', function ($query) use ($selectedClass) {
                $query->whereRaw('TRIM(COALESCE(sp.class_name, "")) = ?', [$selectedClass]);
            })
            ->select(
                's.id as student_id',
                's.name as student_name',
                's.nis as student_nis',
                DB::raw($hasStudentProfiles ? 'sp.major_name as major_name' : 'NULL as major_name'),
                DB::raw($hasStudentProfiles ? 'sp.class_name as class_name' : 'NULL as class_name'),
                DB::raw($hasStudentProfiles ? 'sp.pkl_place_name as pkl_place_name' : 'NULL as pkl_place_name'),
                DB::raw($hasStudentProfiles ? 'sp.pkl_place_address as pkl_place_address' : 'NULL as pkl_place_address')
            )
            ->orderBy('s.name')
            ->get();

        $studentIds = $students->pluck('student_id')->map(fn ($id) => (int) $id)->filter()->values();

        $absenceByStudent = collect();
        if (Schema::hasTable('attendance_excuses') && $studentIds->isNotEmpty()) {
            $absenceRows = DB::table('attendance_excuses')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $selectedDate)
                ->orderByDesc('updated_at')
                ->get([
                    'student_id',
                    'absence_type',
                    'status',
                    'reason',
                    'attachment_path',
                    'rejection_notes',
                ]);
            $absenceByStudent = $absenceRows->unique('student_id')->keyBy('student_id');
        }

        $checkedInToday = 0;
        if (Schema::hasTable('attendances') && $studentIds->isNotEmpty()) {
            $checkedInToday = DB::table('attendances')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->whereNotNull('check_in_at')
                ->distinct('student_id')
                ->count('student_id');
        }

        $approvalQueue = collect();
        if (Schema::hasTable('daily_logs') && $studentIds->isNotEmpty()) {
            $approvalQueue = DB::table('daily_logs as dl')
                ->join('users as s', 's.id', '=', 'dl.student_id')
                ->when($hasStudentProfiles, function ($query) {
                    $query->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id');
                })
                ->whereIn('dl.student_id', $studentIds)
                ->whereDate('dl.work_date', '>=', $monitoringStart)
                ->when(Schema::hasColumn('daily_logs', 'mentor_review_status'), function ($query) {
                    $query->where('dl.mentor_review_status', 'approved');
                })
                ->where(function ($query) {
                    if (Schema::hasColumn('daily_logs', 'reviewed_at')) {
                        $query->whereNull('dl.reviewed_at');
                    } else {
                        $query->whereNull('dl.kajur_feedback');
                    }
                })
                ->select(
                    'dl.id',
                    'dl.work_date',
                    'dl.title',
                    'dl.description',
                    'dl.work_realization',
                    'dl.kajur_feedback',
                    's.name as student_name',
                    's.nis as student_nis',
                    DB::raw($hasStudentProfiles ? 'sp.class_name as class_name' : 'NULL as class_name'),
                    DB::raw($hasStudentProfiles ? 'sp.major_name as major_name' : 'NULL as major_name')
                )
                ->orderByDesc('dl.work_date')
                ->orderBy('s.name')
                ->limit(40)
                ->get();
        }

        $industryMapPoints = collect();
        $attendanceMapPoints = collect();
        if (Schema::hasTable('attendances') && $hasStudentProfiles && $studentIds->isNotEmpty()) {
            $industryMapPoints = DB::table('attendances as a')
                ->join('student_profiles as sp', 'sp.student_id', '=', 'a.student_id')
                ->whereIn('a.student_id', $studentIds)
                ->whereNotNull('a.latitude')
                ->whereNotNull('a.longitude')
                ->whereNotNull('a.check_in_at')
                ->whereDate('a.attendance_date', '>=', $monitoringStart)
                ->groupBy('sp.pkl_place_name', 'sp.pkl_place_address')
                ->selectRaw("
                    COALESCE(NULLIF(TRIM(sp.pkl_place_name), ''), 'Unknown Industry') as company_name,
                    COALESCE(NULLIF(TRIM(sp.pkl_place_address), ''), '-') as company_address,
                    AVG(a.latitude) as latitude,
                    AVG(a.longitude) as longitude,
                    COUNT(DISTINCT a.student_id) as active_students
                ")
                ->orderByDesc('active_students')
                ->get();

            $attendanceMapPoints = DB::table('attendances as a')
                ->join('student_profiles as sp', 'sp.student_id', '=', 'a.student_id')
                ->whereIn('a.student_id', $studentIds)
                ->whereNotNull('sp.pkl_place_name')
                ->whereNotNull('a.latitude')
                ->whereNotNull('a.longitude')
                ->whereNotNull('a.check_in_at')
                ->whereDate('a.attendance_date', '>=', $mapStart)
                ->whereDate('a.attendance_date', '<=', $today)
                ->groupBy('a.attendance_date', 'sp.pkl_place_name', 'sp.pkl_place_address')
                ->selectRaw("
                    a.attendance_date,
                    COALESCE(NULLIF(TRIM(sp.pkl_place_name), ''), 'Unknown Industry') as company_name,
                    COALESCE(NULLIF(TRIM(sp.pkl_place_address), ''), '-') as company_address,
                    AVG(a.latitude) as latitude,
                    AVG(a.longitude) as longitude,
                    COUNT(DISTINCT a.student_id) as attendance_total
                ")
                ->get();
        }

        $attendanceByDate = collect();
        if (Schema::hasTable('attendances') && $studentIds->isNotEmpty()) {
            $attendanceByDate = DB::table('attendances')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', '>=', $mapStart)
                ->whereDate('attendance_date', '<=', $today)
                ->whereNotNull('check_in_at')
                ->groupBy('attendance_date')
                ->selectRaw('attendance_date, COUNT(DISTINCT student_id) as total')
                ->pluck('total', 'attendance_date');
        }

        $heatmap = collect();
        $maxAttendance = 0;
        for ($i = 29; $i >= 0; $i--) {
            $date = $wibNow->copy()->subDays($i)->toDateString();
            $total = (int) ($attendanceByDate[$date] ?? 0);
            $maxAttendance = max($maxAttendance, $total);
            $heatmap->push([
                'date' => $date,
                'total' => $total,
            ]);
        }

        $redFlagDays = Schema::hasColumn('users', 'kajur_red_flag_days')
            ? max(1, (int) ($user->kajur_red_flag_days ?? 2))
            : 2;
        $lastAttendanceByStudent = collect();
        if (Schema::hasTable('attendances') && $studentIds->isNotEmpty()) {
            $lastAttendanceByStudent = DB::table('attendances')
                ->whereIn('student_id', $studentIds)
                ->whereNotNull('check_in_at')
                ->groupBy('student_id')
                ->selectRaw('student_id, MAX(attendance_date) as last_attendance_date')
                ->get()
                ->keyBy('student_id');
        }

        $problemAlerts = $students
            ->map(function ($student) use ($lastAttendanceByStudent, $today, $redFlagDays) {
                $lastAttendanceDate = data_get($lastAttendanceByStudent->get($student->student_id), 'last_attendance_date');
                $daysMissing = is_string($lastAttendanceDate) && $lastAttendanceDate !== ''
                    ? Carbon::parse($lastAttendanceDate, 'Asia/Jakarta')->diffInDays(Carbon::parse($today, 'Asia/Jakarta'))
                    : null;

                $student->last_attendance_date = $lastAttendanceDate;
                $student->days_missing = $daysMissing;
                $student->is_red_flag = is_null($daysMissing) || $daysMissing >= $redFlagDays;
                return $student;
            })
            ->filter(fn ($student) => (bool) $student->is_red_flag)
            ->sortByDesc(fn ($student) => is_null($student->days_missing) ? 9999 : $student->days_missing)
            ->values();

        $rows = DB::table('weekly_journals as wj')
            ->join('users as s', 's.id', '=', 'wj.student_id')
            ->when($hasStudentProfiles, function ($query) {
                $query->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id');
            })
            ->whereIn('wj.student_id', $studentIds->isNotEmpty() ? $studentIds : [-1])
            ->whereDate('wj.week_start_date', $weekStart)
            ->whereDate('wj.week_end_date', $weekEnd)
            ->select(
                'wj.id',
                'wj.learning_notes',
                'wj.student_mentor_notes',
                'wj.mentor_is_correct',
                'wj.missing_info_notes',
                'wj.kajur_notes',
                'wj.status',
                's.name as student_name',
                's.nis as student_nis',
                DB::raw($hasStudentProfiles ? 'sp.class_name as class_name' : 'NULL as class_name')
            )
            ->orderBy('s.name')
            ->get();

        return view('dashboard.kajur-weekly-journal', [
            'rows' => $rows,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'today' => $today,
            'selectedMajor' => $selectedMajor,
            'managedMajor' => $managedMajor,
            'majorOptions' => $majorOptions,
            'selectedClass' => $selectedClass,
            'classOptions' => $classOptions,
            'students' => $students,
            'checkedInToday' => $checkedInToday,
            'approvalQueue' => $approvalQueue,
            'industryMapPoints' => $industryMapPoints,
            'attendanceMapPoints' => $attendanceMapPoints,
            'heatmap' => $heatmap,
            'maxAttendance' => $maxAttendance,
            'problemAlerts' => $problemAlerts,
            'redFlagDays' => $redFlagDays,
            'profileMajorOptions' => $majorOptions,
        ]);
    })->name('dashboard.kajur.dashboard');

    Route::get('/dashboard/kajur/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'weekly_journal', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $defaultWeekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekStartInput = trim((string) $request->query('week_start', $defaultWeekStart));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStartInput)) {
            $weekStartDate = Carbon::parse($weekStartInput, 'Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        } else {
            $weekStartDate = Carbon::parse($defaultWeekStart, 'Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        }
        $weekStart = $weekStartDate->toDateString();
        $weekEnd = $weekStartDate->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $search = trim((string) $request->query('q', ''));
        $statusFilter = strtolower(trim((string) $request->query('status', 'all')));
        if (!in_array($statusFilter, ['all', 'submitted', 'approved', 'needs_revision', 'draft', 'no_submission'], true)) {
            $statusFilter = 'all';
        }

        $majorOptions = collect(['RPL', 'BDP', 'AKL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->where('major_name', '<>', '')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->values();
        }
        if ($majorOptions->isEmpty()) {
            $majorOptions = collect(['RPL']);
        }

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        if ($managedMajor === '' || !$majorOptions->contains($managedMajor)) {
            $managedMajor = (string) $majorOptions->first();
        }

        $selectedMajor = strtoupper(trim((string) $request->query('major', $managedMajor)));
        if ($selectedMajor === '' || !$majorOptions->contains($selectedMajor)) {
            $selectedMajor = $managedMajor;
        }

        $hasStudentProfiles = Schema::hasTable('student_profiles');
        $studentsBase = DB::table('users as s')
            ->where('s.role', User::ROLE_STUDENT);
        if ($hasStudentProfiles) {
            $studentsBase->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id');
            if ($selectedMajor !== 'ALL') {
                $studentsBase->whereRaw('UPPER(COALESCE(sp.major_name, "")) = ?', [$selectedMajor]);
            }
        }

        $classOptions = collect(['ALL']);
        if ($hasStudentProfiles) {
            $dynamicClasses = (clone $studentsBase)
                ->whereNotNull('sp.class_name')
                ->whereRaw('TRIM(sp.class_name) <> ""')
                ->distinct()
                ->pluck('sp.class_name')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->sort()
                ->values();
            $classOptions = $classOptions->merge($dynamicClasses)->unique()->values();
        }
        $selectedClass = trim((string) $request->query('class', 'ALL'));
        if ($selectedClass === '' || !$classOptions->contains($selectedClass)) {
            $selectedClass = 'ALL';
        }

        $students = (clone $studentsBase)
            ->when($hasStudentProfiles && $selectedClass !== 'ALL', function ($query) use ($selectedClass) {
                $query->whereRaw('TRIM(COALESCE(sp.class_name, "")) = ?', [$selectedClass]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('s.name', 'like', '%' . $search . '%')
                        ->orWhere('s.nis', 'like', '%' . $search . '%');
                });
            })
            ->select(
                's.id as student_id',
                's.name as student_name',
                's.nis as student_nis',
                DB::raw($hasStudentProfiles ? 'sp.major_name as major_name' : 'NULL as major_name'),
                DB::raw($hasStudentProfiles ? 'sp.class_name as class_name' : 'NULL as class_name')
            )
            ->orderBy('s.name')
            ->paginate(20)
            ->withQueryString();

        $studentIds = $students->getCollection()->pluck('student_id')->map(fn ($id) => (int) $id)->filter()->values();
        $journalByStudent = collect();
        if ($studentIds->isNotEmpty()) {
            $journalRows = DB::table('weekly_journals as wj')
                ->leftJoin('users as m', 'm.id', '=', 'wj.mentor_id')
                ->leftJoin('users as k', 'k.id', '=', 'wj.kajur_id')
                ->leftJoin('users as b', 'b.id', '=', 'wj.bindo_id')
                ->whereIn('wj.student_id', $studentIds)
                ->whereDate('wj.week_start_date', $weekStart)
                ->whereDate('wj.week_end_date', $weekEnd)
                ->select(
                    'wj.id',
                    'wj.student_id',
                    'wj.learning_notes',
                    'wj.student_mentor_notes',
                    'wj.mentor_is_correct',
                    'wj.missing_info_notes',
                    'wj.kajur_notes',
                    'wj.bindo_notes',
                    'wj.status',
                    'm.name as mentor_name',
                    'k.name as kajur_name',
                    'b.name as bindo_name'
                )
                ->orderByDesc('wj.id')
                ->get();
            $journalByStudent = $journalRows->unique('student_id')->keyBy('student_id');
        }

        $students->setCollection(
            $students->getCollection()->map(function ($student) use ($journalByStudent) {
                $journal = $journalByStudent->get((int) $student->student_id);
                $student->journal_id = data_get($journal, 'id');
                $student->journal_status = data_get($journal, 'status');
                $student->learning_notes = data_get($journal, 'learning_notes');
                $student->student_mentor_notes = data_get($journal, 'student_mentor_notes');
                $student->mentor_is_correct = data_get($journal, 'mentor_is_correct');
                $student->missing_info_notes = data_get($journal, 'missing_info_notes');
                $student->kajur_notes = data_get($journal, 'kajur_notes');
                $student->bindo_notes = data_get($journal, 'bindo_notes');
                $student->mentor_name = data_get($journal, 'mentor_name');
                $student->kajur_name = data_get($journal, 'kajur_name');
                $student->bindo_name = data_get($journal, 'bindo_name');
                return $student;
            })->filter(function ($student) use ($statusFilter) {
                if ($statusFilter === 'all') {
                    return true;
                }
                if ($statusFilter === 'no_submission') {
                    return empty($student->journal_id);
                }
                return strtolower((string) ($student->journal_status ?? '')) === $statusFilter;
            })->values()
        );

        return view('dashboard.kajur-weekly-journals', [
            'students' => $students,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'managedMajor' => $managedMajor,
            'selectedMajor' => $selectedMajor,
            'selectedClass' => $selectedClass,
            'classOptions' => $classOptions,
            'statusFilter' => $statusFilter,
            'search' => $search,
        ]);
    })->name('dashboard.kajur.weekly-journal');

    Route::get('/dashboard/kajur/daily-checkin', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'weekly_journal', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $mapStart = $wibNow->copy()->subDays(29)->toDateString();
        $selectedDateInput = trim((string) $request->query('date', $today));
        $selectedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateInput) ? $selectedDateInput : $today;

        $majorOptions = collect(['RPL', 'BDP', 'AKL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->where('major_name', '<>', '')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->values();
        }
        if ($majorOptions->isEmpty()) {
            $majorOptions = collect(['RPL']);
        }

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        if ($managedMajor === '' || !$majorOptions->contains($managedMajor)) {
            $managedMajor = (string) $majorOptions->first();
        }

        $hasStudentProfiles = Schema::hasTable('student_profiles');
        $studentsBase = DB::table('users as s')
            ->where('s.role', User::ROLE_STUDENT);
        if ($hasStudentProfiles) {
            $studentsBase->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id');
            $studentsBase->whereRaw('UPPER(COALESCE(sp.major_name, "")) = ?', [$managedMajor]);
        }

        $classOptions = collect(['ALL']);
        if ($hasStudentProfiles) {
            $dynamicClasses = (clone $studentsBase)
                ->whereNotNull('sp.class_name')
                ->whereRaw('TRIM(sp.class_name) <> ""')
                ->distinct()
                ->pluck('sp.class_name')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->sort()
                ->values();
            $classOptions = $classOptions->merge($dynamicClasses)->unique()->values();
        }

        $selectedClass = trim((string) $request->query('class', 'ALL'));
        if ($selectedClass === '' || !$classOptions->contains($selectedClass)) {
            $selectedClass = 'ALL';
        }
        $effectiveClassScope = $selectedClass === 'ALL' ? null : $selectedClass;
        $isSelectedDateNonWorking = attendance_is_non_working_day($selectedDate, $managedMajor, $effectiveClassScope);
        $calendarException = null;
        if (attendance_calendar_storage_ready()) {
            $calendarException = DB::table('attendance_calendar_exceptions')
                ->whereDate('exception_date', $selectedDate)
                ->where(function ($query) use ($managedMajor, $effectiveClassScope) {
                    $query->where(function ($global) {
                        $global->whereNull('major_name')->whereNull('class_name');
                    })->orWhere(function ($majorOnly) use ($managedMajor) {
                        $majorOnly->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$managedMajor])
                            ->whereNull('class_name');
                    });
                    if ($effectiveClassScope !== null) {
                        $query->orWhere(function ($majorAndClass) use ($managedMajor, $effectiveClassScope) {
                            $majorAndClass->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$managedMajor])
                                ->whereRaw('TRIM(COALESCE(class_name, "")) = ?', [$effectiveClassScope]);
                        });
                    }
                })
                ->orderByDesc('id')
                ->first();
        }

        $students = (clone $studentsBase)
            ->when($hasStudentProfiles && $selectedClass !== 'ALL', function ($query) use ($selectedClass) {
                $query->whereRaw('TRIM(COALESCE(sp.class_name, "")) = ?', [$selectedClass]);
            })
            ->select(
                's.id as student_id',
                's.name as student_name',
                's.nis as student_nis',
                DB::raw($hasStudentProfiles ? 'sp.class_name as class_name' : 'NULL as class_name'),
                DB::raw($hasStudentProfiles ? 'sp.pkl_place_name as pkl_place_name' : 'NULL as pkl_place_name')
            )
            ->orderBy('s.name')
            ->get();

        $studentIds = $students->pluck('student_id')->map(fn ($id) => (int) $id)->filter()->values();

        $attendanceByStudent = collect();
        if (Schema::hasTable('attendances') && $studentIds->isNotEmpty()) {
            $attendanceRows = DB::table('attendances')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $selectedDate)
                ->orderByDesc('check_in_at')
                ->orderByDesc('created_at')
                ->select(
                    'student_id',
                    'attendance_date',
                    'check_in_at',
                    'check_out_at',
                    'latitude',
                    'longitude',
                    'photo_path',
                    'status'
                )
                ->get();
            $attendanceByStudent = $attendanceRows->unique('student_id')->keyBy('student_id');
        }
        $absenceByStudent = collect();
        if (Schema::hasTable('attendance_excuses') && $studentIds->isNotEmpty()) {
            $absenceRows = DB::table('attendance_excuses')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $selectedDate)
                ->orderByDesc('updated_at')
                ->get([
                    'student_id',
                    'absence_type',
                    'status',
                    'reason',
                    'attachment_path',
                    'rejection_notes',
                ]);
            $absenceByStudent = $absenceRows->unique('student_id')->keyBy('student_id');
        }

        $dailyCheckins = $students->map(function ($student) use ($attendanceByStudent, $absenceByStudent, $selectedDate, $today, $isSelectedDateNonWorking) {
            $attendance = $attendanceByStudent->get($student->student_id);
            $absence = $absenceByStudent->get($student->student_id);
            $checkInAt = data_get($attendance, 'check_in_at');
            $checkOutAt = data_get($attendance, 'check_out_at');
            $statusLabel = 'No Check-in';
            $absenceStatus = strtolower((string) data_get($absence, 'status', ''));
            $absenceType = strtolower((string) data_get($absence, 'absence_type', ''));

            if ($checkInAt) {
                $statusLabel = $checkOutAt ? 'Checked Out' : 'Checked In';
            } elseif ($isSelectedDateNonWorking) {
                $statusLabel = 'School Off';
            } elseif ($absenceStatus === 'approved' && in_array($absenceType, ['sick', 'permit'], true)) {
                $statusLabel = 'Excused (' . strtoupper($absenceType) . ')';
            } elseif ($absenceStatus === 'pending') {
                $statusLabel = 'Excuse Pending';
            } elseif ($selectedDate < $today) {
                $statusLabel = 'Alpha';
            }

            $student->attendance_status = $statusLabel;
            $student->check_in_at = $checkInAt;
            $student->check_out_at = $checkOutAt;
            $student->attendance_status_raw = data_get($attendance, 'status');
            $student->late_minutes = (int) data_get($attendance, 'late_minutes', 0);
            $student->location = (!is_null(data_get($attendance, 'latitude')) && !is_null(data_get($attendance, 'longitude')))
                ? ((string) data_get($attendance, 'latitude') . ', ' . (string) data_get($attendance, 'longitude'))
                : '-';
            $photoPath = trim((string) data_get($attendance, 'photo_path', ''));
            $student->photo_url = $photoPath !== '' ? Storage::url($photoPath) : null;
            $student->absence_status = $absenceStatus !== '' ? $absenceStatus : null;
            $student->absence_type = $absenceType !== '' ? $absenceType : null;
            $student->absence_reason = data_get($absence, 'reason');
            $student->absence_attachment_url = !empty($absence?->attachment_path) ? Storage::url((string) $absence->attachment_path) : null;
            $student->absence_rejection_notes = data_get($absence, 'rejection_notes');
            return $student;
        });

        $pendingAbsenceRequests = collect();
        if (Schema::hasTable('attendance_excuses') && $studentIds->isNotEmpty()) {
            $pendingAbsenceRequests = DB::table('attendance_excuses as ae')
                ->join('users as s', 's.id', '=', 'ae.student_id')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 'ae.student_id')
                ->whereIn('ae.student_id', $studentIds)
                ->whereDate('ae.attendance_date', $selectedDate)
                ->where('ae.status', 'pending')
                ->orderBy('s.name')
                ->get([
                    'ae.id',
                    'ae.student_id',
                    'ae.attendance_date',
                    'ae.absence_type',
                    'ae.reason',
                    'ae.attachment_path',
                    's.name as student_name',
                    's.nis as student_nis',
                    DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                ])
                ->map(function ($row) {
                    $row->attachment_url = !empty($row->attachment_path)
                        ? Storage::url((string) $row->attachment_path)
                        : null;
                    return $row;
                });
        }

        $escalationAlerts = collect();
        if (
            !$isSelectedDateNonWorking
            && $selectedDate === $today
            && Carbon::now('Asia/Jakarta')->greaterThan(attendance_deadline_for_date($today))
        ) {
            $missingNoExcuse = $dailyCheckins->filter(function ($row) {
                return $row->attendance_status === 'No Check-in';
            })->count();
            $pendingExcuse = $dailyCheckins->filter(function ($row) {
                return $row->attendance_status === 'Excuse Pending';
            })->count();
            if ($missingNoExcuse > 0) {
                $escalationAlerts->push($missingNoExcuse . ' student(s) missed check-in deadline without approved excuse.');
            }
            if ($pendingExcuse > 0) {
                $escalationAlerts->push($pendingExcuse . ' student(s) have pending excuse requests after deadline.');
            }
        } elseif ($isSelectedDateNonWorking) {
            $escalationAlerts->push('Selected date is marked as non-working day. Alpha escalation is disabled for this date.');
        }
        $recentAlerts = attendance_fetch_recent_alerts($user, 8);

        $companyMapPoints = collect();
        $attendanceMapPoints = collect();
        $companyStudentsByCompany = [];
        if (Schema::hasTable('attendances') && $hasStudentProfiles && $studentIds->isNotEmpty()) {
            $companyMapPoints = DB::table('attendances as a')
                ->join('student_profiles as sp', 'sp.student_id', '=', 'a.student_id')
                ->whereIn('a.student_id', $studentIds)
                ->whereNotNull('sp.pkl_place_name')
                ->whereNotNull('a.latitude')
                ->whereNotNull('a.longitude')
                ->whereNotNull('a.check_in_at')
                ->whereDate('a.attendance_date', '>=', $mapStart)
                ->whereDate('a.attendance_date', '<=', $today)
                ->groupBy('sp.pkl_place_name', 'sp.pkl_place_address')
                ->selectRaw("
                    sp.pkl_place_name as company_name,
                    sp.pkl_place_address as company_address,
                    AVG(a.latitude) as latitude,
                    AVG(a.longitude) as longitude,
                    COUNT(DISTINCT a.student_id) as active_students
                ")
                ->get();

            $attendanceMapPoints = DB::table('attendances as a')
                ->join('student_profiles as sp', 'sp.student_id', '=', 'a.student_id')
                ->whereIn('a.student_id', $studentIds)
                ->whereNotNull('sp.pkl_place_name')
                ->whereNotNull('a.latitude')
                ->whereNotNull('a.longitude')
                ->whereNotNull('a.check_in_at')
                ->whereDate('a.attendance_date', '>=', $mapStart)
                ->whereDate('a.attendance_date', '<=', $today)
                ->groupBy('a.attendance_date', 'sp.pkl_place_name', 'sp.pkl_place_address')
                ->selectRaw("
                    a.attendance_date,
                    sp.pkl_place_name as company_name,
                    sp.pkl_place_address as company_address,
                    AVG(a.latitude) as latitude,
                    AVG(a.longitude) as longitude,
                    COUNT(DISTINCT a.student_id) as attendance_total
                ")
                ->get();

            $companyStudentsByCompany = DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->join('attendances as a', 'a.student_id', '=', 'sp.student_id')
                ->where('u.role', User::ROLE_STUDENT)
                ->whereIn('u.id', $studentIds)
                ->whereNotNull('sp.pkl_place_name')
                ->whereNotNull('a.latitude')
                ->whereNotNull('a.longitude')
                ->whereNotNull('a.check_in_at')
                ->whereDate('a.attendance_date', '>=', $mapStart)
                ->whereDate('a.attendance_date', '<=', $today)
                ->select(
                    'sp.pkl_place_name as company_name',
                    'sp.pkl_place_address as company_address',
                    'u.id as student_id',
                    'u.name as student_name',
                    'u.nis as student_nis',
                    'sp.major_name',
                    'a.check_in_at'
                )
                ->orderBy('u.name')
                ->get()
                ->groupBy(fn ($row) => trim((string) $row->company_name) . '||' . trim((string) ($row->company_address ?? '')))
                ->map(function ($rows) {
                    return $rows
                        ->sortByDesc('check_in_at')
                        ->unique('student_id')
                        ->values()
                        ->map(function ($row) {
                            return [
                                'student_name' => $row->student_name,
                                'student_nis' => $row->student_nis,
                                'major_name' => $row->major_name,
                                'check_in_at' => $row->check_in_at
                                    ? Carbon::parse($row->check_in_at, 'Asia/Jakarta')->format('d M Y H:i')
                                    : null,
                            ];
                        })
                        ->values();
                })
                ->toArray();
        }

        $attendanceByDate = collect();
        if (Schema::hasTable('attendances') && $studentIds->isNotEmpty()) {
            $attendanceByDate = DB::table('attendances')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', '>=', $mapStart)
                ->whereDate('attendance_date', '<=', $today)
                ->whereNotNull('check_in_at')
                ->groupBy('attendance_date')
                ->selectRaw('attendance_date, COUNT(DISTINCT student_id) as total')
                ->pluck('total', 'attendance_date');
        }

        $heatmap = collect();
        $maxAttendance = 0;
        for ($i = 29; $i >= 0; $i--) {
            $date = $wibNow->copy()->subDays($i)->toDateString();
            $total = (int) ($attendanceByDate[$date] ?? 0);
            $maxAttendance = max($maxAttendance, $total);
            $heatmap->push([
                'date' => $date,
                'total' => $total,
            ]);
        }

        return view('dashboard.kajur-daily-checkin', [
            'managedMajor' => $managedMajor,
            'selectedClass' => $selectedClass,
            'classOptions' => $classOptions,
            'selectedDate' => $selectedDate,
            'today' => $today,
            'dailyCheckins' => $dailyCheckins,
            'companyMapPoints' => $companyMapPoints,
            'attendanceMapPoints' => $attendanceMapPoints,
            'companyStudentsByCompany' => $companyStudentsByCompany,
            'heatmap' => $heatmap,
            'maxAttendance' => $maxAttendance,
            'pendingAbsenceRequests' => $pendingAbsenceRequests,
            'escalationAlerts' => $escalationAlerts,
            'checkInCutoffTime' => attendance_checkin_cutoff_time(),
            'calendarException' => $calendarException,
            'isSelectedDateNonWorking' => $isSelectedDateNonWorking,
            'recentAlerts' => $recentAlerts,
        ]);
    })->name('dashboard.kajur.daily-checkin');

    Route::post('/dashboard/kajur/attendance-calendar', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'weekly_journal', 'update');

        if (!attendance_calendar_storage_ready()) {
            return back()->withErrors(['attendance_calendar' => 'Attendance calendar table is not ready. Run migrations first.']);
        }

        $validated = $request->validate([
            'exception_date' => ['required', 'date'],
            'class_name' => ['nullable', 'string', 'max:120'],
            'exception_type' => ['required', 'in:holiday,school_off,company_off'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'action' => ['required', 'in:upsert,delete'],
        ]);

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        if ($managedMajor === '') {
            return back()->withErrors(['attendance_calendar' => 'Managed major is not set for this Kajur account.']);
        }

        $className = trim((string) ($validated['class_name'] ?? ''));
        $className = strtoupper($className) === 'ALL' ? '' : $className;

        if ($validated['action'] === 'delete') {
            DB::table('attendance_calendar_exceptions')
                ->whereDate('exception_date', $validated['exception_date'])
                ->where('exception_type', $validated['exception_type'])
                ->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$managedMajor])
                ->where(function ($query) use ($className) {
                    if ($className === '') {
                        $query->whereNull('class_name');
                    } else {
                        $query->whereRaw('TRIM(COALESCE(class_name, "")) = ?', [$className]);
                    }
                })
                ->delete();
            return back()->with('status', 'Attendance calendar exception removed.');
        }

        DB::table('attendance_calendar_exceptions')->updateOrInsert(
            [
                'exception_date' => $validated['exception_date'],
                'exception_type' => $validated['exception_type'],
                'major_name' => $managedMajor,
                'class_name' => $className !== '' ? $className : null,
            ],
            [
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'created_by_user_id' => $user->id,
                'updated_at' => now('Asia/Jakarta'),
                'created_at' => now('Asia/Jakarta'),
            ]
        );

        return back()->with('status', 'Attendance calendar exception saved.');
    })->name('dashboard.kajur.attendance-calendar');

    Route::post('/dashboard/kajur/absence-request/{absenceRequest}/review', function (Request $request, int $absenceRequest) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'weekly_journal', 'update');

        if (!Schema::hasTable('attendance_excuses')) {
            return back()->withErrors(['absence_review' => 'Absence request table is not ready. Run migrations first.']);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_notes' => [Rule::requiredIf(fn () => $request->input('action') === 'reject'), 'nullable', 'string', 'max:5000'],
        ]);

        $record = DB::table('attendance_excuses as ae')
            ->join('users as s', 's.id', '=', 'ae.student_id')
            ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 'ae.student_id')
            ->where('ae.id', $absenceRequest)
            ->select('ae.id', 'ae.status', DB::raw('UPPER(TRIM(COALESCE(sp.major_name, ""))) as major_name'))
            ->first();
        abort_unless($record, 404);

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        if ($managedMajor !== '') {
            abort_unless((string) ($record->major_name ?? '') === $managedMajor, 403);
        }

        $newStatus = $validated['action'] === 'approve' ? 'approved' : 'rejected';
        DB::table('attendance_excuses')
            ->where('id', $absenceRequest)
            ->update([
                'status' => $newStatus,
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => now('Asia/Jakarta'),
                'rejection_notes' => $newStatus === 'rejected' ? trim((string) ($validated['rejection_notes'] ?? '')) : null,
                'updated_at' => now('Asia/Jakarta'),
            ]);

        return back()->with('status', 'Absence request ' . $newStatus . ' successfully.');
    })->name('dashboard.kajur.absence-review');

    Route::post('/dashboard/kajur/attendance-correction/{student}', function (Request $request, int $student) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'weekly_journal', 'update');

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'correction_type' => ['required', 'in:present,late,excused_sick,excused_permit,alpha'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $studentRow = DB::table('users as s')
            ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->where('s.id', $student)
            ->where('s.role', User::ROLE_STUDENT)
            ->select('s.id', DB::raw('UPPER(TRIM(COALESCE(sp.major_name, ""))) as major_name'))
            ->first();
        abort_unless($studentRow, 404);

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        if ($managedMajor !== '') {
            abort_unless((string) ($studentRow->major_name ?? '') === $managedMajor, 403);
        }

        $date = Carbon::parse($validated['attendance_date'], 'Asia/Jakarta')->toDateString();
        $wibNow = now('Asia/Jakarta');
        $attendanceBefore = DB::table('attendances')
            ->where('student_id', $student)
            ->whereDate('attendance_date', $date)
            ->first();
        $excuseBefore = Schema::hasTable('attendance_excuses')
            ? DB::table('attendance_excuses')
                ->where('student_id', $student)
                ->whereDate('attendance_date', $date)
                ->first()
            : null;

        $checkInAt = null;
        $checkOutAt = null;
        if (!empty($validated['check_in_time'])) {
            $checkInAt = Carbon::parse($date . ' ' . $validated['check_in_time'] . ':00', 'Asia/Jakarta');
        }
        if (!empty($validated['check_out_time'])) {
            $checkOutAt = Carbon::parse($date . ' ' . $validated['check_out_time'] . ':00', 'Asia/Jakarta');
        }

        $correctionType = $validated['correction_type'];
        if (in_array($correctionType, ['present', 'late'], true)) {
            if (Schema::hasTable('attendance_excuses')) {
                DB::table('attendance_excuses')
                    ->where('student_id', $student)
                    ->whereDate('attendance_date', $date)
                    ->delete();
            }

            $payload = [
                'student_id' => $student,
                'attendance_date' => $date,
                'check_in_at' => $checkInAt ?? $wibNow,
                'check_out_at' => $checkOutAt,
                'status' => $correctionType,
                'updated_at' => $wibNow,
                'created_at' => $wibNow,
            ];
            if (Schema::hasColumn('attendances', 'late_minutes')) {
                $payload['late_minutes'] = $correctionType === 'late'
                    ? attendance_late_minutes(($checkInAt ?? $wibNow), $date)
                    : 0;
            }
            DB::table('attendances')->updateOrInsert(
                ['student_id' => $student, 'attendance_date' => $date],
                $payload
            );
        } elseif (in_array($correctionType, ['excused_sick', 'excused_permit'], true)) {
            DB::table('attendances')
                ->where('student_id', $student)
                ->whereDate('attendance_date', $date)
                ->delete();

            if (Schema::hasTable('attendance_excuses')) {
                $absenceType = $correctionType === 'excused_sick' ? 'sick' : 'permit';
                DB::table('attendance_excuses')->updateOrInsert(
                    [
                        'student_id' => $student,
                        'attendance_date' => $date,
                    ],
                    [
                        'absence_type' => $absenceType,
                        'reason' => trim((string) ($validated['notes'] ?? '')) ?: 'Manual correction by Kajur.',
                        'status' => 'approved',
                        'reviewed_by_user_id' => $user->id,
                        'reviewed_at' => $wibNow,
                        'rejection_notes' => null,
                        'updated_at' => $wibNow,
                        'created_at' => $wibNow,
                    ]
                );
            }
        } else {
            DB::table('attendances')
                ->where('student_id', $student)
                ->whereDate('attendance_date', $date)
                ->delete();
            if (Schema::hasTable('attendance_excuses')) {
                DB::table('attendance_excuses')
                    ->where('student_id', $student)
                    ->whereDate('attendance_date', $date)
                    ->delete();
            }
        }

        $attendanceAfter = DB::table('attendances')
            ->where('student_id', $student)
            ->whereDate('attendance_date', $date)
            ->first();
        $excuseAfter = Schema::hasTable('attendance_excuses')
            ? DB::table('attendance_excuses')
                ->where('student_id', $student)
                ->whereDate('attendance_date', $date)
                ->first()
            : null;

        if (Schema::hasTable('attendance_correction_logs')) {
            DB::table('attendance_correction_logs')->insert([
                'student_id' => $student,
                'attendance_date' => $date,
                'corrected_by_user_id' => $user->id,
                'correction_type' => $correctionType,
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'before_payload' => json_encode([
                    'attendance' => $attendanceBefore ? (array) $attendanceBefore : null,
                    'excuse' => $excuseBefore ? (array) $excuseBefore : null,
                ], JSON_UNESCAPED_UNICODE),
                'after_payload' => json_encode([
                    'attendance' => $attendanceAfter ? (array) $attendanceAfter : null,
                    'excuse' => $excuseAfter ? (array) $excuseAfter : null,
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => $wibNow,
                'updated_at' => $wibNow,
            ]);
        }

        return back()->with('status', 'Attendance correction saved.');
    })->name('dashboard.kajur.attendance-correction');

    Route::get('/dashboard/kajur/absence-report', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'weekly_journal', 'view');

        $wibNow = now('Asia/Jakarta');
        $defaultStart = $wibNow->copy()->subDays(29)->toDateString();
        $defaultEnd = $wibNow->toDateString();
        $startDate = trim((string) $request->query('start', $defaultStart));
        $endDate = trim((string) $request->query('end', $defaultEnd));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = $defaultStart;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = $defaultEnd;
        }
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        if ($managedMajor === '') {
            return back()->withErrors(['absence_report' => 'Managed major is not set for this Kajur account.']);
        }

        $classOptions = collect(['ALL']);
        if (Schema::hasTable('student_profiles')) {
            $classOptions = $classOptions->merge(
                DB::table('student_profiles')
                    ->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$managedMajor])
                    ->whereNotNull('class_name')
                    ->whereRaw('TRIM(class_name) <> ""')
                    ->distinct()
                    ->pluck('class_name')
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->sort()
                    ->values()
            )->unique()->values();
        }
        $selectedClass = trim((string) $request->query('class', 'ALL'));
        if (!$classOptions->contains($selectedClass)) {
            $selectedClass = 'ALL';
        }
        $classScope = $selectedClass === 'ALL' ? null : $selectedClass;

        $students = DB::table('users as s')
            ->join('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->where('s.role', User::ROLE_STUDENT)
            ->whereRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) = ?', [$managedMajor])
            ->when($classScope !== null, function ($query) use ($classScope) {
                $query->whereRaw('TRIM(COALESCE(sp.class_name, "")) = ?', [$classScope]);
            })
            ->select('s.id', 's.name', 's.nis', 'sp.class_name')
            ->orderBy('s.name')
            ->get();

        $studentIds = $students->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();

        $attendanceByStudentDate = collect();
        if (Schema::hasTable('attendances') && $studentIds->isNotEmpty()) {
            $attendanceByStudentDate = DB::table('attendances')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', '>=', $startDate)
                ->whereDate('attendance_date', '<=', $endDate)
                ->whereNotNull('check_in_at')
                ->get(['student_id', 'attendance_date'])
                ->groupBy(fn ($row) => (int) $row->student_id);
        }

        $approvedExcuseByStudentDate = collect();
        if (Schema::hasTable('attendance_excuses') && $studentIds->isNotEmpty()) {
            $approvedExcuseByStudentDate = DB::table('attendance_excuses')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', '>=', $startDate)
                ->whereDate('attendance_date', '<=', $endDate)
                ->where('status', 'approved')
                ->get(['student_id', 'attendance_date'])
                ->groupBy(fn ($row) => (int) $row->student_id);
        }

        $rows = $students->map(function ($student) use ($startDate, $endDate, $attendanceByStudentDate, $approvedExcuseByStudentDate, $managedMajor) {
            $cursor = Carbon::parse($startDate, 'Asia/Jakarta')->startOfDay();
            $endCursor = Carbon::parse($endDate, 'Asia/Jakarta')->startOfDay();
            $workingDays = 0;
            $presentDays = 0;
            $excusedDays = 0;
            $attendanceSet = $attendanceByStudentDate->get((int) $student->id, collect())->pluck('attendance_date')->flip();
            $excusedSet = $approvedExcuseByStudentDate->get((int) $student->id, collect())->pluck('attendance_date')->flip();
            $className = trim((string) ($student->class_name ?? ''));

            while ($cursor->lessThanOrEqualTo($endCursor)) {
                $date = $cursor->toDateString();
                if (!in_array($cursor->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY], true)) {
                    if (!attendance_is_non_working_day($date, $managedMajor, $className !== '' ? $className : null)) {
                        $workingDays++;
                        if ($attendanceSet->has($date)) {
                            $presentDays++;
                        } elseif ($excusedSet->has($date)) {
                            $excusedDays++;
                        }
                    }
                }
                $cursor->addDay();
            }

            $alphaDays = max(0, $workingDays - $presentDays - $excusedDays);
            $absenceDays = $excusedDays + $alphaDays;
            $absenceRate = $workingDays > 0 ? round(($absenceDays / $workingDays) * 100, 2) : 0;

            return (object) [
                'student_id' => (int) $student->id,
                'student_name' => $student->name,
                'student_nis' => $student->nis,
                'class_name' => $student->class_name ?: '-',
                'working_days' => $workingDays,
                'present_days' => $presentDays,
                'excused_days' => $excusedDays,
                'alpha_days' => $alphaDays,
                'absence_days' => $absenceDays,
                'absence_rate' => $absenceRate,
            ];
        })->sortByDesc('absence_rate')->values();

        $summary = [
            'students' => $rows->count(),
            'working_days_total' => (int) $rows->sum('working_days'),
            'present_total' => (int) $rows->sum('present_days'),
            'excused_total' => (int) $rows->sum('excused_days'),
            'alpha_total' => (int) $rows->sum('alpha_days'),
            'absence_total' => (int) $rows->sum('absence_days'),
        ];
        $summary['absence_rate'] = $summary['working_days_total'] > 0
            ? round(($summary['absence_total'] / $summary['working_days_total']) * 100, 2)
            : 0;

        if (strtolower(trim((string) $request->query('export', ''))) === 'csv') {
            $filename = 'kajur-absence-report-' . $startDate . '-to-' . $endDate . '.csv';
            $handle = fopen('php://temp', 'r+');
            fputcsv($handle, ['Kajur Absence Rate Report']);
            fputcsv($handle, ['Major', $managedMajor]);
            fputcsv($handle, ['Class Scope', $selectedClass]);
            fputcsv($handle, ['Period', $startDate . ' to ' . $endDate]);
            fputcsv($handle, []);
            fputcsv($handle, ['Student', 'NIS', 'Class', 'Working Days', 'Present', 'Excused', 'Alpha', 'Absence Days', 'Absence Rate %']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->student_name,
                    $row->student_nis,
                    $row->class_name,
                    $row->working_days,
                    $row->present_days,
                    $row->excused_days,
                    $row->alpha_days,
                    $row->absence_days,
                    $row->absence_rate,
                ]);
            }
            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        return view('dashboard.kajur-absence-report', [
            'managedMajor' => $managedMajor,
            'selectedClass' => $selectedClass,
            'classOptions' => $classOptions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rows' => $rows,
            'summary' => $summary,
        ]);
    })->name('dashboard.kajur.absence-report');

    Route::post('/dashboard/kajur/daily-log/{log}/academic-validation', function (Request $request, int $log) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'weekly_journal', 'update');

        if (!Schema::hasTable('daily_logs')) {
            return back()->withErrors(['academic_validation' => 'Daily logs table is not ready.']);
        }

        $validated = $request->validate([
            'validation_status' => ['required', 'in:valid,revise'],
            'kajur_feedback' => [Rule::requiredIf(fn () => $request->input('validation_status') === 'revise'), 'nullable', 'string', 'max:7000'],
        ]);

        $hasStudentProfiles = Schema::hasTable('student_profiles');
        $dailyLog = DB::table('daily_logs as dl')
            ->join('users as s', 's.id', '=', 'dl.student_id')
            ->when($hasStudentProfiles, function ($query) {
                $query->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id');
            })
            ->where('dl.id', $log)
            ->where('s.role', User::ROLE_STUDENT)
            ->select('dl.id', DB::raw($hasStudentProfiles ? 'sp.major_name as major_name' : 'NULL as major_name'))
            ->first();
        abort_unless($dailyLog, 404);

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        if ($managedMajor !== '') {
            $studentMajor = strtoupper(trim((string) ($dailyLog->major_name ?? '')));
            abort_unless($studentMajor === $managedMajor, 403);
        }

        $wibNow = now('Asia/Jakarta');
        $feedbackNote = trim((string) ($validated['kajur_feedback'] ?? ''));
        $feedbackPrefix = $validated['validation_status'] === 'valid'
            ? 'Academic validation: MATCHED with school competency.'
            : 'Academic validation: NEEDS REVISION.';
        $feedbackText = trim($feedbackPrefix . ' ' . $feedbackNote);

        $payload = [
            'kajur_feedback' => $feedbackText,
            'reviewed_at' => $wibNow,
            'updated_at' => $wibNow,
        ];
        if (Schema::hasColumn('daily_logs', 'kajur_id')) {
            $payload['kajur_id'] = $user->id;
        }
        if (Schema::hasColumn('daily_logs', 'mentor_review_status')) {
            $payload['mentor_review_status'] = $validated['validation_status'] === 'valid' ? 'approved' : 'revise';
        }

        DB::table('daily_logs')
            ->where('id', $log)
            ->update($payload);

        return back()->with('status', 'Academic validation saved.');
    })->name('dashboard.kajur.daily-log.academic-validation');

    Route::post('/dashboard/kajur/weekly-journal/{journal}', function (Request $request, int $journal) {
        $user = $request->user();
        abort_unless($user->role === 'kajur', 403);
        require_user_permission($user, 'weekly_journal', 'update');

        $validated = $request->validate([
            'kajur_notes' => ['nullable', 'string', 'max:7000'],
        ]);

        $wibNow = Carbon::now('Asia/Jakarta');

        DB::table('weekly_journals')
            ->where('id', $journal)
            ->update([
                'kajur_id' => $user->id,
                'kajur_notes' => $validated['kajur_notes'] ?? null,
                'kajur_reviewed_at' => $wibNow,
                'updated_at' => $wibNow,
            ]);

        return back()->with('status', 'Kajur notes saved.');
    })->name('dashboard.kajur.weekly-journal.note');

    Route::get('/dashboard/bindo/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        require_user_permission($user, 'weekly_journal', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $supervisedStudentIds = teacher_supervised_student_ids($user);

        $assignedStudents = collect();
        if ($supervisedStudentIds->isNotEmpty()) {
            $assignedStudents = DB::table('users as s')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
                ->where('s.role', User::ROLE_STUDENT)
                ->whereIn('s.id', $supervisedStudentIds)
                ->select(
                    's.id',
                    's.name',
                    's.nis',
                    's.avatar_url',
                    DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_name), ""), "-") as pkl_place_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_address), ""), "-") as pkl_place_address'),
                    'sp.pkl_place_phone'
                )
                ->orderBy('s.name')
                ->get();
        }

        $latestAttendanceByStudent = collect();
        if (Schema::hasTable('attendances') && $supervisedStudentIds->isNotEmpty()) {
            $attendanceRows = DB::table('attendances')
                ->whereIn('student_id', $supervisedStudentIds)
                ->orderByDesc('attendance_date')
                ->orderByDesc(DB::raw('COALESCE(check_in_at, created_at)'))
                ->select(
                    'student_id',
                    'attendance_date',
                    'check_in_at',
                    'latitude',
                    'longitude',
                    'photo_path'
                )
                ->get();

            $latestAttendanceByStudent = $attendanceRows->unique('student_id')->keyBy('student_id');
        }

        $companyPhones = collect();
        if ($assignedStudents->isNotEmpty() && Schema::hasTable('partner_companies')) {
            $companyRows = DB::table('partner_companies')
                ->where('is_active', true)
                ->select('name', 'address', 'contact_person', 'contact_phone')
                ->get();

            $companyPhones = $companyRows->keyBy(function ($row) {
                $name = trim((string) ($row->name ?? ''));
                $address = trim((string) ($row->address ?? '-'));
                if ($address === '') {
                    $address = '-';
                }
                return mb_strtolower($name . '||' . $address);
            });
        }

        $assignedStudents = $assignedStudents->map(function ($student) use ($latestAttendanceByStudent, $companyPhones) {
            $attendance = $latestAttendanceByStudent->get($student->id);

            $student->latest_photo_url = null;
            if (!empty($attendance?->photo_path)) {
                $student->latest_photo_url = Storage::url($attendance->photo_path);
            } elseif (!empty($student->avatar_url)) {
                $student->latest_photo_url = Str::startsWith($student->avatar_url, ['http://', 'https://'])
                    ? $student->avatar_url
                    : Storage::url($student->avatar_url);
            }

            $latitude = data_get($attendance, 'latitude');
            $longitude = data_get($attendance, 'longitude');
            $student->latest_location = ($latitude !== null && $longitude !== null)
                ? ((string) $latitude . ', ' . (string) $longitude)
                : '-';
            $student->latest_attendance_date = data_get($attendance, 'attendance_date');

            $companyKey = mb_strtolower(
                trim((string) ($student->pkl_place_name ?? '-'))
                . '||' .
                (trim((string) ($student->pkl_place_address ?? '-')) ?: '-')
            );
            $company = $companyPhones->get($companyKey);
            $student->mentor_contact_name = trim((string) data_get($company, 'contact_person', '')) ?: ($student->pkl_place_name ?? 'Industry Mentor');
            $student->mentor_contact_phone = trim((string) data_get($company, 'contact_phone', '')) ?: trim((string) ($student->pkl_place_phone ?? ''));
            $student->mentor_whatsapp_url = normalize_whatsapp_number($student->mentor_contact_phone);

            return $student;
        });

        $rows = collect();
        if ($supervisedStudentIds->isNotEmpty()) {
            $rows = DB::table('weekly_journals as wj')
                ->join('users as s', 's.id', '=', 'wj.student_id')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
                ->whereIn('wj.student_id', $supervisedStudentIds)
                ->select(
                    'wj.id',
                    'wj.week_start_date',
                    'wj.week_end_date',
                    'wj.learning_notes',
                    'wj.student_mentor_notes',
                    'wj.bindo_notes',
                    'wj.status',
                    's.name as student_name',
                    's.nis as student_nis',
                    DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name')
                )
                ->orderByDesc('wj.week_start_date')
                ->orderBy('s.name')
                ->limit(60)
                ->get();
        }

        $visitLogs = collect();
        $visitLogReady = Schema::hasTable('teacher_site_visits');
        if ($visitLogReady) {
            $visitLogs = DB::table('teacher_site_visits as tsv')
                ->join('users as s', 's.id', '=', 'tsv.student_id')
                ->where('tsv.teacher_id', $user->id)
                ->select(
                    'tsv.id',
                    'tsv.photo_path',
                    'tsv.visit_notes',
                    'tsv.company_name',
                    'tsv.company_address',
                    'tsv.visited_at',
                    's.name as student_name',
                    's.nis as student_nis'
                )
                ->orderByDesc('tsv.visited_at')
                ->limit(20)
                ->get()
                ->map(function ($row) {
                    $row->photo_url = !empty($row->photo_path) ? Storage::url($row->photo_path) : null;
                    return $row;
                });
        }

        $selectedStudentId = (int) $request->query('student_id', 0);
        if (!$assignedStudents->pluck('id')->contains($selectedStudentId)) {
            $selectedStudentId = (int) data_get($assignedStudents->first(), 'id', 0);
        }

        return view('dashboard.bindo-weekly-journal', [
            'assignedStudents' => $assignedStudents,
            'rows' => $rows,
            'visitLogs' => $visitLogs,
            'visitLogReady' => $visitLogReady,
            'selectedStudentId' => $selectedStudentId,
            'today' => $wibNow->toDateString(),
        ]);
    })->name('dashboard.bindo.weekly-journal');

    Route::post('/dashboard/bindo/weekly-journal/{journal}', function (Request $request, int $journal) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        require_user_permission($user, 'weekly_journal', 'update');

        $supervisedStudentIds = teacher_supervised_student_ids($user);
        $targetJournal = DB::table('weekly_journals')
            ->where('id', $journal)
            ->first(['id', 'student_id']);
        abort_unless($targetJournal && $supervisedStudentIds->contains((int) $targetJournal->student_id), 404);

        $validated = $request->validate([
            'teacher_comment' => ['nullable', 'string', 'max:7000'],
            'bindo_notes' => ['nullable', 'string', 'max:7000'],
        ]);

        $teacherComment = trim((string) ($validated['teacher_comment'] ?? $validated['bindo_notes'] ?? ''));
        $wibNow = Carbon::now('Asia/Jakarta');

        DB::table('weekly_journals')
            ->where('id', $journal)
            ->update([
                'bindo_id' => $user->id,
                'bindo_notes' => $teacherComment !== '' ? $teacherComment : null,
                'bindo_reviewed_at' => $wibNow,
                'updated_at' => $wibNow,
            ]);

        return back()->with('status', 'Teacher comment saved.');
    })->name('dashboard.bindo.weekly-journal.note');

    Route::post('/dashboard/bindo/site-visit', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        require_user_permission($user, 'weekly_journal', 'create');

        if (!Schema::hasTable('teacher_site_visits')) {
            return back()->withErrors([
                'site_visit' => 'Visit log table is not ready yet. Run migrations first.',
            ]);
        }

        $supervisedStudentIds = teacher_supervised_student_ids($user);
        $validated = $request->validate([
            'student_id' => ['required', Rule::in($supervisedStudentIds->all())],
            'visited_at' => ['required', 'date'],
            'visit_notes' => ['nullable', 'string', 'max:7000'],
            'visit_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $studentProfile = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles')
                ->where('student_id', (int) $validated['student_id'])
                ->first(['pkl_place_name', 'pkl_place_address'])
            : null;

        $photoPath = $request->file('visit_photo')->store('teacher-site-visits', 'public');
        DB::table('teacher_site_visits')->insert([
            'teacher_id' => $user->id,
            'student_id' => (int) $validated['student_id'],
            'company_name' => trim((string) data_get($studentProfile, 'pkl_place_name', '')) ?: null,
            'company_address' => trim((string) data_get($studentProfile, 'pkl_place_address', '')) ?: null,
            'photo_path' => $photoPath,
            'visit_notes' => trim((string) ($validated['visit_notes'] ?? '')) ?: null,
            'visited_at' => Carbon::parse($validated['visited_at'], 'Asia/Jakarta'),
            'created_at' => now('Asia/Jakarta'),
            'updated_at' => now('Asia/Jakarta'),
        ]);

        return redirect()->route('dashboard.bindo.weekly-journal', [
            'student_id' => (int) $validated['student_id'],
        ])->with('status', 'Site visit recorded successfully.');
    })->name('dashboard.bindo.site-visit.store');

    Route::get('/dashboard/principal/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'principal', 403);
        require_user_permission($user, 'weekly_journal', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $payload = build_principal_dashboard_payload($weekStart, $weekEnd, $today);
        $absenceOverview = [
            'pending_today' => 0,
            'approved_today' => 0,
            'rejected_today' => 0,
            'alpha_students_today' => 0,
        ];
        if (Schema::hasTable('attendance_excuses')) {
            $absenceByStatus = DB::table('attendance_excuses')
                ->whereDate('attendance_date', $today)
                ->groupBy('status')
                ->selectRaw('status, COUNT(*) as total')
                ->pluck('total', 'status');
            $absenceOverview['pending_today'] = (int) ($absenceByStatus['pending'] ?? 0);
            $absenceOverview['approved_today'] = (int) ($absenceByStatus['approved'] ?? 0);
            $absenceOverview['rejected_today'] = (int) ($absenceByStatus['rejected'] ?? 0);
        }
        if (Schema::hasTable('attendances')) {
            $studentIds = DB::table('users')->where('role', User::ROLE_STUDENT)->pluck('id');
            $checkedInIds = DB::table('attendances')
                ->whereDate('attendance_date', $today)
                ->whereNotNull('check_in_at')
                ->pluck('student_id');
            $approvedExcusedIds = Schema::hasTable('attendance_excuses')
                ? DB::table('attendance_excuses')
                    ->whereDate('attendance_date', $today)
                    ->where('status', 'approved')
                    ->pluck('student_id')
                : collect();
            $alphaCandidateIds = $studentIds->diff($checkedInIds)->diff($approvedExcusedIds)->values();
            $absenceOverview['alpha_students_today'] = (int) $alphaCandidateIds->count();
        }
        $recentAlerts = attendance_fetch_recent_alerts($user, 10);

        return view('dashboard.principal-weekly-journal', array_merge($payload, [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'absenceOverview' => $absenceOverview,
            'recentAlerts' => $recentAlerts,
        ]));
    })->name('dashboard.principal.weekly-journal');

    Route::get('/dashboard/principal/master-report', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'principal', 403);
        require_user_permission($user, 'weekly_journal', 'view');

        $format = strtolower(trim((string) $request->query('format', 'excel')));
        if (!in_array($format, ['excel', 'pdf'], true)) {
            $format = 'excel';
        }

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $payload = build_principal_dashboard_payload($weekStart, $weekEnd, $today);

        if ($format === 'pdf') {
            return response()
                ->view('dashboard.principal-master-report-pdf', array_merge($payload, [
                    'weekStart' => $weekStart,
                    'weekEnd' => $weekEnd,
                ]));
        }

        $filenameDate = Carbon::parse($today, 'Asia/Jakarta')->format('Ymd');
        $filename = "principal-master-report-{$filenameDate}.csv";

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Master Report - School PKL']);
        fputcsv($handle, ['Generated At', Carbon::now('Asia/Jakarta')->format('d M Y H:i') . ' WIB']);
        fputcsv($handle, ['Season Window', Carbon::parse($payload['seasonStart'], 'Asia/Jakarta')->format('d M Y') . ' - ' . Carbon::parse($payload['today'], 'Asia/Jakarta')->format('d M Y')]);
        fputcsv($handle, []);

        fputcsv($handle, ['Executive Summary']);
        fputcsv($handle, ['Total Students in School', (int) ($payload['totalStudentsInSchool'] ?? 0)]);
        fputcsv($handle, ['Total Students Placed', (int) ($payload['totalStudentsPlaced'] ?? 0)]);
        fputcsv($handle, []);

        fputcsv($handle, ['Top 5 Industry Partners']);
        fputcsv($handle, ['Company', 'Address', 'Students']);
        foreach (($payload['topIndustryPartners'] ?? collect()) as $partner) {
            fputcsv($handle, [
                (string) ($partner->company_name ?? '-'),
                (string) ($partner->company_address ?? '-'),
                (int) ($partner->total_students ?? 0),
            ]);
        }
        fputcsv($handle, []);

        fputcsv($handle, ['Department Attendance Comparison (Last 30 Days)']);
        fputcsv($handle, ['Department', 'Students', 'Checked Student-Days', 'Attendance Rate %']);
        foreach (($payload['departmentAttendance'] ?? collect()) as $dept) {
            fputcsv($handle, [
                (string) data_get($dept, 'label', '-'),
                (int) data_get($dept, 'students', 0),
                (int) data_get($dept, 'checked_days', 0),
                (float) data_get($dept, 'rate', 0),
            ]);
        }
        fputcsv($handle, []);

        fputcsv($handle, ['MOU Tracker']);
        fputcsv($handle, ['Company', 'Address', 'Contact', 'Phone', 'Expiry Date', 'Source']);
        foreach (($payload['mouTracker'] ?? collect()) as $row) {
            fputcsv($handle, [
                (string) data_get($row, 'company_name', '-'),
                (string) data_get($row, 'company_address', '-'),
                (string) data_get($row, 'contact_person', '-'),
                (string) data_get($row, 'contact_phone', '-'),
                data_get($row, 'expiry_date')
                    ? Carbon::parse((string) data_get($row, 'expiry_date'), 'Asia/Jakarta')->format('Y-m-d')
                    : '-',
                (string) data_get($row, 'expiry_source', '-'),
            ]);
        }
        fputcsv($handle, []);

        fputcsv($handle, ['Student Placement List']);
        fputcsv($handle, ['Student', 'NIS', 'Major', 'Class', 'Company', 'Company Address', 'PKL Start', 'PKL End']);
        foreach (($payload['placementRows'] ?? collect()) as $row) {
            fputcsv($handle, [
                (string) ($row->student_name ?? '-'),
                (string) ($row->student_nis ?? '-'),
                (string) ($row->major_name ?? '-'),
                (string) ($row->class_name ?? '-'),
                (string) ($row->company_name ?? '-'),
                (string) ($row->company_address ?? '-'),
                (string) ($row->pkl_start_date ?? '-'),
                (string) ($row->pkl_end_date ?? '-'),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    })->name('dashboard.principal.master-report');

    $principalPlaceholderPage = function (Request $request, string $pageTitle, string $pageDescription, string $activePage) {
        $user = $request->user();
        abort_unless($user->role === 'principal', 403);
        require_user_permission($user, 'weekly_journal', 'view');

        return view('dashboard.principal-placeholder', [
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'activePage' => $activePage,
        ]);
    };

    Route::get('/dashboard/principal/master-report-page', function (Request $request) use ($principalPlaceholderPage) {
        return $principalPlaceholderPage(
            $request,
            'Master Report',
            'Export center and formal principal reporting page.',
            'master-report'
        );
    })->name('dashboard.principal.master-report-page');

    Route::get('/dashboard/principal/attendance-alerts', function (Request $request) use ($principalPlaceholderPage) {
        return $principalPlaceholderPage(
            $request,
            'Attendance Alerts',
            'School-wide attendance risk monitoring and unresolved check-in issues.',
            'attendance-alerts'
        );
    })->name('dashboard.principal.attendance-alerts');

    Route::get('/dashboard/principal/partner-companies', function (Request $request) use ($principalPlaceholderPage) {
        return $principalPlaceholderPage(
            $request,
            'Partner Companies',
            'Overview of active PKL partners, MOU coverage, and placement spread.',
            'partner-companies'
        );
    })->name('dashboard.principal.partner-companies');

    Route::get('/dashboard/principal/journal-oversight', function (Request $request) use ($principalPlaceholderPage) {
        return $principalPlaceholderPage(
            $request,
            'Weekly Journal Oversight',
            'Monitoring page for submission, review, and follow-up completion.',
            'journal-oversight'
        );
    })->name('dashboard.principal.journal-oversight');

    Route::get('/dashboard/principal/school-performance', function (Request $request) use ($principalPlaceholderPage) {
        return $principalPlaceholderPage(
            $request,
            'School Performance',
            'Department-level attendance, completion, and placement quality comparisons.',
            'school-performance'
        );
    })->name('dashboard.principal.school-performance');

    Route::get('/dashboard/principal/timeline', function (Request $request) use ($principalPlaceholderPage) {
        return $principalPlaceholderPage(
            $request,
            'Timeline / PKL Status',
            'Season progress, milestone status, and principal-facing deadline visibility.',
            'timeline'
        );
    })->name('dashboard.principal.timeline');

    Route::get('/dashboard/super-admin', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'super_admin_dashboard', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $mapStart = $wibNow->copy()->subDays(29)->toDateString();

        $companyStats = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->where('u.role', 'student')
                ->whereNotNull('sp.pkl_place_name')
                ->whereDate('sp.pkl_start_date', '<=', $today)
                ->whereDate('sp.pkl_end_date', '>=', $today)
                ->groupBy('sp.pkl_place_name', 'sp.pkl_place_address')
                ->selectRaw("
                    sp.pkl_place_name as company_name,
                    sp.pkl_place_address as company_address,
                    'Not specified' as industry_sector,
                    COUNT(DISTINCT sp.student_id) as slot_capacity
                ")
                ->orderByDesc('slot_capacity')
                ->orderBy('company_name')
                ->get()
            : collect();

        $companyMapPoints = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles as sp')
                ->join('attendances as a', 'a.student_id', '=', 'sp.student_id')
                ->whereNotNull('sp.pkl_place_name')
                ->whereNotNull('a.latitude')
                ->whereNotNull('a.longitude')
                ->whereDate('sp.pkl_start_date', '<=', $today)
                ->whereDate('sp.pkl_end_date', '>=', $today)
                ->groupBy('sp.pkl_place_name', 'sp.pkl_place_address')
                ->selectRaw("
                    sp.pkl_place_name as company_name,
                    sp.pkl_place_address as company_address,
                    AVG(a.latitude) as latitude,
                    AVG(a.longitude) as longitude,
                    COUNT(DISTINCT sp.student_id) as active_students
                ")
                ->get()
            : collect();

        $attendanceMapPoints = Schema::hasTable('student_profiles')
            ? DB::table('attendances as a')
                ->join('student_profiles as sp', 'sp.student_id', '=', 'a.student_id')
                ->join('users as u', 'u.id', '=', 'a.student_id')
                ->where('u.role', 'student')
                ->whereNotNull('sp.pkl_place_name')
                ->whereNotNull('a.latitude')
                ->whereNotNull('a.longitude')
                ->whereNotNull('a.check_in_at')
                ->whereDate('a.attendance_date', '>=', $mapStart)
                ->whereDate('a.attendance_date', '<=', $today)
                ->groupBy('a.attendance_date', 'sp.pkl_place_name', 'sp.pkl_place_address')
                ->selectRaw("
                    a.attendance_date,
                    sp.pkl_place_name as company_name,
                    sp.pkl_place_address as company_address,
                    AVG(a.latitude) as latitude,
                    AVG(a.longitude) as longitude,
                    COUNT(DISTINCT a.student_id) as attendance_total
                ")
                ->get()
            : collect();

        $majorDistribution = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->where('u.role', 'student')
                ->whereNotNull('sp.major_name')
                ->whereDate('sp.pkl_start_date', '<=', $today)
                ->whereDate('sp.pkl_end_date', '>=', $today)
                ->groupBy('sp.major_name')
                ->selectRaw('sp.major_name as major, COUNT(*) as total')
                ->orderByDesc('total')
                ->get()
            : collect();

        $companyStudentsByCompany = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->join('attendances as a', 'a.student_id', '=', 'sp.student_id')
                ->where('u.role', 'student')
                ->whereNotNull('sp.pkl_place_name')
                ->whereNotNull('a.latitude')
                ->whereNotNull('a.longitude')
                ->whereDate('sp.pkl_start_date', '<=', $today)
                ->whereDate('sp.pkl_end_date', '>=', $today)
                ->select(
                    'sp.pkl_place_name as company_name',
                    'sp.pkl_place_address as company_address',
                    'u.id as student_id',
                    'u.name as student_name',
                    'u.nis as student_nis',
                    'sp.major_name',
                    'a.check_in_at'
                )
                ->orderBy('u.name')
                ->get()
                ->groupBy(fn ($row) => trim((string) $row->company_name) . '||' . trim((string) ($row->company_address ?? '')))
                ->map(function ($rows) {
                    return $rows
                        ->unique('student_id')
                        ->values()
                        ->map(function ($row) {
                            return [
                                'student_name' => $row->student_name,
                                'student_nis' => $row->student_nis,
                                'major_name' => $row->major_name,
                                'check_in_at' => $row->check_in_at,
                            ];
                        })
                        ->values();
                })
                ->toArray()
            : [];

        $heatStart = $mapStart;
        $attendanceByDate = DB::table('attendances')
            ->whereDate('attendance_date', '>=', $heatStart)
            ->whereDate('attendance_date', '<=', $today)
            ->whereNotNull('check_in_at')
            ->groupBy('attendance_date')
            ->selectRaw('attendance_date, COUNT(DISTINCT student_id) as total')
            ->pluck('total', 'attendance_date');

        $heatmap = collect();
        $maxAttendance = 0;
        for ($i = 29; $i >= 0; $i--) {
            $date = $wibNow->copy()->subDays($i)->toDateString();
            $total = (int) ($attendanceByDate[$date] ?? 0);
            $maxAttendance = max($maxAttendance, $total);
            $heatmap->push([
                'date' => $date,
                'total' => $total,
            ]);
        }

        $attendanceFeed = Schema::hasTable('student_profiles')
            ? DB::table('attendances as a')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 'a.student_id')
                ->whereNotNull('a.check_in_at')
                ->whereDate('a.attendance_date', '>=', $wibNow->copy()->subDays(14)->toDateString())
                ->orderByDesc('a.check_in_at')
                ->limit(20)
                ->get([
                    'a.check_in_at as happened_at',
                    'sp.major_name',
                    'sp.pkl_place_name',
                ])
                ->map(function ($row) {
                    $major = $row->major_name ?: 'Unknown major';
                    $company = $row->pkl_place_name ?: 'Unknown company';
                    return [
                        'happened_at' => $row->happened_at,
                        'message' => "A student from {$major} just checked in at {$company}.",
                    ];
                })
            : collect();

        $journalFeed = Schema::hasTable('student_profiles')
            ? DB::table('weekly_journals as wj')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 'wj.student_id')
                ->whereIn('wj.status', ['approved', 'needs_revision', 'submitted'])
                ->orderByDesc('wj.updated_at')
                ->limit(20)
                ->get([
                    'wj.updated_at as happened_at',
                    'wj.status',
                    'sp.major_name',
                ])
                ->map(function ($row) {
                    $major = $row->major_name ?: 'Unknown major';
                    $statusText = $row->status === 'approved'
                        ? 'validated'
                        : ($row->status === 'needs_revision' ? 'marked for revision' : 'submitted');
                    return [
                        'happened_at' => $row->happened_at,
                        'message' => "Weekly Journal {$statusText} for {$major} major.",
                    ];
                })
            : collect();

        $activityFeed = $attendanceFeed
            ->concat($journalFeed)
            ->sortByDesc('happened_at')
            ->take(20)
            ->values();

        $timelineMajorOptions = collect(['RPL', 'BDP', 'AKL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicTimelineMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->where('major_name', '<>', '')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter();
            $timelineMajorOptions = $timelineMajorOptions->merge($dynamicTimelineMajors)->unique()->values();
        }

        $timelineMajor = strtoupper(trim((string) $request->query('timeline_major', 'ALL')));
        if ($timelineMajor === '' || ($timelineMajor !== 'ALL' && !$timelineMajorOptions->contains($timelineMajor))) {
            $timelineMajor = 'ALL';
        }

        $timelinePayload = build_implementation_timeline_payload($today, $timelineMajor);

        return view('dashboard.super-admin', [
            'companyStats' => $companyStats,
            'companyMapPoints' => $companyMapPoints,
            'attendanceMapPoints' => $attendanceMapPoints,
            'companyStudentsByCompany' => $companyStudentsByCompany,
            'majorDistribution' => $majorDistribution,
            'heatmap' => $heatmap,
            'maxAttendance' => $maxAttendance,
            'activityFeed' => $activityFeed,
            'timelineMajor' => $timelineMajor,
            'timelineMajorOptions' => $timelineMajorOptions,
            'timelineStart' => $timelinePayload['timelineStart'],
            'timelineEnd' => $timelinePayload['timelineEnd'],
            'timelineStatus' => $timelinePayload['timelineStatus'],
        ]);
    })->name('dashboard.super-admin');

    Route::get('/dashboard/super-admin/checkins', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_SUPER_ADMIN, 403);
        require_user_permission($user, 'super_admin_dashboard', 'view');

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $selectedDateInput = trim((string) $request->query('date', $today));
        $selectedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateInput) ? $selectedDateInput : $today;
        $selectedMajor = strtoupper(trim((string) $request->query('major', 'ALL')));
        $selectedClass = trim((string) $request->query('class', 'ALL'));
        $statusFilter = strtolower(trim((string) $request->query('status', 'ALL')));
        $search = trim((string) $request->query('q', ''));

        $majorOptions = collect(['ALL', 'RPL', 'BDP', 'AKL']);
        $classOptions = collect(['ALL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->whereRaw('TRIM(major_name) <> ""')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->values();

            $dynamicClasses = DB::table('student_profiles')
                ->whereNotNull('class_name')
                ->whereRaw('TRIM(class_name) <> ""')
                ->distinct()
                ->pluck('class_name')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->sort()
                ->values();
            $classOptions = $classOptions->merge($dynamicClasses)->unique()->values();
        }

        if (!$majorOptions->contains($selectedMajor)) {
            $selectedMajor = 'ALL';
        }
        if (!$classOptions->contains($selectedClass)) {
            $selectedClass = 'ALL';
        }
        if (!in_array($statusFilter, ['all', 'checked_in', 'checked_out', 'no_checkin'], true)) {
            $statusFilter = 'all';
        }

        $attendanceDailySub = DB::table('attendances')
            ->whereDate('attendance_date', $selectedDate)
            ->selectRaw('MAX(id) as attendance_id, student_id')
            ->groupBy('student_id');

        $students = DB::table('users as s')
            ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->leftJoinSub($attendanceDailySub, 'ads', function ($join) {
                $join->on('ads.student_id', '=', 's.id');
            })
            ->leftJoin('attendances as ad', 'ad.id', '=', 'ads.attendance_id')
            ->where('s.role', User::ROLE_STUDENT)
            ->when($selectedMajor !== 'ALL', function ($query) use ($selectedMajor) {
                $query->whereRaw('UPPER(COALESCE(sp.major_name, "")) = ?', [$selectedMajor]);
            })
            ->when($selectedClass !== 'ALL', function ($query) use ($selectedClass) {
                $query->whereRaw('TRIM(COALESCE(sp.class_name, "")) = ?', [$selectedClass]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('s.name', 'like', '%' . $search . '%')
                        ->orWhere('s.nis', 'like', '%' . $search . '%');
                });
            })
            ->when($statusFilter === 'checked_in', function ($query) {
                $query->whereNotNull('ad.check_in_at')->whereNull('ad.check_out_at');
            })
            ->when($statusFilter === 'checked_out', function ($query) {
                $query->whereNotNull('ad.check_out_at');
            })
            ->when($statusFilter === 'no_checkin', function ($query) {
                $query->whereNull('ad.check_in_at');
            })
            ->select(
                's.id as student_id',
                's.name as student_name',
                's.nis as student_nis',
                DB::raw('COALESCE(NULLIF(TRIM(sp.major_name), ""), "-") as major_name'),
                DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_name), ""), "-") as company_name'),
                'ad.attendance_date',
                'ad.check_in_at',
                'ad.check_out_at',
                'ad.status as attendance_status_raw',
                'ad.latitude',
                'ad.longitude',
                'ad.photo_path'
            )
            ->orderBy('s.name')
            ->paginate(24)
            ->withQueryString();

        $students->setCollection(
            $students->getCollection()->map(function ($row) {
                $checkInAt = data_get($row, 'check_in_at');
                $checkOutAt = data_get($row, 'check_out_at');
                if ($checkOutAt) {
                    $row->attendance_state = 'Checked Out';
                } elseif ($checkInAt) {
                    $row->attendance_state = 'Checked In';
                } else {
                    $row->attendance_state = 'No Check-in';
                }
                $row->location = (!is_null($row->latitude) && !is_null($row->longitude))
                    ? ((string) $row->latitude . ', ' . (string) $row->longitude)
                    : '-';
                $photoPath = trim((string) ($row->photo_path ?? ''));
                $row->photo_url = $photoPath !== '' ? Storage::url($photoPath) : null;
                return $row;
            })
        );

        return view('dashboard.super-admin-checkins', [
            'students' => $students,
            'selectedDate' => $selectedDate,
            'today' => $today,
            'selectedMajor' => $selectedMajor,
            'selectedClass' => $selectedClass,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'majorOptions' => $majorOptions,
            'classOptions' => $classOptions,
        ]);
    })->name('dashboard.super-admin.checkins');

    Route::get('/dashboard/super-admin/weekly-journals', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_SUPER_ADMIN, 403);
        require_user_permission($user, 'super_admin_dashboard', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $defaultWeekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $selectedWeekStartInput = trim((string) $request->query('week_start', $defaultWeekStart));
        $selectedWeekStart = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedWeekStartInput) ? $selectedWeekStartInput : $defaultWeekStart;
        $selectedWeekEnd = Carbon::parse($selectedWeekStart, 'Asia/Jakarta')->endOfWeek(Carbon::SUNDAY)->toDateString();

        $selectedMajor = strtoupper(trim((string) $request->query('major', 'ALL')));
        $selectedClass = trim((string) $request->query('class', 'ALL'));
        $statusFilter = strtolower(trim((string) $request->query('status', 'all')));
        $search = trim((string) $request->query('q', ''));

        $majorOptions = collect(['ALL', 'RPL', 'BDP', 'AKL']);
        $classOptions = collect(['ALL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->whereRaw('TRIM(major_name) <> ""')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->values();

            $dynamicClasses = DB::table('student_profiles')
                ->whereNotNull('class_name')
                ->whereRaw('TRIM(class_name) <> ""')
                ->distinct()
                ->pluck('class_name')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->sort()
                ->values();
            $classOptions = $classOptions->merge($dynamicClasses)->unique()->values();
        }

        if (!$majorOptions->contains($selectedMajor)) {
            $selectedMajor = 'ALL';
        }
        if (!$classOptions->contains($selectedClass)) {
            $selectedClass = 'ALL';
        }
        if (!in_array($statusFilter, ['all', 'submitted', 'approved', 'needs_revision', 'draft', 'no_submission'], true)) {
            $statusFilter = 'all';
        }

        $weeklyJournalSub = DB::table('weekly_journals')
            ->whereDate('week_start_date', $selectedWeekStart)
            ->whereDate('week_end_date', $selectedWeekEnd)
            ->selectRaw('MAX(id) as journal_id, student_id')
            ->groupBy('student_id');

        $students = DB::table('users as s')
            ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->leftJoinSub($weeklyJournalSub, 'wjs', function ($join) {
                $join->on('wjs.student_id', '=', 's.id');
            })
            ->leftJoin('weekly_journals as wj', 'wj.id', '=', 'wjs.journal_id')
            ->leftJoin('users as m', 'm.id', '=', 'wj.mentor_id')
            ->leftJoin('users as k', 'k.id', '=', 'wj.kajur_id')
            ->leftJoin('users as b', 'b.id', '=', 'wj.bindo_id')
            ->where('s.role', User::ROLE_STUDENT)
            ->when($selectedMajor !== 'ALL', function ($query) use ($selectedMajor) {
                $query->whereRaw('UPPER(COALESCE(sp.major_name, "")) = ?', [$selectedMajor]);
            })
            ->when($selectedClass !== 'ALL', function ($query) use ($selectedClass) {
                $query->whereRaw('TRIM(COALESCE(sp.class_name, "")) = ?', [$selectedClass]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('s.name', 'like', '%' . $search . '%')
                        ->orWhere('s.nis', 'like', '%' . $search . '%');
                });
            })
            ->when($statusFilter === 'no_submission', function ($query) {
                $query->whereNull('wj.id');
            })
            ->when($statusFilter !== 'all' && $statusFilter !== 'no_submission', function ($query) use ($statusFilter) {
                $query->where('wj.status', $statusFilter);
            })
            ->select(
                's.id as student_id',
                's.name as student_name',
                's.nis as student_nis',
                DB::raw('COALESCE(NULLIF(TRIM(sp.major_name), ""), "-") as major_name'),
                DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                'wj.id as journal_id',
                'wj.week_start_date',
                'wj.week_end_date',
                'wj.learning_notes',
                'wj.student_mentor_notes',
                'wj.mentor_is_correct',
                'wj.missing_info_notes',
                'wj.kajur_notes',
                'wj.bindo_notes',
                'wj.status as journal_status',
                'm.name as mentor_name',
                'k.name as kajur_name',
                'b.name as bindo_name'
            )
            ->orderBy('s.name')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.super-admin-weekly-journals', [
            'students' => $students,
            'selectedWeekStart' => $selectedWeekStart,
            'selectedWeekEnd' => $selectedWeekEnd,
            'selectedMajor' => $selectedMajor,
            'selectedClass' => $selectedClass,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'majorOptions' => $majorOptions,
            'classOptions' => $classOptions,
        ]);
    })->name('dashboard.super-admin.weekly-journals');

    Route::get('/dashboard/super-admin/completion', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_SUPER_ADMIN, 403);
        require_user_permission($user, 'super_admin_dashboard', 'view');

        $selectedMajor = strtoupper(trim((string) $request->query('major', 'ALL')));
        $selectedClass = trim((string) $request->query('class', 'ALL'));
        $search = trim((string) $request->query('q', ''));

        $majorOptions = collect(['ALL', 'RPL', 'BDP', 'AKL']);
        $classOptions = collect(['ALL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->whereRaw('TRIM(major_name) <> ""')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->values();

            $dynamicClasses = DB::table('student_profiles')
                ->whereNotNull('class_name')
                ->whereRaw('TRIM(class_name) <> ""')
                ->distinct()
                ->pluck('class_name')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->sort()
                ->values();
            $classOptions = $classOptions->merge($dynamicClasses)->unique()->values();
        }

        if (!$majorOptions->contains($selectedMajor)) {
            $selectedMajor = 'ALL';
        }
        if (!$classOptions->contains($selectedClass)) {
            $selectedClass = 'ALL';
        }

        $students = DB::table('users as s')
            ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->where('s.role', User::ROLE_STUDENT)
            ->when($selectedMajor !== 'ALL', function ($query) use ($selectedMajor) {
                $query->whereRaw('UPPER(COALESCE(sp.major_name, "")) = ?', [$selectedMajor]);
            })
            ->when($selectedClass !== 'ALL', function ($query) use ($selectedClass) {
                $query->whereRaw('TRIM(COALESCE(sp.class_name, "")) = ?', [$selectedClass]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('s.name', 'like', '%' . $search . '%')
                        ->orWhere('s.nis', 'like', '%' . $search . '%');
                });
            })
            ->select(
                's.id as student_id',
                's.name as student_name',
                's.nis as student_nis',
                DB::raw('COALESCE(NULLIF(TRIM(sp.major_name), ""), "-") as major_name'),
                DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                'sp.pkl_start_date',
                'sp.pkl_end_date'
            )
            ->orderBy('s.name')
            ->paginate(24)
            ->withQueryString();

        $students->setCollection(
            $students->getCollection()->map(function ($student) {
                $pklStartDate = data_get($student, 'pkl_start_date');
                $pklEndDate = data_get($student, 'pkl_end_date');
                $hasPklRange = !empty($pklStartDate) && !empty($pklEndDate);

                if ($hasPklRange) {
                    $completedDays = DB::table('attendances')
                        ->where('student_id', $student->student_id)
                        ->whereNotNull('check_out_at')
                        ->whereBetween('attendance_date', [$pklStartDate, $pklEndDate])
                        ->whereRaw('DAYOFWEEK(attendance_date) NOT IN (1, 6, 7)')
                        ->count();

                    $targetDays = 0;
                    $cursor = Carbon::parse($pklStartDate, 'Asia/Jakarta')->startOfDay();
                    $endCursor = Carbon::parse($pklEndDate, 'Asia/Jakarta')->startOfDay();
                    while ($cursor->lessThanOrEqualTo($endCursor)) {
                        if (!in_array($cursor->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY], true)) {
                            $targetDays++;
                        }
                        $cursor->addDay();
                    }
                } else {
                    $completedDays = DB::table('attendances')
                        ->where('student_id', $student->student_id)
                        ->whereNotNull('check_out_at')
                        ->count();
                    $targetDays = 90;
                }

                $student->completed_days = (int) $completedDays;
                $student->target_days = (int) $targetDays;
                $student->progress_percent = $targetDays > 0
                    ? min(100, (int) round(($completedDays / $targetDays) * 100))
                    : 0;

                return $student;
            })
        );

        return view('dashboard.super-admin-completion', [
            'students' => $students,
            'selectedMajor' => $selectedMajor,
            'selectedClass' => $selectedClass,
            'search' => $search,
            'majorOptions' => $majorOptions,
            'classOptions' => $classOptions,
        ]);
    })->name('dashboard.super-admin.completion');

    Route::post('/dashboard/super-admin/profile', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:50', 'unique:users,nis,' . $user->id],
            'avatar_crop_data' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->nis = $validated['nis'];

        if (!empty($validated['avatar_crop_data'])) {
            $base64 = $validated['avatar_crop_data'];
            $prefix = 'data:image/png;base64,';

            if (!Str::startsWith($base64, $prefix)) {
                return back()->withErrors(['avatar_crop_data' => 'Invalid cropped image format.']);
            }

            $decoded = base64_decode(substr($base64, strlen($prefix)), true);
            if ($decoded === false) {
                return back()->withErrors(['avatar_crop_data' => 'Could not process cropped image.']);
            }

            if (!empty($user->avatar_url) && !Str::startsWith($user->avatar_url, ['http://', 'https://'])) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            $path = 'avatars/' . Str::uuid() . '.png';
            Storage::disk('public')->put($path, $decoded);
            $user->avatar_url = $path;
        }

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        log_user_activity(
            $user,
            'super_admin_profile_updated',
            'user',
            $user->id,
            'Super Admin profile updated.',
            ['name' => $user->name, 'nis' => $user->nis]
        );

        return back()->with('status', 'Profile updated successfully.');
    })->name('dashboard.super-admin.profile');

    Route::get('/dashboard/super-admin/activities', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'view');

        if (!user_activity_storage_ready()) {
            return view('dashboard.super-admin-activities', [
                'activities' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]),
                'activityStorageReady' => false,
            ]);
        }

        $activities = DB::table('user_activity_logs as l')
            ->leftJoin('users as a', 'a.id', '=', 'l.actor_user_id')
            ->select(
                'l.id',
                'l.action',
                'l.subject_type',
                'l.subject_id',
                'l.description',
                'l.metadata',
                'l.can_revert',
                'l.reverted_at',
                'l.purged_at',
                'l.created_at',
                'a.name as actor_name',
                'a.nis as actor_nis'
            )
            ->orderByDesc('l.id')
            ->paginate(20)
            ->withQueryString();

        $activities->setCollection(
            collect($activities->items())->map(function ($row) {
                $decoded = [];
                if (is_string($row->metadata) && $row->metadata !== '') {
                    $parsed = json_decode($row->metadata, true);
                    $decoded = is_array($parsed) ? $parsed : [];
                } elseif (is_array($row->metadata)) {
                    $decoded = $row->metadata;
                }
                $row->metadata_decoded = $decoded;
                return $row;
            })
        );

        return view('dashboard.super-admin-activities', [
            'activities' => $activities,
            'activityStorageReady' => true,
        ]);
    })->name('dashboard.super-admin.activities');

    Route::post('/dashboard/super-admin/activities/{activityId}/revert', function (Request $request, int $activityId) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'update');

        if (!user_activity_storage_ready() || !deleted_user_archive_storage_ready()) {
            return back()->withErrors(['activities' => 'Activity restore storage is not ready. Run migrations first.']);
        }

        $activity = DB::table('user_activity_logs')->where('id', $activityId)->first();
        if (!$activity || !$activity->can_revert || $activity->reverted_at || $activity->purged_at) {
            return back()->withErrors(['activities' => 'This activity cannot be reverted.']);
        }

        $metadata = [];
        if (is_string($activity->metadata) && $activity->metadata !== '') {
            $parsed = json_decode($activity->metadata, true);
            $metadata = is_array($parsed) ? $parsed : [];
        } elseif (is_array($activity->metadata)) {
            $metadata = $activity->metadata;
        }

        $archiveId = (int) data_get($metadata, 'archive_id', 0);
        if ($archiveId <= 0) {
            return back()->withErrors(['activities' => 'No archive record found for this activity.']);
        }

        $archive = DB::table('deleted_user_archives')->where('id', $archiveId)->first();
        if (!$archive || $archive->restored_at || $archive->purged_at) {
            return back()->withErrors(['activities' => 'Archive already restored or purged.']);
        }

        $userData = [];
        if (is_string($archive->user_data) && $archive->user_data !== '') {
            $parsed = json_decode($archive->user_data, true);
            $userData = is_array($parsed) ? $parsed : [];
        }

        $profileData = [];
        if (is_string($archive->profile_data) && $archive->profile_data !== '') {
            $parsed = json_decode($archive->profile_data, true);
            $profileData = is_array($parsed) ? $parsed : [];
        }

        $restoredUserId = (int) data_get($userData, 'id', 0);
        if ($restoredUserId <= 0) {
            return back()->withErrors(['activities' => 'Corrupted archive data.']);
        }

        if (User::query()->where('id', $restoredUserId)->exists()) {
            return back()->withErrors(['activities' => 'Cannot restore because user id already exists.']);
        }

        DB::transaction(function () use ($archiveId, $activityId, $userData, $profileData, $restoredUserId, $user) {
            $userInsert = [
                'id' => $restoredUserId,
                'name' => data_get($userData, 'name'),
                'nis' => data_get($userData, 'nis'),
                'email' => data_get($userData, 'email'),
                'role' => data_get($userData, 'role'),
                'password' => data_get($userData, 'password'),
                'created_at' => data_get($userData, 'created_at') ?: now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ];
            if (Schema::hasColumn('users', 'avatar_url')) {
                $userInsert['avatar_url'] = data_get($userData, 'avatar_url');
            }
            if (Schema::hasColumn('users', 'permissions_json')) {
                $perms = data_get($userData, 'permissions_json');
                $userInsert['permissions_json'] = $perms ? json_encode($perms, JSON_UNESCAPED_UNICODE) : null;
            }
            if (Schema::hasColumn('users', 'remember_token')) {
                $userInsert['remember_token'] = data_get($userData, 'remember_token');
            }
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $userInsert['email_verified_at'] = data_get($userData, 'email_verified_at');
            }

            DB::table('users')->insert($userInsert);

            if (!empty($profileData) && Schema::hasTable('student_profiles') && data_get($userData, 'role') === User::ROLE_STUDENT) {
                $profileData['student_id'] = $restoredUserId;
                $profileData['updated_at'] = now('Asia/Jakarta');
                $profileData['created_at'] = data_get($profileData, 'created_at') ?: now('Asia/Jakarta');
                DB::table('student_profiles')->updateOrInsert(['student_id' => $restoredUserId], $profileData);
            }

            DB::table('deleted_user_archives')->where('id', $archiveId)->update([
                'restored_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]);

            DB::table('user_activity_logs')->where('id', $activityId)->update([
                'reverted_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]);

            log_user_activity(
                $user,
                'user_restored',
                'user',
                $restoredUserId,
                'Deleted user restored from activity archive.',
                ['archive_id' => $archiveId, 'activity_id' => $activityId]
            );
        });

        return back()->with('status', 'Deleted user successfully restored.');
    })->name('dashboard.super-admin.activities.revert');

    Route::post('/dashboard/super-admin/activities/{activityId}/purge', function (Request $request, int $activityId) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'update');

        if (!user_activity_storage_ready() || !deleted_user_archive_storage_ready()) {
            return back()->withErrors(['activities' => 'Activity purge storage is not ready. Run migrations first.']);
        }

        $activity = DB::table('user_activity_logs')->where('id', $activityId)->first();
        if (!$activity || !$activity->can_revert || $activity->reverted_at || $activity->purged_at) {
            return back()->withErrors(['activities' => 'This activity cannot be permanently deleted.']);
        }

        $metadata = [];
        if (is_string($activity->metadata) && $activity->metadata !== '') {
            $parsed = json_decode($activity->metadata, true);
            $metadata = is_array($parsed) ? $parsed : [];
        } elseif (is_array($activity->metadata)) {
            $metadata = $activity->metadata;
        }

        $archiveId = (int) data_get($metadata, 'archive_id', 0);
        if ($archiveId <= 0) {
            return back()->withErrors(['activities' => 'No archive record found for this activity.']);
        }

        DB::transaction(function () use ($archiveId, $activityId, $user) {
            DB::table('deleted_user_archives')->where('id', $archiveId)->update([
                'user_data' => null,
                'profile_data' => null,
                'purged_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]);

            DB::table('user_activity_logs')->where('id', $activityId)->update([
                'purged_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]);

            log_user_activity(
                $user,
                'deleted_user_archive_purged',
                'deleted_user_archive',
                $archiveId,
                'Deleted user archive permanently purged.',
                ['activity_id' => $activityId]
            );
        });

        return back()->with('status', 'Deleted archive permanently removed.');
    })->name('dashboard.super-admin.activities.purge');

    Route::get('/dashboard/super-admin/mass-edit', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'view');
        $allowedPerPage = [5, 10, 20, 50, 100];
        $perPage = (int) $request->query('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }
        $page = max(1, (int) $request->query('page', 1));

        $makeKey = function (string $name, string $address): string {
            return mb_strtolower(trim($name) . '||' . trim($address));
        };

        $statsRows = collect();
        if (Schema::hasTable('student_profiles')) {
            $statsRows = DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->where('u.role', User::ROLE_STUDENT)
                ->whereNotNull('sp.pkl_place_name')
                ->where('sp.pkl_place_name', '<>', '')
                ->groupBy('sp.pkl_place_name', 'sp.pkl_place_address')
                ->selectRaw('sp.pkl_place_name as company_name, sp.pkl_place_address as company_address, COUNT(DISTINCT sp.student_id) as total_students')
                ->get();
        }

        $companiesMap = [];
        if (Schema::hasTable('partner_companies')) {
            $partners = DB::table('partner_companies')
                ->where('is_active', true)
                ->whereNotNull('name')
                ->where('name', '<>', '')
                ->select('id', 'name', 'address', 'max_students')
                ->orderBy('name')
                ->orderBy('address')
                ->get();

            foreach ($partners as $row) {
                $name = trim((string) ($row->name ?? ''));
                if ($name === '') continue;
                $address = trim((string) ($row->address ?? '')) ?: '-';
                $key = $makeKey($name, $address);
                $companiesMap[$key] = (object) [
                    'name' => $name,
                    'address' => $address,
                    'max_students' => $row->max_students !== null ? (int) $row->max_students : null,
                    'total_students' => 0,
                ];
            }
        }

        foreach ($statsRows as $row) {
            $name = trim((string) ($row->company_name ?? ''));
            if ($name === '') continue;
            $address = trim((string) ($row->company_address ?? '')) ?: '-';
            $key = $makeKey($name, $address);
            if (!array_key_exists($key, $companiesMap)) {
                $companiesMap[$key] = (object) [
                    'name' => $name,
                    'address' => $address,
                    'max_students' => null,
                    'total_students' => 0,
                ];
            }
            $companiesMap[$key]->total_students = (int) ($row->total_students ?? 0);
        }

        $allCompanies = collect(array_values($companiesMap))
            ->sortBy(fn ($row) => mb_strtolower((string) $row->name))
            ->values();

        $companies = new \Illuminate\Pagination\LengthAwarePaginator(
            $allCompanies->forPage($page, $perPage)->values(),
            $allCompanies->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $majorOptions = collect(['RPL', 'BDP', 'AKL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->where('major_name', '<>', '')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->values();
        }

        $timelineMajor = strtoupper(trim((string) $request->query('timeline_major', 'ALL')));
        if ($timelineMajor === '') {
            $timelineMajor = 'ALL';
        }

        $timelinePayload = build_implementation_timeline_payload(null, $timelineMajor);

        return view('dashboard.super-admin-mass-edit', [
            'companies' => $companies,
            'allCompanies' => $allCompanies,
            'majorOptions' => $majorOptions,
            'perPage' => $perPage,
            'allowedPerPage' => $allowedPerPage,
            'timelineMajor' => $timelineMajor,
            'timelineStart' => $timelinePayload['timelineStart'],
            'timelineEnd' => $timelinePayload['timelineEnd'],
            'timelineWeeks' => $timelinePayload['timelineWeeks'],
            'timelineStatus' => $timelinePayload['timelineStatus'],
        ]);
    })->name('dashboard.super-admin.mass-edit');

    Route::post('/dashboard/super-admin/mass-edit/timeline-statuses', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'update');

        if (!implementation_timeline_storage_ready()) {
            return back()->withErrors(['mass_edit' => 'Timeline status table is not ready. Run migrations first.']);
        }

        $validated = $request->validate([
            'weeks' => ['required', 'array'],
            'weeks.*.week_end' => ['required', 'date'],
            'weeks.*.status_label' => ['nullable', 'string', 'max:120'],
        ]);

        $updatedCount = 0;
        foreach (($validated['weeks'] ?? []) as $weekStart => $row) {
            try {
                $weekStartDate = Carbon::parse((string) $weekStart, 'Asia/Jakarta')->toDateString();
            } catch (\Throwable $e) {
                continue;
            }

            $weekEndDate = Carbon::parse((string) ($row['week_end'] ?? ''), 'Asia/Jakarta')->toDateString();
            $statusLabel = trim((string) ($row['status_label'] ?? ''));

            if ($statusLabel === '') {
                DB::table('implementation_timeline_statuses')
                    ->whereDate('week_start', $weekStartDate)
                    ->delete();
                continue;
            }

            DB::table('implementation_timeline_statuses')->updateOrInsert(
                ['week_start' => $weekStartDate],
                [
                    'week_end' => $weekEndDate,
                    'status_label' => $statusLabel,
                    'updated_at' => now('Asia/Jakarta'),
                    'created_at' => now('Asia/Jakarta'),
                ]
            );
            $updatedCount++;
        }

        log_user_activity(
            $user,
            'mass_edit_timeline_statuses',
            'timeline',
            null,
            'Implementation timeline statuses updated.',
            ['updated_weeks' => $updatedCount]
        );

        return back()->with('status', "Updated timeline statuses for {$updatedCount} week(s).");
    })->name('dashboard.super-admin.mass-edit.timeline-statuses');

    Route::post('/dashboard/super-admin/mass-edit/companies', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'update');

        if (!Schema::hasTable('partner_companies')) {
            return back()->withErrors(['mass_edit' => 'Partner companies table is not ready. Run migrations first.']);
        }

        $validated = $request->validate([
            'companies' => ['required', 'array'],
            'companies.*.name' => ['required', 'string', 'max:150'],
            'companies.*.address' => ['required', 'string', 'max:2000'],
            'companies.*.max_students' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $updatedCount = 0;
        foreach (($validated['companies'] ?? []) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $address = trim((string) ($row['address'] ?? ''));
            if ($name === '') {
                continue;
            }
            if ($address === '') {
                $address = '-';
            }

            DB::table('partner_companies')->updateOrInsert(
                ['name' => $name, 'address' => $address],
                [
                    'max_students' => array_key_exists('max_students', $row) && $row['max_students'] !== null && $row['max_students'] !== ''
                        ? (int) $row['max_students']
                        : null,
                    'is_active' => true,
                    'updated_at' => now('Asia/Jakarta'),
                    'created_at' => now('Asia/Jakarta'),
                ]
            );
            $updatedCount++;
        }

        log_user_activity(
            $user,
            'mass_edit_companies_capacity',
            'company',
            null,
            'Bulk updated company max students.',
            ['updated_companies' => $updatedCount]
        );

        return back()->with('status', "Updated max students for {$updatedCount} companies.");
    })->name('dashboard.super-admin.mass-edit.companies');

    Route::post('/dashboard/super-admin/mass-edit/major-schedule', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'update');

        if (!Schema::hasTable('student_profiles')) {
            return back()->withErrors(['mass_edit' => 'Student profiles table is not ready.']);
        }

        $validated = $request->validate([
            'major_name' => ['required', 'string', 'max:50'],
            'pkl_start_date' => ['required', 'date'],
            'pkl_end_date' => ['required', 'date', 'after_or_equal:pkl_start_date'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'company_address' => ['nullable', 'string', 'max:2000'],
        ]);

        $major = strtoupper(trim((string) $validated['major_name']));
        $query = DB::table('student_profiles');
        if ($major !== 'ALL') {
            $query->whereRaw('UPPER(COALESCE(major_name, "")) = ?', [$major]);
        }

        $payload = [
            'pkl_start_date' => $validated['pkl_start_date'],
            'pkl_end_date' => $validated['pkl_end_date'],
            'updated_at' => now('Asia/Jakarta'),
        ];

        $companyName = trim((string) ($validated['company_name'] ?? ''));
        if ($companyName !== '') {
            $payload['pkl_place_name'] = $companyName;
            $payload['pkl_place_address'] = trim((string) ($validated['company_address'] ?? '')) ?: '-';
        }

        $affected = $query->update($payload);

        log_user_activity(
            $user,
            'mass_edit_major_schedule',
            'student_profile',
            null,
            'Bulk updated PKL schedule by major.',
            [
                'major' => $major,
                'pkl_start_date' => $validated['pkl_start_date'],
                'pkl_end_date' => $validated['pkl_end_date'],
                'company_name' => $companyName !== '' ? $companyName : null,
                'affected_rows' => $affected,
            ]
        );

        return back()->with('status', "Bulk schedule updated for {$affected} student profiles.");
    })->name('dashboard.super-admin.mass-edit.major-schedule');

    Route::get('/dashboard/super-admin/companies', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'view');

        $q = trim((string) $request->query('q', ''));
        $perPage = 5;
        $page = max(1, (int) $request->query('page', 1));
        $makeKey = function (string $name, string $address): string {
            return mb_strtolower(trim($name) . '||' . trim($address));
        };

        $statsRows = collect();
        if (Schema::hasTable('student_profiles')) {
            $statsRows = DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->where('u.role', User::ROLE_STUDENT)
                ->whereNotNull('sp.pkl_place_name')
                ->where('sp.pkl_place_name', '<>', '')
                ->groupBy('sp.pkl_place_name', 'sp.pkl_place_address')
                ->selectRaw("
                    sp.pkl_place_name as company_name,
                    sp.pkl_place_address as company_address,
                    COUNT(DISTINCT sp.student_id) as total_students,
                    COUNT(DISTINCT COALESCE(NULLIF(TRIM(sp.major_name), ''), 'Unknown')) as total_majors
                ")
                ->get();
        }

        $statsByKey = $statsRows->keyBy(function ($row) use ($makeKey) {
            $name = trim((string) ($row->company_name ?? ''));
            $address = trim((string) ($row->company_address ?? '')) ?: '-';
            return $makeKey($name, $address);
        });

        $merged = collect();

        if (Schema::hasTable('partner_companies')) {
            $partnerRows = DB::table('partner_companies')
                ->where('is_active', true)
                ->whereNotNull('name')
                ->where('name', '<>', '')
                ->select('name', 'address', 'logo_url', 'contact_person', 'contact_phone', 'contact_email', 'website_url', 'max_students')
                ->orderBy('name')
                ->orderBy('address')
                ->get();

            foreach ($partnerRows as $partner) {
                $name = trim((string) ($partner->name ?? ''));
                if ($name === '') {
                    continue;
                }
                $address = trim((string) ($partner->address ?? '')) ?: '-';
                $key = $makeKey($name, $address);
                $stats = $statsByKey->get($key);
                $initials = collect(preg_split('/\s+/', $name))
                    ->filter()
                    ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                    ->take(2)
                    ->implode('');

                $merged[$key] = (object) [
                    'company_name' => $name,
                    'company_address' => $address,
                    'total_students' => (int) data_get($stats, 'total_students', 0),
                    'total_majors' => (int) data_get($stats, 'total_majors', 0),
                    'logo_url' => $partner->logo_url,
                    'contact_person' => $partner->contact_person,
                    'contact_phone' => $partner->contact_phone,
                    'contact_email' => $partner->contact_email,
                    'website_url' => $partner->website_url,
                    'max_students' => $partner->max_students !== null ? (int) $partner->max_students : null,
                    'has_meta' => true,
                    'logo_initials' => $initials !== '' ? $initials : 'CO',
                ];
            }
        }

        foreach ($statsRows as $row) {
            $name = trim((string) ($row->company_name ?? ''));
            if ($name === '') {
                continue;
            }
            $address = trim((string) ($row->company_address ?? '')) ?: '-';
            $key = $makeKey($name, $address);
            if ($merged->has($key)) {
                continue;
            }
            $initials = collect(preg_split('/\s+/', $name))
                ->filter()
                ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                ->take(2)
                ->implode('');
            $merged[$key] = (object) [
                'company_name' => $name,
                'company_address' => $address,
                'total_students' => (int) ($row->total_students ?? 0),
                'total_majors' => (int) ($row->total_majors ?? 0),
                'logo_url' => null,
                'contact_person' => null,
                'contact_phone' => null,
                'contact_email' => null,
                'website_url' => null,
                'max_students' => null,
                'has_meta' => false,
                'logo_initials' => $initials !== '' ? $initials : 'CO',
            ];
        }

        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $merged = $merged->filter(function ($row) use ($qLower) {
                return str_contains(mb_strtolower((string) $row->company_name), $qLower)
                    || str_contains(mb_strtolower((string) $row->company_address), $qLower);
            });
        }

        $sorted = $merged->sortBy(fn ($row) => mb_strtolower((string) $row->company_name))->values();
        $total = $sorted->count();
        $items = $sorted->forPage($page, $perPage)->values();
        $companies = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboard.super-admin-companies', [
            'companies' => $companies,
            'q' => $q,
        ]);
    })->name('dashboard.super-admin.companies');

    Route::get('/dashboard/super-admin/companies/modal-data', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'view');
        abort_unless(Schema::hasTable('student_profiles'), 404);

        $companyName = trim((string) $request->query('company_name', ''));
        $companyAddress = trim((string) $request->query('company_address', '-'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 5;

        if ($companyAddress === '') {
            $companyAddress = '-';
        }
        abort_if($companyName === '', 404);

        $studentsBase = DB::table('student_profiles as sp')
            ->join('users as u', 'u.id', '=', 'sp.student_id')
            ->where('u.role', User::ROLE_STUDENT)
            ->whereRaw('TRIM(sp.pkl_place_name) = ?', [$companyName])
            ->whereRaw("COALESCE(NULLIF(TRIM(sp.pkl_place_address), ''), '-') = ?", [$companyAddress]);

        $studentsAll = (clone $studentsBase)
            ->selectRaw("
                u.name as student_name,
                u.nis as student_nis,
                sp.major_name,
                sp.pkl_start_date,
                sp.pkl_end_date
            ")
            ->orderBy('u.name')
            ->get()
            ->map(function ($row) {
                $row->major_name = trim((string) ($row->major_name ?? '')) !== '' ? trim((string) $row->major_name) : 'Unknown';
                return $row;
            })
            ->values();

        $totalStudents = $studentsAll->count();
        $lastPage = max(1, (int) ceil($totalStudents / $perPage));
        $page = min($page, $lastPage);
        $studentsPage = $studentsAll->forPage($page, $perPage)->values();

        $majorSummaryRaw = (clone $studentsBase)
            ->selectRaw('sp.major_name as major_name, COUNT(*) as total')
            ->groupBy('sp.major_name')
            ->get();

        $majorSummary = $majorSummaryRaw
            ->map(function ($row) {
                $name = trim((string) ($row->major_name ?? ''));
                return [
                    'major_name' => $name !== '' ? $name : 'Unknown',
                    'total' => (int) $row->total,
                ];
            })
            ->groupBy('major_name')
            ->map(function ($rows, $majorName) {
                return [
                    'major_name' => $majorName,
                    'total' => (int) collect($rows)->sum('total'),
                ];
            })
            ->values()
            ->sortByDesc('total')
            ->values();

        $companyMeta = null;
        if (Schema::hasTable('partner_companies')) {
            $companyMeta = DB::table('partner_companies')
                ->whereRaw('TRIM(name) = ?', [$companyName])
                ->whereRaw("COALESCE(NULLIF(TRIM(address), ''), '-') = ?", [$companyAddress])
                ->first(['logo_url', 'contact_person', 'contact_phone', 'contact_email', 'website_url', 'max_students']);
        }

        return response()->json([
            'company_name' => $companyName,
            'company_address' => $companyAddress,
            'students' => $studentsPage->map(fn ($row) => [
                'student_name' => $row->student_name,
                'student_nis' => $row->student_nis,
                'major_name' => $row->major_name,
                'pkl_start_date' => $row->pkl_start_date,
                'pkl_end_date' => $row->pkl_end_date,
            ])->values(),
            'students_total' => $totalStudents,
            'students_page' => $page,
            'students_last_page' => $lastPage,
            'major_summary' => $majorSummary,
            'meta' => [
                'logo_url' => data_get($companyMeta, 'logo_url'),
                'contact_person' => data_get($companyMeta, 'contact_person'),
                'contact_phone' => data_get($companyMeta, 'contact_phone'),
                'contact_email' => data_get($companyMeta, 'contact_email'),
                'website_url' => data_get($companyMeta, 'website_url'),
                'max_students' => data_get($companyMeta, 'max_students'),
            ],
        ]);
    })->name('dashboard.super-admin.companies.modal-data');

    Route::post('/dashboard/super-admin/companies/meta/save', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'update');

        if (!Schema::hasTable('partner_companies')) {
            return back()->withErrors(['companies' => 'Partner companies table is not ready. Run migrations first.']);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_address' => ['required', 'string', 'max:2000'],
            'logo_url' => ['nullable', 'url', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'max_students' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $name = trim((string) $validated['company_name']);
        $address = trim((string) $validated['company_address']);

        DB::table('partner_companies')->updateOrInsert(
            ['name' => $name, 'address' => $address],
            [
                'logo_url' => $validated['logo_url'] ?? null,
                'contact_person' => $validated['contact_person'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
                'website_url' => $validated['website_url'] ?? null,
                'max_students' => $validated['max_students'] ?? null,
                'is_active' => true,
                'updated_at' => now('Asia/Jakarta'),
                'created_at' => now('Asia/Jakarta'),
            ]
        );

        log_user_activity(
            $user,
            'company_profile_saved',
            'company',
            null,
            'Company profile saved.',
            [
                'company_name' => $name,
                'company_address' => $address,
                'max_students' => $validated['max_students'] ?? null,
                'edit_url' => route('dashboard.super-admin.companies', ['q' => $name]),
            ]
        );

        return back()->with('status', 'Company profile saved.');
    })->name('dashboard.super-admin.companies.meta.save');

    Route::post('/dashboard/super-admin/companies/meta/delete', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'update');

        if (!Schema::hasTable('partner_companies')) {
            return back()->withErrors(['companies' => 'Partner companies table is not ready. Run migrations first.']);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_address' => ['required', 'string', 'max:2000'],
        ]);

        DB::table('partner_companies')
            ->where('name', trim((string) $validated['company_name']))
            ->where('address', trim((string) $validated['company_address']))
            ->delete();

        log_user_activity(
            $user,
            'company_profile_deleted',
            'company',
            null,
            'Company profile deleted.',
            [
                'company_name' => trim((string) $validated['company_name']),
                'company_address' => trim((string) $validated['company_address']),
                'edit_url' => route('dashboard.super-admin.companies', ['q' => trim((string) $validated['company_name'])]),
            ]
        );

        return back()->with('status', 'Company profile deleted.');
    })->name('dashboard.super-admin.companies.meta.delete');

    Route::get('/dashboard/super-admin/companies/detail', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'view');
        abort_unless(Schema::hasTable('student_profiles'), 404);

        $companyName = trim((string) $request->query('company_name', ''));
        $companyAddress = trim((string) $request->query('company_address', '-'));
        if ($companyAddress === '') {
            $companyAddress = '-';
        }

        abort_if($companyName === '', 404);

        $studentsBase = DB::table('student_profiles as sp')
            ->join('users as u', 'u.id', '=', 'sp.student_id')
            ->where('u.role', User::ROLE_STUDENT)
            ->whereRaw('TRIM(sp.pkl_place_name) = ?', [$companyName])
            ->whereRaw("COALESCE(NULLIF(TRIM(sp.pkl_place_address), ''), '-') = ?", [$companyAddress]);

        $students = (clone $studentsBase)
            ->selectRaw("
                u.name as student_name,
                u.nis as student_nis,
                COALESCE(NULLIF(TRIM(sp.major_name), ''), 'Unknown') as major_name,
                sp.pkl_start_date,
                sp.pkl_end_date,
                sp.company_instructor_position
            ")
            ->orderBy('u.name')
            ->paginate(5)
            ->appends([
                'company_name' => $companyName,
                'company_address' => $companyAddress,
            ]);

        $majorSummary = (clone $studentsBase)
            ->selectRaw('sp.major_name as major_name, COUNT(*) as total')
            ->groupBy('sp.major_name')
            ->get()
            ->map(function ($row) {
                $name = trim((string) ($row->major_name ?? ''));
                return [
                    'major_name' => $name !== '' ? $name : 'Unknown',
                    'total' => (int) $row->total,
                ];
            })
            ->groupBy('major_name')
            ->map(function ($rows, $majorName) {
                return (object) [
                    'major_name' => $majorName,
                    'total' => (int) collect($rows)->sum('total'),
                ];
            })
            ->values()
            ->sortByDesc('total')
            ->values();

        $companyMeta = null;
        if (Schema::hasTable('partner_companies')) {
            $companyMeta = DB::table('partner_companies')
                ->whereRaw('TRIM(name) = ?', [$companyName])
                ->whereRaw("COALESCE(NULLIF(TRIM(address), ''), '-') = ?", [$companyAddress])
                ->first(['logo_url', 'contact_person', 'contact_phone', 'contact_email', 'website_url']);
        }

        $logoInitials = collect(preg_split('/\s+/', $companyName))
            ->filter()
            ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return view('dashboard.super-admin-company-detail', [
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'students' => $students,
            'majorSummary' => $majorSummary,
            'companyMeta' => $companyMeta,
            'logoInitials' => $logoInitials !== '' ? $logoInitials : 'CO',
        ]);
    })->name('dashboard.super-admin.companies.detail');

    Route::get('/dashboard/super-admin/users', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'view');

        $q = trim((string) $request->query('q', ''));
        $role = trim((string) $request->query('role', ''));

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($role !== '', fn ($query) => $query->where('role', $role))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $profileRows = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles')
                ->whereIn('student_id', $users->pluck('id'))
                ->get()
                ->keyBy('student_id')
            : collect();
        $mentorCompanyRows = collect();
        if (Schema::hasTable('partner_companies') && Schema::hasColumn('users', 'partner_company_id')) {
            $mentorCompanyRows = DB::table('partner_companies')
                ->whereIn('id', $users->pluck('partner_company_id')->filter()->unique()->values())
                ->select('id', 'name', 'address')
                ->get()
                ->keyBy('id');
        }

        $userFormData = [];
        foreach ($users as $row) {
            $profile = $profileRows->get($row->id);
            $mentorCompany = $mentorCompanyRows->get((int) ($row->partner_company_id ?? 0));
            $userFormData[$row->id] = [
                'id' => $row->id,
                'name' => $row->name,
                'nis' => $row->nis,
                'email' => $row->email,
                'role' => $row->role,
                'partner_company_id' => $row->partner_company_id,
                'mentor_company_name' => data_get($mentorCompany, 'name'),
                'mentor_company_address' => data_get($mentorCompany, 'address'),
                'major_name' => data_get($profile, 'major_name'),
                'class_name' => data_get($profile, 'class_name'),
                'birth_place' => data_get($profile, 'birth_place'),
                'birth_date' => data_get($profile, 'birth_date'),
                'address' => data_get($profile, 'address'),
                'phone_number' => data_get($profile, 'phone_number'),
                'pkl_place_name' => data_get($profile, 'pkl_place_name'),
                'pkl_place_address' => data_get($profile, 'pkl_place_address'),
                'pkl_place_phone' => data_get($profile, 'pkl_place_phone'),
                'pkl_start_date' => data_get($profile, 'pkl_start_date'),
                'pkl_end_date' => data_get($profile, 'pkl_end_date'),
                'mentor_teacher_name' => data_get($profile, 'mentor_teacher_name'),
                'school_supervisor_teacher_name' => data_get($profile, 'school_supervisor_teacher_name'),
                'company_instructor_position' => data_get($profile, 'company_instructor_position'),
                'kajur_major_name' => Schema::hasColumn('users', 'kajur_major_name') ? $row->kajur_major_name : null,
                'kajur_red_flag_days' => Schema::hasColumn('users', 'kajur_red_flag_days') ? $row->kajur_red_flag_days : null,
                'teacher_class_name' => Schema::hasColumn('users', 'teacher_class_name') ? $row->teacher_class_name : null,
                'permissions_json' => user_permissions_payload($row),
            ];
        }

        $companyOptions = collect();
        $companyMap = [];

        if (Schema::hasTable('partner_companies')) {
            $partnerRows = DB::table('partner_companies')
                ->where('is_active', true)
                ->whereNotNull('name')
                ->where('name', '<>', '')
                ->select('name', 'address')
                ->get();

            foreach ($partnerRows as $row) {
                $name = trim((string) ($row->name ?? ''));
                if ($name === '') {
                    continue;
                }
                $address = trim((string) ($row->address ?? '')) ?: '-';
                $key = mb_strtolower($name . '||' . $address);
                $companyMap[$key] = (object) [
                    'name' => $name,
                    'address' => $address,
                ];
            }
        }

        if (Schema::hasTable('student_profiles')) {
            $profileCompanies = DB::table('student_profiles')
                ->whereNotNull('pkl_place_name')
                ->where('pkl_place_name', '<>', '')
                ->groupBy('pkl_place_name', 'pkl_place_address')
                ->select('pkl_place_name as name', 'pkl_place_address as address')
                ->get();

            foreach ($profileCompanies as $row) {
                $name = trim((string) ($row->name ?? ''));
                if ($name === '') {
                    continue;
                }
                $address = trim((string) ($row->address ?? '')) ?: '-';
                $key = mb_strtolower($name . '||' . $address);
                if (!array_key_exists($key, $companyMap)) {
                    $companyMap[$key] = (object) [
                        'name' => $name,
                        'address' => $address,
                    ];
                }
            }
        }

        $companyOptions = collect(array_values($companyMap))
            ->sortBy(fn ($row) => mb_strtolower((string) data_get($row, 'name')))
            ->values();

        $majorOptions = collect(['RPL', 'BDP', 'AKL']);
        $classOptions = collect(['ALL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->whereRaw('TRIM(major_name) <> ""')
                ->groupBy('major_name')
                ->orderBy('major_name')
                ->pluck('major_name')
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter()
                ->values();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->values();

            $dynamicClasses = DB::table('student_profiles')
                ->whereNotNull('class_name')
                ->whereRaw('TRIM(class_name) <> ""')
                ->groupBy('class_name')
                ->orderBy('class_name')
                ->pluck('class_name')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values();
            $classOptions = $classOptions->merge($dynamicClasses)->unique()->values();
        }

        return view('dashboard.super-admin-users', [
            'users' => $users,
            'q' => $q,
            'roleFilter' => $role,
            'roleOptions' => User::ROLES,
            'userFormData' => $userFormData,
            'companyOptions' => $companyOptions,
            'majorOptions' => $majorOptions,
            'classOptions' => $classOptions,
            'availablePermissionModules' => available_permission_modules(),
        ]);
    })->name('dashboard.super-admin.users');

    Route::get('/dashboard/super-admin/permissions', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'view');

        $role = trim((string) $request->query('role', User::ROLE_STUDENT));
        if (!in_array($role, User::ROLES, true)) {
            $role = User::ROLE_STUDENT;
        }

        $rolePermissionsStorageReady = role_permissions_storage_ready();
        $targetPermissions = role_permissions_payload($role);
        $modulesForRole = permission_modules_for_role($role);

        return view('dashboard.super-admin-permissions', [
            'selectedRole' => $role,
            'targetPermissions' => $targetPermissions,
            'roleOptions' => User::ROLES,
            'availablePermissionModules' => $modulesForRole,
            'rolePermissionsStorageReady' => $rolePermissionsStorageReady,
        ]);
    })->name('dashboard.super-admin.permissions');

    Route::post('/dashboard/super-admin/permissions/{targetRole}', function (Request $request, string $targetRole) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'update');

        if (!in_array($targetRole, User::ROLES, true)) {
            abort(404);
        }

        if (!role_permissions_storage_ready()) {
            return back()->withErrors([
                'permissions' => 'Role permissions table is not available yet. Run database migrations first.',
            ]);
        }

        if ($targetRole === User::ROLE_SUPER_ADMIN) {
            return back()->with('status', 'Super Admin always has full access and does not require manual permission setup.');
        }

        $permissionModules = array_keys(permission_modules_for_role($targetRole));
        $permissionActions = ['view', 'create', 'update', 'delete'];
        $permissionsPayload = [];
        foreach ($permissionModules as $module) {
            foreach ($permissionActions as $action) {
                $permissionsPayload[$module][$action] = $request->boolean("permissions.{$module}.{$action}");
            }
        }

        DB::table('role_permissions')->updateOrInsert(
            ['role' => $targetRole],
            [
                'permissions_json' => json_encode($permissionsPayload, JSON_THROW_ON_ERROR),
                'created_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]
        );

        log_user_activity(
            $user,
            'role_permissions_updated',
            'role',
            null,
            'Role permissions updated.',
            ['role' => $targetRole]
        );

        return redirect()->route('dashboard.super-admin.permissions', [
            'role' => $targetRole,
        ])->with('status', 'Permissions updated for role: ' . strtoupper($targetRole) . '.');
    })->name('dashboard.super-admin.permissions.update');

    Route::post('/dashboard/super-admin/users/create', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:' . implode(',', User::ROLES)],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $baseNis = 250510;
        $nisPrefix = (string) $baseNis;
        $maxNumericNis = User::query()
            ->whereNotNull('nis')
            ->where('nis', 'like', $nisPrefix . '%')
            ->pluck('nis')
            ->map(function ($value) {
                $value = (string) $value;
                return ctype_digit($value) ? (int) $value : null;
            })
            ->filter()
            ->max();

        $nextNis = (string) (max($baseNis - 1, (int) ($maxNumericNis ?? 0)) + 1);

        $created = new User();
        $created->name = $validated['name'];
        $created->email = $validated['email'];
        $created->nis = $nextNis;
        $created->role = $validated['role'];
        $created->password = $validated['password'];
        $created->save();

        log_user_activity(
            $user,
            'user_created',
            'user',
            $created->id,
            'User account created.',
            [
                'name' => $created->name,
                'role' => $created->role,
                'edit_url' => route('dashboard.super-admin.users.edit', $created->id),
            ]
        );

        return back()->with('status', "User created successfully. Auto NIS: {$nextNis}");
    })->name('dashboard.super-admin.users.create');

    Route::get('/dashboard/super-admin/users/{managedUser}/edit', function (Request $request, int $managedUser) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'view');

        try {
            $target = User::findOrFail($managedUser);
            $companyMap = [];
            if (Schema::hasTable('partner_companies')) {
                $hasPartnerNameColumn = Schema::hasColumn('partner_companies', 'name');
                $hasPartnerAddressColumn = Schema::hasColumn('partner_companies', 'address');
                $hasPartnerIsActiveColumn = Schema::hasColumn('partner_companies', 'is_active');
                $partnerRows = collect();
                if ($hasPartnerNameColumn) {
                    $partnerQuery = DB::table('partner_companies')
                        ->whereNotNull('name')
                        ->where('name', '<>', '');
                    if ($hasPartnerIsActiveColumn) {
                        $partnerQuery->where('is_active', true);
                    }
                    $partnerSelect = ['id', 'name'];
                    if ($hasPartnerAddressColumn) {
                        $partnerSelect[] = 'address';
                    }
                    $partnerQuery->orderBy('name');
                    if ($hasPartnerAddressColumn) {
                        $partnerQuery->orderBy('address');
                    }
                    $partnerRows = $partnerQuery->get($partnerSelect);
                }
                foreach ($partnerRows as $row) {
                    $name = trim((string) ($row->name ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $address = trim((string) ($row->address ?? '')) ?: '-';
                    $key = mb_strtolower($name . '||' . $address);
                    $companyMap[$key] = (object) [
                        'id' => (int) ($row->id ?? 0),
                        'name' => $name,
                        'address' => $address,
                    ];
                }
            }
            if (
                Schema::hasTable('student_profiles')
                && Schema::hasColumn('student_profiles', 'pkl_place_name')
                && Schema::hasColumn('student_profiles', 'pkl_place_address')
            ) {
                $profileCompanies = DB::table('student_profiles')
                    ->whereNotNull('pkl_place_name')
                    ->where('pkl_place_name', '<>', '')
                    ->groupBy('pkl_place_name', 'pkl_place_address')
                    ->select('pkl_place_name as name', 'pkl_place_address as address')
                    ->get();
                foreach ($profileCompanies as $row) {
                    $name = trim((string) ($row->name ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $address = trim((string) ($row->address ?? '')) ?: '-';
                    $key = mb_strtolower($name . '||' . $address);
                    if (!array_key_exists($key, $companyMap)) {
                        $companyMap[$key] = (object) [
                            'id' => null,
                            'name' => $name,
                            'address' => $address,
                        ];
                    }
                }
            }
            $companyOptions = collect(array_values($companyMap))
                ->sortBy(fn ($row) => mb_strtolower((string) data_get($row, 'name')))
                ->values();
            $targetMentorCompany = null;
            if (
                Schema::hasTable('partner_companies')
                && Schema::hasColumn('users', 'partner_company_id')
                && !empty($target->partner_company_id)
            ) {
                $partnerSelect = ['id', 'name'];
                if (Schema::hasColumn('partner_companies', 'address')) {
                    $partnerSelect[] = 'address';
                }
                $targetMentorCompany = DB::table('partner_companies')
                    ->where('id', $target->partner_company_id)
                    ->first($partnerSelect);
            }

            $targetProfile = null;
            $classOptions = collect(['ALL']);
            $majorOptions = collect(['RPL', 'BDP', 'AKL']);
            if (Schema::hasTable('student_profiles')) {
                $hasStudentClassColumn = Schema::hasColumn('student_profiles', 'class_name');
                $hasStudentMajorColumn = Schema::hasColumn('student_profiles', 'major_name');
                $targetProfile = DB::table('student_profiles')
                    ->where('student_id', $target->id)
                    ->first();

                if ($hasStudentClassColumn) {
                    $dynamicClassOptions = DB::table('student_profiles')
                        ->whereNotNull('class_name')
                        ->whereRaw('TRIM(class_name) <> ""')
                        ->groupBy('class_name')
                        ->orderBy('class_name')
                        ->pluck('class_name')
                        ->map(fn ($value) => trim((string) $value))
                        ->filter()
                        ->values();
                    $classOptions = $classOptions->merge($dynamicClassOptions)->unique()->values();
                }

                if ($hasStudentMajorColumn) {
                    $dynamicMajorOptions = DB::table('student_profiles')
                        ->whereNotNull('major_name')
                        ->whereRaw('TRIM(major_name) <> ""')
                        ->groupBy('major_name')
                        ->orderBy('major_name')
                        ->pluck('major_name')
                        ->map(fn ($value) => strtoupper(trim((string) $value)))
                        ->filter()
                        ->values();
                    $majorOptions = $majorOptions->merge($dynamicMajorOptions)->unique()->values();
                }
            }

            return view('dashboard.super-admin-users-edit', [
                'target' => $target,
                'targetProfile' => $targetProfile,
                'roleOptions' => User::ROLES,
                'companyOptions' => $companyOptions,
                'targetMentorCompany' => $targetMentorCompany,
                'classOptions' => $classOptions,
                'majorOptions' => $majorOptions,
                'availablePermissionModules' => available_permission_modules(),
                'targetPermissions' => user_permissions_payload($target),
            ]);
        } catch (\Throwable $e) {
            @file_put_contents(
                storage_path('logs/user-edit-debug.log'),
                '[' . now('Asia/Jakarta')->toDateTimeString() . "] GET /users/{managedUser}/edit failed: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL . PHP_EOL,
                FILE_APPEND
            );
            report($e);
            return redirect()->route('dashboard.super-admin.users')
                ->withErrors(['users_edit' => 'Failed to open user edit page: ' . $e->getMessage()]);
        }
    })->name('dashboard.super-admin.users.edit');

    Route::post('/dashboard/super-admin/users/{managedUser}/edit', function (Request $request, int $managedUser) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'update');

        try {
            $target = User::findOrFail($managedUser);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:50', 'unique:users,nis,' . $target->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $target->id],
            'role' => ['required', 'in:' . implode(',', User::ROLES)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'mentor_company_name' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_MENTOR), 'nullable', 'string', 'max:150'],
            'mentor_company_address' => ['nullable', 'string', 'max:2000'],
            'major_name' => ['nullable', 'in:RPL,BDP,AKL'],
            'class_name' => ['nullable', 'string', 'max:120'],
            'birth_place' => ['nullable', 'string', 'max:120'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'pkl_place_name' => ['nullable', 'string', 'max:150'],
            'pkl_place_address' => ['nullable', 'string', 'max:2000'],
            'pkl_place_phone' => ['nullable', 'string', 'max:30'],
            'pkl_start_date' => ['nullable', 'date'],
            'pkl_end_date' => ['nullable', 'date', 'after_or_equal:pkl_start_date'],
            'mentor_teacher_name' => ['nullable', 'string', 'max:150'],
            'school_supervisor_teacher_name' => ['nullable', 'string', 'max:150'],
            'company_instructor_position' => ['nullable', 'string', 'max:150'],
            'kajur_major_name' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_KAJUR), 'nullable', 'in:RPL,BDP,AKL'],
            'kajur_red_flag_days' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_KAJUR), 'nullable', 'integer', 'between:1,14'],
            'teacher_class_name' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_TEACHER), 'nullable', 'string', 'max:120'],
        ]);

        $permissionModules = array_keys(array_merge(
            available_permission_modules(),
            ['mentor_review_center' => 'Mentor Review Center (Daily Scoring)']
        ));
        $permissionActions = ['view', 'create', 'update', 'delete'];
        $permissionsPayload = [];
        foreach ($permissionModules as $module) {
            foreach ($permissionActions as $action) {
                $permissionsPayload[$module][$action] = $request->boolean("permissions.{$module}.{$action}");
            }
        }

        $mentorCompanyId = null;
        if ($validated['role'] === User::ROLE_MENTOR) {
            if (!Schema::hasTable('partner_companies')) {
                return back()->withErrors(['mentor_company_name' => 'Partner companies table is not ready. Run migrations first.']);
            }
            if (!Schema::hasColumn('partner_companies', 'name')) {
                return back()->withErrors(['mentor_company_name' => 'Partner companies table is missing required `name` column.']);
            }
            if (!Schema::hasColumn('partner_companies', 'address')) {
                return back()->withErrors(['mentor_company_address' => 'Partner companies table is missing required `address` column.']);
            }
            $hasPartnerIsActiveColumn = Schema::hasColumn('partner_companies', 'is_active');
            $hasPartnerUpdatedAtColumn = Schema::hasColumn('partner_companies', 'updated_at');
            $hasPartnerCreatedAtColumn = Schema::hasColumn('partner_companies', 'created_at');

            $mentorCompanyName = trim((string) ($validated['mentor_company_name'] ?? ''));
            $mentorCompanyAddress = trim((string) ($validated['mentor_company_address'] ?? '')) ?: '-';
            if ($mentorCompanyName !== '') {
                $partnerPayload = [];
                if ($hasPartnerIsActiveColumn) {
                    $partnerPayload['is_active'] = true;
                }
                if ($hasPartnerUpdatedAtColumn) {
                    $partnerPayload['updated_at'] = now('Asia/Jakarta');
                }
                if ($hasPartnerCreatedAtColumn) {
                    $partnerPayload['created_at'] = now('Asia/Jakarta');
                }
                DB::table('partner_companies')->updateOrInsert(
                    ['name' => $mentorCompanyName, 'address' => $mentorCompanyAddress],
                    $partnerPayload
                );

                $mentorCompanyId = DB::table('partner_companies')
                    ->where('name', $mentorCompanyName)
                    ->where('address', $mentorCompanyAddress)
                    ->value('id');
            }
        }

        $target->name = $validated['name'];
        $target->nis = $validated['nis'];
        $target->email = $validated['email'];
        $target->role = $validated['role'];
        if (Schema::hasColumn('users', 'partner_company_id')) {
            $target->partner_company_id = $validated['role'] === User::ROLE_MENTOR ? $mentorCompanyId : null;
        }
        if (Schema::hasColumn('users', 'kajur_major_name')) {
            $target->kajur_major_name = $validated['role'] === User::ROLE_KAJUR
                ? strtoupper(trim((string) ($validated['kajur_major_name'] ?? '')))
                : null;
        }
        if (Schema::hasColumn('users', 'kajur_red_flag_days')) {
            $target->kajur_red_flag_days = $validated['role'] === User::ROLE_KAJUR
                ? max(1, (int) ($validated['kajur_red_flag_days'] ?? 2))
                : null;
        }
        if (Schema::hasColumn('users', 'teacher_class_name')) {
            $teacherClass = trim((string) ($validated['teacher_class_name'] ?? ''));
            $target->teacher_class_name = $validated['role'] === User::ROLE_TEACHER
                ? ($teacherClass !== '' ? $teacherClass : 'ALL')
                : null;
        }
        if (permissions_storage_ready()) {
            $target->permissions_json = $permissionsPayload;
        }
        if (!empty($validated['password'])) {
            $target->password = $validated['password'];
        }
        $target->save();

        if (Schema::hasTable('student_profiles') && $validated['role'] === User::ROLE_STUDENT) {
            $existingProfile = DB::table('student_profiles')
                ->where('student_id', $target->id)
                ->first();

            $profilePayload = [
                'updated_at' => now('Asia/Jakarta'),
                'created_at' => now('Asia/Jakarta'),
            ];
            $profileFields = [
                'major_name' => 'major_name',
                'class_name' => 'class_name',
                'birth_place' => 'birth_place',
                'birth_date' => 'birth_date',
                'address' => 'address',
                'phone_number' => 'phone_number',
                'pkl_place_name' => 'pkl_place_name',
                'pkl_place_address' => 'pkl_place_address',
                'pkl_place_phone' => 'pkl_place_phone',
                'pkl_start_date' => 'pkl_start_date',
                'pkl_end_date' => 'pkl_end_date',
                'mentor_teacher_name' => 'mentor_teacher_name',
                'school_supervisor_teacher_name' => 'school_supervisor_teacher_name',
                'company_instructor_position' => 'company_instructor_position',
            ];
            foreach ($profileFields as $column => $inputKey) {
                if (Schema::hasColumn('student_profiles', $column)) {
                    $profilePayload[$column] = array_key_exists($inputKey, $validated)
                        ? $validated[$inputKey]
                        : data_get($existingProfile, $column);
                }
            }

            DB::table('student_profiles')->updateOrInsert(
                ['student_id' => $target->id],
                $profilePayload
            );

            $studentCompanyName = trim((string) ($validated['pkl_place_name'] ?? ''));
            if (
                $studentCompanyName !== ''
                && Schema::hasTable('partner_companies')
                && Schema::hasColumn('partner_companies', 'name')
                && Schema::hasColumn('partner_companies', 'address')
            ) {
                $studentCompanyAddress = trim((string) ($validated['pkl_place_address'] ?? '')) ?: '-';
                $studentPartnerPayload = [];
                if (Schema::hasColumn('partner_companies', 'is_active')) {
                    $studentPartnerPayload['is_active'] = true;
                }
                if (Schema::hasColumn('partner_companies', 'updated_at')) {
                    $studentPartnerPayload['updated_at'] = now('Asia/Jakarta');
                }
                if (Schema::hasColumn('partner_companies', 'created_at')) {
                    $studentPartnerPayload['created_at'] = now('Asia/Jakarta');
                }
                DB::table('partner_companies')->updateOrInsert(
                    ['name' => $studentCompanyName, 'address' => $studentCompanyAddress],
                    $studentPartnerPayload
                );
            }
        }

        log_user_activity(
            $user,
            'user_updated',
            'user',
            $target->id,
            'User account updated.',
            [
                'name' => $target->name,
                'role' => $target->role,
                'edit_url' => route('dashboard.super-admin.users.edit', $target->id),
            ]
        );

            return redirect()->route('dashboard.super-admin.users')->with('status', 'User updated successfully.');
        } catch (\Throwable $e) {
            @file_put_contents(
                storage_path('logs/user-edit-debug.log'),
                '[' . now('Asia/Jakarta')->toDateTimeString() . "] POST /users/{managedUser}/edit failed: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL . PHP_EOL,
                FILE_APPEND
            );
            report($e);
            return back()->withInput()->withErrors(['users_edit' => 'Failed to update user: ' . $e->getMessage()]);
        }
    })->name('dashboard.super-admin.users.update');

    Route::post('/dashboard/super-admin/users/{managedUser}/delete', function (Request $request, int $managedUser) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'delete');

        $target = User::findOrFail($managedUser);
        if ($target->id === $user->id) {
            return back()->withErrors(['user_delete' => 'You cannot delete your own account.']);
        }

        if (!deleted_user_archive_storage_ready()) {
            return back()->withErrors(['user_delete' => 'Delete archive table is not ready. Run migrations first.']);
        }

        $archiveId = null;
        DB::transaction(function () use ($target, $user, &$archiveId) {
            $targetRow = DB::table('users')->where('id', $target->id)->first();
            $profileRow = Schema::hasTable('student_profiles')
                ? DB::table('student_profiles')->where('student_id', $target->id)->first()
                : null;

            $userPayload = $targetRow ? json_decode(json_encode($targetRow), true) : [];
            if (!empty($userPayload['permissions_json']) && is_string($userPayload['permissions_json'])) {
                $decodedPerm = json_decode($userPayload['permissions_json'], true);
                $userPayload['permissions_json'] = is_array($decodedPerm) ? $decodedPerm : null;
            }
            $profilePayload = $profileRow ? json_decode(json_encode($profileRow), true) : null;

            $archiveId = DB::table('deleted_user_archives')->insertGetId([
                'deleted_user_id' => $target->id,
                'deleted_by_user_id' => $user->id,
                'user_data' => json_encode($userPayload, JSON_UNESCAPED_UNICODE),
                'profile_data' => $profilePayload ? json_encode($profilePayload, JSON_UNESCAPED_UNICODE) : null,
                'deleted_at' => now('Asia/Jakarta'),
                'created_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]);

            $target->delete();
        });

        log_user_activity(
            $user,
            'user_deleted',
            'user',
            $target->id,
            'User account deleted and archived.',
            [
                'name' => $target->name,
                'role' => $target->role,
                'archive_id' => $archiveId,
            ],
            true
        );

        return back()->with('status', 'User deleted successfully.');
    })->name('dashboard.super-admin.users.delete');

    Route::post('/dashboard/super-admin/users/{managedUser}/reset-password', function (Request $request, int $managedUser) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);
        require_user_permission($user, 'users_management', 'update');

        $target = User::findOrFail($managedUser);
        $temporaryPassword = Str::upper(Str::random(8));
        $target->password = $temporaryPassword;
        $target->save();

        log_user_activity(
            $user,
            'user_password_reset',
            'user',
            $target->id,
            'User password reset.',
            [
                'name' => $target->name,
                'edit_url' => route('dashboard.super-admin.users.edit', $target->id),
            ]
        );

        return back()->with('status', "Password for {$target->name} reset. Temporary password: {$temporaryPassword}");
    })->name('dashboard.super-admin.users.reset-password');
});
