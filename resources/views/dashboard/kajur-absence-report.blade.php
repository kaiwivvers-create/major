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
        body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); background:var(--bg); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:270px 1fr; }
        .sidebar { position:sticky; top:0; height:100vh; display:flex; flex-direction:column; border-right:1px solid var(--border); background:rgba(15,23,42,.92); padding:16px 14px; }
        .sidebar-brand { font-weight:700; letter-spacing:.02em; padding:8px 10px; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.45); margin-bottom:14px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:8px; }
        .sidebar-nav a { text-decoration:none; color:var(--text); border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:rgba(30,41,59,.6); font-weight:500; }
        .sidebar-nav a.active { border-color:var(--primary); background:linear-gradient(135deg, rgba(37,99,235,.32), rgba(29,78,216,.32)); font-weight:700; }
        .main { padding:16px; }
        .card { border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.92); padding:14px; margin-bottom:12px; }
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
        @media (max-width:1100px){ .app-shell{grid-template-columns:1fr;} .sidebar{position:static;height:auto;} .filters{grid-template-columns:1fr 1fr;} .stats{grid-template-columns:repeat(2,minmax(0,1fr));} }
    </style>
</head>
<body>
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
        </aside>
        <main class="main">
            <section class="card">
                <h1>Absence Rate Report</h1>
                <p class="muted" style="margin-top:4px;">Major: <strong>{{ $managedMajor }}</strong></p>
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
</body>
</html>

