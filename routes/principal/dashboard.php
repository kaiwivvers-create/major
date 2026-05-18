<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/principal/master-report', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_PRINCIPAL, 403);
        require_user_permission($user, 'principal_master_report', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $defaultWeekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekStartInput = trim((string) $request->query('week_start', $defaultWeekStart));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStartInput)) {
            $weekStartDate = Carbon::parse($weekStartInput, 'Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        } else {
            $weekStartDate = Carbon::parse($defaultWeekStart, 'Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        }
        $weekStart = $weekStartDate->toDateString();
        $weekEnd = $weekStartDate->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $payload = build_principal_dashboard_payload($weekStart, $weekEnd, $today);

        $search = trim((string) $request->query('q', ''));
        $perPageOptions = [10, 20, 50, 100];
        $perPage = (int) $request->query('per_page', 20);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }
        $currentPage = max(1, (int) $request->query('page', 1));

        $filteredPlacementRows = collect($payload['placementRows'] ?? []);
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $filteredPlacementRows = $filteredPlacementRows->filter(function ($row) use ($needle) {
                return Str::contains(mb_strtolower((string) ($row->student_name ?? '')), $needle)
                    || Str::contains(mb_strtolower((string) ($row->student_nis ?? '')), $needle)
                    || Str::contains(mb_strtolower((string) ($row->major_name ?? '')), $needle)
                    || Str::contains(mb_strtolower((string) ($row->class_name ?? '')), $needle)
                    || Str::contains(mb_strtolower((string) ($row->company_name ?? '')), $needle);
            });
        }

        $totalRows = $filteredPlacementRows->count();
        $items = $filteredPlacementRows->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $placementPage = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $totalRows,
            $perPage,
            $currentPage,
            [
                'path' => url()->current(),
                'query' => $request->query(),
            ]
        );

        return view('dashboard.principal-master-report', array_merge($payload, [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'search' => $search,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'placementPage' => $placementPage,
        ]));
    })->name('dashboard.principal.master-report-page');

    Route::get('/dashboard/principal/partner-companies', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_PRINCIPAL, 403);
        require_user_permission($user, 'principal_partner_companies', 'view');

        $q = trim((string) $request->query('q', ''));
        $majorOptions = collect(['ALL', 'RPL', 'BDP', 'AKL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->where('major_name', '<>', '')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter()
                ->values();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->values();
        }
        if ($majorOptions->count() === 1) {
            $majorOptions = $majorOptions->merge(collect(['RPL', 'BDP', 'AKL']))->unique()->values();
        }

        $selectedMajor = strtoupper(trim((string) $request->query('major', 'ALL')));
        if (!$majorOptions->contains($selectedMajor)) {
            $selectedMajor = 'ALL';
        }

        $perPageOptions = [10, 20, 50];
        $perPage = (int) $request->query('per_page', 20);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }
        $page = max(1, (int) $request->query('page', 1));

        $makeKey = function (string $name, string $address): string {
            return mb_strtolower(trim($name) . '||' . trim($address));
        };

        $statsRows = collect();
        if (Schema::hasTable('student_profiles')) {
            $statsQuery = DB::table('student_profiles as sp')
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
                ");

            if ($selectedMajor !== 'ALL' && Schema::hasColumn('student_profiles', 'major_name')) {
                $statsQuery->whereRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) = ?', [$selectedMajor]);
            }

            $statsRows = $statsQuery->get();
        }

        $statsByKey = $statsRows->keyBy(function ($row) use ($makeKey) {
            $name = trim((string) ($row->company_name ?? ''));
            $address = trim((string) ($row->company_address ?? '')) ?: '-';
            return $makeKey($name, $address);
        });

        $merged = collect();
        if (Schema::hasTable('partner_companies')) {
            $partnerSelect = ['name', 'address'];
            $optionalColumns = ['logo_url', 'contact_person', 'contact_phone', 'contact_email', 'website_url', 'max_students', 'is_active'];
            foreach ($optionalColumns as $column) {
                if (Schema::hasColumn('partner_companies', $column)) {
                    $partnerSelect[] = $column;
                }
            }

            $partnerQuery = DB::table('partner_companies')
                ->whereNotNull('name')
                ->where('name', '<>', '')
                ->select($partnerSelect)
                ->orderBy('name')
                ->orderBy('address');
            if (in_array('is_active', $partnerSelect, true)) {
                $partnerQuery->where('is_active', true);
            }

            $partnerRows = $partnerQuery->get();

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
                    'logo_url' => data_get($partner, 'logo_url'),
                    'contact_person' => data_get($partner, 'contact_person'),
                    'contact_phone' => data_get($partner, 'contact_phone'),
                    'contact_email' => data_get($partner, 'contact_email'),
                    'website_url' => data_get($partner, 'website_url'),
                    'max_students' => data_get($partner, 'max_students') !== null ? (int) data_get($partner, 'max_students') : null,
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
                $existing = $merged->get($key);
                $existing->total_students = (int) ($row->total_students ?? 0);
                $existing->total_majors = (int) ($row->total_majors ?? 0);
                $merged[$key] = $existing;
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

        $sorted = $merged
            ->sortByDesc(fn ($row) => (int) ($row->total_students ?? 0))
            ->sortBy(fn ($row) => mb_strtolower((string) $row->company_name))
            ->values();

        $summary = [
            'companies_total' => $sorted->count(),
            'students_total' => (int) $sorted->sum(fn ($row) => (int) ($row->total_students ?? 0)),
            'companies_without_meta' => (int) $sorted->where('has_meta', false)->count(),
            'over_capacity' => (int) $sorted->filter(function ($row) {
                return !is_null($row->max_students) && (int) $row->total_students > (int) $row->max_students;
            })->count(),
        ];

        $total = $sorted->count();
        $items = $sorted->forPage($page, $perPage)->values();
        $companies = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboard.principal-partner-companies', [
            'companies' => $companies,
            'summary' => $summary,
            'q' => $q,
            'selectedMajor' => $selectedMajor,
            'majorOptions' => $majorOptions,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
        ]);
    })->name('dashboard.principal.partner-companies');

    Route::get('/dashboard/principal/journal-oversight', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_PRINCIPAL, 403);
        require_user_permission($user, 'principal_journal_oversight', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $defaultWeekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekStartInput = trim((string) $request->query('week_start', $defaultWeekStart));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStartInput)) {
            $weekStartDate = Carbon::parse($weekStartInput, 'Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        } else {
            $weekStartDate = Carbon::parse($defaultWeekStart, 'Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        }
        $weekStart = $weekStartDate->toDateString();
        $weekEnd = $weekStartDate->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $payload = build_principal_dashboard_payload($weekStart, $weekEnd, $today);
        $rows = collect($payload['rows'] ?? collect());

        $statusFilter = strtolower(trim((string) $request->query('status', 'all')));
        $allowedStatusFilters = ['all', 'draft', 'submitted', 'needs_revision', 'approved'];
        if (!in_array($statusFilter, $allowedStatusFilters, true)) {
            $statusFilter = 'all';
        }

        $followupFilter = strtolower(trim((string) $request->query('followup', 'all')));
        if (!in_array($followupFilter, ['all', 'yes', 'no'], true)) {
            $followupFilter = 'all';
        }

        $search = trim((string) $request->query('q', ''));
        $perPageOptions = [10, 20, 50, 100];
        $perPage = (int) $request->query('per_page', 20);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }
        $currentPage = max(1, (int) $request->query('page', 1));

        $summary = [
            'total' => $rows->count(),
            'draft' => $rows->where('status', 'draft')->count(),
            'submitted' => $rows->where('status', 'submitted')->count(),
            'needs_revision' => $rows->where('status', 'needs_revision')->count(),
            'approved' => $rows->where('status', 'approved')->count(),
        ];

        $rows = $rows->map(function ($row) {
            $status = strtolower(trim((string) ($row->status ?? '')));
            $mentorIncorrect = (int) ($row->mentor_is_correct ?? -1) === 0;
            $hasMissingInfo = trim((string) ($row->missing_info_notes ?? '')) !== '';
            $isDraft = $status === 'draft';
            $needsRevision = $status === 'needs_revision';
            $needsFollowup = $isDraft || $needsRevision || $mentorIncorrect || $hasMissingInfo;

            $row->needs_followup = $needsFollowup;
            $row->oversight_flag = $needsRevision
                ? 'Revision'
                : ($isDraft
                    ? 'Not Submitted'
                    : ($mentorIncorrect
                        ? 'Mentor Issue'
                        : ($hasMissingInfo ? 'Missing Info' : 'On Track')));
            return $row;
        });

        $followupSummary = [
            'needs_followup' => $rows->where('needs_followup', true)->count(),
            'on_track' => $rows->where('needs_followup', false)->count(),
        ];

        $riskRows = $rows->filter(fn ($row) => (bool) ($row->needs_followup ?? false))
            ->sortBy(function ($row) {
                $status = strtolower(trim((string) ($row->status ?? '')));
                $rank = match ($status) {
                    'needs_revision' => 1,
                    'draft' => 2,
                    'submitted' => 3,
                    default => 4,
                };
                return $rank . '|' . mb_strtolower((string) ($row->student_name ?? ''));
            })
            ->take(10)
            ->values();

        $filteredRows = $rows;
        if ($statusFilter !== 'all') {
            $filteredRows = $filteredRows->filter(fn ($row) => strtolower((string) ($row->status ?? '')) === $statusFilter)->values();
        }
        if ($followupFilter === 'yes') {
            $filteredRows = $filteredRows->filter(fn ($row) => (bool) ($row->needs_followup ?? false))->values();
        } elseif ($followupFilter === 'no') {
            $filteredRows = $filteredRows->filter(fn ($row) => !(bool) ($row->needs_followup ?? false))->values();
        }
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $filteredRows = $filteredRows->filter(function ($row) use ($needle) {
                return Str::contains(mb_strtolower((string) ($row->student_name ?? '')), $needle)
                    || Str::contains(mb_strtolower((string) ($row->student_nis ?? '')), $needle)
                    || Str::contains(mb_strtolower((string) ($row->mentor_name ?? '')), $needle)
                    || Str::contains(mb_strtolower((string) ($row->kajur_name ?? '')), $needle)
                    || Str::contains(mb_strtolower((string) ($row->bindo_name ?? '')), $needle)
                    || Str::contains(mb_strtolower((string) ($row->learning_notes ?? '')), $needle);
            })->values();
        }

        $totalRows = $filteredRows->count();
        $items = $filteredRows->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $rowsPage = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $totalRows,
            $perPage,
            $currentPage,
            [
                'path' => url()->current(),
                'query' => $request->query(),
            ]
        );

        return view('dashboard.principal-journal-oversight', array_merge($payload, [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'statusFilter' => $statusFilter,
            'followupFilter' => $followupFilter,
            'search' => $search,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'summary' => $summary,
            'followupSummary' => $followupSummary,
            'riskRows' => $riskRows,
            'rowsPage' => $rowsPage,
        ]));
    })->name('dashboard.principal.journal-oversight');

    Route::get('/dashboard/principal/school-performance', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_PRINCIPAL, 403);
        require_user_permission($user, 'principal_school_performance', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $periodOptions = [14, 30, 60, 90];
        $periodDays = (int) $request->query('period_days', 30);
        if (!in_array($periodDays, $periodOptions, true)) {
            $periodDays = 30;
        }
        $periodStart = $wibNow->copy()->subDays($periodDays - 1)->toDateString();

        $defaultWeekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekStartInput = trim((string) $request->query('week_start', $defaultWeekStart));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStartInput)) {
            $weekStartDate = Carbon::parse($weekStartInput, 'Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        } else {
            $weekStartDate = Carbon::parse($defaultWeekStart, 'Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        }
        $weekStart = $weekStartDate->toDateString();
        $weekEnd = $weekStartDate->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $majorOptions = collect(['RPL', 'BDP', 'AKL']);
        if (Schema::hasTable('student_profiles') && Schema::hasColumn('student_profiles', 'major_name')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->whereRaw('TRIM(major_name) <> ""')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($major) => strtoupper(trim((string) $major)))
                ->filter()
                ->values();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->sort()->values();
        }
        $majorOptions = collect(['ALL'])->merge($majorOptions)->values();

        $selectedMajor = strtoupper(trim((string) $request->query('major', 'ALL')));
        if (!$majorOptions->contains($selectedMajor)) {
            $selectedMajor = 'ALL';
        }

        $studentCountsByMajor = collect();
        $placedCountsByMajor = collect();
        if (Schema::hasTable('student_profiles')) {
            $studentBase = DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->where('u.role', User::ROLE_STUDENT);
            if ($selectedMajor !== 'ALL') {
                $studentBase->whereRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) = ?', [$selectedMajor]);
            }

            $studentCountsByMajor = (clone $studentBase)
                ->groupByRaw('UPPER(TRIM(COALESCE(sp.major_name, "")))')
                ->selectRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) as major_key, COUNT(DISTINCT sp.student_id) as total_students')
                ->pluck('total_students', 'major_key');

            $placedCountsByMajor = (clone $studentBase)
                ->whereRaw('TRIM(COALESCE(sp.pkl_place_name, "")) <> ""')
                ->groupByRaw('UPPER(TRIM(COALESCE(sp.major_name, "")))')
                ->selectRaw('UPPER(TRIM(COALESCE(sp.major_key, ""))) as major_key, COUNT(DISTINCT sp.student_id) as total_placed')
                ->pluck('total_placed', 'major_key');
        }

        $attendanceByMajor = collect();
        if (Schema::hasTable('attendances') && Schema::hasTable('student_profiles')) {
            $attendanceBase = DB::table('attendances as a')
                ->join('student_profiles as sp', 'sp.student_id', '=', 'a.student_id')
                ->join('users as u', 'u.id', '=', 'a.student_id')
                ->where('u.role', User::ROLE_STUDENT)
                ->whereDate('a.attendance_date', '>=', $periodStart)
                ->whereDate('a.attendance_date', '<=', $today)
                ->whereNotNull('a.check_in_at');

            if ($selectedMajor !== 'ALL') {
                $attendanceBase->whereRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) = ?', [$selectedMajor]);
            }

            $attendanceByMajor = $attendanceBase
                ->groupByRaw('UPPER(TRIM(COALESCE(sp.major_name, "")))')
                ->selectRaw("UPPER(TRIM(COALESCE(sp.major_name, ''))) as major_key, COUNT(DISTINCT CONCAT(a.student_id, '|', a.attendance_date)) as attended_days")
                ->pluck('attended_days', 'major_key');
        }

        $journalByMajor = collect();
        if (Schema::hasTable('weekly_journals') && Schema::hasTable('student_profiles')) {
            $journalBase = DB::table('weekly_journals as wj')
                ->join('student_profiles as sp', 'sp.student_id', '=', 'wj.student_id')
                ->join('users as u', 'u.id', '=', 'wj.student_id')
                ->where('u.role', User::ROLE_STUDENT)
                ->whereDate('wj.week_start_date', $weekStart)
                ->whereDate('wj.week_end_date', $weekEnd);

            if ($selectedMajor !== 'ALL') {
                $journalBase->whereRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) = ?', [$selectedMajor]);
            }

            $journalByMajor = $journalBase
                ->groupByRaw('UPPER(TRIM(COALESCE(sp.major_name, "")))')
                ->selectRaw("
                    UPPER(TRIM(COALESCE(sp.major_name, ''))) as major_key,
                    COUNT(*) as journal_total,
                    SUM(CASE WHEN LOWER(COALESCE(wj.status, '')) = 'approved' THEN 1 ELSE 0 END) as approved_total
                ")
                ->get()
                ->keyBy('major_key');
        }

        $majorsForRows = $selectedMajor === 'ALL'
            ? $majorOptions->reject(fn ($major) => $major === 'ALL')->values()
            : collect([$selectedMajor]);

        $workingDays = 0;
        $cursor = Carbon::parse($periodStart, 'Asia/Jakarta')->startOfDay();
        $endCursor = Carbon::parse($today, 'Asia/Jakarta')->startOfDay();
        while ($cursor->lessThanOrEqualTo($endCursor)) {
            if (!in_array((int) $cursor->dayOfWeekIso, [6, 7], true)) {
                $workingDays++;
            }
            $cursor->addDay();
        }

        $performanceRows = $majorsForRows->map(function ($major) use ($studentCountsByMajor, $placedCountsByMajor, $attendanceByMajor, $journalByMajor, $workingDays) {
            $majorKey = strtoupper((string) $major);
            $students = (int) ($studentCountsByMajor[$majorKey] ?? 0);
            $placed = (int) ($placedCountsByMajor[$majorKey] ?? 0);
            $attendedDays = (int) ($attendanceByMajor[$majorKey] ?? 0);
            $journalTotal = (int) data_get($journalByMajor->get($majorKey), 'journal_total', 0);
            $approvedTotal = (int) data_get($journalByMajor->get($majorKey), 'approved_total', 0);

            $expectedAttendanceDays = $students * max(1, $workingDays);
            $placementRate = $students > 0 ? round(($placed / $students) * 100, 1) : 0;
            $attendanceRate = $expectedAttendanceDays > 0 ? round(($attendedDays / $expectedAttendanceDays) * 100, 1) : 0;
            $journalApprovalRate = $journalTotal > 0 ? round(($approvedTotal / $journalTotal) * 100, 1) : 0;

            return (object) [
                'major' => $majorKey,
                'students' => $students,
                'placed' => $placed,
                'placement_rate' => $placementRate,
                'attended_days' => $attendedDays,
                'expected_attendance_days' => $expectedAttendanceDays,
                'attendance_rate' => $attendanceRate,
                'journal_total' => $journalTotal,
                'journal_approved' => $approvedTotal,
                'journal_approval_rate' => $journalApprovalRate,
            ];
        })->sortByDesc('attendance_rate')->values();

        $summary = [
            'students_total' => (int) $performanceRows->sum('students'),
            'placed_total' => (int) $performanceRows->sum('placed'),
            'journals_total' => (int) $performanceRows->sum('journal_total'),
            'journals_approved' => (int) $performanceRows->sum('journal_approved'),
            'avg_attendance_rate' => $performanceRows->count() > 0 ? round($performanceRows->avg('attendance_rate'), 1) : 0,
            'avg_journal_approval_rate' => $performanceRows->count() > 0 ? round($performanceRows->avg('journal_approval_rate'), 1) : 0,
        ];

        return view('dashboard.principal-school-performance', [
            'today' => $today,
            'periodStart' => $periodStart,
            'periodDays' => $periodDays,
            'periodOptions' => $periodOptions,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'selectedMajor' => $selectedMajor,
            'majorOptions' => $majorOptions,
            'workingDays' => $workingDays,
            'summary' => $summary,
            'performanceRows' => $performanceRows,
        ]);
    })->name('dashboard.principal.school-performance');

    Route::get('/dashboard/principal/timeline', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_PRINCIPAL, 403);
        require_user_permission($user, 'principal_timeline', 'view');

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $majorOptions = collect(['ALL']);
        if (Schema::hasTable('student_profiles') && Schema::hasColumn('student_profiles', 'major_name')) {
            $dynamicMajors = DB::table('student_profiles')
                ->whereNotNull('major_name')
                ->whereRaw('TRIM(major_name) <> ""')
                ->distinct()
                ->pluck('major_name')
                ->map(fn ($major) => strtoupper(trim((string) $major)))
                ->filter()
                ->sort()
                ->values();
            $majorOptions = $majorOptions->merge($dynamicMajors)->unique()->values();
        }
        if ($majorOptions->count() === 1) {
            $majorOptions = $majorOptions->merge(collect(['RPL', 'BDP', 'AKL']))->unique()->values();
        }

        $selectedMajor = strtoupper(trim((string) $request->query('major', 'ALL')));
        if (!$majorOptions->contains($selectedMajor)) {
            $selectedMajor = 'ALL';
        }

        $classOptions = collect(['ALL']);
        if (
            Schema::hasTable('student_profiles')
            && Schema::hasColumn('student_profiles', 'major_name')
            && Schema::hasColumn('student_profiles', 'class_name')
        ) {
            $classQuery = DB::table('student_profiles')
                ->whereNotNull('class_name')
                ->whereRaw('TRIM(class_name) <> ""');
            if ($selectedMajor !== 'ALL') {
                $classQuery->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$selectedMajor]);
            }
            $dynamicClasses = $classQuery
                ->distinct()
                ->pluck('class_name')
                ->map(fn ($class) => trim((string) $class))
                ->filter()
                ->sort()
                ->values();
            $classOptions = $classOptions->merge($dynamicClasses)->unique()->values();
        }

        $selectedClass = trim((string) $request->query('class', 'ALL'));
        if (!$classOptions->contains($selectedClass)) {
            $selectedClass = 'ALL';
        }

        $statusFilter = strtolower(trim((string) $request->query('status', 'all')));
        $allowedStatusFilters = ['all', 'not_started', 'ongoing', 'completed', 'missing_schedule'];
        if (!in_array($statusFilter, $allowedStatusFilters, true)) {
            $statusFilter = 'all';
        }

        $search = trim((string) $request->query('q', ''));
        $perPageOptions = [20, 50, 100];
        $perPage = (int) $request->query('per_page', 20);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }
        $currentPage = max(1, (int) $request->query('page', 1));

        $hasProfiles = Schema::hasTable('student_profiles');
        $hasMajorColumn = $hasProfiles && Schema::hasColumn('student_profiles', 'major_name');
        $hasClassColumn = $hasProfiles && Schema::hasColumn('student_profiles', 'class_name');
        $hasCompanyNameColumn = $hasProfiles && Schema::hasColumn('student_profiles', 'pkl_place_name');
        $hasCompanyAddressColumn = $hasProfiles && Schema::hasColumn('student_profiles', 'pkl_place_address');
        $hasStartColumn = $hasProfiles && Schema::hasColumn('student_profiles', 'pkl_start_date');
        $hasEndColumn = $hasProfiles && Schema::hasColumn('student_profiles', 'pkl_end_date');

        $students = collect();
        if ($hasProfiles) {
            $studentsQuery = DB::table('users as s')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
                ->where('s.role', User::ROLE_STUDENT);

            if ($selectedMajor !== 'ALL' && $hasMajorColumn) {
                $studentsQuery->whereRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) = ?', [$selectedMajor]);
            }
            if ($selectedClass !== 'ALL' && $hasClassColumn) {
                $studentsQuery->whereRaw('TRIM(COALESCE(sp.class_name, "")) = ?', [$selectedClass]);
            }
            if ($search !== '') {
                $needle = '%' . $search . '%';
                $studentsQuery->where(function ($query) use ($needle, $hasMajorColumn, $hasClassColumn, $hasCompanyNameColumn) {
                    $query->where('s.name', 'like', $needle)
                        ->orWhere('s.nis', 'like', $needle);
                    if ($hasMajorColumn) {
                        $query->orWhere('sp.major_name', 'like', $needle);
                    }
                    if ($hasClassColumn) {
                        $query->orWhere('sp.class_name', 'like', $needle);
                    }
                    if ($hasCompanyNameColumn) {
                        $query->orWhere('sp.pkl_place_name', 'like', $needle);
                    }
                });
            }

            $students = $studentsQuery
                ->select(
                    's.id as student_id',
                    's.name as student_name',
                    's.nis as student_nis',
                    DB::raw($hasMajorColumn ? 'sp.major_name as major_name' : 'NULL as major_name'),
                    DB::raw($hasClassColumn ? 'sp.class_name as class_name' : 'NULL as class_name'),
                    DB::raw($hasCompanyNameColumn ? 'sp.pkl_place_name as company_name' : 'NULL as company_name'),
                    DB::raw($hasCompanyAddressColumn ? 'sp.pkl_place_address as company_address' : 'NULL as company_address'),
                    DB::raw($hasStartColumn ? 'sp.pkl_start_date as pkl_start_date' : 'NULL as pkl_start_date'),
                    DB::raw($hasEndColumn ? 'sp.pkl_end_date as pkl_end_date' : 'NULL as pkl_end_date')
                )
                ->orderBy('s.name')
                ->get();
        }

        $rows = $students->map(function ($row) use ($today) {
            $startDate = trim((string) ($row->pkl_start_date ?? ''));
            $endDate = trim((string) ($row->pkl_end_date ?? ''));

            $statusKey = 'missing_schedule';
            $statusLabel = 'Missing Schedule';
            $progressPercent = 0;
            $elapsedDays = 0;
            $totalDays = 0;

            if ($startDate !== '' && $endDate !== '') {
                $start = Carbon::parse($startDate, 'Asia/Jakarta')->startOfDay();
                $end = Carbon::parse($endDate, 'Asia/Jakarta')->startOfDay();
                $todayDate = Carbon::parse($today, 'Asia/Jakarta')->startOfDay();
                $totalDays = max(1, $start->diffInDays($end) + 1);

                if ($todayDate->lessThan($start)) {
                    $statusKey = 'not_started';
                    $statusLabel = 'Not Started';
                    $elapsedDays = 0;
                } elseif ($todayDate->greaterThan($end)) {
                    $statusKey = 'completed';
                    $statusLabel = 'Completed';
                    $elapsedDays = $totalDays;
                } else {
                    $statusKey = 'ongoing';
                    $statusLabel = 'Ongoing';
                    $elapsedDays = max(1, $start->diffInDays($todayDate) + 1);
                }

                $progressPercent = (int) round(($elapsedDays / $totalDays) * 100);
            }

            $row->major_name = strtoupper(trim((string) ($row->major_name ?? ''))) ?: '-';
            $row->class_name = trim((string) ($row->class_name ?? '')) ?: '-';
            $row->company_name = trim((string) ($row->company_name ?? '')) ?: '-';
            $row->company_address = trim((string) ($row->company_address ?? '')) ?: '-';
            $row->status_key = $statusKey;
            $row->status_label = $statusLabel;
            $row->elapsed_days = $elapsedDays;
            $row->total_days = $totalDays;
            $row->progress_percent = $progressPercent;

            return $row;
        });

        $summary = [
            'total_students' => $rows->count(),
            'missing_schedule' => $rows->where('status_key', 'missing_schedule')->count(),
            'not_started' => $rows->where('status_key', 'not_started')->count(),
            'ongoing' => $rows->where('status_key', 'ongoing')->count(),
            'completed' => $rows->where('status_key', 'completed')->count(),
        ];

        if ($statusFilter !== 'all') {
            $rows = $rows->where('status_key', $statusFilter)->values();
        } else {
            $rows = $rows->values();
        }

        $totalRows = $rows->count();
        $items = $rows->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $rowsPage = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $totalRows,
            $perPage,
            $currentPage,
            [
                'path' => url()->current(),
                'query' => $request->query(),
            ]
        );

        $timelinePayload = build_implementation_timeline_payload($today, $selectedMajor);

        return view('dashboard.principal-timeline', [
            'today' => $today,
            'selectedMajor' => $selectedMajor,
            'majorOptions' => $majorOptions,
            'selectedClass' => $selectedClass,
            'classOptions' => $classOptions,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'summary' => $summary,
            'rowsPage' => $rowsPage,
            'timelineStart' => $timelinePayload['timelineStart'],
            'timelineEnd' => $timelinePayload['timelineEnd'],
            'timelineWeeks' => $timelinePayload['timelineWeeks'],
            'timelineStatus' => $timelinePayload['timelineStatus'],
        ]);
    })->name('dashboard.principal.timeline');

});
