<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kajur Dashboard - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
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
            --warning: #f59e0b;
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

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-nav a {
            text-decoration: none;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            background: rgba(30, 41, 59, 0.6);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar-nav a:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .sidebar-nav a.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.32), rgba(29, 78, 216, 0.32));
            color: #f8fafc;
            font-weight: 700;
        }

        .sidebar-profile {
            margin-top: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.55);
            padding: 12px;
        }

        .main { padding: 20px; }

        .container {
            display: grid;
            gap: 14px;
        }

        .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.9); padding:14px 16px; }
        .card { border: 1px solid var(--border); border-radius: 14px; background: linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding: 14px; }

        .title-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .badge {
            font-size: 0.76rem;
            font-weight: 700;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 5px 10px;
            color: #dbeafe;
            background: rgba(37, 99, 235, 0.24);
        }

        .muted { color: var(--muted); font-size: 0.92rem; }

        .alert {
            border-radius: 10px;
            padding: 10px 12px;
            margin-top: 8px;
            font-size: 0.92rem;
        }

        .alert.success {
            border: 1px solid rgba(56, 189, 248, 0.45);
            background: rgba(14, 165, 233, 0.12);
        }

        .alert.error {
            border: 1px solid rgba(248, 113, 113, 0.55);
            background: rgba(127, 29, 29, 0.25);
        }

        .profile-trigger {
            appearance: none;
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            background: rgba(15, 23, 42, 0.52);
            cursor: pointer;
            padding: 8px 10px;
            display: grid;
            grid-template-columns: 40px 1fr 16px;
            align-items: center;
            gap: 8px;
            text-align: left;
            min-width: 0;
            overflow: hidden;
        }

        .profile-trigger:hover { border-color: var(--accent); box-shadow: 0 8px 18px rgba(2, 6, 23, 0.35); transform: translateY(-1px); }

        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid rgba(56, 189, 248, 0.55);
            background: linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24));
            display: grid;
            place-items: center;
            font-size: 0.8rem;
            font-weight: 700;
            overflow: hidden;
        }

        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .profile-name {
            font-weight: 700;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .profile-meta {
            font-size: 0.8rem;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .profile-arrow { color: var(--muted); font-size: 0.95rem; text-align: right; }
        .profile-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2200;
            padding: 16px;
        }
        .profile-modal-backdrop.open { display: flex; }
        .profile-modal-panel {
            width: min(560px, 96vw);
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 16px;
            max-height: 92vh;
            overflow: auto;
        }
        .profile-modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .profile-modal-close,
        .profile-modal-btn {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.72);
            color: var(--text);
            padding: 8px 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .profile-modal-btn.primary {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #f8fafc;
        }
        .profile-modal-field { margin-bottom: 12px; }
        .profile-modal-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .profile-modal-field input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 10px 12px;
            font-size: 0.95rem;
        }
        .profile-modal-actions {
            margin-top: 14px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .profile-modal-alert.error {
            border: 1px solid rgba(248, 113, 113, 0.6);
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(127, 29, 29, 0.25);
            margin-bottom: 12px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .stat {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.52);
            padding: 12px;
        }

        .stat strong { display: block; font-size: 1.5rem; margin-top: 6px; }

        .week-controls {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }

        .week-filter-form {
            display: grid;
            grid-template-columns: minmax(220px, 320px) auto;
            gap: 10px;
            align-items: end;
        }

        .filter-field {
            display: grid;
            gap: 6px;
        }

        .filter-field label {
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #cbd5e1;
            font-weight: 700;
        }

        .week-date-input {
            width: 100%;
            height: 42px;
            border: 1px solid #1d4ed8;
            border-radius: 12px;
            background: linear-gradient(160deg, rgba(37, 99, 235, 0.24), rgba(15, 23, 42, 0.9));
            color: var(--text);
            padding: 0 12px;
            font-size: 0.92rem;
            font-family: inherit;
            font-weight: 600;
        }

        .week-date-input:focus-visible {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .week-submit-btn {
            height: 44px;
            align-self: start;
            padding-inline: 14px;
            margin-top: 26px;
        }

        .week-filter-note {
            font-size: 0.82rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .class-switcher {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .class-switcher a {
            text-decoration: none;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 0.86rem;
            background: rgba(30, 41, 59, 0.5);
        }

        .class-switcher a.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(37,99,235,.28), rgba(29,78,216,.24));
            font-weight: 700;
        }

        .class-switcher-empty {
            margin-top: 8px;
            font-size: 0.84rem;
            color: var(--muted);
            border: 1px dashed rgba(148, 163, 184, 0.4);
            border-radius: 10px;
            padding: 8px 10px;
            background: rgba(15, 23, 42, 0.4);
        }

        .layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 14px;
            align-items: start;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 10px;
        }

        .queue-item,
        .alert-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px;
            background: rgba(2, 6, 23, 0.45);
            margin-bottom: 8px;
        }

        .queue-item h4,
        .alert-item h4 { font-size: 0.92rem; margin-bottom: 5px; }

        .queue-item textarea {
            width: 100%;
            min-height: 64px;
            margin-top: 8px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.68);
            color: var(--text);
            padding: 8px;
            font-family: inherit;
            resize: vertical;
        }

        .queue-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .btn {
            border: 1px solid var(--border);
            border-radius: 9px;
            color: var(--text);
            background: rgba(30, 41, 59, 0.6);
            padding: 8px 10px;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
        }

        .btn.primary {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #f8fafc;
        }

        .btn.warn {
            border-color: rgba(245, 158, 11, 0.45);
            background: rgba(120, 53, 15, 0.44);
            color: #fde68a;
        }

        .alert-item {
            border-color: rgba(248, 113, 113, 0.4);
        }

        .alert-item strong { color: #fecaca; }

        #industry-map {
            width: 100%;
            height: 280px;
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
            margin-top: 8px;
        }

        .heatmap-grid {
            margin-top: 8px;
            display: grid;
            grid-template-columns: repeat(10, minmax(0, 1fr));
            gap: 6px;
        }

        .heat-cell {
            border-radius: 6px;
            border: 1px solid rgba(51, 65, 85, 0.7);
            background: rgba(15, 23, 42, 0.45);
            min-height: 28px;
            display: grid;
            place-items: center;
            font-size: 0.78rem;
            font-weight: 700;
            color: #cbd5e1;
            font-variant-numeric: tabular-nums;
            cursor: pointer;
            transition: transform 0.14s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .heat-cell:hover {
            transform: translateY(-1px);
            border-color: rgba(56, 189, 248, 0.75);
        }

        .heat-cell.is-selected {
            border-color: rgba(103, 232, 249, 0.9);
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.28);
        }

        .heat-cell[data-level="1"] { background: rgba(59, 130, 246, 0.22); }
        .heat-cell[data-level="2"] { background: rgba(59, 130, 246, 0.36); }
        .heat-cell[data-level="3"] { background: rgba(37, 99, 235, 0.52); color: #e2e8f0; }
        .heat-cell[data-level="4"] { background: rgba(29, 78, 216, 0.72); color: #f8fafc; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid var(--border);
            padding: 8px;
            vertical-align: top;
            text-align: left;
            font-size: 0.88rem;
        }

        th { background: rgba(15, 23, 42, 0.8); }

        @media (max-width: 1050px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .main { padding-top: 0; }
            .layout { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 640px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .main { padding: 12px; }
            .stats-grid { grid-template-columns: 1fr; }
            .week-filter-form { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $openProfileModal = $errors->has('name')
            || $errors->has('nis')
            || $errors->has('avatar_crop_data')
            || $errors->has('password');
        $avatarInitials = collect(explode(' ', trim($user->name ?? 'U')))
            ->filter()
            ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');
        $avatarSource = !empty($user?->avatar_url)
            ? (\Illuminate\Support\Str::startsWith($user->avatar_url, ['http://', 'https://']) ? $user->avatar_url : \Illuminate\Support\Facades\Storage::url($user->avatar_url))
            : null;
        $avatarSourceWithVersion = $avatarSource
            ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($user->updated_at?->timestamp ?? time())
            : null;

        $totalStudents = (int) (($students ?? collect())->count());
        $checkedInTodayCount = (int) ($checkedInToday ?? 0);
        $approvalQueueCount = (int) (($approvalQueue ?? collect())->count());
        $problemAlertCount = (int) (($problemAlerts ?? collect())->count());
        $canViewWeeklyJournal = function_exists('user_can_access') ? user_can_access($user, 'weekly_journal', 'view') : true;
    @endphp

    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">{{ config('app.name', 'Kips') }} - Kajur</div>
            <nav class="sidebar-nav" aria-label="Kajur menu">
                @if ($canViewWeeklyJournal)
                    <a class="{{ request()->routeIs('dashboard.kajur.dashboard') ? 'active' : '' }}" href="{{ route('dashboard.kajur.dashboard') }}">Kajur Dashboard</a>
                    <a class="{{ request()->routeIs('dashboard.kajur.weekly-journal') ? 'active' : '' }}" href="{{ route('dashboard.kajur.weekly-journal') }}">Weekly Journals</a>
                    <a class="{{ request()->routeIs('dashboard.kajur.daily-checkin') ? 'active' : '' }}" href="{{ route('dashboard.kajur.daily-checkin') }}">Daily Check-ins</a>
                    <a class="{{ request()->routeIs('dashboard.kajur.absence-report') ? 'active' : '' }}" href="{{ route('dashboard.kajur.absence-report') }}">Absence Report</a>
                @endif
                <a href="{{ url('/') }}">Back to Home</a>
            </nav>
            <div class="sidebar-profile">
                <button type="button" class="profile-trigger" id="open-profile-modal" aria-label="Open profile modal">
                    <span class="profile-avatar">
                        @if (!empty($avatarSourceWithVersion))
                            <img src="{{ $avatarSourceWithVersion }}" alt="Profile picture" onerror="this.style.display='none'; this.parentElement.textContent='{{ $avatarInitials }}';">
                        @else
                            {{ $avatarInitials }}
                        @endif
                    </span>
                    <span>
                        <div class="profile-name">{{ $user->name }}</div>
                        <div class="profile-meta">NIS: {{ $user->nis ?? '-' }} &middot; {{ strtoupper($user->role) }}</div>
                    </span>
                    <span class="profile-arrow">></span>
                </button>
            </div>
        </aside>

        <main class="main">
    <div class="container">
        <header class="topbar">
            <div>
                <div class="title-row">
                    <h1>Kajur Dashboard</h1>
                    <span class="badge">Managed Major: {{ strtoupper((string) ($managedMajor ?? '-')) }}</span>
                </div>
                <p class="muted" style="margin-top:4px;">Monitoring {{ strtoupper((string) ($selectedMajor ?? '-')) }} - Week {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</p>
            </div>
        </header>

        <section class="card">
            @if (session('status'))
                <div class="alert success">{{ session('status') }}</div>
            @endif
            @if ($errors->has('academic_validation'))
                <div class="alert error">{{ $errors->first('academic_validation') }}</div>
            @endif

            <div class="stats-grid" style="margin-top:12px;">
                <article class="stat">
                    <div class="muted">Students in Scope</div>
                    <strong>{{ $totalStudents }}</strong>
                </article>
                <article class="stat">
                    <div class="muted">Checked In Today</div>
                    <strong>{{ $checkedInTodayCount }}</strong>
                </article>
                <article class="stat">
                    <div class="muted">Academic Validation Queue</div>
                    <strong>{{ $approvalQueueCount }}</strong>
                </article>
                <article class="stat">
                    <div class="muted">Red Flags ({{ (int) ($redFlagDays ?? 2) }}+ days)</div>
                    <strong>{{ $problemAlertCount }}</strong>
                </article>
            </div>

            <div class="week-controls">
                <form method="GET" action="{{ route('dashboard.kajur.dashboard') }}" class="week-filter-form">
                    <div class="filter-field">
                        <label for="week_start">Week Start</label>
                        <input id="week_start" class="week-date-input" name="week_start" type="date" value="{{ $weekStart }}">
                        <div class="week-filter-note">Select any date in a week. The dashboard will use that week's Monday.</div>
                        <input type="hidden" name="major" value="{{ $selectedMajor ?: $managedMajor }}">
                        <input type="hidden" name="class" value="{{ $selectedClass ?: 'ALL' }}">
                    </div>
                    <button class="btn primary week-submit-btn" type="submit">View Week</button>
                </form>
                <div class="muted">Class Switcher</div>
                <div class="class-switcher">
                    @foreach (($classOptions ?? collect(['ALL'])) as $classOption)
                        <a
                            href="{{ route('dashboard.kajur.dashboard', ['major' => $selectedMajor, 'class' => $classOption, 'week_start' => $weekStart]) }}"
                            class="{{ (string) ($selectedClass ?? 'ALL') === (string) $classOption ? 'active' : '' }}"
                        >
                            {{ $classOption }}
                        </a>
                    @endforeach
                </div>
                @if (($classOptions ?? collect(['ALL']))->count() <= 1)
                    <div class="class-switcher-empty">
                        Only <strong>ALL</strong> is available right now. Class chips will appear after student class data exists in profiles.
                    </div>
                @endif
            </div>
        </section>

        <section class="layout">
            <article class="card">
                <div class="section-head">
                    <h2>Approval Queue</h2>
                    <span class="muted">Daily logs waiting for Academic Validation</span>
                </div>
                @forelse (($approvalQueue ?? collect()) as $log)
                    <div class="queue-item">
                        <h4>{{ $log->student_name }} ({{ $log->student_nis ?? '-' }})</h4>
                        <p class="muted">{{ strtoupper((string) ($log->major_name ?? '-')) }} &middot; {{ $log->class_name ?? '-' }} &middot; {{ \Illuminate\Support\Carbon::parse($log->work_date, 'Asia/Jakarta')->format('d M Y') }}</p>
                        <p style="margin-top:8px;"><strong>{{ $log->title }}</strong></p>
                        <p class="muted" style="margin-top:4px;">{{ trim((string) ($log->work_realization ?: $log->description)) ?: 'No realization details.' }}</p>
                        <form method="POST" action="{{ route('dashboard.kajur.daily-log.academic-validation', $log->id) }}">
                            @csrf
                            <textarea name="kajur_feedback" placeholder="Academic note (required when revision is needed).">{{ old('kajur_feedback') }}</textarea>
                            <div class="queue-actions">
                                <button type="submit" name="validation_status" value="valid" class="btn primary">Validate Match</button>
                                <button type="submit" name="validation_status" value="revise" class="btn warn">Request Revision</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="muted">No pending daily activity in this scope.</p>
                @endforelse
            </article>

            <article class="card">
                <div class="section-head">
                    <h2>Problem Alerts</h2>
                    <span class="muted">No check-in for {{ (int) ($redFlagDays ?? 2) }}+ days</span>
                </div>
                @forelse (($problemAlerts ?? collect()) as $alert)
                    <div class="alert-item">
                        <h4>{{ $alert->student_name }} ({{ $alert->student_nis ?? '-' }})</h4>
                        <p class="muted">{{ strtoupper((string) ($alert->major_name ?? '-')) }} &middot; {{ $alert->class_name ?? '-' }}</p>
                        <p style="margin-top:4px;">
                            <strong>
                                @if (is_null($alert->days_missing))
                                    Never checked in yet
                                @else
                                    Missing {{ (int) $alert->days_missing }} day(s)
                                @endif
                            </strong>
                        </p>
                        <p class="muted" style="margin-top:2px;">Last check-in: {{ $alert->last_attendance_date ? \Illuminate\Support\Carbon::parse($alert->last_attendance_date, 'Asia/Jakarta')->format('d M Y') : '-' }}</p>
                    </div>
                @empty
                    <p class="muted">No red flags currently.</p>
                @endforelse
            </article>
        </section>

        <section class="layout">
            <article class="card">
                <h2>Industry Map</h2>
                <p class="muted">Student distribution based on attendance GPS in {{ strtoupper((string) ($selectedMajor ?? '-')) }} {{ ($selectedClass ?? 'ALL') !== 'ALL' ? ' &middot; ' . $selectedClass : '' }}.</p>
                <p class="muted" id="live-map-filter-label" style="margin-top:6px;">Showing current company locations from the last 30 days.</p>
                <div id="industry-map"></div>
                @if (($industryMapPoints ?? collect())->isEmpty())
                    <p class="muted" style="margin-top:8px;">No map points yet. Pins appear when students check in with GPS.</p>
                @endif
            </article>

            <article class="card">
                <h2>Attendance Heatmap (Last 30 Days)</h2>
                <p class="muted">Click a day number to filter map pins by that date.</p>
                <div class="heatmap-grid">
                    @foreach (($heatmap ?? collect()) as $cell)
                        @php
                            $level = ($maxAttendance ?? 0) > 0 ? (int) ceil(($cell['total'] / $maxAttendance) * 4) : 0;
                            $level = max(0, min(4, $level));
                            $dayNumber = \Illuminate\Support\Carbon::parse($cell['date'], 'Asia/Jakarta')->format('d');
                        @endphp
                        <div
                            class="heat-cell"
                            data-level="{{ $level }}"
                            data-date="{{ $cell['date'] }}"
                            data-total="{{ (int) $cell['total'] }}"
                            title="{{ \Illuminate\Support\Carbon::parse($cell['date'], 'Asia/Jakarta')->format('d M Y') }}: {{ $cell['total'] }} check-ins"
                        >{{ $dayNumber }}</div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="card">
            <div class="section-head">
                <h2>Weekly Journal Snapshot</h2>
                <span class="muted">Selected week</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Mentor Validation</th>
                        <th>Kajur Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($rows ?? collect()) as $row)
                        <tr>
                            <td>
                                {{ $row->student_name }}<br>
                                <span class="muted">NIS: {{ $row->student_nis }} &middot; {{ $row->class_name ?? '-' }}</span>
                            </td>
                            <td>
                                <div>Check: {{ is_null($row->mentor_is_correct) ? '-' : ($row->mentor_is_correct ? 'Correct' : 'Not Complete') }}</div>
                                <div>Missing: {{ $row->missing_info_notes ?? '-' }}</div>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('dashboard.kajur.weekly-journal.note', $row->id) }}">
                                    @csrf
                                    <textarea name="kajur_notes" placeholder="Write kajur notes...">{{ old('kajur_notes', $row->kajur_notes ?? '') }}</textarea>
                                    <div class="queue-actions">
                                        <button class="btn primary" type="submit">Save Note</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No weekly journals for this class scope.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script>
        (() => {
            const mapEl = document.getElementById('industry-map');
            if (!mapEl || typeof window.L === 'undefined') return;

            const points = @json($industryMapPoints ?? []);
            const attendancePoints = @json($attendanceMapPoints ?? []);
            const heatCells = Array.from(document.querySelectorAll('.heat-cell[data-date]'));
            const filterLabel = document.getElementById('live-map-filter-label');
            const map = L.map(mapEl, { zoomControl: true, scrollWheelZoom: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            const markerLayer = L.layerGroup().addTo(map);
            let selectedDate = null;

            const basePoints = (Array.isArray(points) ? points : []).filter((item) =>
                item &&
                item.latitude !== null &&
                item.longitude !== null &&
                !Number.isNaN(Number(item.latitude)) &&
                !Number.isNaN(Number(item.longitude))
            );
            const validAttendancePoints = (Array.isArray(attendancePoints) ? attendancePoints : []).filter((item) =>
                item &&
                item.attendance_date &&
                item.latitude !== null &&
                item.longitude !== null &&
                !Number.isNaN(Number(item.latitude)) &&
                !Number.isNaN(Number(item.longitude))
            );

            const attendancePointsByDate = validAttendancePoints.reduce((acc, item) => {
                const key = String(item.attendance_date || '');
                if (!key) return acc;
                if (!acc[key]) acc[key] = [];
                acc[key].push(item);
                return acc;
            }, {});

            const formatIsoDate = (isoDate) => {
                const parsed = new Date(`${isoDate}T00:00:00`);
                if (Number.isNaN(parsed.getTime())) return isoDate;
                return parsed.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            };

            const renderPoints = (items, popupBuilder) => {
                markerLayer.clearLayers();
                const bounds = [];

                items.forEach((point) => {
                    const lat = Number(point.latitude);
                    const lng = Number(point.longitude);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                    bounds.push([lat, lng]);
                    L.marker([lat, lng]).bindPopup(popupBuilder(point)).addTo(markerLayer);
                });

                if (bounds.length) {
                    map.fitBounds(bounds, { padding: [16, 16] });
                } else {
                    map.setView([-6.2, 106.8], 10);
                }
                return bounds.length;
            };

            const setSelectedHeatCell = (date) => {
                heatCells.forEach((cell) => {
                    const isActive = cell.getAttribute('data-date') === date;
                    cell.classList.toggle('is-selected', Boolean(date) && isActive);
                });
            };

            const renderBaseMap = () => {
                selectedDate = null;
                setSelectedHeatCell(null);
                const count = renderPoints(
                    basePoints,
                    (item) => `
                        <strong>${item.company_name ?? 'Unknown Industry'}</strong><br>
                        <span>${item.company_address ?? '-'}</span><br>
                        <span>Students: ${Number(item.active_students ?? 0)}</span>
                    `
                );

                if (filterLabel) {
                    filterLabel.textContent = count
                        ? 'Showing current company locations from the last 30 days.'
                        : 'No map points yet. Pins appear when students check in with GPS.';
                }
            };

            const renderAttendanceForDate = (isoDate, totalCheckins) => {
                selectedDate = isoDate;
                setSelectedHeatCell(isoDate);
                const filtered = Array.isArray(attendancePointsByDate?.[isoDate]) ? attendancePointsByDate[isoDate] : [];
                const count = renderPoints(
                    filtered,
                    (item) => `
                        <strong>${item.company_name ?? 'Unknown Industry'}</strong><br>
                        <span>${item.company_address ?? '-'}</span><br>
                        <span>Attendance on date: ${Number(item.attendance_total ?? 0)}</span>
                    `
                );

                if (filterLabel) {
                    filterLabel.textContent = count
                        ? `Showing attendance map for ${formatIsoDate(isoDate)} (${totalCheckins} check-ins).`
                        : `No attendance coordinates found for ${formatIsoDate(isoDate)}.`;
                }
            };

            heatCells.forEach((cell) => {
                cell.addEventListener('click', () => {
                    const date = String(cell.getAttribute('data-date') || '');
                    const totalCheckins = Number(cell.getAttribute('data-total') || '0');
                    if (!date) return;
                    if (selectedDate === date) {
                        renderBaseMap();
                        return;
                    }
                    renderAttendanceForDate(date, totalCheckins);
                });
            });

            if (!basePoints.length && !validAttendancePoints.length) return;
            renderBaseMap();
        })();
    </script>
</body>
</html>
