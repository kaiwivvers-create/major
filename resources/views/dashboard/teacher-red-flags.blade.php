<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Red Flag System - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; --accent: {{ $appBranding->accent_color ?? '#38bdf8' }}; --primary: {{ $appBranding->primary_color ?? '#2563eb' }}; --danger:#ef4444; --warn:#f59e0b; --ok:#22c55e; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); background:radial-gradient(1000px 500px at 10% -10%, rgba(56, 189, 248, 0.2), transparent), radial-gradient(900px 450px at 100% 10%, rgba(37, 99, 235, 0.2), transparent), var(--bg); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:270px 1fr; }
        .sidebar { position:sticky; top:0; height:100vh; display:flex; flex-direction:column; border-right:1px solid var(--border); background:rgba(15,23,42,.92); backdrop-filter:blur(10px); padding:16px 14px; }
        .sidebar-brand { font-weight:700; letter-spacing:.02em; padding:8px 10px; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.45); margin-bottom:14px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:8px; }
        .sidebar-nav a { text-decoration:none; color:var(--text); border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:rgba(30,41,59,.6); font-weight:500; transition:all .2s ease; }
        .sidebar-nav a:hover { border-color:var(--accent); color:var(--accent); }
        .sidebar-nav a.active { border-color:var(--primary); background:linear-gradient(135deg, rgba(37,99,235,.32), rgba(29,78,216,.32)); font-weight:700; color:#f8fafc; }
        .sidebar-profile { margin-top:auto; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.55); padding:12px; overflow:hidden; }
        .profile-trigger { width:100%; max-width:100%; min-width:0; text-align:left; appearance:none; border:1px solid var(--border); border-radius:12px; color:var(--text); background:rgba(15,23,42,0.52); cursor:pointer; padding:10px; display:grid; grid-template-columns:42px minmax(0,1fr) 18px; align-items:center; gap:10px; }
        .profile-trigger:hover { border-color:var(--accent); }
        .profile-avatar { width:42px; height:42px; border-radius:999px; border:1px solid rgba(56, 189, 248, 0.55); background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24)); display:grid; place-items:center; font-size:.82rem; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .profile-name { font-weight:700; margin-bottom:2px; }
        .profile-meta { font-size:.85rem; color:var(--muted); }
        .profile-arrow { color:var(--muted); font-size:1rem; text-align:right; }
        .profile-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.62); display:none; align-items:center; justify-content:center; z-index:2200; padding:16px; }
        .profile-modal-backdrop.open { display:flex; }
        .profile-modal-panel { width:min(560px,96vw); border:1px solid var(--border); border-radius:16px; background:linear-gradient(160deg, rgba(30,41,59,.96), rgba(15,23,42,.96)); padding:16px; max-height:92vh; overflow:auto; overflow-x:hidden; }
        .profile-modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .profile-modal-close,.profile-modal-btn { border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:8px 12px; cursor:pointer; font-weight:600; }
        .profile-modal-btn.primary { border-color:#2563eb; background:linear-gradient(135deg,#2563eb,#1d4ed8); color:#f8fafc; }
        .profile-modal-field { margin-bottom:12px; }
        .profile-modal-field label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; }
        .profile-modal-field input { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.95rem; }
        .profile-modal-actions { margin-top:14px; display:flex; justify-content:flex-end; gap:8px; flex-wrap:wrap; }
        .profile-modal-alert.error { border:1px solid rgba(248,113,113,.6); border-radius:12px; padding:10px 12px; background:rgba(127,29,29,.25); margin-bottom:12px; }
        .main { padding:20px; display:grid; gap:14px; }
        .topbar, .card { border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding:14px; }
        .topbar h1 { font-size:1.1rem; margin-bottom:4px; }
        .muted { color:var(--muted); font-size:.9rem; line-height:1.45; }
        .summary-grid { margin-top:10px; display:grid; gap:8px; grid-template-columns:repeat(6, minmax(0,1fr)); }
        .summary-item { border:1px solid rgba(148,163,184,.24); border-radius:10px; background:rgba(15,23,42,.5); padding:8px; }
        .summary-item .k { color:var(--muted); font-size:.75rem; }
        .summary-item .v { margin-top:2px; font-size:1rem; font-weight:700; }
        .v.critical { color:#fecaca; }
        .v.high { color:#fde68a; }
        .v.medium { color:#bfdbfe; }
        .v.low { color:#bbf7d0; }
        .alert { border-radius:10px; padding:10px 12px; font-size:.9rem; }
        .alert.ok { border:1px solid rgba(34,197,94,.5); background:rgba(21,128,61,.2); color:#dcfce7; }
        .alert.error { border:1px solid rgba(248,113,113,.55); background:rgba(127,29,29,.25); color:#fee2e2; }
        .students-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); }
        .student-card { border:1px solid rgba(148,163,184,.24); border-radius:12px; background:rgba(15,23,42,.5); padding:12px; display:grid; gap:8px; }
        .student-head { display:flex; align-items:center; justify-content:space-between; gap:8px; }
        .student-name { font-weight:700; }
        .badge { border:1px solid rgba(148,163,184,.35); border-radius:999px; padding:3px 9px; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
        .badge.critical { border-color:rgba(239,68,68,.55); color:#fecaca; background:rgba(153,27,27,.22); }
        .badge.high { border-color:rgba(245,158,11,.55); color:#fde68a; background:rgba(146,64,14,.22); }
        .badge.medium { border-color:rgba(59,130,246,.55); color:#bfdbfe; background:rgba(30,64,175,.22); }
        .badge.low { border-color:rgba(34,197,94,.55); color:#bbf7d0; background:rgba(21,128,61,.22); }
        .meta { color:var(--muted); font-size:.85rem; line-height:1.45; }
        .indicator-list { display:grid; gap:5px; padding-left:16px; font-size:.85rem; color:#dbeafe; }
        .indicator-list li { line-height:1.4; }
        .intervention-box { border-top:1px dashed rgba(148,163,184,.3); padding-top:8px; display:grid; gap:8px; }
        .latest { border:1px solid rgba(148,163,184,.24); border-radius:10px; background:rgba(15,23,42,.55); padding:8px; display:grid; gap:4px; }
        .form-grid { display:grid; gap:8px; }
        .form-row { display:grid; gap:8px; grid-template-columns:1fr 1fr; }
        .input, .textarea, .select { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.75); color:var(--text); padding:8px 10px; font-size:.88rem; }
        .textarea { min-height:74px; resize:vertical; }
        .btn { border:1px solid rgba(59,130,246,.65); border-radius:10px; background:linear-gradient(135deg, #2563eb, #1d4ed8); color:#eff6ff; padding:8px 12px; font-weight:700; font-size:.86rem; cursor:pointer; }
        .btn:disabled { opacity:.5; cursor:not-allowed; }
        .empty { min-height:200px; border:1px dashed rgba(56,189,248,.28); border-radius:12px; display:grid; place-items:center; text-align:center; }
        .recent-list { display:grid; gap:8px; }
        .recent-item { border:1px solid rgba(148,163,184,.24); border-radius:10px; background:rgba(15,23,42,.48); padding:8px; }
        .main { animation: page-drift-up 0.7s ease-out both; }
        .profile-modal-backdrop.open .profile-modal-panel { animation: page-drift-up 0.7s ease-out 0.15s both; }
        @keyframes page-drift-up {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width:980px) {
            .app-shell { grid-template-columns:1fr; }
            .sidebar { position:static; height:auto; }
            .summary-grid { grid-template-columns:repeat(2, minmax(0,1fr)); }
            .form-row { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
    @endphp

    <div class="app-shell">
        @include('dashboard.partials.teacher-sidebar', ['user' => $user, 'activePage' => 'red-flags'])

        <main class="main">
            <header class="topbar">
                <h1>Red Flag System</h1>
                <p class="muted">Early warning indicators for supervised students. Window: {{ \Illuminate\Support\Carbon::parse($windowStart, 'Asia/Jakarta')->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($today, 'Asia/Jakarta')->format('d M Y') }}.</p>
                <p class="muted">Class Scope: {{ $teacherClassScope !== '' ? $teacherClassScope : 'ALL' }}</p>
                <div class="summary-grid">
                    <div class="summary-item"><div class="k">Total</div><div class="v">{{ $riskSummary['total_students'] ?? 0 }}</div></div>
                    <div class="summary-item"><div class="k">Need Intervention</div><div class="v">{{ $riskSummary['needs_intervention'] ?? 0 }}</div></div>
                    <div class="summary-item"><div class="k">Critical</div><div class="v critical">{{ $riskSummary['critical'] ?? 0 }}</div></div>
                    <div class="summary-item"><div class="k">High</div><div class="v high">{{ $riskSummary['high'] ?? 0 }}</div></div>
                    <div class="summary-item"><div class="k">Medium</div><div class="v medium">{{ $riskSummary['medium'] ?? 0 }}</div></div>
                    <div class="summary-item"><div class="k">Low</div><div class="v low">{{ $riskSummary['low'] ?? 0 }}</div></div>
                </div>
            </header>

            @if (session('status'))
                <div class="alert ok">{{ session('status') }}</div>
            @endif
            @if ($errors->has('red_flag_intervention'))
                <div class="alert error">{{ $errors->first('red_flag_intervention') }}</div>
            @endif

            <section class="card">
                @if ($students->isEmpty())
                    <div class="empty">
                        <div>
                            <strong>No supervised students found.</strong>
                            <p class="muted" style="margin-top:6px;">Check teacher assignment mapping and student profile data.</p>
                        </div>
                    </div>
                @else
                    <div class="students-grid">
                        @foreach ($students as $student)
                            <article class="student-card">
                                <div class="student-head">
                                    <div>
                                        <div class="student-name">{{ $student->name }}</div>
                                        <div class="meta">NIS: {{ $student->nis ?? '-' }} &middot; Class: {{ $student->class_name }}</div>
                                    </div>
                                    <span class="badge {{ $student->risk_level }}">{{ strtoupper($student->risk_level) }} · {{ (int) $student->risk_score }}</span>
                                </div>
                                <div class="meta">Company: {{ $student->pkl_place_name }}</div>
                                <div class="meta">Last check-in: {{ $student->last_checkin_label }} WIB</div>
                                <div class="meta">Last photo evidence: {{ $student->last_visit_label }} WIB</div>
                                @if (!empty($student->indicators))
                                    <ul class="indicator-list">
                                        @foreach ($student->indicators as $indicator)
                                            <li>{{ $indicator }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="meta">No red-flag indicators detected in this window.</p>
                                @endif

                                <div class="intervention-box">
                                    @if (!empty($student->latest_intervention))
                                        <div class="latest">
                                            <div class="meta"><strong>Latest action:</strong> {{ $student->latest_intervention['actioned_at_label'] ?? '-' }} WIB</div>
                                            <div class="meta"><strong>Status:</strong> {{ strtoupper($student->latest_intervention['intervention_status'] ?? 'open') }}</div>
                                            <div class="meta"><strong>Follow-up:</strong> {{ !empty($student->latest_intervention['follow_up_date']) ? \Illuminate\Support\Carbon::parse($student->latest_intervention['follow_up_date'], 'Asia/Jakarta')->format('d M Y') : '-' }}</div>
                                            <div class="meta"><strong>Notes:</strong> {{ $student->latest_intervention['intervention_notes'] ?? '-' }}</div>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('dashboard.bindo.red-flags.intervention.store') }}" class="form-grid">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ (int) $student->id }}">
                                        <input type="hidden" name="risk_score" value="{{ (int) $student->risk_score }}">
                                        <input type="hidden" name="risk_level" value="{{ $student->risk_level }}">
                                        <input type="hidden" name="indicator_snapshot" value='@json($student->indicator_snapshot ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                                        <div class="form-row">
                                            <div>
                                                <label class="meta" for="intervention_status_{{ (int) $student->id }}">Intervention Status</label>
                                                <select id="intervention_status_{{ (int) $student->id }}" class="select" name="intervention_status" {{ !$interventionStorageReady ? 'disabled' : '' }}>
                                                    <option value="open">Open</option>
                                                    <option value="monitoring">Monitoring</option>
                                                    <option value="resolved">Resolved</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="meta" for="follow_up_date_{{ (int) $student->id }}">Follow-up Date</label>
                                                <input id="follow_up_date_{{ (int) $student->id }}" class="input" type="date" name="follow_up_date" {{ !$interventionStorageReady ? 'disabled' : '' }}>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="meta" for="intervention_notes_{{ (int) $student->id }}">Action Notes</label>
                                            <textarea id="intervention_notes_{{ (int) $student->id }}" class="textarea" name="intervention_notes" placeholder="Example: Called parent and mentor, scheduled follow-up meeting." {{ !$interventionStorageReady ? 'disabled' : '' }} required></textarea>
                                        </div>
                                        <div>
                                            <button type="submit" class="btn" {{ !$interventionStorageReady ? 'disabled' : '' }}>Save Intervention</button>
                                        </div>
                                    </form>
                                    @if (!$interventionStorageReady)
                                        <p class="meta">Intervention storage table is not ready. Run migrations first.</p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="card">
                <h2 style="font-size:1rem; margin-bottom:8px;">Recent Intervention Actions</h2>
                @if ($recentInterventions->isEmpty())
                    <p class="muted">No intervention action logged yet.</p>
                @else
                    <div class="recent-list">
                        @foreach ($recentInterventions as $row)
                            <article class="recent-item">
                                <div style="display:flex; justify-content:space-between; gap:8px; align-items:center;">
                                    <strong>{{ $row->student_name }} ({{ $row->student_nis ?? '-' }})</strong>
                                    <span class="badge {{ strtolower((string) ($row->risk_level ?? 'low')) }}">{{ strtoupper((string) ($row->risk_level ?? 'low')) }} · {{ (int) ($row->risk_score ?? 0) }}</span>
                                </div>
                                <p class="meta" style="margin-top:4px;">
                                    Status: {{ strtoupper((string) ($row->intervention_status ?? 'open')) }}
                                    &middot; Actioned: {{ !empty($row->actioned_at) ? \Illuminate\Support\Carbon::parse($row->actioned_at, 'Asia/Jakarta')->format('d M Y H:i') : '-' }} WIB
                                    &middot; Follow-up: {{ !empty($row->follow_up_date) ? \Illuminate\Support\Carbon::parse($row->follow_up_date, 'Asia/Jakarta')->format('d M Y') : '-' }}
                                </p>
                                <p class="meta" style="margin-top:4px;">{{ $row->intervention_notes }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])

    @include('partials.chatbot')
</body>
</html>

