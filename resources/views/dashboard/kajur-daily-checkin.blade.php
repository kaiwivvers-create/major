<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kajur Daily Check-ins - {{ config('app.name', 'Kips') }}</title>
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
            --primary: #2563eb;
            --accent: #38bdf8;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: #334155;
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

        .profile-trigger {
            appearance: none;
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            background: rgba(15, 23, 42, 0.52);
            cursor: pointer;
            padding: 10px;
            display: grid;
            grid-template-columns: 42px 1fr 18px;
            align-items: center;
            gap: 10px;
            text-align: left;
            min-width: 0;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .profile-trigger:hover {
            border-color: var(--accent);
            box-shadow: 0 8px 18px rgba(2, 6, 23, 0.35);
            transform: translateY(-1px);
        }

        .profile-avatar {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid rgba(56, 189, 248, 0.55);
            background: linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24));
            display: grid;
            place-items: center;
            font-size: 0.82rem;
            font-weight: 700;
            overflow: hidden;
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .profile-name { font-weight: 700; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .profile-meta { font-size: 0.85rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .profile-arrow { color: var(--muted); font-size: 1rem; text-align: right; }

        .profile-modal-backdrop { position: fixed; inset: 0; background: rgba(2, 6, 23, 0.62); display: none; align-items: center; justify-content: center; z-index: 2200; padding: 16px; }
        .profile-modal-backdrop.open { display: flex; }
        .profile-modal-panel { width: min(560px, 96vw); border: 1px solid var(--border); border-radius: 16px; background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96)); padding: 16px; max-height: 92vh; overflow: auto; }
        .profile-modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .profile-modal-close, .profile-modal-btn { border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.72); color: var(--text); padding: 8px 12px; cursor: pointer; font-weight: 600; }
        .profile-modal-btn.primary { border-color: var(--primary); background: linear-gradient(135deg, var(--primary), #1d4ed8); color: #f8fafc; }
        .profile-modal-field { margin-bottom: 12px; }
        .profile-modal-field label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; }
        .profile-modal-field input { width: 100%; border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.7); color: var(--text); padding: 10px 12px; font-size: 0.95rem; }
        .profile-modal-actions { margin-top: 14px; display: flex; justify-content: flex-end; gap: 8px; }
        .profile-modal-alert.error { border: 1px solid rgba(248, 113, 113, 0.6); border-radius: 12px; padding: 10px 12px; background: rgba(127, 29, 29, 0.25); margin-bottom: 12px; }

        .main { padding: 20px; }

        .container { display: grid; gap: 14px; }

        .topbar {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.9);
            padding: 14px 16px;
        }

        .card { border: 1px solid var(--border); border-radius: 14px; background: linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding: 14px; }
        .grid {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 12px;
        }
        #live-map {
            height: 340px;
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
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

        .muted { color: var(--muted); font-size: 0.92rem; }

        .filters {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
            align-items: end;
        }

        .field label { display: block; margin-bottom: 6px; font-size: 0.86rem; color: var(--muted); font-weight: 600; }
        .field input, .field select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 10px 12px;
            font-size: 0.92rem;
        }

        .btn {
            border: 1px solid var(--primary);
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #fff;
            padding: 10px 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .table-wrap {
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        table { width: 100%; border-collapse: collapse; min-width: 980px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid var(--border); vertical-align: top; font-size: 0.9rem; }
        th { background: rgba(15, 23, 42, 0.95); color: #cbd5e1; font-weight: 700; }

        .status-badge {
            display: inline-block;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.7);
            padding: 3px 9px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.checked-in { border-color: rgba(56, 189, 248, 0.6); color: #bae6fd; }
        .status-badge.checked-out { border-color: rgba(34, 197, 94, 0.6); color: #bbf7d0; }
        .status-badge.no-checkin { border-color: rgba(248, 113, 113, 0.5); color: #fecaca; }
        .status-badge.excused { border-color: rgba(34, 197, 94, 0.6); color: #dcfce7; }
        .status-badge.pending { border-color: rgba(245, 158, 11, 0.6); color: #fde68a; }
        .status-badge.alpha { border-color: rgba(239, 68, 68, 0.75); color: #fecaca; }

        .photo-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
            display: block;
        }

        .photo-link {
            color: #93c5fd;
            text-decoration: none;
            font-size: 0.82rem;
        }

        .photo-link:hover { text-decoration: underline; }
        .alert {
            border: 1px solid rgba(56, 189, 248, 0.55);
            border-radius: 12px;
            padding: 10px 12px;
            margin-top: 10px;
            background: rgba(30, 64, 175, 0.22);
        }
        .alert.error {
            border-color: rgba(248, 113, 113, 0.65);
            background: rgba(127, 29, 29, 0.28);
        }
        .alert.warn {
            border-color: rgba(245, 158, 11, 0.65);
            background: rgba(120, 53, 15, 0.3);
        }

        .map-students-modal {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2300;
            padding: 16px;
        }

        .map-students-modal.open {
            display: flex;
        }

        .map-students-panel {
            width: min(640px, 96vw);
            max-height: 85vh;
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 14px;
        }

        .map-students-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
        }

        .map-students-close {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 6px 10px;
            cursor: pointer;
        }

        .map-students-list {
            display: grid;
            gap: 8px;
            margin-top: 8px;
        }

        .map-student-item {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.62);
            padding: 10px;
        }

        .map-students-pagination {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .map-students-btn {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 7px 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .map-students-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 1050px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .main { padding-top: 0; }
        }

        @media (max-width: 800px) {
            .filters { grid-template-columns: 1fr; }
        }
        @media (max-width: 1000px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
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
                        <h1>Kajur Daily Check-ins</h1>
                        <p class="muted" style="margin-top:4px;">Showing student attendance by day for managed major: <strong>{{ strtoupper((string) $managedMajor) }}</strong>.</p>
                        <p class="muted" style="margin-top:4px;">Check-in cutoff: <strong>{{ \Illuminate\Support\Str::substr((string) ($checkInCutoffTime ?? '08:00:00'), 0, 5) }} WIB</strong>.</p>
                    </div>
                </header>

                <section class="card">
                    @if (session('status'))
                        <div class="alert">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert error">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    @foreach (($escalationAlerts ?? collect()) as $escalationAlert)
                        <div class="alert warn">{{ $escalationAlert }}</div>
                    @endforeach
                    @if (($recentAlerts ?? collect())->isNotEmpty())
                        <div class="alert">
                            <strong>Recent Auto Alerts</strong>
                            @foreach (($recentAlerts ?? collect()) as $alertRow)
                                <div class="muted" style="margin-top:4px;">
                                    {{ \Illuminate\Support\Carbon::parse($alertRow->alert_date, 'Asia/Jakarta')->format('d M Y') }}:
                                    {{ $alertRow->message }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form method="GET" action="{{ route('dashboard.kajur.daily-checkin') }}">
                        <div class="filters">
                            <div class="field">
                                <label for="date">Date</label>
                                <input id="date" name="date" type="date" value="{{ $selectedDate }}" max="{{ $today }}">
                            </div>
                            <div class="field">
                                <label for="class">Class</label>
                                <select id="class" name="class">
                                    @foreach (($classOptions ?? collect(['ALL'])) as $classOption)
                                        <option value="{{ $classOption }}" {{ (string) $selectedClass === (string) $classOption ? 'selected' : '' }}>{{ $classOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn" type="submit">Apply</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('dashboard.kajur.attendance-calendar') }}" style="margin-top:10px;">
                        @csrf
                        <input type="hidden" name="exception_date" value="{{ $selectedDate }}">
                        <input type="hidden" name="class_name" value="{{ $selectedClass }}">
                        <div class="filters" style="grid-template-columns: 1fr 1fr auto auto;">
                            <div class="field">
                                <label for="exception_type">Calendar Type</label>
                                <select id="exception_type" name="exception_type">
                                    <option value="holiday">Holiday</option>
                                    <option value="school_off">School Off</option>
                                    <option value="company_off">Company Off</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="calendar_notes">Notes</label>
                                <input id="calendar_notes" name="notes" type="text" placeholder="Optional note">
                            </div>
                            <input type="hidden" name="action" value="upsert">
                            <button class="btn" type="submit">Mark Non-Working</button>
                        </div>
                    </form>
                    @if (!empty($calendarException))
                        <div class="alert warn">
                            Selected date has calendar exception: <strong>{{ strtoupper((string) ($calendarException->exception_type ?? '-')) }}</strong>
                            @if (!empty($calendarException->notes))
                                <div class="muted" style="margin-top:4px;">{{ $calendarException->notes }}</div>
                            @endif
                            <form method="POST" action="{{ route('dashboard.kajur.attendance-calendar') }}" style="margin-top:8px;">
                                @csrf
                                <input type="hidden" name="exception_date" value="{{ $selectedDate }}">
                                <input type="hidden" name="class_name" value="{{ $selectedClass }}">
                                <input type="hidden" name="exception_type" value="{{ $calendarException->exception_type }}">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn" type="submit">Remove Exception</button>
                            </form>
                        </div>
                    @endif
                </section>

                <section class="grid">
                    <article class="card">
                        <h2>Attendance Map (Major Scoped)</h2>
                        <p class="muted">Map is limited to students in your managed major{{ $selectedClass !== 'ALL' ? ' and class ' . $selectedClass : '' }}.</p>
                        <p class="muted" id="live-map-filter-label" style="margin-top:6px;">Showing current company locations from the last 30 days.</p>
                        <div id="live-map"></div>
                        @if (($companyMapPoints ?? collect())->isEmpty())
                            <p class="muted" style="margin-top:8px;">No map coordinates yet for this major scope.</p>
                        @endif
                    </article>

                    <article class="card">
                        <h2>Attendance Heatmap (Last 30 Days)</h2>
                        <p class="muted">Click a day to filter map pins by attendance date.</p>
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
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Selfie</th>
                                    <th>Correction</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($dailyCheckins ?? collect()) as $row)
                                    @php
                                        $statusClass = 'no-checkin';
                                        if ($row->attendance_status === 'Checked In') {
                                            $statusClass = 'checked-in';
                                        } elseif ($row->attendance_status === 'Checked Out') {
                                            $statusClass = 'checked-out';
                                        } elseif (\Illuminate\Support\Str::startsWith((string) $row->attendance_status, 'Excused')) {
                                            $statusClass = 'excused';
                                        } elseif ($row->attendance_status === 'Excuse Pending') {
                                            $statusClass = 'pending';
                                        } elseif ($row->attendance_status === 'Alpha') {
                                            $statusClass = 'alpha';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $row->student_name }}<br><span class="muted">NIS: {{ $row->student_nis ?? '-' }}</span></td>
                                        <td>{{ $row->class_name ?: '-' }}</td>
                                        <td>
                                            {{ $row->check_in_at ? \Illuminate\Support\Carbon::parse($row->check_in_at, 'Asia/Jakarta')->format('H:i') : '-' }}
                                            @if (($row->late_minutes ?? 0) > 0)
                                                <br><span class="muted">Late {{ (int) $row->late_minutes }}m</span>
                                            @endif
                                        </td>
                                        <td>{{ $row->check_out_at ? \Illuminate\Support\Carbon::parse($row->check_out_at, 'Asia/Jakarta')->format('H:i') : '-' }}</td>
                                        <td>
                                            <span class="status-badge {{ $statusClass }}">{{ $row->attendance_status }}</span>
                                            @if (!empty($row->absence_reason))
                                                <br><span class="muted">{{ \Illuminate\Support\Str::limit((string) $row->absence_reason, 80) }}</span>
                                            @endif
                                            @if (!empty($row->absence_attachment_url))
                                                <br><a class="photo-link" href="{{ $row->absence_attachment_url }}" target="_blank" rel="noopener noreferrer">Open proof</a>
                                            @endif
                                        </td>
                                        <td>{{ $row->location }}</td>
                                        <td>
                                            @if (!empty($row->photo_url))
                                                <img src="{{ $row->photo_url }}" class="photo-thumb" alt="Check-in selfie">
                                                <a class="photo-link" href="{{ $row->photo_url }}" target="_blank" rel="noopener noreferrer">Open</a>
                                            @else
                                                <span class="muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('dashboard.kajur.attendance-correction', $row->student_id) }}" style="display:grid; gap:6px;">
                                                @csrf
                                                <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">
                                                <select name="correction_type" style="border:1px solid var(--border); border-radius:8px; background:rgba(15,23,42,.7); color:var(--text); padding:6px 8px;">
                                                    <option value="present">Present</option>
                                                    <option value="late">Late</option>
                                                    <option value="excused_sick">Excused Sick</option>
                                                    <option value="excused_permit">Excused Permit</option>
                                                    <option value="alpha">Alpha</option>
                                                </select>
                                                <input name="check_in_time" type="time" value="{{ $row->check_in_at ? \Illuminate\Support\Carbon::parse($row->check_in_at, 'Asia/Jakarta')->format('H:i') : '' }}" style="border:1px solid var(--border); border-radius:8px; background:rgba(15,23,42,.7); color:var(--text); padding:6px 8px;">
                                                <input name="check_out_time" type="time" value="{{ $row->check_out_at ? \Illuminate\Support\Carbon::parse($row->check_out_at, 'Asia/Jakarta')->format('H:i') : '' }}" style="border:1px solid var(--border); border-radius:8px; background:rgba(15,23,42,.7); color:var(--text); padding:6px 8px;">
                                                <input name="notes" type="text" placeholder="Correction notes" style="border:1px solid var(--border); border-radius:8px; background:rgba(15,23,42,.7); color:var(--text); padding:6px 8px;">
                                                <button class="btn" type="submit">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">No students found for this scope.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card">
                    <h2>Pending Absence Requests (Selected Date)</h2>
                    @if (($pendingAbsenceRequests ?? collect())->isEmpty())
                        <p class="muted">No pending requests for this date.</p>
                    @else
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Type</th>
                                        <th>Reason</th>
                                        <th>Proof</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (($pendingAbsenceRequests ?? collect()) as $requestRow)
                                        <tr>
                                            <td>{{ $requestRow->student_name }}<br><span class="muted">NIS: {{ $requestRow->student_nis ?? '-' }}</span></td>
                                            <td>{{ $requestRow->class_name ?? '-' }}</td>
                                            <td>{{ strtoupper((string) $requestRow->absence_type) }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit((string) $requestRow->reason, 120) }}</td>
                                            <td>
                                                @if (!empty($requestRow->attachment_url))
                                                    <a class="photo-link" href="{{ $requestRow->attachment_url }}" target="_blank" rel="noopener noreferrer">Open</a>
                                                @else
                                                    <span class="muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <form method="POST" action="{{ route('dashboard.kajur.absence-review', $requestRow->id) }}" style="display:grid; gap:6px;">
                                                    @csrf
                                                    <input type="hidden" name="action" value="approve">
                                                    <button class="btn" type="submit">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ route('dashboard.kajur.absence-review', $requestRow->id) }}" style="display:grid; gap:6px; margin-top:6px;">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <input name="rejection_notes" type="text" placeholder="Reject notes" style="border:1px solid var(--border); border-radius:8px; background:rgba(15,23,42,.7); color:var(--text); padding:6px 8px;">
                                                    <button class="btn" type="submit">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
        </main>
    </div>

    <div class="map-students-modal" id="map-students-modal" aria-hidden="true">
        <div class="map-students-panel" role="dialog" aria-modal="true" aria-labelledby="map-students-title">
            <div class="map-students-head">
                <div>
                    <h3 id="map-students-title">Company Students</h3>
                    <p class="muted" id="map-students-subtitle">-</p>
                </div>
                <button type="button" class="map-students-close" id="map-students-close">Close</button>
            </div>
            <div class="muted" id="map-students-count">-</div>
            <div class="map-students-list" id="map-students-list"></div>
            <div class="map-students-pagination">
                <button type="button" class="map-students-btn" id="map-students-prev">Previous</button>
                <div class="muted" id="map-students-page">Page 1 / 1</div>
                <button type="button" class="map-students-btn" id="map-students-next">Next</button>
            </div>
        </div>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script>
        (() => {
            const mapEl = document.getElementById('live-map');
            if (!mapEl || typeof window.L === 'undefined') return;

            const points = @json($companyMapPoints ?? []);
            const attendancePoints = @json($attendanceMapPoints ?? []);
            const initialDate = @json($selectedDate ?? null);
            const heatCells = Array.from(document.querySelectorAll('.heat-cell[data-date]'));
            const filterLabel = document.getElementById('live-map-filter-label');
            const studentsByCompany = @json($companyStudentsByCompany ?? []);
            const studentsModal = document.getElementById('map-students-modal');
            const studentsClose = document.getElementById('map-students-close');
            const studentsTitle = document.getElementById('map-students-title');
            const studentsSubtitle = document.getElementById('map-students-subtitle');
            const studentsCount = document.getElementById('map-students-count');
            const studentsList = document.getElementById('map-students-list');
            const studentsPrev = document.getElementById('map-students-prev');
            const studentsNext = document.getElementById('map-students-next');
            const studentsPage = document.getElementById('map-students-page');
            const pageSize = 5;
            let currentStudents = [];
            let currentPage = 1;

            const companyKey = (companyName, companyAddress) => `${(companyName ?? '').trim()}||${(companyAddress ?? '').trim()}`;

            const renderStudentsPage = () => {
                if (!studentsList || !studentsPrev || !studentsNext || !studentsPage || !studentsCount) return;
                const total = currentStudents.length;
                const totalPages = Math.max(1, Math.ceil(total / pageSize));
                if (currentPage > totalPages) currentPage = totalPages;
                const start = (currentPage - 1) * pageSize;
                const slice = currentStudents.slice(start, start + pageSize);

                studentsList.innerHTML = '';
                if (!slice.length) {
                    const empty = document.createElement('div');
                    empty.className = 'muted';
                    empty.textContent = 'No students available for this location.';
                    studentsList.appendChild(empty);
                } else {
                    slice.forEach((student) => {
                        const item = document.createElement('div');
                        item.className = 'map-student-item';
                        const checkInText = student.check_in_at ? ` | Check-in: ${student.check_in_at} WIB` : '';
                        item.innerHTML = `<strong>${student.student_name ?? '-'}</strong><br><span class="muted">NIS: ${student.student_nis ?? '-'} | Major: ${student.major_name ?? '-'}${checkInText}</span>`;
                        studentsList.appendChild(item);
                    });
                }

                studentsCount.textContent = `Total students here: ${total}`;
                studentsPage.textContent = `Page ${currentPage} / ${totalPages}`;
                studentsPrev.disabled = currentPage <= 1;
                studentsNext.disabled = currentPage >= totalPages;
            };

            const openStudentsModal = (companyName, companyAddress) => {
                if (!studentsModal || !studentsTitle || !studentsSubtitle) return;
                const key = companyKey(companyName, companyAddress);
                currentStudents = Array.isArray(studentsByCompany?.[key]) ? studentsByCompany[key] : [];
                currentPage = 1;
                studentsTitle.textContent = companyName || 'Company Students';
                studentsSubtitle.textContent = companyAddress || '-';
                renderStudentsPage();
                studentsModal.classList.add('open');
                studentsModal.setAttribute('aria-hidden', 'false');
            };

            const closeStudentsModal = () => {
                if (!studentsModal) return;
                studentsModal.classList.remove('open');
                studentsModal.setAttribute('aria-hidden', 'true');
            };

            studentsPrev?.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderStudentsPage();
                }
            });
            studentsNext?.addEventListener('click', () => {
                const totalPages = Math.max(1, Math.ceil(currentStudents.length / pageSize));
                if (currentPage < totalPages) {
                    currentPage++;
                    renderStudentsPage();
                }
            });
            studentsClose?.addEventListener('click', closeStudentsModal);
            studentsModal?.addEventListener('click', (event) => {
                if (event.target === studentsModal) closeStudentsModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && studentsModal?.classList.contains('open')) closeStudentsModal();
            });

            const map = L.map(mapEl, {
                center: [-6.2, 106.8],
                zoom: 10,
                scrollWheelZoom: false,
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

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

            const markerLayer = L.layerGroup().addTo(map);
            let selectedDate = null;

            const formatIsoDate = (isoDate) => {
                const parsed = new Date(`${isoDate}T00:00:00`);
                if (Number.isNaN(parsed.getTime())) return isoDate;
                return parsed.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            };

            const renderPoints = (items, popupBuilder, clickHandler) => {
                markerLayer.clearLayers();
                const bounds = [];
                items.forEach((item) => {
                    const lat = Number(item.latitude);
                    const lng = Number(item.longitude);
                    bounds.push([lat, lng]);
                    L.circleMarker([lat, lng], {
                        radius: 8,
                        color: '#67e8f9',
                        weight: 2,
                        fillColor: '#2563eb',
                        fillOpacity: 0.85,
                    }).addTo(markerLayer).bindPopup(popupBuilder(item)).on('click', () => clickHandler(item));
                });
                if (bounds.length) {
                    map.fitBounds(bounds, { padding: [18, 18] });
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
                    (item) => `<strong>${item.company_name ?? 'Company'}</strong><br>${item.company_address ?? '-'}<br>Active students: ${item.active_students ?? 0}`,
                    (item) => openStudentsModal(item.company_name, item.company_address)
                );
                if (filterLabel) {
                    filterLabel.textContent = count
                        ? 'Showing current company locations from the last 30 days.'
                        : 'No map coordinates yet for this major scope.';
                }
            };

            const renderAttendanceForDate = (isoDate, totalCheckins) => {
                selectedDate = isoDate;
                setSelectedHeatCell(isoDate);
                const filtered = Array.isArray(attendancePointsByDate?.[isoDate]) ? attendancePointsByDate[isoDate] : [];
                const count = renderPoints(
                    filtered,
                    (item) => `<strong>${item.company_name ?? 'Company'}</strong><br>${item.company_address ?? '-'}<br>Attendance on date: ${item.attendance_total ?? 0}`,
                    (item) => openStudentsModal(item.company_name, item.company_address)
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

            const initialCell = heatCells.find((cell) => String(cell.getAttribute('data-date') || '') === String(initialDate || ''));
            if (initialCell) {
                renderAttendanceForDate(
                    String(initialCell.getAttribute('data-date') || ''),
                    Number(initialCell.getAttribute('data-total') || '0')
                );
            } else {
                renderBaseMap();
            }
        })();
    </script>
</body>
</html>
