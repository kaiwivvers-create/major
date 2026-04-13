<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Error') . ' - ' . config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --surface-2: #0b1222;
            --primary: #2563eb;
            --accent: #38bdf8;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: #334155;
            --danger: #f87171;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Instrument Sans', sans-serif;
            color: var(--text);
            background:
                radial-gradient(1000px 500px at 10% -10%, rgba(56, 189, 248, 0.2), transparent),
                radial-gradient(900px 450px at 100% 10%, rgba(37, 99, 235, 0.2), transparent),
                var(--bg);
        }

        .app-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 270px 1fr;
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(10px);
            padding: 16px 14px;
        }

        .sidebar-brand {
            font-weight: 700;
            letter-spacing: 0.02em;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.45);
            margin-bottom: 14px;
        }

        .sidebar-meta {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-nav a,
        .logout-btn {
            text-decoration: none;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            background: rgba(30, 41, 59, 0.6);
            font-weight: 500;
            transition: all 0.2s ease;
            text-align: left;
            font: inherit;
            cursor: pointer;
            width: 100%;
        }

        .sidebar-nav a:hover,
        .logout-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .sidebar-profile {
            margin-top: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.55);
            padding: 12px;
        }

        .profile-trigger {
            width: 100%;
            text-align: left;
            appearance: none;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            background: rgba(15, 23, 42, 0.52);
            cursor: default;
            padding: 10px;
            display: grid;
            grid-template-columns: 42px 1fr 18px;
            align-items: center;
            gap: 10px;
        }

        .profile-avatar {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid rgba(56, 189, 248, 0.55);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.28), rgba(56, 189, 248, 0.24));
            display: grid;
            place-items: center;
            font-size: 0.82rem;
            font-weight: 700;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-name { font-weight: 700; margin-bottom: 2px; }
        .profile-meta { font-size: 0.85rem; color: var(--muted); }
        .profile-arrow { color: var(--muted); font-size: 1rem; text-align: right; }

        .main {
            padding: 20px;
            display: grid;
            place-items: center;
        }

        .error-card {
            width: min(760px, 100%);
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94));
            padding: 22px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(248, 113, 113, 0.5);
            border-radius: 999px;
            background: rgba(127, 29, 29, 0.25);
            color: #fecaca;
            font-weight: 700;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 0.86rem;
        }

        h1 {
            font-size: clamp(1.35rem, 2vw, 1.8rem);
            margin-bottom: 8px;
        }

        .muted {
            color: var(--muted);
            line-height: 1.55;
        }

        .actions {
            margin-top: 16px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            background: rgba(15, 23, 42, 0.7);
            padding: 9px 12px;
            text-decoration: none;
            font-weight: 600;
        }

        .btn.primary {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #f8fafc;
        }

        @media (max-width: 1100px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .main { padding-top: 0; }
        }
    </style>
</head>
<body>
@php
    $errorUser = auth()->user();
    $homeUrl = url('/');
    $dashboardUrl = $errorUser ? route('dashboard') : route('login');
    $roleLabel = $errorUser ? strtoupper((string) $errorUser->role) : 'GUEST';
    $role = $errorUser?->role;
    $canAccess = function (string $module, string $action = 'view') use ($errorUser): bool {
        if (!$errorUser) {
            return false;
        }

        return function_exists('user_can_access')
            ? user_can_access($errorUser, $module, $action)
            : true;
    };
    $avatarInitials = $errorUser
        ? collect(explode(' ', trim($errorUser->name ?? 'U')))
            ->filter()
            ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('')
        : 'G';
    $avatarSource = $errorUser && !empty($errorUser->avatar_url)
        ? (\Illuminate\Support\Str::startsWith($errorUser->avatar_url, ['http://', 'https://'])
            ? $errorUser->avatar_url
            : \Illuminate\Support\Facades\Storage::url($errorUser->avatar_url))
        : null;
    $avatarSourceWithVersion = $avatarSource
        ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($errorUser->updated_at?->timestamp ?? time())
        : null;
    $studentClassName = '';
    if ($errorUser && $role === 'student' && \Illuminate\Support\Facades\Schema::hasTable('student_profiles')) {
        $studentClassName = trim((string) \Illuminate\Support\Facades\DB::table('student_profiles')
            ->where('student_id', $errorUser->id)
            ->value('class_name'));
    }
