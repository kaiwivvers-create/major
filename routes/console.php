<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
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

Artisan::command('user:fix-password {identifier} {password}', function ($identifier, $password) {
    $search = trim((string) $identifier);
    
    $conn = DB::getDefaultConnection();
    $dbName = DB::getDatabaseName();
    $this->info("Checking Database: {$dbName} (Connection: {$conn})");

    if (!Schema::hasTable('users')) {
        $this->error("The 'users' table does not exist. Please fix your migrations and run 'php artisan migrate' first.");
        return;
    }

    $totalUsers = DB::table('users')->count();
    if ($totalUsers === 0) {
        $this->error("The 'users' table is currently empty. You need to create a user or run seeders first.");
        return;
    }

    $query = DB::table('users');
    $query->where(function ($q) use ($search) {
        $q->where('id', $search)
          ->orWhere('email', $search);
        if (Schema::hasColumn('users', 'nis')) {
            $q->orWhere('nis', $search);
        }
    });

    $user = $query->first();
    
    if (!$user) {
        $this->error("User with identifier '{$identifier}' not found.");
        $this->info("Total users in database: {$totalUsers}");
        $this->info("Sample identifiers currently in DB:");
        DB::table('users')->take(5)->get(['id', 'email', 'nis'])->each(function($u) {
            $nis = $u->nis ?? 'N/A';
            $this->line(" - [ID: {$u->id}] [Email: {$u->email}] [NIS: {$nis}]");
        });
        return;
    }

    DB::table('users')->where('id', $user->id)->update([
        'password' => Hash::make($password),
        'updated_at' => now('Asia/Jakarta')
    ]);

    $this->info("Successfully hashed and updated password for {$user->name}.");
})->purpose('Fix unhashed passwords for a specific user via NIS');

Artisan::command('user:create-admin {name} {email} {password}', function ($name, $email, $password) {
    $conn = DB::getDefaultConnection();
    $dbName = DB::getDatabaseName();
    $this->info("Target Database: {$dbName} (Connection: {$conn})");
    if ($conn === 'sqlite') {
        $this->error("WARNING: You are creating an admin in SQLITE, not MySQL!");
    }

    $id = DB::table('users')->insertGetId([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'role' => 'super_admin',
        'nis' => 'ADM' . rand(1000, 9999),
        'created_at' => now('Asia/Jakarta'),
        'updated_at' => now('Asia/Jakarta'),
    ]);

    $this->info("Admin user '{$name}' created successfully!");
    $this->info("User ID: {$id}");
    $this->info("You can now login with Email: {$email}");
})->purpose('Create a new super admin user for testing');

Artisan::command('user:import-csv {file}', function ($file) {
    if (!file_exists($file)) {
        $this->error("File not found: {$file}");
        return;
    }

    $handle = fopen($file, 'r');
    $header = fgetcsv($handle);
    
    if (!$header) {
        $this->error("Empty file.");
        return;
    }

    $this->info("Importing users from CSV...");
    $count = 0;
    $errors = 0;

    while (($row = fgetcsv($handle)) !== false) {
        try {
            $data = array_combine($header, $row);
            
            $email = trim($data['email'] ?? '');
            if (empty($email)) continue;

            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => trim($data['name'] ?? 'New User'),
                    'nis' => trim($data['nis'] ?? ''),
                    'role' => trim($data['role'] ?? 'student'),
                    'password' => Hash::make($data['password'] ?? 'password123'),
                    'updated_at' => now('Asia/Jakarta'),
                    'created_at' => now('Asia/Jakarta'),
                ]
            );

            $user = DB::table('users')->where('email', $email)->first();

            if ($user && $user->role === 'student' && Schema::hasTable('student_profiles')) {
                DB::table('student_profiles')->updateOrInsert(
                    ['student_id' => $user->id],
                    [
                        'major_name' => trim($data['major_name'] ?? ''),
                        'class_name' => trim($data['class_name'] ?? ''),
                        'updated_at' => now('Asia/Jakarta'),
                    ]
                );
            }
            $count++;
        } catch (\Throwable $e) {
            $this->error("Error at row " . ($count + $errors + 2) . ": " . $e->getMessage());
            $errors++;
        }
    }
    fclose($handle);
    $this->info("Successfully imported {$count} users. Failed: {$errors}");
})->purpose('Import users from a CSV file (Headers: name, email, nis, role, password, major_name, class_name)');
