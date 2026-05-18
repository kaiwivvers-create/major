<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/mentor/weekly-journal', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_weekly_journal', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $supervisedStudentIds = mentor_supervised_student_ids($user);

        $rows = DB::table('weekly_journals as wj')
            ->join('users as s', 's.id', '=', 'wj.student_id')
            ->whereIn('wj.student_id', $supervisedStudentIds->isNotEmpty() ? $supervisedStudentIds : [-1])
            ->whereDate('wj.week_start_date', $weekStart)
            ->whereDate('wj.week_end_date', $weekEnd)
            ->select(
                'wj.id',
                'wj.student_id',
                'wj.learning_notes',
                'wj.student_mentor_notes',
                'wj.mentor_is_correct',
                'wj.missing_info_notes',
                'wj.status',
                's.name as student_name',
                's.nis as student_nis'
            )
            ->orderBy('s.name')
            ->get();

        $dailyValidationRows = DB::table('daily_logs as dl')
            ->join('users as s', 's.id', '=', 'dl.student_id')
            ->whereIn('dl.student_id', $supervisedStudentIds->isNotEmpty() ? $supervisedStudentIds : [-1])
            ->whereDate('dl.work_date', $today)
            ->select('dl.id', 'dl.student_id', 'dl.title', 'dl.description', 'dl.mentor_reviewed_at', 'dl.mentor_review_status', 's.name as student_name')
            ->get();

        $activeStudentIds = collect();
        if (Schema::hasTable('student_profiles') && $supervisedStudentIds->isNotEmpty()) {
            $activeStudentIds = DB::table('student_profiles')
                ->whereIn('student_id', $supervisedStudentIds)
                ->whereNotNull('pkl_start_date')
                ->whereNotNull('pkl_end_date')
                ->whereDate('pkl_start_date', '<=', $today)
                ->whereDate('pkl_end_date', '>=', $today)
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        $companySummary = Schema::hasTable('student_profiles') && $activeStudentIds->isNotEmpty()
            ? DB::table('student_profiles')
                ->whereIn('student_id', $activeStudentIds)
                ->whereNotNull('pkl_place_name')
                ->where('pkl_place_name', '<>', '')
                ->groupBy('pkl_place_name', 'pkl_place_address')
                ->selectRaw('pkl_place_name as company_name, COALESCE(NULLIF(TRIM(pkl_place_address), \'\'), \'-\') as company_address, COUNT(*) as total')
                ->orderByDesc('total')
                ->first()
            : null;

        $partnerHasGeoColumns = Schema::hasTable('partner_companies')
            && Schema::hasColumn('partner_companies', 'office_latitude')
            && Schema::hasColumn('partner_companies', 'office_longitude')
            && Schema::hasColumn('partner_companies', 'geofence_radius_meters');

        $companyProfile = null;
        if (Schema::hasTable('partner_companies') && Schema::hasColumn('users', 'partner_company_id') && !empty($user->partner_company_id)) {
            $companyProfile = DB::table('partner_companies')
                ->where('id', $user->partner_company_id)
                ->first();
        }
        if (!$companyProfile && Schema::hasTable('partner_companies')) {
            $mentorCompanyNameFromSummary = trim((string) data_get($companySummary, 'company_name', ''));
            $mentorCompanyAddressFromSummary = trim((string) data_get($companySummary, 'company_address', '-')) ?: '-';
            if ($mentorCompanyNameFromSummary !== '') {
                $companyProfile = DB::table('partner_companies')
                    ->whereRaw('TRIM(name) = ?', [$mentorCompanyNameFromSummary])
                    ->whereRaw("COALESCE(NULLIF(TRIM(address), ''), '-') = ?", [$mentorCompanyAddressFromSummary])
                    ->first();
            }
        }

        $mentorCompanyName = trim((string) data_get($companyProfile, 'name', data_get($companySummary, 'company_name', '')));
        $mentorCompanyAddress = trim((string) data_get($companyProfile, 'address', data_get($companySummary, 'company_address', '-'))) ?: '-';

        $todaysCheckins = DB::table('attendances as a')
            ->join('users as u', 'u.id', '=', 'a.student_id')
            ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 'a.student_id')
            ->whereIn('a.student_id', $supervisedStudentIds->isNotEmpty() ? $supervisedStudentIds : [-1])
            ->whereDate('a.attendance_date', $today)
            ->select(
                'a.id',
                'a.check_in_at',
                'a.check_out_at',
                'a.status',
                'a.photo_path',
                'a.latitude',
                'a.longitude',
                'u.name as student_name',
                'u.nis as student_nis',
                DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name')
            )
            ->orderBy('u.name')
            ->get();

        $presenceRows = collect();
        if ($activeStudentIds->isNotEmpty()) {
            $presenceRows = DB::table('users as u')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 'u.id')
                ->leftJoin('attendances as a', function ($join) use ($today) {
                    $join->on('a.student_id', '=', 'u.id')
                        ->whereDate('a.attendance_date', $today);
                })
                ->whereIn('u.id', $activeStudentIds)
                ->select(
                    'u.id',
                    'u.name as student_name',
                    'u.nis as student_nis',
                    'sp.class_name',
                    'a.check_in_at',
                    'a.check_out_at',
                    'a.status'
                )
                ->orderBy('u.name')
                ->get();
        }

        $pendingJournals = DB::table('weekly_journals')
            ->whereIn('student_id', $supervisedStudentIds->isNotEmpty() ? $supervisedStudentIds : [-1])
            ->where('status', 'pending')
            ->count();

        $absenceRows = collect();
        if (Schema::hasTable('attendance_excuses') && $supervisedStudentIds->isNotEmpty()) {
            $absenceRows = DB::table('attendance_excuses as ae')
                ->join('users as s', 's.id', '=', 'ae.student_id')
                ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 'ae.student_id')
                ->whereIn('ae.student_id', $supervisedStudentIds)
                ->whereDate('ae.attendance_date', $today)
                ->orderBy('s.name')
                ->get([
                    'ae.attendance_date',
                    'ae.absence_type',
                    'ae.status',
                    'ae.reason',
                    's.name as student_name',
                    's.nis as student_nis',
                    DB::raw('COALESCE(NULLIF(TRIM(sp.class_name), ""), "-") as class_name'),
                ]);
        }
        $recentAlerts = attendance_fetch_recent_alerts($user, 8);

        return view('dashboard.mentor-weekly-journal', [
            'today' => $today,
            'rows' => $rows,
            'dailyValidationRows' => $dailyValidationRows,
            'presenceRows' => $presenceRows,
            'totalActiveStudents' => $activeStudentIds->count(),
            'todaysCheckins' => $todaysCheckins,
            'pendingJournals' => $pendingJournals,
            'mentorCompanyName' => $mentorCompanyName,
            'mentorCompanyAddress' => $mentorCompanyAddress,
            'companyProfile' => $companyProfile,
            'partnerHasGeoColumns' => $partnerHasGeoColumns,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'absenceRows' => $absenceRows,
            'recentAlerts' => $recentAlerts,
        ]);
    })->name('dashboard.mentor.weekly-journal');

    Route::post('/dashboard/mentor/daily-log/{log}/review', function (Request $request, int $log) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_review_center', 'update');

        $validated = $request->validate([
            'action' => ['required', 'in:approve,revise'],
            'revision_notes' => [Rule::requiredIf(fn () => $request->input('action') === 'revise'), 'nullable', 'string', 'max:7000'],
        ]);

        if (!Schema::hasTable('daily_logs')) {
            return back()->withErrors(['mentor_daily_log' => 'Daily logs table is not ready.']);
        }

        $supervisedStudentIds = mentor_supervised_student_ids($user);
        if ($supervisedStudentIds->isEmpty()) {
            abort(403);
        }

        $dailyLog = DB::table('daily_logs as dl')
            ->where('dl.id', $log)
            ->whereIn('dl.student_id', $supervisedStudentIds)
            ->select('dl.id')
            ->first();

        abort_unless($dailyLog, 403);

        $payload = [
            'score_mentor_id' => $user->id,
            'scored_at' => now('Asia/Jakarta'),
            'updated_at' => now('Asia/Jakarta'),
        ];
        if (Schema::hasColumn('daily_logs', 'mentor_review_status')) {
            $payload['mentor_review_status'] = $validated['action'] === 'approve' ? 'approved' : 'revise';
        }
        if (Schema::hasColumn('daily_logs', 'mentor_revision_notes')) {
            $payload['mentor_revision_notes'] = $validated['action'] === 'approve' ? null : trim((string) ($validated['revision_notes'] ?? ''));
        }
        if (Schema::hasColumn('daily_logs', 'mentor_reviewed_at')) {
            $payload['mentor_reviewed_at'] = now('Asia/Jakarta');
        }

        DB::table('daily_logs')
            ->where('id', $log)
            ->update($payload);

        return back()->with('status', $validated['action'] === 'approve' ? 'Daily log approved.' : 'Daily log marked for revision.');
    })->name('dashboard.mentor.daily-log.review');

    Route::get('/dashboard/mentor/review-center', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_review_center', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();
        $weekStart = $wibNow->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $wibNow->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $supervisedStudentIds = mentor_supervised_student_ids($user);

        $dailyScoreRows = DB::table('daily_logs as dl')
            ->join('users as s', 's.id', '=', 'dl.student_id')
            ->leftJoin('student_profiles as sp', 'sp.student_id', '=', 's.id')
            ->leftJoin('attendances as a', function ($join) {
                $join->on('a.student_id', '=', 'dl.student_id')
                    ->on('a.attendance_date', '=', 'dl.work_date');
            })
            ->whereIn('dl.student_id', $supervisedStudentIds->isNotEmpty() ? $supervisedStudentIds : [-1])
            ->whereDate('dl.work_date', '>=', $weekStart)
            ->whereDate('dl.work_date', '<=', $weekEnd)
            ->select(
                'dl.id',
                'dl.student_id',
                'dl.work_date',
                'dl.title',
                'dl.description',
                'dl.planned_today',
                'dl.work_realization',
                'dl.score_smile',
                'dl.score_friendliness',
                'dl.score_appearance',
                'dl.score_communication',
                'dl.score_work_realization',
                'dl.scored_at',
                's.name as student_name',
                's.nis as student_nis',
                'sp.major_name',
                'a.check_in_at',
                'a.check_out_at'
            )
            ->orderByDesc('dl.work_date')
            ->orderBy('s.name')
            ->get()
            ->map(function ($row) {
                $isCompleted = !empty($row->check_in_at)
                    && !empty($row->check_out_at)
                    && trim((string) ($row->work_realization ?? '')) !== '';
                $row->is_completed = $isCompleted;
                return $row;
            });

        $weeklyRows = DB::table('weekly_journals as wj')
            ->join('users as s', 's.id', '=', 'wj.student_id')
            ->whereIn('wj.student_id', $supervisedStudentIds->isNotEmpty() ? $supervisedStudentIds : [-1])
            ->whereDate('wj.week_start_date', $weekStart)
            ->whereDate('wj.week_end_date', $weekEnd)
            ->select(
                'wj.id',
                'wj.student_id',
                'wj.learning_notes',
                'wj.student_mentor_notes',
                'wj.mentor_is_correct',
                'wj.missing_info_notes',
                'wj.mentor_feedback_summary',
                'wj.mentor_attitude_rating',
                'wj.mentor_skill_rating',
                'wj.status',
                's.name as student_name',
                's.nis as student_nis'
            )
            ->orderBy('s.name')
            ->get();

        return view('dashboard.mentor-review-center', [
            'today' => $today,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'dailyScoreRows' => $dailyScoreRows,
            'weeklyRows' => $weeklyRows,
        ]);
    })->name('dashboard.mentor.review-center');

    Route::post('/dashboard/mentor/daily-scoring/{dailyLog}', function (Request $request, int $dailyLog) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_review_center', 'update');

        $validated = $request->validate([
            'score_smile' => ['required', 'integer', 'between:1,5'],
            'score_friendliness' => ['required', 'integer', 'between:1,5'],
            'score_appearance' => ['required', 'integer', 'between:1,5'],
            'score_communication' => ['required', 'integer', 'between:1,5'],
            'score_work_realization' => ['required', 'integer', 'between:1,5'],
        ]);

        if (!Schema::hasTable('daily_logs') || !Schema::hasTable('attendances')) {
            return back()->withErrors(['daily_scoring' => 'Required tables are not ready. Run migrations first.']);
        }

        $supervisedStudentIds = mentor_supervised_student_ids($user);
        if ($supervisedStudentIds->isEmpty()) {
            abort(403);
        }

        $row = DB::table('daily_logs as dl')
            ->leftJoin('attendances as a', function ($join) {
                $join->on('a.student_id', '=', 'dl.student_id')
                    ->on('a.attendance_date', '=', 'dl.work_date');
            })
            ->where('dl.id', $dailyLog)
            ->whereIn('dl.student_id', $supervisedStudentIds)
            ->select(
                'dl.id',
                'dl.student_id',
                'dl.work_date',
                'dl.work_realization',
                'a.check_in_at',
                'a.check_out_at'
            )
            ->first();

        abort_unless($row, 403);

        $isCompleted = !empty($row->check_in_at)
            && !empty($row->check_out_at)
            && trim((string) ($row->work_realization ?? '')) !== '';

        if (!$isCompleted) {
            return back()->withErrors(['daily_scoring' => 'Daily scoring is allowed only after student check-out and completed work realization.']);
        }

        DB::table('daily_logs')
            ->where('id', $dailyLog)
            ->update([
                'score_smile' => (int) $validated['score_smile'],
                'score_friendliness' => (int) $validated['score_friendliness'],
                'score_appearance' => (int) $validated['score_appearance'],
                'score_communication' => (int) $validated['score_communication'],
                'score_work_realization' => (int) $validated['score_work_realization'],
                'score_mentor_id' => $user->id,
                'scored_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]);

        return back()->with('status', 'Daily score saved.');
    })->name('dashboard.mentor.daily-scoring.save');

    Route::post('/dashboard/mentor/weekly-journal/{journal}', function (Request $request, int $journal) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_weekly_journal', 'update');

        $validated = $request->validate([
            'mentor_is_correct' => ['required', 'in:1,0'],
            'missing_info_notes' => ['nullable', 'string', 'max:7000'],
            'mentor_feedback_summary' => ['nullable', 'string', 'max:7000'],
            'mentor_attitude_rating' => ['nullable', 'integer', 'between:1,5'],
            'mentor_skill_rating' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $wibNow = Carbon::now('Asia/Jakarta');
        $isCorrect = $validated['mentor_is_correct'] === '1';

        $payload = [
            'mentor_id' => $user->id,
            'mentor_is_correct' => $isCorrect,
            'missing_info_notes' => $isCorrect ? null : ($validated['missing_info_notes'] ?? null),
            'status' => $isCorrect ? 'approved' : 'needs_revision',
            'mentor_reviewed_at' => $wibNow,
            'updated_at' => $wibNow,
        ];
        if (Schema::hasColumn('weekly_journals', 'mentor_feedback_summary')) {
            $payload['mentor_feedback_summary'] = trim((string) ($validated['mentor_feedback_summary'] ?? '')) ?: null;
        }
        if (Schema::hasColumn('weekly_journals', 'mentor_attitude_rating')) {
            $payload['mentor_attitude_rating'] = $validated['mentor_attitude_rating'] ?? null;
        }
        if (Schema::hasColumn('weekly_journals', 'mentor_skill_rating')) {
            $payload['mentor_skill_rating'] = $validated['mentor_skill_rating'] ?? null;
        }

        DB::table('weekly_journals')
            ->where('id', $journal)
            ->update($payload);

        return back()->with('status', 'Mentor validation updated.');
    })->name('dashboard.mentor.weekly-journal.review');

    Route::get('/dashboard/mentor/company-settings-page', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_company_settings', 'view');

        $wibNow = Carbon::now('Asia/Jakarta');
        $today = $wibNow->toDateString();

        $supervisedStudentIds = mentor_supervised_student_ids($user);

        $activeStudentIds = collect();
        if (Schema::hasTable('student_profiles') && $supervisedStudentIds->isNotEmpty()) {
            $activeStudentIds = DB::table('student_profiles')
                ->whereIn('student_id', $supervisedStudentIds)
                ->whereNotNull('pkl_start_date')
                ->whereNotNull('pkl_end_date')
                ->whereDate('pkl_start_date', '<=', $today)
                ->whereDate('pkl_end_date', '>=', $today)
                pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        $companySummary = Schema::hasTable('student_profiles') && $activeStudentIds->isNotEmpty()
            ? DB::table('student_profiles')
                ->whereIn('student_id', $activeStudentIds)
                ->whereNotNull('pkl_place_name')
                ->where('pkl_place_name', '<>', '')
                ->groupBy('pkl_place_name', 'pkl_place_address')
                ->selectRaw('pkl_place_name as company_name, COALESCE(NULLIF(TRIM(pkl_place_address), \'\'), \'-\') as company_address, COUNT(*) as total')
                ->orderByDesc('total')
                ->first()
            : null;

        $partnerHasGeoColumns = Schema::hasTable('partner_companies')
            && Schema::hasColumn('partner_companies', 'office_latitude')
            && Schema::hasColumn('partner_companies', 'office_longitude')
            && Schema::hasColumn('partner_companies', 'geofence_radius_meters');

        $companyProfile = null;
        if (Schema::hasTable('partner_companies') && Schema::hasColumn('users', 'partner_company_id') && !empty($user->partner_company_id)) {
            $companyProfile = DB::table('partner_companies')
                ->where('id', $user->partner_company_id)
                ->first();
        }
        if (!$companyProfile && Schema::hasTable('partner_companies')) {
            $mentorCompanyNameFromSummary = trim((string) data_get($companySummary, 'company_name', ''));
            $mentorCompanyAddressFromSummary = trim((string) data_get($companySummary, 'company_address', '-')) ?: '-';
            if ($mentorCompanyNameFromSummary !== '') {
                $companyProfile = DB::table('partner_companies')
                    ->whereRaw('TRIM(name) = ?', [$mentorCompanyNameFromSummary])
                    ->whereRaw("COALESCE(NULLIF(TRIM(address), ''), '-') = ?", [$mentorCompanyAddressFromSummary])
                    ->first();
            }
        }

        $mentorCompanyName = trim((string) data_get($companyProfile, 'name', data_get($companySummary, 'company_name', '')));
        $mentorCompanyAddress = trim((string) data_get($companyProfile, 'address', data_get($companySummary, 'company_address', '-'))) ?: '-';

        return view('dashboard.mentor-company-settings', [
            'today' => $today,
            'mentorCompanyName' => $mentorCompanyName,
            'mentorCompanyAddress' => $mentorCompanyAddress,
            'companyProfile' => $companyProfile,
            'partnerHasGeoColumns' => $partnerHasGeoColumns,
        ]);
    })->name('dashboard.mentor.company-settings-page');

    Route::post('/dashboard/mentor/company-settings', function (Request $request) {
        $user = $request->user();
        abort_unless($user->role === 'mentor', 403);
        require_user_permission($user, 'mentor_company_settings', 'update');

        if (!Schema::hasTable('partner_companies')) {
            return back()->withErrors(['mentor_company' => 'Company profile table is not ready yet.']);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_address' => ['required', 'string', 'max:2000'],
            'office_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'office_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'logo_crop_data' => ['nullable', 'string'],
        ]);

        $name = trim((string) $validated['company_name']);
        $address = trim((string) $validated['company_address']) ?: '-';

        $existing = DB::table('partner_companies')
            ->whereRaw('TRIM(name) = ?', [$name])
            ->whereRaw("COALESCE(NULLIF(TRIM(address), ''), '-') = ?", [$address])
            ->first();

        $payload = [
            'name' => $name,
            'address' => $address,
            'updated_at' => now('Asia/Jakarta'),
        ];

        if (!empty($validated['logo_crop_data'])) {
            $base64 = (string) $validated['logo_crop_data'];
            $prefix = 'data:image/png;base64,';
            if (!Str::startsWith($base64, $prefix)) {
                return back()->withErrors(['logo_crop_data' => 'Invalid logo image format. Please crop and submit again.']);
            }

            $decoded = base64_decode(substr($base64, strlen($prefix)), true);
            if ($decoded === false) {
                return back()->withErrors(['logo_crop_data' => 'Could not process cropped logo image.']);
            }

            $fileName = 'company-logo-' . now('Asia/Jakarta')->format('YmdHis') . '-' . Str::random(8) . '.png';
            $filePath = 'company-logos/' . $fileName;
            Storage::disk('public')->put($filePath, $decoded);
            $payload['logo_url'] = $filePath;
        }

        if (Schema::hasColumn('partner_companies', 'office_latitude')) {
            $payload['office_latitude'] = $validated['office_latitude'] ?? null;
        }
        if (Schema::hasColumn('partner_companies', 'office_longitude')) {
            $payload['office_longitude'] = $validated['office_longitude'] ?? null;
        }
        if (Schema::hasColumn('partner_companies', 'geofence_radius_meters')) {
            $payload['geofence_radius_meters'] = $validated['geofence_radius_meters'] ?? null;
        }

        if ($existing) {
            DB::table('partner_companies')->where('id', $existing->id)->update($payload);
            $companyId = $existing->id;
        } else {
            $payload['is_active'] = true;
            $payload['created_at'] = now('Asia/Jakarta');
            $companyId = DB::table('partner_companies')->insertGetId($payload);
        }

        if (Schema::hasColumn('users', 'partner_company_id')) {
            DB::table('users')->where('id', $user->id)->update(['partner_company_id' => $companyId]);
        }

        return back()->with('status', 'Company settings saved.');
    })->name('dashboard.mentor.company-settings.save');
});
