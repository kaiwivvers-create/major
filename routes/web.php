<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
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
            return redirect()->route('dashboard.mentor.weekly-journal');
        }
        if ($user->role === 'kajur') {
            return redirect()->route('dashboard.kajur.weekly-journal');
        }
        if ($user->role === 'teacher') {
            return redirect()->route('dashboard.bindo.weekly-journal');
        }
        if ($user->role === 'principal') {
            return redirect()->route('dashboard.principal.weekly-journal');
        }

        return redirect('/')->with('status', 'Dashboard for your role is not available yet.');
    })->name('dashboard');

    Route::get('/dashboard/student', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $todayAttendance = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $todayLog = DB::table('daily_logs')
            ->where('student_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $weeklyJournal = DB::table('weekly_journals')
            ->where('student_id', $user->id)
            ->whereDate('week_start_date', $weekStart)
            ->whereDate('week_end_date', $weekEnd)
            ->first();

        $completedDays = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereNotNull('check_out_at')
            ->count();

        $targetDays = 90;
        $progressPercent = min(100, (int) round(($completedDays / $targetDays) * 100));

        return view('dashboard.student', [
            'todayAttendance' => $todayAttendance,
            'todayLog' => $todayLog,
            'weeklyJournal' => $weeklyJournal,
            'completedDays' => $completedDays,
            'targetDays' => $targetDays,
            'progressPercent' => $progressPercent,
        ]);
    })->name('dashboard.student');

    Route::get('/dashboard/student/checkin', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();

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

        return view('dashboard.student-checkin', [
            'todayAttendance' => $todayAttendance,
            'attendanceHistory' => $attendanceHistory,
            'today' => $today,
            'wibNow' => $wibNow,
        ]);
    })->name('dashboard.student.checkin-page');

    Route::get('/dashboard/student/task-log', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);

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

        $completedDays = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereNotNull('check_out_at')
            ->count();

        $targetDays = 90;
        $progressPercent = min(100, (int) round(($completedDays / $targetDays) * 100));

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

    Route::post('/dashboard/student/task-log', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);

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

    Route::post('/dashboard/student/check-in', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);

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

        DB::table('attendances')->updateOrInsert(
            [
                'student_id' => $user->id,
                'attendance_date' => $today,
            ],
            [
                'check_in_at' => $wibNow,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'ip_address' => $request->ip(),
                'photo_path' => $photoPath,
                'status' => 'pending',
                'updated_at' => $wibNow,
                'created_at' => $wibNow,
            ]
        );

        return back()->with('status', 'Check-in successful.');
    })->name('dashboard.student.check-in');

    Route::post('/dashboard/student/check-out', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);

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
                'status' => 'present',
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

    Route::get('/dashboard/student/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);

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

        $wibNow = Carbon::now('Asia/Jakarta');
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $rows = DB::table('weekly_journals as wj')
            ->join('users as s', 's.id', '=', 'wj.student_id')
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

        return view('dashboard.mentor-weekly-journal', [
            'rows' => $rows,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
        ]);
    })->name('dashboard.mentor.weekly-journal');

    Route::post('/dashboard/mentor/weekly-journal/{journal}', function (Request $request, int $journal) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);

        $validated = $request->validate([
            'mentor_is_correct' => ['required', 'in:1,0'],
            'missing_info_notes' => ['nullable', 'string', 'max:7000'],
        ]);

        $wibNow = Carbon::now('Asia/Jakarta');
        $isCorrect = $validated['mentor_is_correct'] === '1';

        DB::table('weekly_journals')
            ->where('id', $journal)
            ->update([
                'mentor_id' => $user->id,
                'mentor_is_correct' => $isCorrect,
                'missing_info_notes' => $isCorrect ? null : ($validated['missing_info_notes'] ?? null),
                'status' => $isCorrect ? 'approved' : 'needs_revision',
                'mentor_reviewed_at' => $wibNow,
                'updated_at' => $wibNow,
            ]);

        return back()->with('status', 'Mentor validation updated.');
    })->name('dashboard.mentor.weekly-journal.review');

    Route::get('/dashboard/kajur/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'kajur', 403);

        $wibNow = Carbon::now('Asia/Jakarta');
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $rows = DB::table('weekly_journals as wj')
            ->join('users as s', 's.id', '=', 'wj.student_id')
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
                's.nis as student_nis'
            )
            ->orderBy('s.name')
            ->get();

        return view('dashboard.kajur-weekly-journal', [
            'rows' => $rows,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
        ]);
    })->name('dashboard.kajur.weekly-journal');

    Route::post('/dashboard/kajur/weekly-journal/{journal}', function (Request $request, int $journal) {
        $user = $request->user();
        abort_unless($user->role === 'kajur', 403);

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

        $wibNow = Carbon::now('Asia/Jakarta');
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $rows = DB::table('weekly_journals as wj')
            ->join('users as s', 's.id', '=', 'wj.student_id')
            ->whereDate('wj.week_start_date', $weekStart)
            ->whereDate('wj.week_end_date', $weekEnd)
            ->select(
                'wj.id',
                'wj.learning_notes',
                'wj.student_mentor_notes',
                'wj.bindo_notes',
                'wj.status',
                's.name as student_name',
                's.nis as student_nis'
            )
            ->orderBy('s.name')
            ->get();

        return view('dashboard.bindo-weekly-journal', [
            'rows' => $rows,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
        ]);
    })->name('dashboard.bindo.weekly-journal');

    Route::post('/dashboard/bindo/weekly-journal/{journal}', function (Request $request, int $journal) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);

        $validated = $request->validate([
            'bindo_notes' => ['nullable', 'string', 'max:7000'],
        ]);

        $wibNow = Carbon::now('Asia/Jakarta');

        DB::table('weekly_journals')
            ->where('id', $journal)
            ->update([
                'bindo_id' => $user->id,
                'bindo_notes' => $validated['bindo_notes'] ?? null,
                'bindo_reviewed_at' => $wibNow,
                'updated_at' => $wibNow,
            ]);

        return back()->with('status', 'Bindo notes saved.');
    })->name('dashboard.bindo.weekly-journal.note');

    Route::get('/dashboard/principal/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'principal', 403);

        $wibNow = Carbon::now('Asia/Jakarta');
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

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

        return view('dashboard.principal-weekly-journal', [
            'rows' => $rows,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
        ]);
    })->name('dashboard.principal.weekly-journal');
});
