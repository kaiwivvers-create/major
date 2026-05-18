<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::middleware(['auth'])->group(function () {
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
                ->get();
        }

        return view('dashboard.teacher-weekly-journals', [
            'rows' => $rows,
            'assignedStudents' => $assignedStudents,
            'mapPoints' => $mapPoints,
            'wibNow' => $wibNow,
        ]);
    })->name('dashboard.bindo.weekly-journal');

    Route::post('/dashboard/bindo/weekly-journal/{journal}', function (Request $request, int $journal) {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        abort_unless(teacher_has_dashboard_access($user, 'update'), 403);

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

        return back()->with('status', 'Teacher notes saved.');
    })->name('dashboard.bindo.weekly-journal.note');
});
