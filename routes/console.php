<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:generate-alerts', function () {
    if (!DB::getSchemaBuilder()->hasTable('attendance_alert_notifications')) {
        $this->warn('attendance_alert_notifications table not found. Run migrations first.');
        return;
    }
    if (!DB::getSchemaBuilder()->hasTable('users') || !DB::getSchemaBuilder()->hasTable('attendances')) {
        $this->warn('Required attendance tables are not ready.');
        return;
    }

    $now = Carbon::now('Asia/Jakarta');
    $today = $now->toDateString();
    $cutoffRaw = trim((string) env('ATTENDANCE_CHECKIN_CUTOFF', '08:00'));
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $cutoffRaw)) {
        $cutoffRaw = '08:00:00';
    } elseif (strlen($cutoffRaw) === 5) {
        $cutoffRaw .= ':00';
    }
    $deadline = Carbon::parse($today . ' ' . $cutoffRaw, 'Asia/Jakarta');
    if ($now->lessThan($deadline)) {
        $this->info('Skipped: deadline not reached yet.');
        return;
    }

    $hasStudentProfiles = DB::getSchemaBuilder()->hasTable('student_profiles');
    $hasExcuses = DB::getSchemaBuilder()->hasTable('attendance_excuses');
    $hasCalendar = DB::getSchemaBuilder()->hasTable('attendance_calendar_exceptions');

    $kajurs = DB::table('users')
        ->where('role', 'kajur')
        ->get(['id', 'name', 'kajur_major_name']);

    foreach ($kajurs as $kajur) {
        $major = strtoupper(trim((string) ($kajur->kajur_major_name ?? '')));
        if ($major === '' || !$hasStudentProfiles) {
            continue;
        }

        $studentRows = DB::table('users as s')
            ->join('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->where('s.role', 'student')
            ->whereRaw('UPPER(TRIM(COALESCE(sp.major_name, ""))) = ?', [$major])
            ->select('s.id', 'sp.class_name')
            ->get();
        if ($studentRows->isEmpty()) {
            continue;
        }

        $checkedInIds = DB::table('attendances')
            ->whereIn('student_id', $studentRows->pluck('id'))
            ->whereDate('attendance_date', $today)
            ->whereNotNull('check_in_at')
            ->pluck('student_id');

        $pendingExcuseIds = collect();
        $approvedExcuseIds = collect();
        if ($hasExcuses) {
            $pendingExcuseIds = DB::table('attendance_excuses')
                ->whereIn('student_id', $studentRows->pluck('id'))
                ->whereDate('attendance_date', $today)
                ->where('status', 'pending')
                ->pluck('student_id');
            $approvedExcuseIds = DB::table('attendance_excuses')
                ->whereIn('student_id', $studentRows->pluck('id'))
                ->whereDate('attendance_date', $today)
                ->where('status', 'approved')
                ->pluck('student_id');
        }

        $missingCount = 0;
        foreach ($studentRows as $studentRow) {
            $sid = (int) $studentRow->id;
            if ($checkedInIds->contains($sid) || $approvedExcuseIds->contains($sid)) {
                continue;
            }
            $className = trim((string) ($studentRow->class_name ?? ''));
            $isNonWorking = false;
            if ($hasCalendar) {
                $isNonWorking = DB::table('attendance_calendar_exceptions')
                    ->whereDate('exception_date', $today)
                    ->where(function ($query) use ($major, $className) {
                        $query->where(function ($global) {
                            $global->whereNull('major_name')->whereNull('class_name');
                        })->orWhere(function ($majorOnly) use ($major) {
                            $majorOnly->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$major])
                                ->whereNull('class_name');
                        });
                        if ($className !== '') {
                            $query->orWhere(function ($majorClass) use ($major, $className) {
                                $majorClass->whereRaw('UPPER(TRIM(COALESCE(major_name, ""))) = ?', [$major])
                                    ->whereRaw('TRIM(COALESCE(class_name, "")) = ?', [$className]);
                            });
                        }
                    })
                    ->exists();
            }
            if (!$isNonWorking) {
                $missingCount++;
            }
        }

        $pendingCount = (int) $pendingExcuseIds->unique()->count();

        if ($missingCount > 0) {
            DB::table('attendance_alert_notifications')->updateOrInsert(
                [
                    'alert_date' => $today,
                    'recipient_role' => 'kajur',
                    'recipient_user_id' => $kajur->id,
                    'alert_type' => 'missing_checkin_after_cutoff',
                    'major_name' => $major,
                    'class_name' => null,
                ],
                [
                    'message' => $missingCount . ' student(s) in major ' . $major . ' missed check-in deadline without approved excuse.',
                    'is_read' => false,
                    'read_at' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        if ($pendingCount > 0) {
            DB::table('attendance_alert_notifications')->updateOrInsert(
                [
                    'alert_date' => $today,
                    'recipient_role' => 'kajur',
                    'recipient_user_id' => $kajur->id,
                    'alert_type' => 'pending_excuse_after_cutoff',
                    'major_name' => $major,
                    'class_name' => null,
                ],
                [
                    'message' => $pendingCount . ' student(s) in major ' . $major . ' have pending excuse requests.',
                    'is_read' => false,
                    'read_at' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    $studentIds = DB::table('users')->where('role', 'student')->pluck('id');
    $checkedInIds = DB::table('attendances')
        ->whereIn('student_id', $studentIds)
        ->whereDate('attendance_date', $today)
        ->whereNotNull('check_in_at')
        ->pluck('student_id');
    $approvedExcusedIds = $hasExcuses
        ? DB::table('attendance_excuses')
            ->whereIn('student_id', $studentIds)
            ->whereDate('attendance_date', $today)
            ->where('status', 'approved')
            ->pluck('student_id')
        : collect();
    $alphaCount = $studentIds->diff($checkedInIds)->diff($approvedExcusedIds)->count();

    DB::table('attendance_alert_notifications')->updateOrInsert(
        [
            'alert_date' => $today,
            'recipient_role' => 'principal',
            'recipient_user_id' => null,
            'alert_type' => 'school_alpha_summary',
            'major_name' => null,
            'class_name' => null,
        ],
        [
            'message' => $alphaCount . ' student(s) are currently alpha (no check-in and no approved excuse).',
            'is_read' => false,
            'read_at' => null,
            'updated_at' => $now,
            'created_at' => $now,
        ]
    );

    $this->info('Attendance alerts generated.');
})->purpose('Generate attendance alerts after daily cutoff');

Schedule::command('attendance:generate-alerts')->everyThirtyMinutes();
