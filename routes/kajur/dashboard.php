<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/kajur/dashboard', function (Request $request) {
        $user = $request->user();
        require_user_permission($user, 'kajur_dashboard', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $seasonStart = $wibNow->copy()->subDays(29)->toDateString();

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        if ($managedMajor === '') {
            return view('dashboard.kajur-dashboard', [
                'managedMajor' => null,
                'summary' => null,
                'classPresence' => collect(),
                'recentAlerts' => collect(),
            ]);
        }

        $studentIds = collect();
        if (Schema::hasTable('student_profiles')) {
            $studentIds = DB::table('student_profiles as sp')
                ->join('users as u', 'u.id', '=', 'sp.student_id')
                ->where('u.role', User::ROLE_STUDENT)
                ->whereRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) = ?', [$managedMajor])
                ->pluck('sp.student_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
        }

        $totalStudents = $studentIds->count();
        $presentToday = 0;
        $lateToday = 0;
        $excusedToday = 0;
        if ($studentIds->isNotEmpty()) {
            $presentToday = DB::table('attendances')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->whereNotNull('check_in_at')
                ->count();
            $lateToday = DB::table('attendances')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->where('status', 'late')
                ->count();
            if (Schema::hasTable('attendance_excuses')) {
                $excusedToday = DB::table('attendance_excuses')
                    ->whereIn('student_id', $studentIds)
                    ->whereDate('attendance_date', $today)
                    ->where('status', 'approved')
                    ->count();
            }
        }
        $alphaToday = max(0, $totalStudents - $presentToday - $excusedToday);

        $classPresence = collect();
        if ($studentIds->isNotEmpty() && Schema::hasTable('student_profiles')) {
            $classPresence = DB::table('users as u')
                ->join('student_profiles as sp', 'sp.student_id', '=', 'u.id')
                ->leftJoin('attendances as a', function ($join) use ($today) {
                    $join->on('a.student_id', '=', 'u.id')
                        ->whereDate('a.attendance_date', $today);
                })
                ->whereIn('u.id', $studentIds)
                ->groupBy('sp.class_name')
                ->selectRaw("
                    COALESCE(NULLIF(TRIM(sp.class_name), ''), 'Unknown') as class_name,
                    COUNT(DISTINCT u.id) as total_students,
                    COUNT(DISTINCT a.student_id) as present_count
                ")
                ->get()
                ->map(function ($row) {
                    $total = (int) $row->total_students;
                    $present = (int) $row->present_count;
                    $row->rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
                    return $row;
                })
                ->sortBy('class_name')
                ->values();
        }

        $summary = (object) [
            'total_students' => $totalStudents,
            'present_today' => $presentToday,
            'late_today' => $lateToday,
            'excused_today' => $excusedToday,
            'alpha_today' => $alphaToday,
            'attendance_rate' => $totalStudents > 0 ? round(($presentToday / $totalStudents) * 100, 1) : 0,
        ];

        $recentAlerts = attendance_fetch_recent_alerts($user, 8);

        return view('dashboard.kajur-dashboard', [
            'managedMajor' => $managedMajor,
            'summary' => $summary,
            'classPresence' => $classPresence,
            'recentAlerts' => $recentAlerts,
            'today' => $today,
        ]);
    })->name('dashboard.kajur.dashboard');

    Route::get('/dashboard/kajur/weekly-journals', function (Request $request) {
        $user = $request->user();
        require_user_permission($user, 'kajur_weekly_journals', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $weekStart = trim((string) $request->query('week_start'));
        $weekEnd = trim((string) $request->query('week_end'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStart)) {
            $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekEnd)) {
            $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
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

        $managedMajor = Schema::hasColumn('users', 'kajur_major_name')
            ? strtoupper(trim((string) ($user->kajur_major_name ?? '')))
            : '';
        $selectedMajor = trim((string) $request->query('major', $managedMajor));
        if ($selectedMajor === '' || !$majorOptions->contains($selectedMajor)) {
            $selectedMajor = $managedMajor ?: (string) $majorOptions->first();
        }

        $hasStudentProfiles = Schema::hasTable('student_profiles');
        $studentsBase = DB::table('users as s')
            ->where('s.role', User::ROLE_STUDENT);
        if ($hasStudentProfiles) {
            $studentsBase->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id');
            $studentsBase->whereRaw('UPPER(COALESCE(sp.major_name, "")) = ?', [$selectedMajor]);
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

        $statusFilter = trim((string) $request->query('status', 'all'));
        $search = trim((string) $request->query('search', ''));

        $studentsQuery = (clone $studentsBase)
            ->when($selectedClass !== 'ALL', function ($query) use ($selectedClass) {
                $query->whereRaw('TRIM(COALESCE(sp.class_name, "")) = ?', [$selectedClass]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('s.name', 'like', "%{$search}%")
                        ->orWhere('s.nis', 'like', "%{$search}%");
                });
            });

        $students = $studentsQuery
            ->select(
                's.id as student_id',
                's.name as student_name',
                's.nis as student_nis',
                DB::raw($hasStudentProfiles ? 'sp.class_name as class_name' : 'NULL as class_name'),
                DB::raw($hasStudentProfiles ? 'sp.major_name as major_name' : 'NULL as major_name')
            )
            ->orderBy('s.name')
            ->paginate(100)
            ->withQueryString();

        $studentIds = $students->getCollection()->pluck('student_id')->map(fn ($id) => (int) $id)->filter()->values();

        $journalsByStudent = collect();
        if (Schema::hasTable('weekly_journals') && $studentIds->isNotEmpty()) {
            $journalsByStudent = DB::table('weekly_journals as wj')
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
                ->get()
                ->keyBy('student_id');
        }

        $students->setCollection(
            $students->getCollection()->map(function ($student) use ($journalsByStudent) {
                $journal = $journalsByStudent->get($student->student_id);
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

    Route::post('/dashboard/kajur/weekly-journal/{journal}', function (Request $request, int $journal) {
        $user = $request->user();
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

    Route::post('/dashboard/kajur/daily-log/{log}/academic-validation', function (Request $request, int $log) {
        $user = $request->user();
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
});
