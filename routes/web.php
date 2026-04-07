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
        if ($user->role === 'super_admin') {
            return redirect()->route('dashboard.super-admin');
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
        abort_unless($user->role === 'student', 403);

        if (!Schema::hasTable('student_profiles')) {
            return back()->withErrors(['student_data' => 'Student data table is not ready yet. Please run database migrations first.']);
        }

        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'birth_place' => ['required', 'string', 'max:120'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'major_name' => ['required', 'in:RPL,BDP,AKL'],
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

    Route::get('/dashboard/super-admin', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();

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

        $heatStart = $wibNow->copy()->subDays(29)->toDateString();
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

        return view('dashboard.super-admin', [
            'companyStats' => $companyStats,
            'companyMapPoints' => $companyMapPoints,
            'majorDistribution' => $majorDistribution,
            'heatmap' => $heatmap,
            'maxAttendance' => $maxAttendance,
            'activityFeed' => $activityFeed,
            'timelineStart' => '2026-01-01',
            'timelineEnd' => '2026-06-30',
            'timelineStatus' => 'Week 12 - Monitoring Phase',
        ]);
    })->name('dashboard.super-admin');

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

        return back()->with('status', 'Profile updated successfully.');
    })->name('dashboard.super-admin.profile');

    Route::get('/dashboard/super-admin/users', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);

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

        $userFormData = [];
        foreach ($users as $row) {
            $profile = $profileRows->get($row->id);
            $userFormData[$row->id] = [
                'id' => $row->id,
                'name' => $row->name,
                'nis' => $row->nis,
                'email' => $row->email,
                'role' => $row->role,
                'major_name' => data_get($profile, 'major_name'),
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
            ];
        }

        return view('dashboard.super-admin-users', [
            'users' => $users,
            'q' => $q,
            'roleFilter' => $role,
            'roleOptions' => User::ROLES,
            'userFormData' => $userFormData,
        ]);
    })->name('dashboard.super-admin.users');

    Route::post('/dashboard/super-admin/users/create', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);

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

        return back()->with('status', "User created successfully. Auto NIS: {$nextNis}");
    })->name('dashboard.super-admin.users.create');

    Route::get('/dashboard/super-admin/users/{managedUser}/edit', function (Request $request, int $managedUser) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);

        $target = User::findOrFail($managedUser);

        return view('dashboard.super-admin-users-edit', [
            'target' => $target,
            'roleOptions' => User::ROLES,
        ]);
    })->name('dashboard.super-admin.users.edit');

    Route::post('/dashboard/super-admin/users/{managedUser}/edit', function (Request $request, int $managedUser) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);

        $target = User::findOrFail($managedUser);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $target->id],
            'role' => ['required', 'in:' . implode(',', User::ROLES)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'major_name' => [
                Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT),
                'nullable',
                'in:RPL,BDP,AKL',
            ],
            'birth_place' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'string', 'max:120'],
            'birth_date' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'date'],
            'address' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'string', 'max:2000'],
            'phone_number' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'string', 'max:30'],
            'pkl_place_name' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'string', 'max:150'],
            'pkl_place_address' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'string', 'max:2000'],
            'pkl_place_phone' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'string', 'max:30'],
            'pkl_start_date' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'date'],
            'pkl_end_date' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'date', 'after_or_equal:pkl_start_date'],
            'mentor_teacher_name' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'string', 'max:150'],
            'school_supervisor_teacher_name' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'string', 'max:150'],
            'company_instructor_position' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT), 'nullable', 'string', 'max:150'],
        ]);

        $target->name = $validated['name'];
        $target->email = $validated['email'];
        $target->role = $validated['role'];
        if (!empty($validated['password'])) {
            $target->password = $validated['password'];
        }
        $target->save();

        if (Schema::hasTable('student_profiles')) {
            DB::table('student_profiles')->updateOrInsert(
                ['student_id' => $target->id],
                [
                    'major_name' => $validated['major_name'] ?? null,
                    'birth_place' => $validated['birth_place'] ?? null,
                    'birth_date' => $validated['birth_date'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'phone_number' => $validated['phone_number'] ?? null,
                    'pkl_place_name' => $validated['pkl_place_name'] ?? null,
                    'pkl_place_address' => $validated['pkl_place_address'] ?? null,
                    'pkl_place_phone' => $validated['pkl_place_phone'] ?? null,
                    'pkl_start_date' => $validated['pkl_start_date'] ?? null,
                    'pkl_end_date' => $validated['pkl_end_date'] ?? null,
                    'mentor_teacher_name' => $validated['mentor_teacher_name'] ?? null,
                    'school_supervisor_teacher_name' => $validated['school_supervisor_teacher_name'] ?? null,
                    'company_instructor_position' => $validated['company_instructor_position'] ?? null,
                    'updated_at' => now('Asia/Jakarta'),
                    'created_at' => now('Asia/Jakarta'),
                ]
            );
        }

        return redirect()->route('dashboard.super-admin.users')->with('status', 'User updated successfully.');
    })->name('dashboard.super-admin.users.update');

    Route::post('/dashboard/super-admin/users/{managedUser}/delete', function (Request $request, int $managedUser) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);

        $target = User::findOrFail($managedUser);
        if ($target->id === $user->id) {
            return back()->withErrors(['user_delete' => 'You cannot delete your own account.']);
        }

        $target->delete();

        return back()->with('status', 'User deleted successfully.');
    })->name('dashboard.super-admin.users.delete');

    Route::post('/dashboard/super-admin/users/{managedUser}/reset-password', function (Request $request, int $managedUser) {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403);

        $target = User::findOrFail($managedUser);
        $temporaryPassword = Str::upper(Str::random(8));
        $target->password = $temporaryPassword;
        $target->save();

        return back()->with('status', "Password for {$target->name} reset. Temporary password: {$temporaryPassword}");
    })->name('dashboard.super-admin.users.reset-password');
});
