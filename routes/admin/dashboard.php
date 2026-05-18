<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

Route::middleware(['auth'])->group(function () {

    // --- KAJUR ROUTES ---

    Route::get('/dashboard/kajur/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'kajur_weekly_journals', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

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
        if (!$majorOptions->contains($selectedMajor)) {
            $selectedMajor = $managedMajor;
        }

        $classOptions = collect(['ALL']);
        if (Schema::hasTable('student_profiles')) {
            $dynamicClasses = DB::table('student_profiles')
                ->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$selectedMajor])
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

        $selectedClass = trim((string) $request->query('class', 'ALL'));
        if (!$classOptions->contains($selectedClass)) {
            $selectedClass = 'ALL';
        }

        $statusFilter = strtolower(trim((string) $request->query('status', 'all')));
        $allowedStatusFilters = ['all', 'approved', 'submitted', 'needs_revision', 'draft', 'no_submission'];
        if (!in_array($statusFilter, $allowedStatusFilters, true)) {
            $statusFilter = 'all';
        }

        $search = trim((string) $request->query('q', ''));

        $studentsQuery = DB::table('users as s')
            ->join('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->where('s.role', User::ROLE_STUDENT)
            ->whereRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) = ?', [$selectedMajor]);

        if ($selectedClass !== 'ALL') {
            $studentsQuery->whereRaw('TRIM(COALESCE(sp.class_name, "")) = ?', [$selectedClass]);
        }

        if ($search !== '') {
            $needle = '%' . $search . '%';
            $studentsQuery->where(function ($query) use ($needle) {
                $query->where('s.name', 'like', $needle)
                    ->orWhere('s.nis', 'like', $needle);
            });
        }

        $weeklyJournalSub = DB::table('weekly_journals')
            ->whereDate('week_start_date', $weekStart)
            ->whereDate('week_end_date', $weekEnd)
            ->selectRaw('MAX(id) as journal_id, student_id')
            ->groupBy('student_id');

        $students = $studentsQuery
            ->leftJoinSub($weeklyJournalSub, 'wjs', function ($join) {
                $join->on('wjs.student_id', '=', 's.id');
            })
            ->leftJoin('weekly_journals as wj', 'wj.id', '=', 'wjs.journal_id')
            ->leftJoin('users as m', 'm.id', '=', 'wj.mentor_id')
            ->leftJoin('users as k', 'k.id', '=', 'wj.kajur_id')
            ->leftJoin('users as b', 'b.id', '=', 'wj.bindo_id')
            ->select(
                's.id as student_id',
                's.name as student_name',
                's.nis as student_nis',
                'sp.class_name',
                'sp.pkl_place_name',
                'wj.id as journal_id',
                'wj.status as journal_status',
                'wj.updated_at as journal_updated_at',
                'm.name as mentor_name',
                'k.name as kajur_name',
                'b.name as bindo_name'
            )
            ->orderBy('s.name')
            ->paginate(30)
            ->withQueryString();

        $students->setCollection(
            $students->getCollection()->map(function ($student) {
                $journal = DB::table('weekly_journals')
                    ->where('id', $student->journal_id)
                    ->first();
                $student->journal_status = data_get($journal, 'status');
                $student->journal_updated_at = data_get($journal, 'updated_at');
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

        $teacherOptions = collect();
        $supervisorsByStudent = [];
        if (school_supervisor_assignment_storage_ready()) {
            $teacherOptions = DB::table('users')
                ->where('role', User::ROLE_TEACHER)
                ->select('id', 'name', 'nis')
                ->orderBy('name')
                ->get();

            $visibleStudentIds = $students->getCollection()
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            if ($visibleStudentIds->isNotEmpty()) {
                $supervisorsByStudent = DB::table('student_school_supervisors as sss')
                    ->join('users as t', 't.id', '=', 'sss.teacher_id')
                    ->whereIn('sss.student_id', $visibleStudentIds)
                    ->where('t.role', User::ROLE_TEACHER)
                    ->orderBy('t.name')
                    ->get([
                        'sss.student_id',
                        't.id as teacher_id',
                        't.name as teacher_name',
                        't.nis as teacher_nis',
                    ])
                    ->groupBy(fn ($row) => (int) $row->student_id)
                    ->map(fn ($rows) => $rows->values())
                    ->toArray();
            }
        }

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
            'teacherOptions' => $teacherOptions,
            'supervisorsByStudent' => $supervisorsByStudent,
            'schoolSupervisorAssignmentReady' => school_supervisor_assignment_storage_ready(),
        ]);
    })->name('dashboard.kajur.weekly-journal');

    Route::post('/dashboard/kajur/student-supervisors/{student}/assign', function (Request $request, int $student) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'kajur_weekly_journals', 'update');

        if (!school_supervisor_assignment_storage_ready()) {
            return back()->withErrors(['school_supervisor' => 'School supervisor assignment table is not ready. Run migrations first.']);
        }

        $validated = $request->validate([
            'teacher_id' => ['required', 'integer'],
        ]);

        $teacherId = (int) $validated['teacher_id'];
        $teacher = DB::table('users')
            ->where('id', $teacherId)
            ->where('role', User::ROLE_TEACHER)
            ->first(['id']);
        if (!$teacher) {
            return back()->withErrors(['school_supervisor' => 'Selected pembimbing is invalid.']);
        }

        $studentRow = DB::table('users as s')
            ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->where('s.id', $student)
            ->where('s.role', User::ROLE_STUDENT)
            ->select(
                's.id',
                DB::raw('UPPER(TRIM(COALESCE(sp.major_name, ""))) as major_name')
            )
            ->first();
        if (!$studentRow) {
            return back()->withErrors(['school_supervisor' => 'Student not found.']);
        }

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        if ($managedMajor !== '' && $managedMajor !== strtoupper((string) ($studentRow->major_name ?? ''))) {
            return back()->withErrors(['school_supervisor' => 'You can only assign students in your managed major.']);
        }

        $existingCount = (int) DB::table('student_school_supervisors')
            ->where('student_id', (int) $studentRow->id)
            ->count();
        $alreadyAssigned = DB::table('student_school_supervisors')
            ->where('student_id', (int) $studentRow->id)
            ->where('teacher_id', $teacherId)
            ->exists();
        if (!$alreadyAssigned && $existingCount >= 2) {
            return back()->withErrors(['school_supervisor' => 'Maximum 2 pembimbing sekolah per student.']);
        }

        DB::table('student_school_supervisors')->updateOrInsert(
            [
                'student_id' => (int) $studentRow->id,
                'teacher_id' => $teacherId,
            ],
            [
                'assigned_by_kajur_id' => $user->id,
                'updated_at' => now('Asia/Jakarta'),
                'created_at' => now('Asia/Jakarta'),
            ]
        );

        return back()->with('status', 'Pembimbing sekolah assigned.');
    })->name('dashboard.kajur.student-supervisor.assign');

    Route::post('/dashboard/kajur/student-supervisors/{student}/remove', function (Request $request, int $student) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'kajur_weekly_journals', 'update');

        if (!school_supervisor_assignment_storage_ready()) {
            return back()->withErrors(['school_supervisor' => 'School supervisor assignment table is not ready.']);
        }

        $validated = $request->validate([
            'teacher_id' => ['required', 'integer'],
        ]);

        DB::table('student_school_supervisors')
            ->where('student_id', $student)
            ->where('teacher_id', (int) $validated['teacher_id'])
            ->delete();

        return back()->with('status', 'Pembimbing sekolah removed.');
    })->name('dashboard.kajur.student-supervisor.remove');

    Route::get('/dashboard/kajur/daily-checkin', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'kajur_daily_checkins', 'view');

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
                    'ip_address',
                    'location_address',
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
            $student->ip_address = data_get($attendance, 'ip_address');
            $student->location_address = trim((string) data_get($attendance, 'location_address', '')) ?: null;
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
        require_user_permission($user, 'kajur_daily_checkins', 'update');

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
        require_user_permission($user, 'kajur_daily_checkins', 'update');

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
        require_user_permission($user, 'kajur_daily_checkins', 'update');

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

        attendance_push_notification(
            'attendance_corrected',
            'Attendance on ' . $date . ' was corrected by Kajur to ' . strtoupper(str_replace('_', ' ', $correctionType)) . '.',
            [
                ['role' => User::ROLE_STUDENT, 'user_id' => (int) $student],
            ],
            data_get($studentRow, 'major_name'),
            data_get($studentRow, 'class_name'),
            $date
        );

        return back()->with('status', 'Attendance correction saved.');
    })->name('dashboard.kajur.attendance-correction');

    Route::get('/dashboard/kajur/absence-report', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === User::ROLE_KAJUR, 403);
        require_user_permission($user, 'kajur_absence_report', 'view');

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

        $recentExcuseRequestsByStudent = collect();
        if (Schema::hasTable('attendance_excuses') && $studentIds->isNotEmpty()) {
            $recentExcuseRequestsByStudent = DB::table('attendance_excuses as ae')
                ->leftJoin('users as reviewer', 'reviewer.id', '=', 'ae.reviewed_by_user_id')
                ->whereIn('ae.student_id', $studentIds)
                ->whereDate('ae.attendance_date', '>=', $startDate)
                ->whereDate('ae.attendance_date', '<=', $endDate)
                ->orderByDesc('ae.attendance_date')
                ->orderByDesc('ae.id')
                ->get([
                    'ae.student_id',
                    'ae.attendance_date',
                    'ae.absence_type',
                    'ae.status',
                    'ae.reviewed_at',
                    'ae.rejection_notes',
                    'reviewer.name as reviewed_by_name',
                ])
                ->groupBy(fn ($row) => (int) $row->student_id)
                ->map(fn ($items) => $items->take(3)->values());
        }

        $rows = $students->map(function ($student) use ($startDate, $endDate, $attendanceByStudentDate, $approvedExcuseByStudentDate, $recentExcuseRequestsByStudent, $managedMajor) {
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
            $attendanceRate = $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 2) : 0;

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
                'attendance_rate' => $attendanceRate,
                'recent_exc_requests' => $recentExcuseRequestsByStudent->get((int) $student->id, collect()),
            ];
        })->sortByDesc('attendance_rate')->values();

        $summary = [
            'students' => $rows->count(),
            'working_days_total' => (int) $rows->sum('working_days'),
            'present_total' => (int) $rows->sum('present_days'),
            'excused_total' => (int) $rows->sum('excused_days'),
            'alpha_total' => (int) $rows->sum('alpha_days'),
            'absence_total' => (int) $rows->sum('absence_days'),
        ];
        $summary['attendance_rate'] = $summary['working_days_total'] > 0
            ? round(($summary['present_total'] / $summary['working_days_total']) * 100, 2)
            : 0;

        if (strtolower(trim((string) $request->query('export', ''))) === 'csv') {
            $filename = 'kajur-attendance-report-' . $startDate . '-to-' . $endDate . '.csv';
            $handle = fopen('php://temp', 'r+');
            fputcsv($handle, ['Kajur Attendance Rate Report']);
            fputcsv($handle, ['Major', $managedMajor]);
            fputcsv($handle, ['Class Scope', $selectedClass]);
            fputcsv($handle, ['Period', $startDate . ' to ' . $endDate]);
            fputcsv($handle, []);
            fputcsv($handle, ['Student', 'NIS', 'Class', 'Working Days', 'Present', 'Excused', 'Alpha', 'Absence Days', 'Attendance Rate %']);
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
                    $row->attendance_rate,
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
        require_user_permission($user, 'kajur_dashboard', 'update');

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
        require_user_permission($user, 'kajur_weekly_journals', 'update');

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


    // --- TEACHER (BINDO) ROUTES ---

    Route::get('/dashboard/bindo/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        abort_unless(teacher_has_dashboard_access($user, 'view'), 403, 'You do not have permission for this action.');

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
                    'photo_path',
                    'location_address'
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
            $student->latest_latitude = $latitude !== null ? (float) $latitude : null;
            $student->latest_longitude = $longitude !== null ? (float) $longitude : null;
            $student->latest_location_address = trim((string) data_get($attendance, 'location_address', '')) ?: '-';

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

        $mapPoints = $assignedStudents
            ->filter(function ($student) {
                return $student->latest_latitude !== null
                    && $student->latest_longitude !== null;
            })
            ->map(function ($student) {
                return [
                    'student_id' => (int) $student->id,
                    'student_name' => (string) ($student->name ?? '-'),
                    'student_nis' => (string) ($student->nis ?? '-'),
                    'class_name' => (string) ($student->class_name ?? '-'),
                    'company_name' => (string) ($student->pkl_place_name ?? '-'),
                    'attendance_date' => (string) ($student->latest_attendance_date ?? '-'),
                    'location_address' => (string) ($student->latest_location_address ?? '-'),
                    'latitude' => (float) $student->latest_latitude,
                    'longitude' => (float) $student->latest_longitude,
                ];
            })
            ->values();

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
        $visitLogReady = Schema::hasTable('attendances');
        if ($visitLogReady && $supervisedStudentIds->isNotEmpty()) {
            $visitLogs = DB::table('attendances as a')
                ->join('users as s', 's.id', '=', 'a.student_id')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
                ->whereIn('a.student_id', $supervisedStudentIds)
                ->whereNotNull('a.photo_path')
                ->where('a.photo_path', '<>', '')
                ->select(
                    'a.id',
                    'a.photo_path',
                    'a.attendance_date',
                    'a.check_in_at',
                    DB::raw('NULL as visit_notes'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_name), ""), "-") as company_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_address), ""), "-") as company_address'),
                    's.name as student_name',
                    's.nis as student_nis'
                )
                ->orderByDesc(DB::raw('COALESCE(a.check_in_at, a.created_at)'))
                ->limit(20)
                ->get()
                ->map(function ($row) {
                    $row->visited_at = $row->check_in_at ?? (($row->attendance_date ?? null) ? ($row->attendance_date . ' 00:00:00') : null);
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
            'mapPoints' => $mapPoints,
            'rows' => $rows,
            'visitLogs' => $visitLogs,
            'visitLogReady' => $visitLogReady,
            'selectedStudentId' => $selectedStudentId,
            'today' => $wibNow->toDateString(),
        ]);
    })->name('dashboard.bindo.weekly-journal');

    Route::get('/dashboard/bindo/watchlist', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        abort_unless(teacher_access($user, 'teacher_watchlist', 'view'), 403, 'You do not have permission for this action.');

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $supervisedStudentIds = teacher_supervised_student_ids($user);

        $students = collect();
        if ($supervisedStudentIds->isNotEmpty()) {
            $students = DB::table('users as s')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
                ->where('s.role', User::ROLE_STUDENT)
                ->whereIn('s.id', $supervisedStudentIds)
                ->select(
                    's.id',
                    's.name',
                    's.nis',
                    DB::raw('COALESCE(NULLIF(TRIM(sp.major_name), ""), "-") as major_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_name), ""), "-") as pkl_place_name'),
                    'sp.pkl_start_date',
                    'sp.pkl_end_date'
                )
                ->orderBy('sp.class_name')
                ->orderBy('s.name')
                ->get();
        }

        $latestAttendanceByStudent = collect();
        $activeTodayStudentIds = collect();
        $attendanceHistoryByStudent = collect();
        $attendanceDateSetByStudent = collect();
        if (Schema::hasTable('attendances') && $supervisedStudentIds->isNotEmpty()) {
            $attendanceRows = DB::table('attendances')
                ->whereIn('student_id', $supervisedStudentIds)
                ->orderByDesc('attendance_date')
                ->orderByDesc(DB::raw('COALESCE(check_in_at, created_at)'))
                ->select('student_id', 'attendance_date', 'check_in_at', 'latitude', 'longitude', 'location_address')
                ->get();

            $latestAttendanceByStudent = $attendanceRows->unique('student_id')->keyBy('student_id');

            $attendanceHistoryRows = DB::table('attendances')
                ->whereIn('student_id', $supervisedStudentIds)
                ->orderByDesc('attendance_date')
                ->orderByDesc(DB::raw('COALESCE(check_in_at, created_at)'))
                ->select(
                    'student_id',
                    'attendance_date',
                    'check_in_at',
                    'check_out_at',
                    'status',
                    'late_minutes',
                    'latitude',
                    'longitude'
                )
                ->get();
            $attendanceHistoryByStudent = $attendanceHistoryRows
                ->groupBy('student_id')
                ->map(function ($rows) {
                    return $rows->take(14)->map(function ($row) {
                        return [
                            'attendance_date' => (string) ($row->attendance_date ?? ''),
                            'check_in_at' => $row->check_in_at
                                ? Carbon::parse($row->check_in_at, 'Asia/Jakarta')->format('d M Y H:i')
                                : null,
                            'check_out_at' => $row->check_out_at
                                ? Carbon::parse($row->check_out_at, 'Asia/Jakarta')->format('d M Y H:i')
                                : null,
                            'status' => strtoupper((string) ($row->status ?? 'PENDING')),
                            'late_minutes' => (int) ($row->late_minutes ?? 0),
                            'location' => ($row->latitude !== null && $row->longitude !== null)
                                ? ((string) $row->latitude . ', ' . (string) $row->longitude)
                                : '-',
                        ];
                    })->values();
                });
            $attendanceDateSetByStudent = $attendanceHistoryRows
                ->filter(fn ($row) => !empty($row->check_in_at) && !empty($row->attendance_date))
                ->groupBy('student_id')
                ->map(fn ($rows) => $rows->pluck('attendance_date')->flip());

            $activeTodayStudentIds = DB::table('attendances')
                ->whereIn('student_id', $supervisedStudentIds)
                ->whereDate('attendance_date', $today)
                ->whereNotNull('check_in_at')
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        $approvedExcuseRowsByStudent = collect();
        if (Schema::hasTable('attendance_excuses') && $supervisedStudentIds->isNotEmpty()) {
            $approvedExcuseRowsByStudent = DB::table('attendance_excuses')
                ->whereIn('student_id', $supervisedStudentIds)
                ->where('status', 'approved')
                ->select('student_id', 'attendance_date', 'absence_type')
                ->get()
                ->groupBy(fn ($row) => (int) $row->student_id);
        }

        $students = $students->map(function ($student) use ($latestAttendanceByStudent, $activeTodayStudentIds, $attendanceHistoryByStudent, $attendanceDateSetByStudent, $approvedExcuseRowsByStudent, $today) {
            $attendance = $latestAttendanceByStudent->get($student->id);
            $latitude = data_get($attendance, 'latitude');
            $longitude = data_get($attendance, 'longitude');

            $student->last_location = ($latitude !== null && $longitude !== null)
                ? ((string) $latitude . ', ' . (string) $longitude)
                : '-';
            $student->last_latitude = $latitude !== null ? (float) $latitude : null;
            $student->last_longitude = $longitude !== null ? (float) $longitude : null;
            $student->last_location_address = trim((string) data_get($attendance, 'location_address', '')) ?: '-';
            $student->last_checkin_date = data_get($attendance, 'attendance_date');
            $student->is_active_today = $activeTodayStudentIds->contains((int) $student->id);
            $student->attendance_history = $attendanceHistoryByStudent->get($student->id, collect())->values()->all();

            $attendanceSet = $attendanceDateSetByStudent->get((int) $student->id, collect());
            $approvedExcuseRows = $approvedExcuseRowsByStudent->get((int) $student->id, collect());
            $approvedExcuseSet = $approvedExcuseRows->pluck('attendance_date')->flip();
            $sickDays = (int) $approvedExcuseRows->filter(fn ($row) => strtolower((string) ($row->absence_type ?? '')) === 'sick')->count();
            $permitDays = (int) $approvedExcuseRows->filter(fn ($row) => strtolower((string) ($row->absence_type ?? '')) === 'permit')->count();
            $excusedDays = $sickDays + $permitDays;

            $effectiveStart = null;
            $pklStartRaw = trim((string) ($student->pkl_start_date ?? ''));
            if ($pklStartRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $pklStartRaw)) {
                $effectiveStart = Carbon::parse($pklStartRaw, 'Asia/Jakarta')->startOfDay();
            }

            if (!$effectiveStart) {
                $historyDates = collect($student->attendance_history)
                    ->pluck('attendance_date')
                    ->filter(fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
                    ->values();
                $excuseDates = $approvedExcuseRows
                    ->pluck('attendance_date')
                    ->filter(fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
                    ->values();
                $seedDate = $historyDates->merge($excuseDates)->sort()->first();
                $effectiveStart = $seedDate
                    ? Carbon::parse($seedDate, 'Asia/Jakarta')->startOfDay()
                    : Carbon::now('Asia/Jakarta')->subDays(30)->startOfDay();
            }

            $effectiveEnd = Carbon::parse($today, 'Asia/Jakarta')->startOfDay();
            $pklEndRaw = trim((string) ($student->pkl_end_date ?? ''));
            if ($pklEndRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $pklEndRaw)) {
                $pklEnd = Carbon::parse($pklEndRaw, 'Asia/Jakarta')->startOfDay();
                if ($pklEnd->lessThan($effectiveEnd)) {
                    $effectiveEnd = $pklEnd;
                }
            }
            if ($effectiveEnd->lessThan($effectiveStart)) {
                $effectiveEnd = $effectiveStart->copy();
            }

            $workingDays = 0;
            $cursor = $effectiveStart->copy();
            while ($cursor->lessThanOrEqualTo($effectiveEnd)) {
                $date = $cursor->toDateString();
                $majorName = trim((string) ($student->major_name ?? ''));
                $className = trim((string) ($student->class_name ?? ''));
                if (!in_array($cursor->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY], true)) {
                    if (!attendance_is_non_working_day($date, $majorName !== '' ? $majorName : null, $className !== '' ? $className : null)) {
                        $workingDays++;
                    }
                }
                $cursor->addDay();
            }

            $presentDays = (int) $attendanceSet->count();
            $missedDays = max(0, $workingDays - $presentDays - $excusedDays);
            $progressPercent = $workingDays > 0 ? (int) round(($presentDays / $workingDays) * 100) : 0;

            $student->attendance_summary = [
                'working_days' => $workingDays,
                'present_days' => $presentDays,
                'sick_days' => $sickDays,
                'permit_days' => $permitDays,
                'excused_days' => $excusedDays,
                'missed_days' => $missedDays,
                'progress_percent' => max(0, min(100, $progressPercent)),
            ];

            return $student;
        });

        $mapPoints = $students
            ->filter(function ($student) {
                return $student->last_latitude !== null
                    && $student->last_longitude !== null
                    && !is_nan((float) $student->last_latitude)
                    && !is_nan((float) $student->last_longitude);
            })
            ->map(function ($student) {
                return [
                    'student_id' => (int) $student->id,
                    'student_name' => (string) ($student->name ?? '-'),
                    'student_nis' => (string) ($student->nis ?? '-'),
                    'class_name' => (string) ($student->class_name ?? '-'),
                    'company_name' => (string) ($student->pkl_place_name ?? '-'),
                    'attendance_date' => (string) ($student->last_checkin_date ?? '-'),
                    'location_address' => (string) ($student->last_location_address ?? '-'),
                    'latitude' => (float) $student->last_latitude,
                    'longitude' => (float) $student->last_longitude,
                    'is_active_today' => (bool) ($student->is_active_today ?? false),
                ];
            })
            ->values();

        return view('dashboard.teacher-watchlist', [
            'students' => $students,
            'mapPoints' => $mapPoints,
            'today' => $today,
            'teacherClassScope' => Schema::hasColumn('users', 'teacher_class_name')
                ? trim((string) ($user->teacher_class_name ?? ''))
                : '',
        ]);
    })->name('dashboard.bindo.watchlist');

    Route::get('/dashboard/bindo/journal-audit', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        abort_unless(teacher_access($user, 'teacher_journal_audit', 'view'), 403, 'You do not have permission for this action.');

        $supervisedStudentIds = teacher_supervised_student_ids($user);
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
                ->limit(80)
                ->get();
        }

        return view('dashboard.teacher-journal-audit', [
            'rows' => $rows,
        ]);
    })->name('dashboard.bindo.journal-audit');

    Route::get('/dashboard/bindo/site-visits', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        abort_unless(teacher_access($user, 'teacher_site_visits', 'view'), 403, 'You do not have permission for this action.');

        $supervisedStudentIds = teacher_supervised_student_ids($user);
        $students = collect();
        if ($supervisedStudentIds->isNotEmpty()) {
            $students = DB::table('users as s')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
                ->where('s.role', User::ROLE_STUDENT)
                ->whereIn('s.id', $supervisedStudentIds)
                ->select(
                    's.id',
                    's.name',
                    's.nis',
                    DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_name), ""), "-") as pkl_place_name')
                )
                ->orderBy('s.name')
                ->get();
        }

        $visitLogReady = Schema::hasTable('attendances');
        $visitRows = collect();
        if ($visitLogReady && $supervisedStudentIds->isNotEmpty()) {
            $visitRows = DB::table('attendances as a')
                ->join('users as s', 's.id', '=', 'a.student_id')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
                ->whereIn('a.student_id', $supervisedStudentIds)
                ->whereNotNull('a.photo_path')
                ->where('a.photo_path', '<>', '')
                ->select(
                    'a.id',
                    'a.student_id',
                    'a.photo_path',
                    DB::raw('NULL as visit_notes'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_name), ""), "-") as company_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_address), ""), "-") as company_address'),
                    'a.attendance_date',
                    'a.check_in_at',
                    's.name as student_name',
                    's.nis as student_nis'
                )
                ->orderByDesc(DB::raw('COALESCE(a.check_in_at, a.created_at)'))
                ->get()
                ->map(function ($row) {
                    $row->photo_url = !empty($row->photo_path) ? Storage::url($row->photo_path) : null;
                    $row->visited_at = $row->check_in_at ?? (($row->attendance_date ?? null) ? ($row->attendance_date . ' 00:00:00') : null);
                    $row->visited_at_label = $row->visited_at
                        ? Carbon::parse($row->visited_at, 'Asia/Jakarta')->format('d M Y H:i')
                        : (trim((string) ($row->attendance_date ?? '')) !== '' ? Carbon::parse($row->attendance_date, 'Asia/Jakarta')->format('d M Y') : '-');
                    return $row;
                });
        }

        $visitRowsByStudent = $visitRows
            ->groupBy('student_id')
            ->map(function ($rows) {
                return $rows->values()->map(function ($row) {
                    return [
                        'id' => (int) $row->id,
                        'visited_at' => (string) ($row->visited_at ?? ''),
                        'visited_at_label' => (string) ($row->visited_at_label ?? '-'),
                        'company_name' => (string) ($row->company_name ?? '-'),
                        'company_address' => (string) ($row->company_address ?? '-'),
                        'visit_notes' => (string) ($row->visit_notes ?? ''),
                        'photo_url' => $row->photo_url,
                    ];
                })->all();
            });

        $studentLogs = $students->map(function ($student) use ($visitRowsByStudent) {
            $logs = collect($visitRowsByStudent->get($student->id, []))->values();
            $student->visit_count = $logs->count();
            $student->latest_visit_at = (string) data_get($logs->first(), 'visited_at_label', '-');
            $student->logs = $logs->all();
            return $student;
        });

        return view('dashboard.teacher-site-visits', [
            'studentLogs' => $studentLogs,
            'visitLogReady' => $visitLogReady,
        ]);
    })->name('dashboard.bindo.site-visits');

    Route::get('/dashboard/bindo/red-flags', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        abort_unless(teacher_access($user, 'teacher_red_flags', 'view'), 403, 'You do not have permission for this action.');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $windowStart = $wibNow->copy()->subDays(13)->toDateString();
        $weeklyWindowStart = $wibNow->copy()->subDays(27)->toDateString();

        $supervisedStudentIds = teacher_supervised_student_ids($user);
        $students = collect();
        if ($supervisedStudentIds->isNotEmpty()) {
            $students = DB::table('users as s')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
                ->where('s.role', User::ROLE_STUDENT)
                ->whereIn('s.id', $supervisedStudentIds)
                ->select(
                    's.id',
                    's.name',
                    's.nis',
                    DB::raw('COALESCE(NULLIF(TRIM(sp.major_name), ""), "-") as major_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_name), ""), "-") as pkl_place_name')
                )
                ->orderBy('sp.class_name')
                ->orderBy('s.name')
                ->get();
        }

        $attendanceRowsByStudent = collect();
        if (Schema::hasTable('attendances') && $supervisedStudentIds->isNotEmpty()) {
            $attendanceRowsByStudent = DB::table('attendances')
                ->whereIn('student_id', $supervisedStudentIds)
                ->whereBetween('attendance_date', [$windowStart, $today])
                ->select('student_id', 'attendance_date', 'check_in_at', 'late_minutes')
                ->get()
                ->groupBy(fn ($row) => (int) $row->student_id);
        }

        $approvedExcusesByStudent = collect();
        if (Schema::hasTable('attendance_excuses') && $supervisedStudentIds->isNotEmpty()) {
            $approvedExcusesByStudent = DB::table('attendance_excuses')
                ->whereIn('student_id', $supervisedStudentIds)
                ->where('status', 'approved')
                ->whereBetween('attendance_date', [$windowStart, $today])
                ->select('student_id', 'attendance_date')
                ->get()
                ->groupBy(fn ($row) => (int) $row->student_id);
        }

        $dailyLogRowsByStudent = collect();
        if (Schema::hasTable('daily_logs') && $supervisedStudentIds->isNotEmpty()) {
            $dailyLogRowsByStudent = DB::table('daily_logs')
                ->whereIn('student_id', $supervisedStudentIds)
                ->whereBetween('work_date', [$windowStart, $today])
                ->select('student_id', 'work_date', 'mentor_review_status')
                ->get()
                ->groupBy(fn ($row) => (int) $row->student_id);
        }

        $weeklyRowsByStudent = collect();
        if (Schema::hasTable('weekly_journals') && $supervisedStudentIds->isNotEmpty()) {
            $weeklyRowsByStudent = DB::table('weekly_journals')
                ->whereIn('student_id', $supervisedStudentIds)
                ->where('week_end_date', '>=', $weeklyWindowStart)
                ->select('student_id', 'week_start_date', 'week_end_date', 'status', 'bindo_reviewed_at')
                ->get()
                ->groupBy(fn ($row) => (int) $row->student_id);
        }

        $visitRowsByStudent = collect();
        if (Schema::hasTable('attendances') && $supervisedStudentIds->isNotEmpty()) {
            $visitRowsByStudent = DB::table('attendances')
                ->whereIn('student_id', $supervisedStudentIds)
                ->whereNotNull('photo_path')
                ->where('photo_path', '<>', '')
                ->select('student_id', 'check_in_at', 'attendance_date')
                ->orderByDesc(DB::raw('COALESCE(check_in_at, created_at)'))
                ->get()
                ->map(function ($row) {
                    $row->visited_at = $row->check_in_at ?? (($row->attendance_date ?? null) ? ($row->attendance_date . ' 00:00:00') : null);
                    return $row;
                })
                ->groupBy(fn ($row) => (int) $row->student_id);
        }

        $interventionStorageReady = Schema::hasTable('teacher_red_flag_interventions');
        $latestInterventionByStudent = collect();
        $recentInterventions = collect();
        if ($interventionStorageReady && $supervisedStudentIds->isNotEmpty()) {
            $latestSub = DB::table('teacher_red_flag_interventions')
                ->where('teacher_id', $user->id)
                ->whereIn('student_id', $supervisedStudentIds)
                ->groupBy('student_id')
                ->selectRaw('student_id, MAX(id) as latest_id');

            $latestInterventionByStudent = DB::table('teacher_red_flag_interventions as tri')
                ->joinSub($latestSub, 'latest', function ($join) {
                    $join->on('tri.id', '=', 'latest.latest_id');
                })
                ->select(
                    'tri.student_id',
                    'tri.risk_score',
                    'tri.risk_level',
                    'tri.intervention_status',
                    'tri.follow_up_date',
                    'tri.intervention_notes',
                    'tri.actioned_at'
                )
                ->get()
                ->keyBy(fn ($row) => (int) $row->student_id);

            $recentInterventions = DB::table('teacher_red_flag_interventions as tri')
                ->join('users as s', 's.id', '=', 'tri.student_id')
                ->where('tri.teacher_id', $user->id)
                ->whereIn('tri.student_id', $supervisedStudentIds)
                ->select(
                    'tri.id',
                    'tri.student_id',
                    'tri.risk_score',
                    'tri.risk_level',
                    'tri.intervention_status',
                    'tri.follow_up_date',
                    'tri.intervention_notes',
                    'tri.actioned_at',
                    's.name as student_name',
                    's.nis as student_nis'
                )
                ->orderByDesc('tri.actioned_at')
                ->limit(20)
                ->get();
        }

        $flaggedStudents = $students->map(function ($student) use (
            $attendanceRowsByStudent,
            $approvedExcusesByStudent,
            $dailyLogRowsByStudent,
            $weeklyRowsByStudent,
            $visitRowsByStudent,
            $latestInterventionByStudent,
            $windowStart,
            $today,
            $wibNow
        ) {
            $attendanceRows = collect($attendanceRowsByStudent->get((int) $student->id, []));
            $excuseRows = collect($approvedExcusesByStudent->get((int) $student->id, []));
            $dailyRows = collect($dailyLogRowsByStudent->get((int) $student->id, []));
            $weeklyRows = collect($weeklyRowsByStudent->get((int) $student->id, []));
            $visitRows = collect($visitRowsByStudent->get((int) $student->id, []));

            $start = Carbon::parse($windowStart, 'Asia/Jakarta')->startOfDay();
            $end = Carbon::parse($today, 'Asia/Jakarta')->startOfDay();
            $workingDays = 0;
            $cursor = $start->copy();
            while ($cursor->lessThanOrEqualTo($end)) {
                if (!in_array($cursor->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY], true)) {
                    $majorName = trim((string) ($student->major_name ?? ''));
                    $className = trim((string) ($student->class_name ?? ''));
                    if (!attendance_is_non_working_day($cursor->toDateString(), $majorName !== '' ? $majorName : null, $className !== '' ? $className : null)) {
                        $workingDays++;
                    }
                }
                $cursor->addDay();
            }

            $presentDays = $attendanceRows
                ->filter(fn ($row) => !empty($row->check_in_at) && !empty($row->attendance_date))
                ->pluck('attendance_date')
                ->unique()
                ->count();
            $lateDays = $attendanceRows
                ->filter(fn ($row) => (int) ($row->late_minutes ?? 0) > 0 && !empty($row->attendance_date))
                ->pluck('attendance_date')
                ->unique()
                ->count();
            $veryLateDays = $attendanceRows
                ->filter(fn ($row) => (int) ($row->late_minutes ?? 0) >= 30 && !empty($row->attendance_date))
                ->pluck('attendance_date')
                ->unique()
                ->count();
            $excusedDays = $excuseRows
                ->pluck('attendance_date')
                ->filter(fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
                ->unique()
                ->count();
            $missedDays = max(0, $workingDays - $presentDays - $excusedDays);

            $lastCheckInAt = $attendanceRows
                ->filter(fn ($row) => !empty($row->check_in_at))
                ->pluck('check_in_at')
                ->filter()
                ->sort()
                ->last();
            $daysSinceLastCheckIn = $lastCheckInAt
                ? Carbon::parse($lastCheckInAt, 'Asia/Jakarta')->startOfDay()->diffInDays($wibNow->copy()->startOfDay())
                : null;
            $lastCheckInLabel = $lastCheckInAt
                ? Carbon::parse($lastCheckInAt, 'Asia/Jakarta')->format('d M Y H:i')
                : '-';

            $dailyLogDays = $dailyRows
                ->pluck('work_date')
                ->filter(fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
                ->unique()
                ->count();
            $mentorReviseCount = $dailyRows
                ->filter(fn ($row) => strtolower((string) ($row->mentor_review_status ?? '')) === 'revise')
                ->count();

            $needsRevisionCount = $weeklyRows
                ->filter(fn ($row) => strtolower((string) ($row->status ?? '')) === 'needs_revision')
                ->count();
            $draftCount = $weeklyRows
                ->filter(function ($row) use ($wibNow) {
                    $status = strtolower((string) ($row->status ?? ''));
                    $endDate = trim((string) ($row->week_end_date ?? ''));
                    if ($status !== 'draft' || $endDate === '') {
                        return false;
                    }
                    return Carbon::parse($endDate, 'Asia/Jakarta')->lt($wibNow->copy()->subDays(2)->startOfDay());
                })
                ->count();
            $submittedUnreviewedCount = $weeklyRows
                ->filter(function ($row) use ($wibNow) {
                    $status = strtolower((string) ($row->status ?? ''));
                    $endDate = trim((string) ($row->week_end_date ?? ''));
                    if ($status !== 'submitted' || !empty($row->bindo_reviewed_at) || $endDate === '') {
                        return false;
                    }
                    return Carbon::parse($endDate, 'Asia/Jakarta')->lt($wibNow->copy()->subDays(2)->startOfDay());
                })
                ->count();

            $lastVisitAt = $visitRows->pluck('visited_at')->filter()->sort()->last();
            $daysSinceLastVisit = $lastVisitAt
                ? Carbon::parse($lastVisitAt, 'Asia/Jakarta')->startOfDay()->diffInDays($wibNow->copy()->startOfDay())
                : null;
            $lastVisitLabel = $lastVisitAt
                ? Carbon::parse($lastVisitAt, 'Asia/Jakarta')->format('d M Y H:i')
                : '-';

            $score = 0;
            $indicators = [];

            if ($missedDays >= 3) {
                $score += 35;
                $indicators[] = "Missed attendance in {$missedDays} working day(s) (14-day window).";
            } elseif ($missedDays >= 1) {
                $score += 20;
                $indicators[] = "Missed attendance in {$missedDays} working day(s) (14-day window).";
            }

            if ($lateDays >= 3) {
                $score += 20;
                $indicators[] = "{$lateDays} late check-in day(s).";
            } elseif ($lateDays >= 1) {
                $score += 10;
                $indicators[] = "{$lateDays} late check-in day(s).";
            }
            if ($veryLateDays >= 1) {
                $score += 10;
                $indicators[] = "{$veryLateDays} very-late day(s) (>= 30 minutes).";
            }

            if ($daysSinceLastCheckIn === null) {
                $score += 15;
                $indicators[] = 'No check-in data found in the last 14 days.';
            } elseif ($daysSinceLastCheckIn >= 4) {
                $score += 15;
                $indicators[] = "No check-in in the last {$daysSinceLastCheckIn} day(s).";
            }

            if ($needsRevisionCount >= 2) {
                $score += 15;
                $indicators[] = "{$needsRevisionCount} weekly journal(s) need revision.";
            } elseif ($needsRevisionCount === 1) {
                $score += 8;
                $indicators[] = '1 weekly journal needs revision.';
            }
            if ($submittedUnreviewedCount >= 1) {
                $score += 12;
                $indicators[] = "{$submittedUnreviewedCount} submitted journal(s) still unreviewed.";
            }
            if ($draftCount >= 1) {
                $score += 10;
                $indicators[] = "{$draftCount} weekly journal draft(s) not finalized.";
            }

            if ($dailyLogDays <= 2) {
                $score += 10;
                $indicators[] = "Low daily-log consistency ({$dailyLogDays} day(s) logged in 14 days).";
            }
            if ($mentorReviseCount >= 2) {
                $score += 8;
                $indicators[] = "Mentor requested revision in {$mentorReviseCount} daily log(s).";
            }

            if ($daysSinceLastVisit === null) {
                $score += 8;
                $indicators[] = 'No student photo evidence found recently.';
            } elseif ($daysSinceLastVisit > 21) {
                $score += 8;
                $indicators[] = "No student photo evidence in {$daysSinceLastVisit} day(s).";
            }

            $score = min(100, $score);
            $riskLevel = 'low';
            if ($score >= 70) {
                $riskLevel = 'critical';
            } elseif ($score >= 40) {
                $riskLevel = 'high';
            } elseif ($score >= 20) {
                $riskLevel = 'medium';
            }

            $latestIntervention = $latestInterventionByStudent->get((int) $student->id);
            $requiresIntervention = $score >= 40 || $missedDays >= 3 || $needsRevisionCount >= 2;

            $student->risk_score = $score;
            $student->risk_level = $riskLevel;
            $student->indicators = $indicators;
            $student->indicator_snapshot = [
                'working_days' => $workingDays,
                'present_days' => $presentDays,
                'excused_days' => $excusedDays,
                'missed_days' => $missedDays,
                'late_days' => $lateDays,
                'very_late_days' => $veryLateDays,
                'daily_log_days' => $dailyLogDays,
                'mentor_revise_count' => $mentorReviseCount,
                'weekly_needs_revision_count' => $needsRevisionCount,
                'weekly_draft_count' => $draftCount,
                'weekly_submitted_unreviewed_count' => $submittedUnreviewedCount,
                'days_since_last_checkin' => $daysSinceLastCheckIn,
                'days_since_last_visit' => $daysSinceLastVisit,
            ];
            $student->requires_intervention = $requiresIntervention;
            $student->last_checkin_label = $lastCheckInLabel;
            $student->last_visit_label = $lastVisitLabel;
            $student->latest_intervention = $latestIntervention ? [
                'risk_score' => (int) ($latestIntervention->risk_score ?? 0),
                'risk_level' => (string) ($latestIntervention->risk_level ?? 'low'),
                'intervention_status' => (string) ($latestIntervention->intervention_status ?? 'open'),
                'follow_up_date' => $latestIntervention->follow_up_date,
                'intervention_notes' => (string) ($latestIntervention->intervention_notes ?? ''),
                'actioned_at_label' => !empty($latestIntervention->actioned_at)
                    ? Carbon::parse($latestIntervention->actioned_at, 'Asia/Jakarta')->format('d M Y H:i')
                    : '-',
            ] : null;

            return $student;
        })->sortBy([
            ['requires_intervention', 'desc'],
            ['risk_score', 'desc'],
            ['name', 'asc'],
        ])->values();

        $riskSummary = [
            'critical' => (int) $flaggedStudents->where('risk_level', 'critical')->count(),
            'high' => (int) $flaggedStudents->where('risk_level', 'high')->count(),
            'medium' => (int) $flaggedStudents->where('risk_level', 'medium')->count(),
            'low' => (int) $flaggedStudents->where('risk_level', 'low')->count(),
            'needs_intervention' => (int) $flaggedStudents->where('requires_intervention', true)->count(),
            'total_students' => (int) $flaggedStudents->count(),
        ];

        return view('dashboard.teacher-red-flags', [
            'students' => $flaggedStudents,
            'riskSummary' => $riskSummary,
            'today' => $today,
            'windowStart' => $windowStart,
            'interventionStorageReady' => $interventionStorageReady,
            'recentInterventions' => $recentInterventions,
            'teacherClassScope' => Schema::hasColumn('users', 'teacher_class_name')
                ? trim((string) ($user->teacher_class_name ?? ''))
                : '',
        ]);
    })->name('dashboard.bindo.red-flags');

    Route::post('/dashboard/bindo/red-flags/intervention', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        abort_unless(teacher_access($user, 'teacher_red_flags', 'create'), 403, 'You do not have permission for this action.');

        if (!Schema::hasTable('teacher_red_flag_interventions')) {
            return back()->withErrors([
                'red_flag_intervention' => 'Red flag intervention table is not ready. Run migrations first.',
            ]);
        }

        $supervisedStudentIds = teacher_supervised_student_ids($user);
        $validated = $request->validate([
            'student_id' => ['required', Rule::in($supervisedStudentIds->all())],
            'intervention_status' => ['required', Rule::in(['open', 'monitoring', 'resolved'])],
            'follow_up_date' => ['nullable', 'date'],
            'intervention_notes' => ['required', 'string', 'max:7000'],
            'risk_score' => ['nullable', 'integer', 'between:0,100'],
            'risk_level' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'indicator_snapshot' => ['nullable', 'string'],
        ]);

        $indicatorSnapshot = null;
        $rawSnapshot = trim((string) ($validated['indicator_snapshot'] ?? ''));
        if ($rawSnapshot !== '') {
            $decoded = json_decode($rawSnapshot, true);
            if (is_array($decoded)) {
                $indicatorSnapshot = $decoded;
            }
        }

        DB::table('teacher_red_flag_interventions')->insert([
            'teacher_id' => $user->id,
            'student_id' => (int) $validated['student_id'],
            'risk_score' => (int) ($validated['risk_score'] ?? 0),
            'risk_level' => (string) ($validated['risk_level'] ?? 'low'),
            'intervention_status' => (string) $validated['intervention_status'],
            'intervention_notes' => trim((string) $validated['intervention_notes']),
            'follow_up_date' => $validated['follow_up_date'] ?? null,
            'indicator_snapshot' => $indicatorSnapshot ? json_encode($indicatorSnapshot, JSON_UNESCAPED_UNICODE) : null,
            'actioned_at' => now('Asia/Jakarta'),
            'created_at' => now('Asia/Jakarta'),
            'updated_at' => now('Asia/Jakarta'),
        ]);

        $notifyMeta = attendance_notification_targets_for_student((int) $validated['student_id']);
        attendance_push_notification(
            'teacher_red_flag_intervention',
            'Teacher intervention logged for student ID ' . (int) $validated['student_id'] . ' with status ' . strtoupper((string) $validated['intervention_status']) . '.',
            (array) data_get($notifyMeta, 'targets', []),
            data_get($notifyMeta, 'major_name'),
            data_get($notifyMeta, 'class_name'),
            now('Asia/Jakarta')->toDateString()
        );

        return back()->with('status', 'Intervention action saved.');
    })->name('dashboard.bindo.red-flags.intervention.store');

    Route::get('/dashboard/bindo/contact-directory', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        abort_unless(teacher_access($user, 'teacher_contact_directory', 'view'), 403, 'You do not have permission for this action.');

        $supervisedStudentIds = teacher_supervised_student_ids($user);
        $students = collect();
        if ($supervisedStudentIds->isNotEmpty()) {
            $students = DB::table('users as s')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
                ->where('s.role', User::ROLE_STUDENT)
                ->whereIn('s.id', $supervisedStudentIds)
                ->select(
                    's.id',
                    's.name',
                    's.nis',
                    DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_name), ""), "-") as company_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_address), ""), "-") as company_address'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.mentor_teacher_name), ""), "") as fallback_contact_name'),
                    DB::raw('COALESCE(NULLIF(TRIM(sp.pkl_place_phone), ""), "") as fallback_phone')
                )
                ->orderBy('sp.class_name')
                ->orderBy('s.name')
                ->get();
        }

        $companiesByKey = collect();
        if (Schema::hasTable('partner_companies')) {
            $hasContactPerson = Schema::hasColumn('partner_companies', 'contact_person');
            $hasContactPhone = Schema::hasColumn('partner_companies', 'contact_phone');
            $hasContactEmail = Schema::hasColumn('partner_companies', 'contact_email');
            $hasWebsite = Schema::hasColumn('partner_companies', 'website_url');
            $hasIsActive = Schema::hasColumn('partner_companies', 'is_active');

            $companySelect = [
                'name',
                DB::raw('COALESCE(NULLIF(TRIM(address), ""), "-") as address'),
            ];
            $companySelect[] = $hasContactPerson ? 'contact_person' : DB::raw('NULL as contact_person');
            $companySelect[] = $hasContactPhone ? 'contact_phone' : DB::raw('NULL as contact_phone');
            $companySelect[] = $hasContactEmail ? 'contact_email' : DB::raw('NULL as contact_email');
            $companySelect[] = $hasWebsite ? 'website_url' : DB::raw('NULL as website_url');

            $companyRowsQuery = DB::table('partner_companies')->select($companySelect);
            if ($hasIsActive) {
                $companyRowsQuery->where('is_active', true);
            }

            $companiesByKey = $companyRowsQuery
                ->get()
                ->keyBy(function ($row) {
                    $name = trim((string) ($row->name ?? ''));
                    $address = trim((string) ($row->address ?? '-')) ?: '-';
                    return mb_strtolower($name . '||' . $address);
                });
        }

        $students = $students->map(function ($student) use ($companiesByKey) {
            $companyName = trim((string) ($student->company_name ?? '-')) ?: '-';
            $companyAddress = trim((string) ($student->company_address ?? '-')) ?: '-';
            $companyKey = mb_strtolower($companyName . '||' . $companyAddress);
            $company = $companiesByKey->get($companyKey);

            $student->contact_person = trim((string) data_get($company, 'contact_person', ''));
            if ($student->contact_person === '') {
                $student->contact_person = trim((string) ($student->fallback_contact_name ?? ''));
            }
            $student->contact_person = $student->contact_person !== '' ? $student->contact_person : $companyName;

            $student->contact_phone = trim((string) data_get($company, 'contact_phone', ''));
            if ($student->contact_phone === '') {
                $student->contact_phone = trim((string) ($student->fallback_phone ?? ''));
            }

            $student->contact_email = trim((string) data_get($company, 'contact_email', ''));
            $student->website_url = trim((string) data_get($company, 'website_url', ''));
            $student->whatsapp_url = normalize_whatsapp_number($student->contact_phone);

            return $student;
        })->values();

        $studentsWithWhatsApp = (int) $students->filter(fn ($student) => !empty($student->whatsapp_url))->count();
        $studentsWithoutContact = (int) $students->filter(function ($student) {
            $phone = trim((string) ($student->contact_phone ?? ''));
            $email = trim((string) ($student->contact_email ?? ''));
            return $phone === '' && $email === '';
        })->count();
        $companyCount = (int) $students
            ->map(fn ($student) => mb_strtolower(trim((string) ($student->company_name ?? '-')) . '||' . trim((string) ($student->company_address ?? '-'))))
            ->filter(fn ($key) => $key !== '||' && $key !== '-||-')
            ->unique()
            ->count();

        return view('dashboard.teacher-contact-directory', [
            'students' => $students,
            'summary' => [
                'total_students' => (int) $students->count(),
                'company_count' => $companyCount,
                'students_with_whatsapp' => $studentsWithWhatsApp,
                'students_without_contact' => $studentsWithoutContact,
            ],
        ]);
    })->name('dashboard.bindo.contact-directory');

    Route::post('/dashboard/bindo/weekly-journal/{journal}', function (Request $request, int $journal) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        abort_unless(teacher_access($user, 'teacher_journal_audit', 'update'), 403, 'You do not have permission for this action.');

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
        abort_unless(teacher_access($user, 'teacher_site_visits', 'view'), 403, 'You do not have permission for this action.');

        return redirect()->route('dashboard.bindo.site-visits')
            ->withErrors(['site_visit' => 'Manual teacher upload is disabled. Site Visit Log now uses student check-in photo evidence.']);
    })->name('dashboard.bindo.site-visit.store');


    // --- PRINCIPAL ROUTES ---

    Route::get('/dashboard/principal/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'principal', 403);
        require_user_permission($user, 'principal_dashboard', 'view');

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
        require_user_permission($user, 'principal_master_report', 'view');

        $format = strtolower(trim((string) $request->query('format', 'excel')));
        if (!in_array($format, ['excel', 'pdf'], true)) {
            $format = 'excel';
        }

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
        fputcsv($handle, ['Journal Week', Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') . ' - ' . Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y')]);
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

    Route::get('/dashboard/principal/master-report-page', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'principal', 403);
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
                ->selectRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) as major_key, COUNT(DISTINCT sp.student_id) as total_placed')
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
