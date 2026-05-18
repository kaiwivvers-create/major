<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School Performance - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; --accent: {{ $appBranding->accent_color ?? '#38bdf8' }}; --primary: {{ $appBranding->primary_color ?? '#2563eb' }}; }
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
        .muted { color:var(--muted); font-size:.92rem; }
        .card { border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding:14px; }
        .toolbar { display:grid; grid-template-columns:minmax(220px,260px) minmax(140px,180px) minmax(140px,180px) auto auto; gap:10px; align-items:end; margin-top:10px; }
        .field label { display:block; margin-bottom:6px; font-size:.84rem; color:var(--muted); font-weight:600; }
        .field input,.field select { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.9rem; }
        .btn { text-decoration:none; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--primary); border-radius:10px; background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#fff; padding:10px 12px; font-weight:700; cursor:pointer; }
        .btn.secondary { border-color:var(--border); background:rgba(30,41,59,.75); }

        .stats-grid { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:10px; margin-top:10px; }
        .stat { border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.5); padding:12px; }
        .stat strong { display:block; margin-top:6px; font-size:1.2rem; }

        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid var(--border); padding:8px; vertical-align:top; text-align:left; font-size:.88rem; }
        th { background:rgba(15,23,42,.95); }

        .bars { display:grid; gap:10px; margin-top:10px; }
        .bar-row { display:grid; grid-template-columns:120px 1fr 74px; gap:8px; align-items:center; font-size:.9rem; }
        .bar-track { height:14px; border:1px solid var(--border); border-radius:999px; background:rgba(30,41,59,.45); overflow:hidden; }
        .bar-track span { display:block; height:100%; width:var(--pct,0%); background:linear-gradient(90deg, #38bdf8, #2563eb); }

        @media (max-width:1200px) {
            .app-shell { grid-template-columns:1fr; }
            .sidebar { position:static; height:auto; }
            .toolbar { grid-template-columns:1fr 1fr; }
            .stats-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .bar-row { grid-template-columns:1fr; }
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
        @include('dashboard.partials.principal-sidebar', ['user' => $user, 'activePage' => 'school-performance'])

        <main class="main">
            <div class="container">
                <header class="topbar">
                    <h1>School Performance</h1>
                    <p class="muted">Period: {{ \Illuminate\Support\Carbon::parse($periodStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($today, 'Asia/Jakarta')->format('d M Y') }} &middot; Journal Week: {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</p>
                </header>

                <section class="card">
                    <form method="GET" action="{{ route('dashboard.principal.school-performance') }}" class="toolbar">
                        <div class="field">
                            <label for="week_start">Journal Week Start</label>
                            <input id="week_start" name="week_start" type="date" value="{{ $weekStart }}">
                        </div>
                        <div class="field">
                            <label for="period_days">Attendance Period</label>
                            <select id="period_days" name="period_days">
                                @foreach (($periodOptions ?? [14,30,60,90]) as $days)
                                    <option value="{{ $days }}" {{ (int) ($periodDays ?? 30) === (int) $days ? 'selected' : '' }}>Last {{ $days }} Days</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="major">Major</label>
                            <select id="major" name="major">
                                @foreach (($majorOptions ?? collect(['ALL'])) as $major)
                                    <option value="{{ $major }}" {{ (string) ($selectedMajor ?? 'ALL') === (string) $major ? 'selected' : '' }}>{{ $major }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn" type="submit">Apply</button>
                        <a class="btn secondary" href="{{ route('dashboard.principal.school-performance') }}">Reset</a>
                    </form>
                </section>

                <section class="card">
                    <h2>Summary</h2>
                    <div class="stats-grid">
                        <div class="stat"><span class="muted">Students</span><strong>{{ (int) data_get($summary, 'students_total', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Placed</span><strong>{{ (int) data_get($summary, 'placed_total', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Journals</span><strong>{{ (int) data_get($summary, 'journals_total', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Approved Journals</span><strong>{{ (int) data_get($summary, 'journals_approved', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Avg Attendance %</span><strong>{{ number_format((float) data_get($summary, 'avg_attendance_rate', 0), 1) }}%</strong></div>
                        <div class="stat"><span class="muted">Avg Journal Approval %</span><strong>{{ number_format((float) data_get($summary, 'avg_journal_approval_rate', 0), 1) }}%</strong></div>
                    </div>
                    <p class="muted" style="margin-top:8px;">Expected attendance baseline uses {{ (int) ($workingDays ?? 0) }} working day(s) in selected period.</p>
                </section>

                <section class="card">
                    <h2>Attendance Trends</h2>
                    <p class="muted">Attendance distribution for the last 30 days.</p>
                    <div style="height: 300px; margin-top: 10px;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </section>

                <section class="card">
                    <h2>Department Comparison</h2>
                    <p class="muted">Attendance rate by major over selected period.</p>
                    <div class="bars">
                        @forelse (($performanceRows ?? collect()) as $row)
                            <div class="bar-row">
                                <strong>{{ $row->major ?? '-' }}</strong>
                                <div class="bar-track" style="--pct: {{ min(100, max(0, (float) ($row->attendance_rate ?? 0))) }}%;"><span></span></div>
                                <span>{{ number_format((float) ($row->attendance_rate ?? 0), 1) }}%</span>
                            </div>
                        @empty
                            <p class="muted">No major performance data for selected filters.</p>
                        @endforelse
                    </div>
                </section>

                <section class="card">
                    <h2>Performance Table</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Major</th>
                                <th>Students</th>
                                <th>Placed</th>
                                <th>Placement %</th>
                                <th>Attendance Days</th>
                                <th>Expected Days</th>
                                <th>Attendance %</th>
                                <th>Journals</th>
                                <th>Approved</th>
                                <th>Approval %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($performanceRows ?? collect()) as $row)
                                <tr>
                                    <td>{{ $row->major ?? '-' }}</td>
                                    <td>{{ (int) ($row->students ?? 0) }}</td>
                                    <td>{{ (int) ($row->placed ?? 0) }}</td>
                                    <td>{{ number_format((float) ($row->placement_rate ?? 0), 1) }}%</td>
                                    <td>{{ (int) ($row->attended_days ?? 0) }}</td>
                                    <td>{{ (int) ($row->expected_attendance_days ?? 0) }}</td>
                                    <td>{{ number_format((float) ($row->attendance_rate ?? 0), 1) }}%</td>
                                    <td>{{ (int) ($row->journal_total ?? 0) }}</td>
                                    <td>{{ (int) ($row->journal_approved ?? 0) }}</td>
                                    <td>{{ number_format((float) ($row->journal_approval_rate ?? 0), 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="10">No performance rows found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const ctx = document.getElementById('attendanceChart');
            if (!ctx) return;

            try {
                const response = await fetch('/api/stats/attendance-overview');
                const result = await response.json();
                
                if (result.success && result.data) {
                    const labels = result.data.map(item => {
                        const date = new Date(item.date);
                        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
                    });
                    const totals = result.data.map(item => item.total);

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Student Check-ins',
                                data: totals,
                                borderColor: '#38bdf8',
                                backgroundColor: 'rgba(56, 189, 248, 0.1)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointBackgroundColor: '#38bdf8',
                                pointBorderColor: '#fff',
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                    titleColor: '#fff',
                                    bodyColor: '#e2e8f0',
                                    borderColor: '#334155',
                                    borderWidth: 1,
                                    padding: 10,
                                    displayColors: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(148, 163, 184, 0.1)'
                                    },
                                    ticks: {
                                        color: '#94a3b8',
                                        font: {
                                            family: "'Instrument Sans', sans-serif"
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#94a3b8',
                                        font: {
                                            family: "'Instrument Sans', sans-serif"
                                        },
                                        maxRotation: 45,
                                        minRotation: 45
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading attendance chart:', error);
            }
        });
    </script>

    @include('partials.chatbot')
</body>
</html>

