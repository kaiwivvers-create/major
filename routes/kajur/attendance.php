<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/kajur/daily-checkin', function (Request $request) {
        $user = $request->user();
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
            ->select('s.id', DB::raw('UPPER(TRIM(COALESCE(sp.major_name, ""))) as major_name'), 'sp.class_name')
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
                'recent_excuse_requests' => $recentExcuseRequestsByStudent->get((int) $student->id, collect()),
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
});
