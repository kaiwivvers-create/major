<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weekly Journal Oversight - {{ config('app.name', 'Kips') }}</title>
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

        .toolbar { display:grid; grid-template-columns:minmax(220px,260px) minmax(140px,180px) minmax(140px,180px) 1fr minmax(120px,150px) auto auto; gap:10px; align-items:end; margin-top:10px; }
        .field label { display:block; margin-bottom:6px; font-size:.84rem; color:var(--muted); font-weight:600; }
        .field input,.field select { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.9rem; }
        .btn { text-decoration:none; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--primary); border-radius:10px; background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#fff; padding:10px 12px; font-weight:700; cursor:pointer; }
        .btn.secondary { border-color:var(--border); background:rgba(30,41,59,.75); }

        .stats-grid { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:10px; margin-top:10px; }
        .stat { border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.5); padding:12px; }
        .stat strong { display:block; margin-top:6px; font-size:1.2rem; }

        .grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid var(--border); padding:8px; vertical-align:top; text-align:left; font-size:.88rem; }
        th { background:rgba(15,23,42,.95); }
        .tag { display:inline-block; border:1px solid var(--border); border-radius:999px; padding:2px 8px; font-size:.76rem; font-weight:700; }
        .tag.warn { border-color:rgba(245,158,11,.6); color:#fde68a; }
        .tag.danger { border-color:rgba(239,68,68,.7); color:#fecaca; }
        .tag.good { border-color:rgba(34,197,94,.6); color:#bbf7d0; }

        .pagination-wrap { margin-top:12px; }
        @media (max-width:1280px) {
            .app-shell { grid-template-columns:1fr; }
            .sidebar { position:static; height:auto; }
            .toolbar { grid-template-columns:1fr 1fr; }
            .stats-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .grid { grid-template-columns:1fr; }
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
        @include('dashboard.partials.principal-sidebar', ['user' => $user, 'activePage' => 'journal-oversight'])

        <main class="main">
            <div class="container">
                <header class="topbar">
                    <h1>Weekly Journal Oversight</h1>
                    <p class="muted">Week: {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</p>
                </header>

                <section class="card">
                    <form method="GET" action="{{ route('dashboard.principal.journal-oversight') }}" class="toolbar">
                        <div class="field">
                            <label for="week_start">Week Start</label>
                            <input id="week_start" name="week_start" type="date" value="{{ $weekStart }}">
                        </div>
                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                <option value="draft" {{ ($statusFilter ?? 'all') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="submitted" {{ ($statusFilter ?? 'all') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                <option value="needs_revision" {{ ($statusFilter ?? 'all') === 'needs_revision' ? 'selected' : '' }}>Needs Revision</option>
                                <option value="approved" {{ ($statusFilter ?? 'all') === 'approved' ? 'selected' : '' }}>Approved</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="followup">Follow-up</label>
                            <select id="followup" name="followup">
                                <option value="all" {{ ($followupFilter ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                <option value="yes" {{ ($followupFilter ?? 'all') === 'yes' ? 'selected' : '' }}>Needs Follow-up</option>
                                <option value="no" {{ ($followupFilter ?? 'all') === 'no' ? 'selected' : '' }}>On Track</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="q">Search</label>
                            <input id="q" name="q" value="{{ $search ?? '' }}" placeholder="Student, NIS, mentor, notes">
                        </div>
                        <div class="field">
                            <label for="per_page">Rows</label>
                            <select id="per_page" name="per_page">
                                @foreach (($perPageOptions ?? [10,20,50,100]) as $pp)
                                    <option value="{{ $pp }}" {{ (int) ($perPage ?? 20) === (int) $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn" type="submit">Apply</button>
                        <a class="btn secondary" href="{{ route('dashboard.principal.journal-oversight') }}">Reset</a>
                    </form>
                </section>

                <section class="card">
                    <h2>Summary</h2>
                    <div class="stats-grid">
                        <div class="stat"><span class="muted">Total Journals</span><strong>{{ (int) data_get($summary, 'total', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Draft</span><strong>{{ (int) data_get($summary, 'draft', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Submitted</span><strong>{{ (int) data_get($summary, 'submitted', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Needs Revision</span><strong>{{ (int) data_get($summary, 'needs_revision', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Approved</span><strong>{{ (int) data_get($summary, 'approved', 0) }}</strong></div>
                        <div class="stat"><span class="muted">Needs Follow-up</span><strong>{{ (int) data_get($followupSummary, 'needs_followup', 0) }}</strong></div>
                    </div>
                </section>

                <section class="grid">
                    <article class="card">
                        <h2>Follow-up Queue</h2>
                        <p class="muted">Highest-priority journals requiring principal attention.</p>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Flag</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($riskRows ?? collect()) as $row)
                                    <tr>
                                        <td>{{ $row->student_name ?? '-' }}<br><span class="muted">{{ $row->student_nis ?? '-' }}</span></td>
                                        <td>{{ strtoupper((string) ($row->status ?? '-')) }}</td>
                                        <td>
                                            @php
                                                $flag = (string) ($row->oversight_flag ?? '-');
                                            @endphp
                                            @if ($flag === 'Revision' || $flag === 'Not Submitted')
                                                <span class="tag danger">{{ $flag }}</span>
                                            @elseif ($flag === 'Mentor Issue' || $flag === 'Missing Info')
                                                <span class="tag warn">{{ $flag }}</span>
                                            @else
                                                <span class="tag good">{{ $flag }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3">No follow-up queue in this week.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </article>

                    <article class="card">
                        <h2>Reviewer Coverage</h2>
                        <p class="muted">Mentor, Kajur, and Bindo attribution check for this week.</p>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Mentor</th>
                                    <th>Kajur</th>
                                    <th>Bindo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($rowsPage ?? collect()) as $row)
                                    <tr>
                                        <td>{{ $row->student_name ?? '-' }}</td>
                                        <td>{{ $row->mentor_name ?? '-' }}</td>
                                        <td>{{ $row->kajur_name ?? '-' }}</td>
                                        <td>{{ $row->bindo_name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4">No rows in current filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </article>
                </section>

                <section class="card">
                    <h2>Journal Oversight Table</h2>
                    <p class="muted">Filtered rows: {{ (int) (($rowsPage ?? collect())->total() ?? 0) }}.</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Follow-up</th>
                                <th>Mentor Check</th>
                                <th>Learning Notes</th>
                                <th>Missing Info</th>
                                <th>Kajur Notes</th>
                                <th>Bindo Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($rowsPage ?? collect()) as $row)
                                <tr>
                                    <td>{{ $row->student_name ?? '-' }}<br><span class="muted">{{ $row->student_nis ?? '-' }}</span></td>
                                    <td>{{ strtoupper((string) ($row->status ?? '-')) }}</td>
                                    <td>
                                        @if ((bool) ($row->needs_followup ?? false))
                                            <span class="tag danger">{{ $row->oversight_flag ?? 'Follow-up' }}</span>
                                        @else
                                            <span class="tag good">On Track</span>
                                        @endif
                                    </td>
                                    <td>{{ is_null($row->mentor_is_correct) ? '-' : ((int) $row->mentor_is_correct === 1 ? 'Correct' : 'Not Correct') }}</td>
                                    <td>{{ $row->learning_notes ?? '-' }}</td>
                                    <td>{{ $row->missing_info_notes ?? '-' }}</td>
                                    <td>{{ $row->kajur_notes ?? '-' }}</td>
                                    <td>{{ $row->bindo_notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8">No weekly journals for this filter.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if (($rowsPage ?? null) && method_exists($rowsPage, 'links'))
                        <div class="pagination-wrap">{{ $rowsPage->links() }}</div>
                    @endif
                </section>
            </div>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])

    @include('partials.chatbot')
</body>
</html>

