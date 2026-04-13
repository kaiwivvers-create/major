<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Principal Dashboard - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; --accent:#38bdf8; --primary:#2563eb; }
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
        .main { padding:20px; }
        .container { display:grid; gap:14px; }
        .topbar { display:flex; align-items:center; justify-content:space-between; gap:12px; border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.9); padding:14px 16px; }
        .topbar h1 { font-size:1.1rem; }
        .card { border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding:14px; }
        .muted { color:var(--muted); font-size:.92rem; }
        .stats-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin-top:10px; }
        .stat { border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.5); padding:12px; }
        .stat strong { display:block; margin-top:6px; font-size:1.6rem; }
        .grid { display:grid; grid-template-columns:1.1fr 1fr; gap:12px; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid var(--border); padding:8px; vertical-align:top; text-align:left; font-size:.88rem; }
        th { background:rgba(15,23,42,.95); }
        .bars { display:grid; gap:10px; margin-top:10px; }
        .bar-row { display:grid; grid-template-columns:110px 1fr 70px; gap:8px; align-items:center; font-size:.9rem; }
        .bar-track { height:14px; border:1px solid var(--border); border-radius:999px; background:rgba(30,41,59,.45); overflow:hidden; }
        .bar-track span { display:block; height:100%; width:var(--pct,0%); background:linear-gradient(90deg, #38bdf8, #2563eb); }
        .export-center { display:grid; gap:10px; margin-top:10px; }
        .export-btn { text-decoration:none; display:block; text-align:center; border:1px solid rgba(56,189,248,.55); border-radius:12px; background:linear-gradient(135deg, rgba(37,99,235,.82), rgba(56,189,248,.74)); color:#fff; padding:16px 12px; font-weight:700; font-size:1rem; }
        .export-btn.secondary { border-color:var(--border); background:rgba(30,41,59,.75); }

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

        @media (max-width: 980px) {
            .app-shell { grid-template-columns:1fr; }
            .sidebar { position:static; height:auto; }
            .grid { grid-template-columns:1fr; }
        }
        @media (max-width: 760px) {
            .stats-grid { grid-template-columns:1fr; }
            .bar-row { grid-template-columns:1fr; }
        }
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
        @include('dashboard.partials.principal-sidebar', ['user' => $user])

        <main class="main">
            <div class="container">
                <header class="topbar">
                    <div>
                        <h1>Principal Dashboard</h1>
                        <p class="muted">Week: {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</p>
                    </div>
                </header>

                <section class="grid" id="executive-summary">
                    <article class="card">
                        <h2>Executive Summary</h2>
                        <p class="muted">School-wide placement snapshot.</p>
                        <div class="stats-grid">
                            <div class="stat">
                                <span class="muted">Total Students in School</span>
                                <strong>{{ (int) ($totalStudentsInSchool ?? 0) }}</strong>
                            </div>
                            <div class="stat">
                                <span class="muted">Total Students Placed</span>
                                <strong>{{ (int) ($totalStudentsPlaced ?? 0) }}</strong>
                            </div>
                            <div class="stat">
                                <span class="muted">Pending Excuses (Today)</span>
                                <strong>{{ (int) data_get($absenceOverview ?? [], 'pending_today', 0) }}</strong>
                            </div>
                            <div class="stat">
                                <span class="muted">Approved Excuses (Today)</span>
                                <strong>{{ (int) data_get($absenceOverview ?? [], 'approved_today', 0) }}</strong>
                            </div>
                            <div class="stat">
                                <span class="muted">Alpha Students (Today)</span>
                                <strong>{{ (int) data_get($absenceOverview ?? [], 'alpha_students_today', 0) }}</strong>
                            </div>
                        </div>
                    </article>

                    <article class="card">
                        <h2>Export Center</h2>
                        <p class="muted">Master report for the current PKL season.</p>
                        <div class="export-center">
                            <a class="export-btn" href="{{ route('dashboard.principal.master-report', ['format' => 'excel']) }}">Download Master Report (Excel/CSV)</a>
                            <a class="export-btn secondary" href="{{ route('dashboard.principal.master-report', ['format' => 'pdf']) }}" target="_blank" rel="noopener noreferrer">Open Master Report (PDF Print View)</a>
                        </div>
                    </article>
                </section>

                @if (($recentAlerts ?? collect())->isNotEmpty())
                    <section class="card" id="attendance-alerts">
                        <h2>Attendance Alerts</h2>
                        <p class="muted">Auto-generated summaries after check-in cutoff.</p>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (($recentAlerts ?? collect()) as $alertRow)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($alertRow->alert_date, 'Asia/Jakarta')->format('d M Y') }}</td>
                                        <td>{{ $alertRow->message }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endif

                <section class="grid" id="industry-partners">
                    <article class="card">
                        <h2>Top 5 Industry Partners</h2>
                        <p class="muted">Ranked by total students currently placed.</p>
                        <table>
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Address</th>
                                    <th>Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($topIndustryPartners ?? collect()) as $partner)
                                    <tr>
                                        <td>{{ $partner->company_name }}</td>
                                        <td>{{ $partner->company_address }}</td>
                                        <td>{{ (int) ($partner->total_students ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3">No placement data yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </article>

                    <article class="card">
                        <h2>Department Comparison</h2>
                        <p class="muted">Attendance rates over the last 30 days: RPL vs BDP vs AKL.</p>
                        <div class="bars">
                            @foreach (($departmentAttendance ?? collect()) as $dept)
                                <div class="bar-row">
                                    <strong>{{ data_get($dept, 'label', '-') }}</strong>
                                    <div class="bar-track" style="--pct: {{ min(100, max(0, (float) data_get($dept, 'rate', 0))) }}%;">
                                        <span></span>
                                    </div>
                                    <span>{{ number_format((float) data_get($dept, 'rate', 0), 1) }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </article>
                </section>

                <section class="card" id="mou-tracker">
                    <h2>MOU Tracker</h2>
                    <p class="muted">Company contract expiry list. If MOU expiry is not stored yet, the table uses inferred PKL end dates.</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Address</th>
                                <th>Contact</th>
                                <th>Phone</th>
                                <th>Expiry</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($mouTracker ?? collect()) as $row)
                                <tr>
                                    <td>{{ data_get($row, 'company_name', '-') }}</td>
                                    <td>{{ data_get($row, 'company_address', '-') }}</td>
                                    <td>{{ data_get($row, 'contact_person', '-') }}</td>
                                    <td>{{ data_get($row, 'contact_phone', '-') }}</td>
                                    <td>
                                        @if (data_get($row, 'expiry_date'))
                                            {{ \Illuminate\Support\Carbon::parse((string) data_get($row, 'expiry_date'), 'Asia/Jakarta')->format('d M Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ data_get($row, 'expiry_source', '-') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No active partner company records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>

                <section class="card" id="pkl-table">
                    <h2>PKL Table</h2>
                    <p class="muted">Student PKL activities and mentor validation.</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>PKL Activity</th>
                                <th>Student Notes</th>
                                <th>Mentor Check</th>
                                <th>Missing Info</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($rows ?? collect()) as $row)
                                <tr>
                                    <td>{{ $row->student_name }}<br><span class="muted">{{ $row->student_nis }}</span></td>
                                    <td>{{ $row->learning_notes }}</td>
                                    <td>{{ $row->student_mentor_notes }}</td>
                                    <td>{{ is_null($row->mentor_is_correct) ? '-' : ($row->mentor_is_correct ? 'Correct' : 'Not Complete') }}</td>
                                    <td>{{ $row->missing_info_notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No weekly journals for this week.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>

                <section class="card" id="school-table">
                    <h2>School Table</h2>
                    <p class="muted">Notes from Kajur and Guru Bindo for school monitoring.</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Kajur Notes</th>
                                <th>Guru Bindo Notes</th>
                                <th>Reviewers</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($rows ?? collect()) as $row)
                                <tr>
                                    <td>{{ $row->student_name }}<br><span class="muted">{{ $row->student_nis }}</span></td>
                                    <td>{{ strtoupper($row->status) }}</td>
                                    <td>{{ $row->kajur_notes ?? '-' }}</td>
                                    <td>{{ $row->bindo_notes ?? '-' }}</td>
                                    <td>
                                        Mentor: {{ $row->mentor_name ?? '-' }}<br>
                                        Kajur: {{ $row->kajur_name ?? '-' }}<br>
                                        Bindo: {{ $row->bindo_name ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No weekly journals for this week.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])
</body>
</html>
