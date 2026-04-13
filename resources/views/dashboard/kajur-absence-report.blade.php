<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kajur Absence Report - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; --primary:#2563eb; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); background:radial-gradient(1000px 500px at 10% -10%, rgba(56,189,248,.2), transparent), radial-gradient(900px 450px at 100% 10%, rgba(37,99,235,.2), transparent), var(--bg); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:270px 1fr; }
        .sidebar { position:sticky; top:0; height:100vh; display:flex; flex-direction:column; border-right:1px solid var(--border); background:rgba(15,23,42,.92); backdrop-filter:blur(10px); padding:16px 14px; }
        .sidebar-brand { font-weight:700; letter-spacing:.02em; padding:8px 10px; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.45); margin-bottom:14px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:8px; }
        .sidebar-nav a { text-decoration:none; color:var(--text); border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:rgba(30,41,59,.6); font-weight:500; transition:all .2s ease; }
        .sidebar-nav a:hover { border-color:var(--accent); color:var(--accent); }
        .sidebar-nav a.active { border-color:var(--primary); background:linear-gradient(135deg, rgba(37,99,235,.32), rgba(29,78,216,.32)); font-weight:700; }
        .sidebar-profile { margin-top:auto; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.55); padding:12px; }
        .profile-trigger { width:100%; text-align:left; appearance:none; border:1px solid var(--border); border-radius:12px; color:var(--text); background:rgba(15,23,42,.52); cursor:pointer; padding:10px; display:grid; grid-template-columns:42px 1fr 18px; align-items:center; gap:10px; transition:all .2s ease; }
        .profile-trigger:hover { border-color:var(--accent); box-shadow:0 8px 18px rgba(2,6,23,.35); transform:translateY(-1px); }
        .profile-avatar { width:42px; height:42px; border-radius:999px; border:1px solid rgba(56,189,248,.55); background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24)); display:grid; place-items:center; font-size:.82rem; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .profile-name { font-weight:700; margin-bottom:2px; }
        .profile-meta { font-size:.85rem; color:var(--muted); }
        .profile-arrow { color:var(--muted); font-size:1rem; text-align:right; }
        .main { padding:20px; }
        .topbar { border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.9); padding:14px 16px; margin-bottom:12px; }
        .card { border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding:14px; margin-bottom:12px; }
        .muted { color:var(--muted); font-size:.9rem; }
        .filters { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)) auto auto; gap:10px; align-items:end; margin-top:10px; }
        .field label { display:block; margin-bottom:6px; font-size:.85rem; color:var(--muted); font-weight:600; }
        .field input,.field select { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; }
        .btn { border:1px solid var(--primary); border-radius:10px; background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#fff; padding:10px 12px; font-weight:700; text-decoration:none; cursor:pointer; display:inline-block; }
        .stats { display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:8px; margin-top:10px; }
        .stat { border:1px solid var(--border); border-radius:10px; background:rgba(30,41,59,.45); padding:10px; }
        .stat .v { font-size:1.2rem; font-weight:700; margin-top:4px; }
        .table-wrap { overflow:auto; border:1px solid var(--border); border-radius:12px; }
        table { width:100%; border-collapse:collapse; min-width:900px; }
        th,td { text-align:left; padding:10px; border-bottom:1px solid var(--border); font-size:.9rem; }
        th { background:rgba(15,23,42,.95); color:#cbd5e1; font-weight:700; }
        .profile-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.62); display:none; align-items:center; justify-content:center; z-index:2200; padding:16px; }
        .profile-modal-backdrop.open { display:flex; }
        .profile-modal-panel { width:min(560px,96vw); border:1px solid var(--border); border-radius:16px; background:linear-gradient(160deg, rgba(30,41,59,.96), rgba(15,23,42,.96)); padding:16px; max-height:92vh; overflow:auto; }
        .profile-modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .profile-modal-close,.profile-modal-btn { border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:8px 12px; cursor:pointer; font-weight:600; }
        .profile-modal-btn.primary { border-color:var(--primary); background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#f8fafc; }
        .profile-modal-field { margin-bottom:12px; }
        .profile-modal-field label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; }
        .profile-modal-field input { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.95rem; }
        .profile-modal-actions { margin-top:14px; display:flex; justify-content:flex-end; gap:8px; }
        .profile-modal-alert.error { border:1px solid rgba(248,113,113,.6); border-radius:12px; padding:10px 12px; background:rgba(127,29,29,.25); margin-bottom:12px; }
        @media (max-width:1100px){ .app-shell{grid-template-columns:1fr;} .sidebar{position:static;height:auto;} .filters{grid-template-columns:1fr 1fr;} .stats{grid-template-columns:repeat(2,minmax(0,1fr));} }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
        $avatarInitials = collect(explode(' ', trim($user->name ?? 'U')))->filter()->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('');
        $avatarSource = !empty($user?->avatar_url)
            ? (\Illuminate\Support\Str::startsWith($user->avatar_url, ['http://', 'https://']) ? $user->avatar_url : \Illuminate\Support\Facades\Storage::url($user->avatar_url))
            : null;
        $avatarSourceWithVersion = $avatarSource ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($user->updated_at?->timestamp ?? time()) : null;
    @endphp
    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">{{ config('app.name', 'Kips') }} - Kajur</div>
            <nav class="sidebar-nav" aria-label="Kajur menu">
                <a href="{{ route('dashboard.kajur.dashboard') }}">Kajur Dashboard</a>
                <a href="{{ route('dashboard.kajur.weekly-journal') }}">Weekly Journals</a>
                <a href="{{ route('dashboard.kajur.daily-checkin') }}">Daily Check-ins</a>
                <a class="active" href="{{ route('dashboard.kajur.absence-report') }}">Absence Report</a>
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
            <header class="topbar">
                <div>
                    <h1>Absence Rate Report</h1>
                    <p class="muted" style="margin-top:4px;">Major: <strong>{{ $managedMajor }}</strong></p>
                </div>
            </header>
            <section class="card">
                <form method="GET" action="{{ route('dashboard.kajur.absence-report') }}">
                    <div class="filters">
                        <div class="field">
                            <label for="start">Start</label>
                            <input id="start" name="start" type="date" value="{{ $startDate }}">
                        </div>
                        <div class="field">
                            <label for="end">End</label>
                            <input id="end" name="end" type="date" value="{{ $endDate }}">
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
                        <a class="btn" href="{{ route('dashboard.kajur.absence-report', ['start' => $startDate, 'end' => $endDate, 'class' => $selectedClass, 'export' => 'csv']) }}">Export CSV</a>
                    </div>
                </form>

                <div class="stats">
                    <div class="stat"><div class="muted">Students</div><div class="v">{{ (int) ($summary['students'] ?? 0) }}</div></div>
                    <div class="stat"><div class="muted">Working Days</div><div class="v">{{ (int) ($summary['working_days_total'] ?? 0) }}</div></div>
                    <div class="stat"><div class="muted">Present</div><div class="v">{{ (int) ($summary['present_total'] ?? 0) }}</div></div>
                    <div class="stat"><div class="muted">Excused</div><div class="v">{{ (int) ($summary['excused_total'] ?? 0) }}</div></div>
                    <div class="stat"><div class="muted">Alpha</div><div class="v">{{ (int) ($summary['alpha_total'] ?? 0) }}</div></div>
                    <div class="stat"><div class="muted">Absence Rate</div><div class="v">{{ number_format((float) ($summary['absence_rate'] ?? 0), 2) }}%</div></div>
                </div>
            </section>

            <section class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>NIS</th>
                                <th>Class</th>
                                <th>Working</th>
                                <th>Present</th>
                                <th>Excused</th>
                                <th>Alpha</th>
                                <th>Absence Days</th>
                                <th>Absence Rate %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($rows ?? collect()) as $row)
                                <tr>
                                    <td>{{ $row->student_name }}</td>
                                    <td>{{ $row->student_nis ?? '-' }}</td>
                                    <td>{{ $row->class_name ?? '-' }}</td>
                                    <td>{{ (int) $row->working_days }}</td>
                                    <td>{{ (int) $row->present_days }}</td>
                                    <td>{{ (int) $row->excused_days }}</td>
                                    <td>{{ (int) $row->alpha_days }}</td>
                                    <td>{{ (int) $row->absence_days }}</td>
                                    <td>{{ number_format((float) $row->absence_rate, 2) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="9">No students in this scope.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])
</body>
</html>
