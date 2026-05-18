<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/student/checkin', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'checkin', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();

        $todayAttendance = DB::table('attendances')
            ->where('student_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $todayExcuse = DB::table('attendance_excuses')
            ->where('student_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        return view('dashboard.student-checkin', [
            'todayAttendance' => $todayAttendance,
            'todayExcuse' => $todayExcuse,
            'today' => $today,
            'wibNow' => $wibNow,
            'checkInCutoffTime' => attendance_checkin_cutoff_time(),
        ]);
    })->name('dashboard.student.checkin-page');

    Route::post('/dashboard/student/check-in', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        require_user_permission($user, 'checkin', 'create');

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string', 'max:1000'],
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
        if (Schema::hasColumn('attendances', 'location_address')) {
            $upsertPayload['location_address'] = trim((string) ($validated['location_address'] ?? '')) ?: null;
        }
        if (Schema::hasColumn('attendances', 'late_minutes')) {
            $upsertPayload['late_minutes'] = $lateMinutes;
        }

        DB::table('attendances')->updateOrInsert(
            [
                'student_id' => $user->id,
                'attendance_date' => $today,
            ],
            $upsertPayload
        );

        if ($lateMinutes > 0) {
            $notifyMeta = attendance_notification_targets_for_student((int) $user->id);
            attendance_push_notification(
                'late_checkin_detected',
                trim((string) $user->name) . ' (' . ((string) ($user->nis ?? '-')) . ') checked in late by ' . $lateMinutes . ' minute(s) on ' . $today . '.',
                (array) data_get($notifyMeta, 'targets', []),
                data_get($notifyMeta, 'major_name'),
                data_get($notifyMeta, 'class_name'),
                $today
            );
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

    Route::post('/dashboard/student/absence-request', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
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

        $notifyMeta = attendance_notification_targets_for_student((int) $user->id);
        attendance_push_notification(
            'absence_request_submitted',
            trim((string) $user->name) . ' (' . ((string) ($user->nis ?? '-')) . ') submitted an absence request (' . strtoupper((string) $validated['absence_type']) . ') for ' . $today . '.',
            (array) data_get($notifyMeta, 'targets', []),
            data_get($notifyMeta, 'major_name'),
            data_get($notifyMeta, 'class_name'),
            $today
        );

        return back()->with('status', 'Absence request submitted and waiting for approval.');
    })->name('dashboard.student.absence-request');
});
