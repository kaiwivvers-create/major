<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::middleware(['auth'])->group(function () {
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
            ? DB::table('student_profiles')->where('student_id', $user->id)->first(['major_name', 'class_name', 'pkl_start_date', 'pkl_end_date'])
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

        $pklStartDate = data_get($studentProfile, 'pkl_start_date');
        $pklEndDate = data_get($studentProfile, 'pkl_end_date');
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
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('status', 'Profile updated successfully.');
    })->name('dashboard.student.profile');

    Route::post('/dashboard/student/contact-directory', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'student_data', 'update');

        if (!Schema::hasTable('student_profiles')) {
            return back()->withErrors(['student_contact' => 'Student profile table is not ready yet. Please run database migrations first.']);
        }

        $validated = $request->validate([
            'pkl_place_name' => ['required', 'string', 'max:150'],
            'pkl_place_address' => ['required', 'string', 'max:2000'],
            'mentor_teacher_name' => ['required', 'string', 'max:150'],
            'pkl_place_phone' => ['required', 'string', 'max:30'],
        ]);

        $wibNow = Carbon::now('Asia/Jakarta');

        $existingProfile = DB::table('student_profiles')
            ->where('student_id', $user->id)
            ->first();

        DB::table('student_profiles')->updateOrInsert(
            ['student_id' => $user->id],
            [
                'pkl_place_name' => trim((string) $validated['pkl_place_name']),
                'pkl_place_address' => trim((string) $validated['pkl_place_address']),
                'mentor_teacher_name' => trim((string) $validated['mentor_teacher_name']),
                'pkl_place_phone' => trim((string) $validated['pkl_place_phone']),
                'updated_at' => $wibNow,
                'created_at' => data_get($existingProfile, 'created_at', $wibNow),
            ]
        );

        return back()->with('status', 'Contact directory data updated.');
    })->name('dashboard.student.contact-directory.update');
});