@endphp
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            @if ($role === 'super_admin')
                {{ config('app.name', 'Kips') }} - Super Admin
            @else
                {{ config('app.name', 'Kips') }}
            @endif
        </div>
        <div class="sidebar-meta">
            {{ $errorUser ? ($errorUser->name . ' · ' . $roleLabel) : 'You are not signed in.' }}
        </div>
        <nav class="sidebar-nav" aria-label="Error page menu">
            @if ($role === 'super_admin')
                @if ($canAccess('super_admin_dashboard', 'view'))
                    <a href="{{ route('dashboard.super-admin') }}">Dashboard</a>
                @endif
                @if ($canAccess('users_management', 'view'))
                    <a href="{{ route('dashboard.super-admin.mass-edit') }}">Implementation Timeline</a>
                    <a href="{{ route('dashboard.super-admin.companies') }}">Companies</a>
                    <a href="{{ route('dashboard.super-admin.users') }}">Users</a>
                    <a href="{{ route('dashboard.super-admin.permissions') }}">Permissions</a>
                    <a href="{{ route('dashboard.super-admin.activities') }}">Activities</a>
                    <a href="{{ route('dashboard.super-admin.mass-edit') }}">Mass Edit</a>
                @endif
                <a href="{{ $homeUrl }}">Back to Home</a>
            @elseif ($role === 'student')
                @if ($canAccess('student_dashboard', 'view'))
                    <a href="{{ route('dashboard.student') }}">Dashboard</a>
                @endif
                @if ($canAccess('checkin', 'view'))
                    <a href="{{ route('dashboard.student.checkin-page') }}">Check-in / Check-out</a>
                @endif
                @if ($canAccess('task_log', 'view'))
                    <a href="{{ route('dashboard.student.task-log-page') }}">Today's Task Log</a>
                @endif
                @if ($canAccess('weekly_journal', 'view'))
                    <a href="{{ route('dashboard.student.weekly-journal') }}">Weekly Journal</a>
                @endif
                @if ($canAccess('completion', 'view'))
                    <a href="{{ route('dashboard.student.completion') }}">Completion Bar</a>
                @endif
                @if ($canAccess('student_data', 'view'))
                    <a href="{{ route('dashboard.student.data-page') }}">Student Data</a>
                @endif
                <a href="{{ $homeUrl }}">Back to Home</a>
            @elseif ($role === 'mentor')
                @if ($canAccess('weekly_journal', 'view'))
                    <a href="{{ route('dashboard.mentor.weekly-journal') }}">Mentor Weekly Validation</a>
                @endif
                <a href="{{ $homeUrl }}">Back to Home</a>
            @elseif ($role === 'kajur')
                @if ($canAccess('weekly_journal', 'view'))
                    <a href="{{ route('dashboard.kajur.weekly-journal') }}">Kajur Weekly Review</a>
                @endif
                <a href="{{ $homeUrl }}">Back to Home</a>
            @elseif ($role === 'teacher')
                @if ($canAccess('weekly_journal', 'view'))
                    <a href="{{ route('dashboard.bindo.weekly-journal') }}">Teacher Weekly Notes</a>
                @endif
                <a href="{{ $homeUrl }}">Back to Home</a>
            @elseif ($role === 'principal')
                @if ($canAccess('weekly_journal', 'view'))
                    <a href="{{ route('dashboard.principal.weekly-journal') }}">Principal Overview</a>
                @endif
                <a href="{{ $homeUrl }}">Back to Home</a>
            @else
                <a href="{{ $homeUrl }}">Home</a>
                <a href="{{ $dashboardUrl }}">{{ $errorUser ? 'Dashboard' : 'Login' }}</a>
            @endif
            @if ($errorUser)
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-btn" type="submit">Logout</button>
                </form>
            @endif
        </nav>
        @if ($errorUser)
            <div class="sidebar-profile">
                <div class="profile-trigger">
                    <span class="profile-avatar">
                        @if (!empty($avatarSourceWithVersion))
                            <img src="{{ $avatarSourceWithVersion }}" alt="Profile picture" onerror="this.style.display='none'; this.parentElement.textContent='{{ $avatarInitials }}';">
                        @else
                            {{ $avatarInitials }}
                        @endif
                    </span>
                    <span>
                        <div class="profile-name">{{ $errorUser->name }}</div>
                        <div class="profile-meta">
                            NIS: {{ $errorUser->nis ?? '-' }}
                            @if ($studentClassName !== '')
                                &middot; Class: {{ $studentClassName }}
                            @endif
                            &middot; {{ $roleLabel }}
                        </div>
                    </span>
                    <span class="profile-arrow">></span>
                </div>
            </div>
        @endif
    </aside>

    <main class="main">
        <section class="error-card">
            <div class="status-badge">Error {{ $status ?? 500 }}</div>
            <h1>{{ $title ?? 'Something went wrong' }}</h1>
            <p class="muted">{{ $message ?? 'The request could not be completed.' }}</p>

            <div class="actions">
                <a class="btn primary" href="{{ $dashboardUrl }}">{{ $errorUser ? 'Back to Dashboard' : 'Go to Login' }}</a>
                <a class="btn" href="{{ $homeUrl }}">Back to Home</a>
            </div>
        </section>
    </main>
</div>
</body>
</html>
