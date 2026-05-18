<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/dashboard/notifications/{notification}/read', function (Request $request, int $notification) {
        $user = $request->user();
        if (!attendance_alert_storage_ready()) {
            return back();
        }

        DB::table('attendance_alert_notifications')
            ->where('id', $notification)
            ->where('recipient_role', (string) ($user->role ?? ''))
            ->where(function ($query) use ($user) {
                $query->whereNull('recipient_user_id')
                    ->orWhere('recipient_user_id', $user->id);
            })
            ->update([
                'is_read' => true,
                'read_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]);

        return back()->with('status', 'Notification marked as read.');
    })->name('dashboard.notifications.read');

    Route::post('/dashboard/notifications/read-all', function (Request $request) {
        $user = $request->user();
        if (!attendance_alert_storage_ready()) {
            return back();
        }

        DB::table('attendance_alert_notifications')
            ->where('recipient_role', (string) ($user->role ?? ''))
            ->where(function ($query) use ($user) {
                $query->whereNull('recipient_user_id')
                    ->orWhere('recipient_user_id', $user->id);
            })
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now('Asia/Jakarta'),
                'updated_at' => now('Asia/Jakarta'),
            ]);

        return back()->with('status', 'All notifications marked as read.');
    })->name('dashboard.notifications.read-all');

    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();

        if ($user->role === 'student') {
            $studentRoutes = [
                ['module' => 'student_dashboard', 'route' => 'dashboard.student'],
                ['module' => 'checkin', 'route' => 'dashboard.student.checkin-page'],
                ['module' => 'task_log', 'route' => 'dashboard.student.task-log-page'],
                ['module' => 'weekly_journal', 'route' => 'dashboard.student.weekly-journal'],
                ['module' => 'completion', 'route' => 'dashboard.student.completion'],
                ['module' => 'student_data', 'route' => 'dashboard.student.data-page'],
            ];
            foreach ($studentRoutes as $candidate) {
                if (user_can_access($user, $candidate['module'], 'view')) {
                    return redirect()->route($candidate['route']);
                }
            }
            return redirect()->route('dashboard.student');
        }
        if ($user->role === 'mentor') {
            if (user_can_access($user, 'mentor_weekly_journal', 'view')) {
                return redirect()->route('dashboard.mentor.weekly-journal');
            }
            if (user_can_access($user, 'mentor_review_center', 'view')) {
                return redirect()->route('dashboard.mentor.review-center');
            }
            if (user_can_access($user, 'mentor_company_settings', 'view')) {
                return redirect()->route('dashboard.mentor.company-settings-page');
            }
            return redirect()->route('dashboard.mentor.weekly-journal');
        }
        if ($user->role === 'kajur') {
            $kajurRoutes = [
                ['module' => 'kajur_dashboard', 'route' => 'dashboard.kajur.dashboard'],
                ['module' => 'kajur_weekly_journals', 'route' => 'dashboard.kajur.weekly-journal'],
                ['module' => 'kajur_daily_checkins', 'route' => 'dashboard.kajur.daily-checkin'],
                ['module' => 'kajur_absence_report', 'route' => 'dashboard.kajur.absence-report'],
            ];
            foreach ($kajurRoutes as $candidate) {
                if (user_can_access($user, $candidate['module'], 'view')) {
                    return redirect()->route($candidate['route']);
                }
            }
            return redirect()->route('dashboard.kajur.dashboard');
        }
        if ($user->role === 'teacher') {
            if (teacher_has_dashboard_access($user, 'view')) {
                return redirect()->route('dashboard.bindo.weekly-journal');
            }
            return redirect()->route('dashboard.bindo.weekly-journal');
        }
        if ($user->role === 'principal') {
            if (user_can_access($user, 'principal_dashboard', 'view')) {
                return redirect()->route('dashboard.principal.weekly-journal');
            }
            if (user_can_access($user, 'principal_master_report', 'view')) {
                return redirect()->route('dashboard.principal.master-report-page');
            }
            if (user_can_access($user, 'principal_attendance_alerts', 'view')) {
                return redirect()->route('dashboard.principal.attendance-alerts');
            }
            if (user_can_access($user, 'principal_partner_companies', 'view')) {
                return redirect()->route('dashboard.principal.partner-companies');
            }
            if (user_can_access($user, 'principal_journal_oversight', 'view')) {
                return redirect()->route('dashboard.principal.journal-oversight');
            }
            if (user_can_access($user, 'principal_school_performance', 'view')) {
                return redirect()->route('dashboard.principal.school-performance');
            }
            if (user_can_access($user, 'principal_timeline', 'view')) {
                return redirect()->route('dashboard.principal.timeline');
            }
            return redirect()->route('dashboard.principal.weekly-journal');
        }
        if (is_admin_like_role($user)) {
            $superAdminRoutes = [
                ['module' => 'super_admin_dashboard', 'route' => 'dashboard.super-admin'],
                ['module' => 'super_admin_users', 'route' => 'dashboard.super-admin.users'],
                ['module' => 'super_admin_companies', 'route' => 'dashboard.super-admin.companies'],
                ['module' => 'super_admin_mass_edit', 'route' => 'dashboard.super-admin.mass-edit'],
                ['module' => 'super_admin_checkins', 'route' => 'dashboard.super-admin.checkins'],
                ['module' => 'super_admin_weekly_journals', 'route' => 'dashboard.super-admin.weekly-journals'],
                ['module' => 'super_admin_completion', 'route' => 'dashboard.super-admin.completion'],
            ];
            if (is_super_admin_role($user)) {
                $superAdminRoutes[] = ['module' => 'super_admin_permissions', 'route' => 'dashboard.super-admin.permissions'];
                $superAdminRoutes[] = ['module' => 'super_admin_activities', 'route' => 'dashboard.super-admin.activities'];
                $superAdminRoutes[] = ['module' => 'super_admin_branding', 'route' => 'dashboard.super-admin.branding'];
                $superAdminRoutes[] = ['module' => 'super_admin_database', 'route' => 'dashboard.super-admin.database'];
            }
            foreach ($superAdminRoutes as $candidate) {
                if (user_can_access($user, $candidate['module'], 'view')) {
                    return redirect()->route($candidate['route']);
                }
            }
            return redirect()->route('dashboard.super-admin');
        }

        return redirect('/')->with('status', 'Dashboard for your role is not available yet.');
    })->name('dashboard');
});
