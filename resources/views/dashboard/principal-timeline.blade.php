<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Timeline / PKL Status - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; --accent: {{ $appBranding->accent_color ?? '#38bdf8' }}; --primary: {{ $appBranding->primary_color ?? '#2563eb' }}; --good:#22c55e; --warn:#f59e0b; --danger:#ef4444; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            min-height:100vh;
            font-family:'Instrument Sans',sans-serif;
            color:var(--text);
            background:
                radial-gradient(1000px 500px at 10% -10%, rgba(56, 189, 248, 0.2), transparent),
                radial-gradient(900px 450px at 100% 10%, rgba(37, 99, 235, 0.2), transparent),
                var(--bg);
        }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:270px 1fr; }
        .sidebar { position:sticky; top:0; height:100vh; display:flex; flex-direction:column; border-right:1px solid var(--border); background:rgba(15,23,42,.92); backdrop-filter:blur(10px); padding:16px 14px; }
        .sidebar-brand { font-weight:700; letter-spacing:.02em; padding:8px 10px; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.45); margin-bottom:14px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:8px; }
        .sidebar-nav a { text-decoration:none; color:var(--text); border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:rgba(30,41,59,.6); font-weight:500; transition:all .2s ease; }
        .sidebar-nav a:hover { border-color:var(--accent); color:var(--accent); }
        .sidebar-nav a.active { border-color:var(--primary); background:linear-gradient(135deg, rgba(37,99,235,.32), rgba(29,78,216,.32)); font-weight:700; color:#f8fafc; }
        .sidebar-profile { margin-top:auto; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.55); padding:12px; }
        .profile-trigger { width:100%; text-align:left; appearance:none; border:1px solid var(--border); border-radius:12px; color:var(--text); background:rgba(15,23,42,0.52); cursor:pointer; padding:10px; display:grid; grid-template-columns:42px 1fr 18px; align-items:center; gap:10px; transition:all .2s ease; }
        .profile-trigger:hover { border-color:var(--accent); box-shadow:0 8px 18px rgba(2,6,23,.35); transform:translateY(-1px); }
        .profile-avatar { width:42px; height:42px; border-radius:999px; border:1px solid rgba(56, 189, 248, 0.55); background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24)); display:grid; place-items:center; font-size:.82rem; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .profile-name { font-weight:700; margin-bottom:2px; }
        .profile-meta { font-size:.85rem; color:var(--muted); }
        .profile-arrow { color:var(--muted); font-size:1rem; text-align:right; }
        .profile-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.62); display:none; align-items:center; justify-content:center; z-index:2200; padding:16px; }
        .profile-modal-backdrop.open { display:flex; }
        .profile-modal-panel { width:min(560px,96vw); border:1px solid var(--border); border-radius:16px; background:linear-gradient(160deg, rgba(30,41,59,.96), rgba(15,23,42,.96)); padding:16px; max-height:92vh; overflow:auto; }
        .profile-modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .profile-modal-close,.profile-modal-btn { border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:8px 12px; cursor:pointer; font-weight:600; }
        .profile-modal-btn.primary { border-color:#2563eb; background:linear-gradient(135deg,#2563eb,#1d4ed8); color:#f8fafc; }
        .profile-modal-field { margin-bottom:12px; }
        .profile-modal-field label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; }
        .profile-modal-field input { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.95rem; }
        .profile-modal-actions { margin-top:14px; display:flex; justify-content:flex-end; gap:8px; }
        .profile-modal-alert.error { border:1px solid rgba(248,113,113,.6); border-radius:12px; padding:10px 12px; background:rgba(127,29,29,.25); margin-bottom:12px; }

        .main { padding:20px; }
        .container { display:grid; gap:14px; }
        .topbar { border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.9); padding:14px 16px; }
        .topbar h1 { font-size:1.1rem; }
        .muted { color:var(--muted); font-size:.92rem; }
        .card { border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding:14px; }

        .toolbar { display:grid; grid-template-columns:minmax(150px,180px) minmax(150px,180px) minmax(150px,180px) minmax(220px,1fr) auto auto; gap:10px; align-items:end; margin-top:10px; }
        .field label { display:block; margin-bottom:6px; font-size:.84rem; color:var(--muted); font-weight:600; }
        .field input,.field select { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.9rem; }
        .btn { text-decoration:none; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--primary); border-radius:10px; background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#fff; padding:10px 12px; font-weight:700; cursor:pointer; }
        .btn.secondary { border-color:var(--border); background:rgba(30,41,59,.75); }

        .stats-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; margin-top:10px; }
        .stat { border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.5); padding:12px; }
        .stat strong { display:block; margin-top:6px; font-size:1.2rem; }

        .timeline-list { margin-top:10px; border:1px solid var(--border); border-radius:12px; overflow:hidden; }
        .timeline-item { display:grid; grid-template-columns:110px 1fr 130px; gap:10px; align-items:center; padding:10px; border-bottom:1px solid rgba(148,163,184,.2); }
        .timeline-item:last-child { border-bottom:none; }
        .status-pill { border:1px solid var(--border); border-radius:999px; padding:5px 10px; font-size:.84rem; text-align:center; width:max-content; }
        .status-pill.current { border-color:rgba(56,189,248,.65); color:#7dd3fc; }
        .status-pill.done { border-color:rgba(34,197,94,.6); color:#86efac; }
        .status-pill.upcoming { border-color:rgba(148,163,184,.55); color:#cbd5e1; }

        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid var(--border); padding:8px; vertical-align:top; text-align:left; font-size:.88rem; }
        th { background:rgba(15,23,42,.95); }
        .badge { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:4px 9px; border:1px solid var(--border); font-size:.77rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
        .badge.ongoing { border-color:rgba(34,197,94,.6); color:#86efac; }
        .badge.not_started { border-color:rgba(245,158,11,.55); color:#fcd34d; }
        .badge.completed { border-color:rgba(56,189,248,.55); color:#7dd3fc; }
        .badge.missing_schedule { border-color:rgba(239,68,68,.58); color:#fecaca; }
        .progress { height:10px; border:1px solid var(--border); border-radius:999px; overflow:hidden; background:rgba(30,41,59,.45); margin-top:6px; }
        .progress > span { display:block; height:100%; width:var(--progress, 0%); background:linear-gradient(90deg, #22c55e, #38bdf8); }
        .pagination { margin-top:12px; }

        @media (max-width:1250px) {
            .app-shell { grid-template-columns:1fr; }
            .sidebar { position:static; height:auto; }
            .toolbar { grid-template-columns:1fr 1fr; }
            .stats-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .timeline-item { grid-template-columns:1fr; }
        }
        @media (max-width:760px) {
            .toolbar { grid-template-columns:1fr; }
            .stats-grid { grid-template-columns:1fr 1fr; }
        }
        .main { animation: page-drift-up 0.7s ease-out both; }
        .profile-modal-backdrop.open .profile-modal-panel { animation: page-drift-up 0.7s ease-out 0.15s both; }
        @keyframes page-drift-up {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
    @endphp

    <div class="app-shell">
        @include('dashboard.partials.principal-sidebar', ['user' => $user, 'activePage' => 'timeline'])

        <main class="main">
            <div class="container">
                <header class="topbar">
                    <h1>Timeline / PKL Status</h1>
                    <p class="muted">Principal-facing season timeline and student PKL progress status.</p>
                </header>

                <section class="card">
                    <form method="GET" action="{{ route('dashboard.principal.timeline') }}" class="toolbar">
                        <div class="field">
                            <label for="major">Major</label>
                            <select id="major" name="major">
                                @foreach (($majorOptions ?? collect(['ALL'])) as $major)
                                    <option value="{{ $major }}" {{ (string) ($selectedMajor ?? 'ALL') === (string) $major ? 'selected' : '' }}>{{ $major }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="class">Class</label>
                            <select id="class" name="class">
                                @foreach (($classOptions ?? collect(['ALL'])) as $class)
                                    <option value="{{ $class }}" {{ (string) ($selectedClass ?? 'ALL') === (string) $class ? 'selected' : '' }}>{{ $class }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                <option value="not_started" {{ ($statusFilter ?? 'all') === 'not_started' ? 'selected' : '' }}>Not Started</option>
                                <option value="ongoing" {{ ($statusFilter ?? 'all') === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                                <option value="completed" {{ ($statusFilter ?? 'all') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="missing_schedule" {{ ($statusFilter ?? 'all') === 'missing_schedule' ? 'selected' : '' }}>Missing Schedule</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="q">Search</label>
                            <input id="q" name="q" type="text" value="{{ $search ?? '' }}" placeholder="Name, NIS, major, class, company">
                        </div>
                        <button class="btn" type="submit">Apply</button>
                        <a class="btn secondary" href="{{ route('dashboard.principal.timeline') }}">Reset</a>
                    </form>
                </section>

                <section class="card">
                    <h2>Status Summary</h2>
                    <div class="stats-grid">
                        <div class="stat"><span class="muted">Students</span><strong>{{ (int) data_get($summary, 'total_students', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Ongoing</span><strong>{{ (int) data_get($summary, 'ongoing', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Not Started</span><strong>{{ (int) data_get($summary, 'not_started', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Completed</span><strong>{{ (int) data_get($summary, 'completed', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Missing Schedule</span><strong>{{ (int) data_get($summary, 'missing_schedule', 0) }}</strong></div>
                    </div>
                </section>

                <section class="card">
                    <h2>Season Timeline</h2>
                    @if (!empty($timelineStart) && !empty($timelineEnd))
                        <p class="muted">
                            {{ \Illuminate\Support\Carbon::parse($timelineStart, 'Asia/Jakarta')->format('d M Y') }}
                            -
                            {{ \Illuminate\Support\Carbon::parse($timelineEnd, 'Asia/Jakarta')->format('d M Y') }}
                            &middot;
                            {{ $timelineStatus }}
                        </p>
                        <div class="timeline-list">
                            @foreach (($timelineWeeks ?? collect()) as $week)
                                @php
                                    $status = strtolower((string) ($week['status_type'] ?? 'upcoming'));
                                    if (!in_array($status, ['current', 'done', 'upcoming'], true)) {
                                        $status = 'upcoming';
                                    }
                                @endphp
                                <div class="timeline-item">
                                    <strong>Week {{ (int) ($week['week'] ?? 0) }}</strong>
                                    <div class="muted">
                                        {{ \Illuminate\Support\Carbon::parse($week['start'], 'Asia/Jakarta')->format('d M Y') }}
                                        -
                                        {{ \Illuminate\Support\Carbon::parse($week['end'], 'Asia/Jakarta')->format('d M Y') }}
                                    </div>
                                    <span class="status-pill {{ $status }}">{{ $week['status_label'] ?? ucfirst($status) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="muted">Timeline is not available yet because PKL start/end dates are still empty.</p>
                    @endif
                </section>

                <section class="card">
                    <h2>Student PKL Status</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Major / Class</th>
                                <th>Company</th>
                                <th>PKL Date Range</th>
                                <th>Status</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($rowsPage ?? collect()) as $row)
                                <tr>
                                    <td>{{ $row->student_name ?? '-' }}<br><span class="muted">NIS: {{ $row->student_nis ?? '-' }}</span></td>
                                    <td>{{ $row->major_name ?? '-' }} / {{ $row->class_name ?? '-' }}</td>
                                    <td>{{ $row->company_name ?? '-' }}<br><span class="muted">{{ $row->company_address ?? '-' }}</span></td>
                                    <td>
                                        @if (!empty($row->pkl_start_date) && !empty($row->pkl_end_date))
                                            {{ \Illuminate\Support\Carbon::parse($row->pkl_start_date, 'Asia/Jakarta')->format('d M Y') }}
                                            -
                                            {{ \Illuminate\Support\Carbon::parse($row->pkl_end_date, 'Asia/Jakarta')->format('d M Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $row->status_key ?? 'missing_schedule' }}">{{ $row->status_label ?? 'Missing Schedule' }}</span></td>
                                    <td>
                                        {{ (int) ($row->progress_percent ?? 0) }}%
                                        <div class="progress" style="--progress: {{ (int) ($row->progress_percent ?? 0) }}%;">
                                            <span></span>
                                        </div>
                                        <span class="muted">
                                            @if ((int) ($row->total_days ?? 0) > 0)
                                                {{ (int) ($row->elapsed_days ?? 0) }} / {{ (int) ($row->total_days ?? 0) }} days
                                            @else
                                                No schedule
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No student PKL status rows found for current filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="pagination">{{ $rowsPage->links() }}</div>
                </section>
            </div>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])

    @include('partials.chatbot')
</body>
</html>

