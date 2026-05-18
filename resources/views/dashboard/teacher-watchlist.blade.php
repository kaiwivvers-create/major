<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Supervised Students - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
    <style>
        :root { --bg:#0f172a; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; --accent: {{ $appBranding->accent_color ?? '#38bdf8' }}; --primary: {{ $appBranding->primary_color ?? '#2563eb' }}; --ok:#22c55e; --danger:#ef4444; }
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
        .card { border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding:16px; }
        .status-row { margin-top:8px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .scope-badge { border:1px solid rgba(56,189,248,.35); border-radius:999px; padding:4px 10px; font-size:.8rem; color:#bae6fd; }
        .students-grid { margin-top:12px; display:grid; gap:10px; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); }
        .map-card { margin-top: 12px; }
        #students-map {
            height: 360px;
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .student-card { border:1px solid var(--border); border-radius:12px; background:rgba(15,23,42,.55); padding:12px; display:grid; gap:8px; }
        .student-top { display:flex; align-items:center; justify-content:space-between; gap:8px; }
        .student-name { font-weight:700; }
        .status-dot { width:10px; height:10px; border-radius:999px; display:inline-block; }
        .status-dot.active { background:var(--ok); box-shadow:0 0 0 4px rgba(34,197,94,.18); }
        .status-dot.inactive { background:var(--danger); box-shadow:0 0 0 4px rgba(239,68,68,.16); }
        .student-meta { font-size:.87rem; color:var(--muted); line-height:1.45; }
        .student-actions { margin-top:4px; display:flex; justify-content:flex-end; }
        .view-btn { border:1px solid rgba(56,189,248,.45); border-radius:10px; background:rgba(14,165,233,.14); color:#bae6fd; padding:6px 10px; font-weight:600; font-size:.84rem; cursor:pointer; }
        .view-btn:hover { border-color:rgba(56,189,248,.8); background:rgba(14,165,233,.24); }
        .empty { min-height:200px; border:1px dashed rgba(56,189,248,.28); border-radius:12px; display:grid; place-items:center; text-align:center; }
        .attendance-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.68); display:none; align-items:center; justify-content:center; z-index:2600; padding:16px; }
        .attendance-modal-backdrop.open { display:flex; }
        .attendance-modal { width:min(720px,96vw); border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.97), rgba(15,23,42,.97)); overflow:hidden; }
        .attendance-head { display:flex; align-items:center; justify-content:space-between; gap:8px; border-bottom:1px solid rgba(148,163,184,.22); padding:12px 14px; }
        .attendance-title { font-weight:700; }
        .attendance-close { border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:6px 10px; cursor:pointer; }
        .attendance-body { padding:12px 14px; max-height:70vh; overflow:auto; }
        .attendance-summary { margin-top:10px; display:grid; gap:8px; grid-template-columns:repeat(3,minmax(0,1fr)); }
        .summary-item { border:1px solid rgba(148,163,184,.22); border-radius:10px; background:rgba(15,23,42,.5); padding:8px; }
        .summary-item .k { font-size:.78rem; color:var(--muted); }
        .summary-item .v { font-size:1rem; font-weight:700; margin-top:2px; }
        .progress-wrap { margin-top:8px; }
        .progress-label { display:flex; justify-content:space-between; gap:8px; font-size:.82rem; color:var(--muted); margin-bottom:5px; }
        .progress-track { height:10px; width:100%; border-radius:999px; background:rgba(148,163,184,.2); overflow:hidden; }
        .progress-fill { height:100%; background:linear-gradient(135deg, #22c55e, #16a34a); border-radius:999px; transition:width .2s ease; width:0%; }
        .attendance-list { display:grid; gap:8px; margin-top:10px; }
        .attendance-item { border:1px solid rgba(148,163,184,.26); border-radius:10px; background:rgba(15,23,42,.5); padding:10px; display:grid; gap:4px; }
        .attendance-item-title { font-weight:600; font-size:.92rem; }
        .attendance-item-meta { color:var(--muted); font-size:.84rem; line-height:1.45; }
        .main { animation: page-drift-up 0.7s ease-out both; }
        .profile-modal-backdrop.open .profile-modal-panel,
        .attendance-modal-backdrop.open .attendance-modal-panel { animation: page-drift-up 0.7s ease-out 0.15s both; }
        @keyframes page-drift-up {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width:980px) {
            .app-shell { grid-template-columns:1fr; }
            .sidebar { position:static; height:auto; }
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
    @endphp

    <div class="app-shell">
        @include('dashboard.partials.teacher-sidebar', ['user' => $user, 'activePage' => 'watchlist'])

        <main class="main">
            <header class="topbar">
                <h1>My Supervised Students</h1>
                <p class="muted">Only students assigned to this teacher are shown. Status is based on check-in for {{ \Illuminate\Support\Carbon::parse($today, 'Asia/Jakarta')->format('d M Y') }}.</p>
                <div class="status-row">
                    <span class="scope-badge">Class Scope: {{ $teacherClassScope !== '' ? $teacherClassScope : 'ALL' }}</span>
                    <span class="muted"><span class="status-dot active"></span> Active today</span>
                    <span class="muted"><span class="status-dot inactive"></span> Inactive today</span>
                </div>
            </header>

            <section class="card">
                <h2 style="font-size:1rem;">Student Location Map</h2>
                <p class="muted" style="margin-top:6px;">Latest check-in coordinates from your supervised students.</p>
                <div class="map-card">
                    <div id="students-map"></div>
                    @if (($mapPoints ?? collect())->isEmpty())
                        <p class="muted" style="margin-top:8px;">No coordinates available yet for your supervised students.</p>
                    @endif
                </div>
            </section>

            <section class="card">
                @if ($students->isEmpty())
                    <div class="empty">
                        <div>
                            <strong>No assigned students found.</strong>
                            <p class="muted" style="margin-top:6px;">Check `School Supervisor Teacher Name` and teacher class scope mapping.</p>
                        </div>
                    </div>
                @else
                    <div class="students-grid">
                        @foreach ($students as $student)
                            <article class="student-card">
                                <div class="student-top">
                                    <div class="student-name">{{ $student->name }}</div>
                                    <span class="status-dot {{ $student->is_active_today ? 'active' : 'inactive' }}" aria-label="{{ $student->is_active_today ? 'Active today' : 'Inactive today' }}"></span>
                                </div>
                                <div class="student-meta">NIS: {{ $student->nis ?? '-' }} &middot; Class: {{ $student->class_name }}</div>
                                <div class="student-meta">Company: {{ $student->pkl_place_name }}</div>
                                <div class="student-meta">Last known location: {{ $student->last_location }}</div>
                                <div class="student-actions">
                                    <button
                                        type="button"
                                        class="view-btn"
                                        data-attendance-view
                                        data-student-name="{{ $student->name }}"
                                        data-student-nis="{{ $student->nis ?? '-' }}"
                                        data-student-class="{{ $student->class_name }}"
                                        data-student-summary='@json($student->attendance_summary ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                                        data-student-attendance='@json($student->attendance_history ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                                    >
                                        View
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </div>

    <div class="attendance-modal-backdrop" id="attendance-modal-backdrop" aria-hidden="true">
        <div class="attendance-modal" role="dialog" aria-modal="true" aria-labelledby="attendance-modal-title">
            <div class="attendance-head">
                <div>
                    <div class="attendance-title" id="attendance-modal-title">Attendance Details</div>
                    <p class="muted" id="attendance-modal-subtitle" style="margin-top:2px;"></p>
                </div>
                <button type="button" class="attendance-close" id="attendance-modal-close">Close</button>
            </div>
            <div class="attendance-body">
                <p class="muted">Recent attendance records (max 14 entries).</p>
                <div class="progress-wrap">
                    <div class="progress-label">
                        <span>Attendance Progress</span>
                        <span id="attendance-progress-label">0%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" id="attendance-progress-fill"></div>
                    </div>
                </div>
                <div class="attendance-summary" id="attendance-summary"></div>
                <div class="attendance-list" id="attendance-modal-list"></div>
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
            const mapEl = document.getElementById('students-map');
            if (!mapEl || typeof window.L === 'undefined') return;

            const points = @json($mapPoints ?? []);
            const validPoints = points.filter((point) =>
                point &&
                point.latitude !== null &&
                point.longitude !== null &&
                !Number.isNaN(Number(point.latitude)) &&
                !Number.isNaN(Number(point.longitude))
            );

            const map = L.map(mapEl, {
                zoomControl: true,
                attributionControl: true,
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            if (validPoints.length === 0) {
                map.setView([-6.2, 106.816666], 5);
                return;
            }

            const bounds = L.latLngBounds([]);
            validPoints.forEach((point) => {
                const lat = Number(point.latitude);
                const lng = Number(point.longitude);
                const marker = L.marker([lat, lng]).addTo(map);
                marker.bindPopup(
                    `<strong>${point.student_name ?? 'Student'}</strong><br>` +
                    `NIS: ${point.student_nis ?? '-'}<br>` +
                    `Class: ${point.class_name ?? '-'}<br>` +
                    `Company: ${point.company_name ?? '-'}<br>` +
                    `Date: ${point.attendance_date ?? '-'}<br>` +
                    `Address: ${point.location_address ?? '-'}`
                );
                bounds.extend([lat, lng]);
            });

            map.fitBounds(bounds, { padding: [16, 16] });
        })();

        (() => {
            const backdrop = document.getElementById('attendance-modal-backdrop');
            const closeBtn = document.getElementById('attendance-modal-close');
            const subtitle = document.getElementById('attendance-modal-subtitle');
            const list = document.getElementById('attendance-modal-list');
            const summary = document.getElementById('attendance-summary');
            const progressFill = document.getElementById('attendance-progress-fill');
            const progressLabel = document.getElementById('attendance-progress-label');
            if (!backdrop || !closeBtn || !subtitle || !list || !summary || !progressFill || !progressLabel) return;

            const closeModal = () => {
                backdrop.classList.remove('open');
                backdrop.setAttribute('aria-hidden', 'true');
            };

            const openModal = (studentName, studentNis, studentClass, summaryData, records) => {
                subtitle.textContent = `${studentName} · NIS ${studentNis} · Class ${studentClass}`;
                list.innerHTML = '';
                summary.innerHTML = '';

                const safePercent = Math.max(0, Math.min(100, Number(summaryData?.progress_percent || 0)));
                progressFill.style.width = `${safePercent}%`;
                progressLabel.textContent = `${safePercent}%`;

                const summaryItems = [
                    { label: 'Working Days', value: Number(summaryData?.working_days || 0) },
                    { label: 'Present', value: Number(summaryData?.present_days || 0) },
                    { label: 'Sick', value: Number(summaryData?.sick_days || 0) },
                    { label: 'Excused (Permit)', value: Number(summaryData?.permit_days || 0) },
                    { label: 'Excused Total', value: Number(summaryData?.excused_days || 0) },
                    { label: 'Missed (Alpha)', value: Number(summaryData?.missed_days || 0) },
                ];
                summaryItems.forEach((entry) => {
                    const item = document.createElement('div');
                    item.className = 'summary-item';
                    item.innerHTML = `<div class="k">${entry.label}</div><div class="v">${entry.value}</div>`;
                    summary.appendChild(item);
                });

                if (!Array.isArray(records) || records.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'attendance-item';
                    empty.innerHTML = '<div class="attendance-item-title">No attendance history yet.</div>';
                    list.appendChild(empty);
                } else {
                    records.forEach((record) => {
                        const item = document.createElement('article');
                        item.className = 'attendance-item';

                        const attendanceDate = record.attendance_date || '-';
                        const checkIn = record.check_in_at || '-';
                        const checkOut = record.check_out_at || '-';
                        const status = record.status || 'PENDING';
                        const lateMinutes = Number(record.late_minutes || 0);
                        const location = record.location || '-';

                        item.innerHTML = `
                            <div class="attendance-item-title">${attendanceDate} · ${status}</div>
                            <div class="attendance-item-meta">Check-in: ${checkIn} WIB</div>
                            <div class="attendance-item-meta">Check-out: ${checkOut} WIB</div>
                            <div class="attendance-item-meta">Late minutes: ${lateMinutes}</div>
                            <div class="attendance-item-meta">Location: ${location}</div>
                        `;
                        list.appendChild(item);
                    });
                }

                backdrop.classList.add('open');
                backdrop.setAttribute('aria-hidden', 'false');
            };

            document.querySelectorAll('[data-attendance-view]').forEach((button) => {
                button.addEventListener('click', () => {
                    const studentName = button.getAttribute('data-student-name') || '-';
                    const studentNis = button.getAttribute('data-student-nis') || '-';
                    const studentClass = button.getAttribute('data-student-class') || '-';
                    const rawSummary = button.getAttribute('data-student-summary') || '{}';
                    const rawAttendance = button.getAttribute('data-student-attendance') || '[]';

                    let summaryData = {};
                    let records = [];
                    try {
                        summaryData = JSON.parse(rawSummary);
                    } catch (error) {
                        summaryData = {};
                    }
                    try {
                        records = JSON.parse(rawAttendance);
                    } catch (error) {
                        records = [];
                    }

                    openModal(studentName, studentNis, studentClass, summaryData, records);
                });
            });

            closeBtn.addEventListener('click', closeModal);
            backdrop.addEventListener('click', (event) => {
                if (event.target === backdrop) {
                    closeModal();
                }
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

