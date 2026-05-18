<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Route::middleware(['auth'])->group(function () {
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

    Route::get('/dashboard/student/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'weekly_journal', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $profile = Schema::hasTable('student_profiles')
            ? DB::table('student_profiles')->where('student_id', $user->id)->first()
            : null;

        $journal = DB::table('weekly_journals')
            ->where('student_id', $user->id)
            ->whereDate('week_start_date', $weekStart)
            ->whereDate('week_end_date', $weekEnd)
            ->first();

        $dailyLogs = DB::table('daily_logs')
            ->where('student_id', $user->id)
            ->whereBetween('work_date', [$weekStart, $weekEnd])
            ->orderBy('work_date')
            ->get();

        return view('dashboard.student-weekly-journal', [
            'profile' => $profile,
            'journal' => $journal,
            'dailyLogs' => $dailyLogs,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'wibNow' => $wibNow,
        ]);
    })->name('dashboard.student.weekly-journal');

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
});
