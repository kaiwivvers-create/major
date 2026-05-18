<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Site Visit Log - {{ config('app.name', 'Kips') }}</title>
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
        .main { padding:20px; }
        .topbar { border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.9); padding:14px 16px; margin-bottom:14px; }
        .topbar h1 { font-size:1.1rem; margin-bottom:4px; }
        .muted { color:var(--muted); font-size:.92rem; }
        .card { border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding:14px; }
        .students-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); }
        .student-card { border:1px solid rgba(148,163,184,.24); border-radius:12px; background:rgba(15,23,42,.55); padding:12px; display:grid; gap:6px; }
        .student-name { font-weight:700; }
        .card-actions { display:flex; justify-content:flex-end; margin-top:4px; }
        .btn { border:1px solid rgba(56,189,248,.45); border-radius:10px; background:rgba(14,165,233,.14); color:#bae6fd; padding:7px 10px; font-weight:700; font-size:.84rem; cursor:pointer; }
        .btn:hover { border-color:rgba(56,189,248,.8); background:rgba(14,165,233,.24); }
        .btn[disabled] { opacity:.45; cursor:not-allowed; }
        .empty { min-height:220px; border:1px dashed rgba(56,189,248,.28); border-radius:12px; display:grid; place-items:center; text-align:center; }
        .visit-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.72); display:none; align-items:center; justify-content:center; z-index:2600; padding:16px; }
        .visit-modal-backdrop.open { display:flex; }
        .visit-modal { width:min(980px,96vw); border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.98), rgba(15,23,42,.98)); overflow:hidden; }
        .visit-modal-head { display:flex; justify-content:space-between; align-items:center; gap:8px; border-bottom:1px solid rgba(148,163,184,.22); padding:12px 14px; }
        .visit-close { border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:6px 10px; cursor:pointer; }
        .visit-modal-body { display:grid; grid-template-columns:280px 1fr; min-height:420px; }
        .visit-list { border-right:1px solid rgba(148,163,184,.22); padding:10px; overflow:auto; max-height:72vh; }
        .visit-list-item { width:100%; text-align:left; border:1px solid rgba(148,163,184,.24); border-radius:10px; background:rgba(15,23,42,.45); color:var(--text); padding:8px 10px; cursor:pointer; margin-bottom:8px; }
        .visit-list-item.active { border-color:rgba(56,189,248,.8); background:rgba(14,165,233,.18); }
        .visit-detail { padding:12px; overflow:auto; max-height:72vh; }
        .visit-photo-wrap { border:1px solid rgba(148,163,184,.24); border-radius:12px; background:rgba(15,23,42,.45); overflow:hidden; margin-bottom:10px; min-height:220px; display:grid; place-items:center; }
        .visit-photo { width:100%; max-height:420px; object-fit:contain; display:block; }
        .visit-photo-empty { color:var(--muted); font-size:.9rem; }
        .visit-meta { display:grid; gap:6px; }
        .visit-meta div { color:#dbeafe; font-size:.9rem; line-height:1.45; }
        .visit-meta strong { color:#f8fafc; }
        .main { animation: page-drift-up 0.7s ease-out both; }
        .profile-modal-backdrop.open .profile-modal-panel,
        .visit-modal-backdrop.open .visit-modal-panel { animation: page-drift-up 0.7s ease-out 0.15s both; }
        @keyframes page-drift-up {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width:980px) {
            .app-shell { grid-template-columns:1fr; }
            .sidebar { position:static; height:auto; }
            .visit-modal-body { grid-template-columns:1fr; }
            .visit-list { border-right:0; border-bottom:1px solid rgba(148,163,184,.22); max-height:250px; }
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
    @endphp

    <div class="app-shell">
        @include('dashboard.partials.teacher-sidebar', ['user' => $user, 'activePage' => 'site-visits'])

        <main class="main">
            <header class="topbar">
                <h1>Site Visit Log</h1>
                <p class="muted">Student-submitted photo evidence from check-in. Open each student to see dated entries, then click an entry to view image, date/time, and location.</p>
            </header>

            <section class="card">
                @if (!$visitLogReady)
                    <div class="empty">
                        <div>
                            <strong>Attendance table is not ready.</strong>
                            <p class="muted" style="margin-top:6px;">Run migrations to enable student check-in photo evidence.</p>
                        </div>
                    </div>
                @elseif ($studentLogs->isEmpty())
                    <div class="empty">
                        <div>
                            <strong>No supervised students found.</strong>
                            <p class="muted" style="margin-top:6px;">Students assigned to this teacher will appear here.</p>
                        </div>
                    </div>
                @else
                    <div class="students-grid">
                        @foreach ($studentLogs as $student)
                            <article class="student-card">
                                <div class="student-name">{{ $student->name }}</div>
                                <div class="muted">NIS: {{ $student->nis ?? '-' }} &middot; Class: {{ $student->class_name }}</div>
                                <div class="muted">Company: {{ $student->pkl_place_name }}</div>
                                <div class="muted">Total logs: {{ (int) ($student->visit_count ?? 0) }}</div>
                                <div class="muted">Latest: {{ $student->latest_visit_at }}</div>
                                <div class="card-actions">
                                    <button
                                        type="button"
                                        class="btn"
                                        data-visit-open
                                        data-student-name="{{ $student->name }}"
                                        data-student-nis="{{ $student->nis ?? '-' }}"
                                        data-student-class="{{ $student->class_name }}"
                                        data-student-logs='@json($student->logs ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                                        {{ empty($student->visit_count) ? 'disabled' : '' }}
                                    >
                                        Open Logs
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </div>

    <div class="visit-modal-backdrop" id="visit-modal-backdrop" aria-hidden="true">
        <div class="visit-modal" role="dialog" aria-modal="true" aria-labelledby="visit-modal-title">
            <div class="visit-modal-head">
                <div>
                    <div id="visit-modal-title" style="font-weight:700;">Student Visit Logs</div>
                    <p class="muted" id="visit-modal-subtitle" style="margin-top:2px;"></p>
                </div>
                <button type="button" class="visit-close" id="visit-modal-close">Close</button>
            </div>
            <div class="visit-modal-body">
                <div class="visit-list" id="visit-modal-list"></div>
                <div class="visit-detail">
                    <div class="visit-photo-wrap" id="visit-photo-wrap">
                        <div class="visit-photo-empty">Select a log entry.</div>
                    </div>
                    <div class="visit-meta" id="visit-meta"></div>
                </div>
            </div>
        </div>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])
    <script>
        (() => {
            const backdrop = document.getElementById('visit-modal-backdrop');
            const closeBtn = document.getElementById('visit-modal-close');
            const subtitle = document.getElementById('visit-modal-subtitle');
            const list = document.getElementById('visit-modal-list');
            const photoWrap = document.getElementById('visit-photo-wrap');
            const meta = document.getElementById('visit-meta');
            if (!backdrop || !closeBtn || !subtitle || !list || !photoWrap || !meta) return;

            const closeModal = () => {
                backdrop.classList.remove('open');
                backdrop.setAttribute('aria-hidden', 'true');
            };

            const renderVisitDetail = (entry) => {
                photoWrap.innerHTML = '';
                meta.innerHTML = '';

                if (entry.photo_url) {
                    const img = document.createElement('img');
                    img.className = 'visit-photo';
                    img.src = entry.photo_url;
                    img.alt = 'Visit proof photo';
                    photoWrap.appendChild(img);
                } else {
                    const empty = document.createElement('div');
                    empty.className = 'visit-photo-empty';
                    empty.textContent = 'No image for this entry.';
                    photoWrap.appendChild(empty);
                }

                const lines = [
                    `<div><strong>Date/Time:</strong> ${entry.visited_at_label || '-' } WIB</div>`,
                    `<div><strong>Location:</strong> ${(entry.company_name || '-')}${entry.company_address ? ' - ' + entry.company_address : ''}</div>`,
                    `<div><strong>Source:</strong> Student check-in photo evidence</div>`,
                ];
                meta.innerHTML = lines.join('');
            };

            const renderVisitList = (logs) => {
                list.innerHTML = '';
                if (!Array.isArray(logs) || logs.length === 0) {
                    list.innerHTML = '<p class="muted">No visit logs for this student.</p>';
                    photoWrap.innerHTML = '<div class="visit-photo-empty">No image to display.</div>';
                    meta.innerHTML = '';
                    return;
                }

                logs.forEach((entry, index) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = `visit-list-item ${index === 0 ? 'active' : ''}`;
                    item.textContent = entry.visited_at_label || `Log #${index + 1}`;
                    item.addEventListener('click', () => {
                        list.querySelectorAll('.visit-list-item').forEach((node) => node.classList.remove('active'));
                        item.classList.add('active');
                        renderVisitDetail(entry);
                    });
                    list.appendChild(item);
                });

                renderVisitDetail(logs[0]);
            };

            document.querySelectorAll('[data-visit-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    const studentName = button.getAttribute('data-student-name') || '-';
                    const studentNis = button.getAttribute('data-student-nis') || '-';
                    const studentClass = button.getAttribute('data-student-class') || '-';
                    const rawLogs = button.getAttribute('data-student-logs') || '[]';

                    let logs = [];
                    try {
                        logs = JSON.parse(rawLogs);
                    } catch (error) {
                        logs = [];
                    }

                    subtitle.textContent = `${studentName} · NIS ${studentNis} · Class ${studentClass}`;
                    renderVisitList(logs);
                    backdrop.classList.add('open');
                    backdrop.setAttribute('aria-hidden', 'false');
                });
            });

            closeBtn.addEventListener('click', closeModal);
            backdrop.addEventListener('click', (event) => {
                if (event.target === backdrop) closeModal();
            });
            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && backdrop.classList.contains('open')) {
                    closeModal();
                }
            });
        })();
    </script>

    @include('partials.chatbot')
</body>
</html>

