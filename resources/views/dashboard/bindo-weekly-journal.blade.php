<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher Dashboard - {{ $appBranding->display_name }}</title>
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
            --bg:#0f172a;
            --bg-soft:#111f3b;
            --panel:#16223f;
            --border:#334155;
            --primary: {{ $appBranding->primary_color ?? '#2563eb' }};
            --accent: {{ $appBranding->accent_color ?? '#38bdf8' }};
            --text:#e2e8f0;
            --muted:#94a3b8;
            --ok:#22c55e;
            --danger:#ef4444;
        }
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            min-height:100vh;
            background:radial-gradient(circle at 10% 5%, #1e3a8a 0%, #0f172a 35%), var(--bg);
            color:var(--text);
            font-family:'Instrument Sans',sans-serif;
        }
        .app-shell {
            min-height:100vh;
            display:grid;
            grid-template-columns:270px 1fr;
        }
        .sidebar {
            position:sticky;
            top:0;
            height:100vh;
            display:flex;
            flex-direction:column;
            border-right:1px solid var(--border);
            background:rgba(15,23,42,.92);
            backdrop-filter:blur(10px);
            padding:16px 14px;
        }
        .sidebar-brand { font-weight:700; letter-spacing:.02em; padding:8px 10px; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.45); margin-bottom:14px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:8px; }
        .sidebar-nav a { text-decoration:none; color:var(--text); border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:rgba(30,41,59,.6); font-weight:500; transition:all .2s ease; }
        .sidebar-nav a:hover { border-color:var(--primary); color:#bfdbfe; }
        .sidebar-nav a.active { border-color:var(--primary); background:linear-gradient(135deg, rgba(37,99,235,.32), rgba(29,78,216,.32)); color:#f8fafc; font-weight:700; }
        .sidebar-profile { margin-top:auto; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.55); padding:12px; overflow:hidden; }
        .main { padding:16px; }
        .shell { width:100%; max-width:none; margin:0; display:grid; gap:14px; }
        .card {
            border:1px solid var(--border);
            border-radius:14px;
            background:linear-gradient(160deg, rgba(22,34,63,.95), rgba(15,23,42,.95));
            padding:14px;
        }
        .page-head { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
        .muted { color:var(--muted); font-size:.92rem; }
        .alert { border:1px solid rgba(56,189,248,.45); border-radius:10px; padding:10px 12px; margin-top:10px; background:rgba(14,165,233,.12); }
        .alert.error { border-color: rgba(248,113,113,.55); background: rgba(127,29,29,.24); }
        .grid-main { display:grid; gap:14px; grid-template-columns:1.15fr 1fr; }
        .section-title { font-size:1.03rem; font-weight:700; margin-bottom:8px; }
        .students-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fill, minmax(230px, 1fr)); }
        #teacher-live-map {
            margin-top: 10px;
            margin-bottom: 14px;
            height: 340px;
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .student-card { border:1px solid var(--border); border-radius:12px; background:rgba(15,23,42,.55); overflow:hidden; }
        .student-photo { width:100%; height:130px; object-fit:cover; background:#0b1222; }
        .student-photo-fallback { width:100%; height:130px; display:grid; place-items:center; color:var(--muted); font-size:.9rem; background:#0b1222; }
        .student-body { padding:10px; display:grid; gap:5px; }
        .student-name { font-weight:700; }
        .student-meta { font-size:.85rem; color:var(--muted); }
        .journal-feed { max-height:520px; overflow:auto; display:grid; gap:10px; padding-right:4px; }
        .journal-item { border:1px solid var(--border); border-radius:12px; background:rgba(15,23,42,.55); padding:10px; }
        .journal-item .top { display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:8px; }
        .tag { font-size:.75rem; border:1px solid rgba(148,163,184,.5); color:#cbd5e1; padding:2px 8px; border-radius:999px; }
        .status-submitted { border-color:rgba(34,197,94,.5); color:#bbf7d0; }
        .status-needs_revision { border-color:rgba(239,68,68,.5); color:#fecaca; }
        .status-approved { border-color:rgba(56,189,248,.5); color:#bae6fd; }
        .journal-notes { display:grid; gap:6px; margin-bottom:8px; font-size:.9rem; }
        .journal-notes div { line-height:1.45; }
        textarea, select, input[type="date"], input[type="file"] {
            width:100%;
            border:1px solid var(--border);
            border-radius:10px;
            background:rgba(15,23,42,.72);
            color:var(--text);
            padding:9px 10px;
            font-family:inherit;
            font-size:.92rem;
        }
        input[type="file"] {
            border-color: rgba(56, 189, 248, 0.35);
            border-radius: 12px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.92));
            color: #cbd5e1;
            padding: 8px;
            cursor: pointer;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
        }
        input[type="file"]::file-selector-button {
            border: 1px solid rgba(56, 189, 248, 0.45);
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.92), rgba(14, 165, 233, 0.82));
            color: #f8fafc;
            padding: 8px 12px;
            margin-right: 12px;
            font-weight: 700;
            font-size: .84rem;
            cursor: pointer;
        }
        input[type="file"]:hover { border-color: rgba(103, 232, 249, 0.7); }
        textarea { min-height:90px; resize:vertical; }
        .btn {
            border:1px solid var(--primary);
            border-radius:10px;
            background:linear-gradient(135deg,var(--primary),#1d4ed8);
            color:#fff;
            font-weight:700;
            padding:8px 12px;
            cursor:pointer;
        }
        .visit-form { display:grid; gap:8px; margin-bottom:12px; }
        .visit-list { max-height:280px; overflow:auto; display:grid; gap:8px; }
        .visit-item { border:1px solid var(--border); border-radius:12px; padding:8px; background:rgba(15,23,42,.55); display:grid; grid-template-columns:88px 1fr; gap:10px; }
        .visit-photo { width:88px; height:88px; object-fit:cover; border-radius:8px; background:#0b1222; }
        .visit-photo-fallback { width:88px; height:88px; border-radius:8px; display:grid; place-items:center; color:var(--muted); background:#0b1222; font-size:.8rem; }
        .wa-links { display:grid; gap:7px; }
        .wa-row { border:1px solid var(--border); border-radius:10px; padding:8px 10px; background:rgba(15,23,42,.45); display:flex; justify-content:space-between; gap:8px; align-items:center; }
        .wa-link { color:#93c5fd; text-decoration:none; font-weight:600; }
        .wa-link:hover { text-decoration:underline; }
        .profile-trigger { width:100%; max-width:100%; min-width:0; appearance:none; border:1px solid var(--border); border-radius:12px; color:var(--text); background:rgba(15,23,42,0.52); cursor:pointer; padding:8px 10px; display:grid; grid-template-columns:40px minmax(0,1fr) 16px; align-items:center; gap:8px; text-align:left; }
        .profile-trigger:hover { border-color:var(--primary); }
        .profile-avatar { width:40px; height:40px; border-radius:999px; border:1px solid rgba(56, 189, 248, 0.55); background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24)); display:grid; place-items:center; font-size:.8rem; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .profile-name { font-weight:700; margin-bottom:2px; }
        .profile-meta { font-size:.8rem; color:var(--muted); }
        .profile-arrow { color:var(--muted); font-size:.95rem; text-align:right; }
        .profile-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.62); display:none; align-items:center; justify-content:center; z-index:2200; padding:16px; }
        .profile-modal-backdrop.open { display:flex; }
        .profile-modal-panel { width:min(560px,96vw); border:1px solid var(--border); border-radius:16px; background:linear-gradient(160deg, rgba(30,41,59,.96), rgba(15,23,42,.96)); padding:16px; max-height:92vh; overflow:auto; overflow-x:hidden; }
        .profile-modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .profile-modal-close, .profile-modal-btn { border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:8px 12px; cursor:pointer; font-weight:600; }
        .profile-modal-btn.primary { border-color:var(--primary); background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#f8fafc; }
        .profile-modal-field { margin-bottom:12px; }
        .profile-modal-field label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; }
        .profile-modal-field input { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.95rem; }
        .profile-modal-actions { margin-top:14px; display:flex; justify-content:flex-end; gap:8px; flex-wrap:wrap; }
        .profile-modal-alert.error { border:1px solid rgba(248,113,113,.6); border-radius:12px; padding:10px 12px; background:rgba(127,29,29,.25); margin-bottom:12px; }
        .scaffold-grid { display:grid; gap:10px; grid-template-columns:repeat(2,minmax(0,1fr)); }
        .scaffold-item { border:1px dashed rgba(56,189,248,.45); border-radius:12px; background:rgba(15,23,42,.45); padding:10px; }
        .scaffold-item h3 { font-size:.96rem; margin-bottom:6px; }
        .scaffold-item p { color:var(--muted); font-size:.88rem; line-height:1.5; }
        @media (max-width:980px) { .scaffold-grid { grid-template-columns:1fr; } }
        @media (max-width:980px) {
            .app-shell { grid-template-columns:1fr; }
            .sidebar { position:static; height:auto; }
            .grid-main { grid-template-columns:1fr; }
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
        $avatarInitials = collect(explode(' ', trim($user->name ?? 'U')))->filter()->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('');
        $avatarSource = !empty($user?->avatar_url)
            ? (\Illuminate\Support\Str::startsWith($user->avatar_url, ['http://', 'https://']) ? $user->avatar_url : \Illuminate\Support\Facades\Storage::url($user->avatar_url))
            : null;
        $avatarSourceWithVersion = $avatarSource ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($user->updated_at?->timestamp ?? time()) : null;
    @endphp

    <div class="app-shell">
        @php
            $canTeacherDashboard = function_exists('teacher_access') ? teacher_access($user, 'teacher_weekly_journal', 'view') : true;
            $canWatchlist = function_exists('teacher_access')
                ? teacher_access($user, 'teacher_watchlist', 'view')
                : true;
            $canJournalAudit = function_exists('teacher_access')
                ? teacher_access($user, 'teacher_journal_audit', 'view')
                : true;
            $canSiteVisits = function_exists('teacher_access')
                ? teacher_access($user, 'teacher_site_visits', 'view')
                : true;
            $canRedFlags = function_exists('teacher_access')
                ? teacher_access($user, 'teacher_red_flags', 'view')
                : true;
            $canContactDirectory = function_exists('teacher_access')
                ? teacher_access($user, 'teacher_contact_directory', 'view')
                : true;
        @endphp
        <aside class="sidebar">
            @include('dashboard.partials.sidebar-brand', ['suffix' => 'Teacher'])
            <nav class="sidebar-nav" aria-label="Teacher menu">
                <a class="active" href="{{ route('dashboard.bindo.weekly-journal') }}">Teacher Dashboard</a>
                @if ($canWatchlist)
                    <a href="{{ route('dashboard.bindo.watchlist') }}">My Supervised Students</a>
                @endif
                @if ($canJournalAudit)
                    <a href="{{ route('dashboard.bindo.journal-audit') }}">Journal Review & Language Audit</a>
                @endif
                @if ($canSiteVisits)
                    <a href="{{ route('dashboard.bindo.site-visits') }}">Site Visit Log</a>
                @endif
                @if ($canRedFlags)
                    <a href="{{ route('dashboard.bindo.red-flags') }}">Red Flag System</a>
                @endif
                @if ($canContactDirectory)
                    <a href="{{ route('dashboard.bindo.contact-directory') }}">Contact Directory</a>
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
    <div class="shell">
        <section class="card">
            <div class="page-head">
                <div>
                    <h1>Teacher Dashboard</h1>
                    <p class="muted">Assigned students, weekly journal review, site-visit proof, and mentor contact shortcuts.</p>
                </div>
            </div>

            @if (session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif
            @if ($errors->has('site_visit'))
                <div class="alert error">{{ $errors->first('site_visit') }}</div>
            @endif
        </section>

        @if ($canWatchlist)
        <section class="card" id="watchlist-section">
            <div class="section-title">Assigned Student List</div>
            <p class="muted">Latest location pins from supervised student check-ins.</p>
            <div id="teacher-live-map"></div>
            @if (($mapPoints ?? collect())->isEmpty())
                <p class="muted" style="margin-top:8px;">No GPS coordinates available yet for your supervised students.</p>
            @endif
            @if ($assignedStudents->isEmpty())
                <p class="muted">No assigned students yet. Match student profile field <strong>School Supervisor Teacher Name</strong> with this teacher account name.</p>
            @else
                <div class="students-grid">
                    @foreach ($assignedStudents as $student)
                        <article class="student-card">
                            @if (!empty($student->latest_photo_url))
                                <img class="student-photo" src="{{ $student->latest_photo_url }}" alt="Latest photo of {{ $student->name }}">
                            @else
                                <div class="student-photo-fallback">No photo yet</div>
                            @endif
                            <div class="student-body">
                                <div class="student-name">{{ $student->name }}</div>
                                <div class="student-meta">NIS: {{ $student->nis ?? '-' }} &middot; Class {{ $student->class_name }}</div>
                                <div class="student-meta">Location: {{ $student->latest_location }}</div>
                                <div class="student-meta">Company: {{ $student->pkl_place_name }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
        @endif

        <div class="grid-main">
            @if ($canJournalAudit)
            <section class="card" id="journal-audit-section">
                <div class="section-title">Journal Feed</div>
                <p class="muted" style="margin-bottom:10px;">Recent weekly journals from your assigned students. Add or update teacher comments.</p>

                <div class="journal-feed">
                    @forelse ($rows as $row)
                        <article class="journal-item">
                            <div class="top">
                                <div>
                                    <strong>{{ $row->student_name }}</strong>
                                    <div class="muted">NIS {{ $row->student_nis }} &middot; Class {{ $row->class_name }}</div>
                                </div>
                                <div>
                                    <span class="tag status-{{ $row->status }}">{{ strtoupper($row->status ?? 'draft') }}</span>
                                    <div class="muted" style="margin-top:4px; text-align:right;">{{ \Illuminate\Support\Carbon::parse($row->week_start_date, 'Asia/Jakarta')->format('d M') }} - {{ \Illuminate\Support\Carbon::parse($row->week_end_date, 'Asia/Jakarta')->format('d M Y') }}</div>
                                </div>
                            </div>

                            <div class="journal-notes">
                                <div><strong>Learning Notes:</strong> {{ $row->learning_notes }}</div>
                                <div><strong>Student Notes:</strong> {{ $row->student_mentor_notes }}</div>
                            </div>

                            <form method="POST" action="{{ route('dashboard.bindo.weekly-journal.note', $row->id) }}">
                                @csrf
                                <textarea name="teacher_comment" placeholder="Teacher Comment (language / quality check)...">{{ old('teacher_comment', $row->bindo_notes ?? '') }}</textarea>
                                <button class="btn" type="submit" style="margin-top:8px;">Save Teacher Comment</button>
                            </form>
                        </article>
                    @empty
                        <p class="muted">No weekly journals found for your assigned students.</p>
                    @endforelse
                </div>
            </section>
            @endif

            @if ($canSiteVisits || $canContactDirectory)
            <section class="card" id="visit-log-section">
                <div class="section-title">Site Visit Log</div>
                @if (!$canSiteVisits)
                    <p class="muted">Visit log access is disabled for your role.</p>
                @elseif (!$visitLogReady)
                    <p class="muted">Attendance table is not ready yet. Run migrations to enable student photo evidence records.</p>
                @else
                    <p class="muted" style="margin-bottom:10px;">Teacher upload is disabled. This section now shows student-submitted photo evidence from check-in.</p>
                @endif

                <div class="section-title" style="margin-top:12px;">Recent Student Photo Evidence</div>
                <div class="visit-list">
                    @forelse ($visitLogs as $visit)
                        <article class="visit-item">
                            @if (!empty($visit->photo_url))
                                <img class="visit-photo" src="{{ $visit->photo_url }}" alt="Visit proof photo">
                            @else
                                <div class="visit-photo-fallback">No photo</div>
                            @endif
                            <div>
                                <strong>{{ $visit->student_name }}</strong>
                                <div class="muted">NIS {{ $visit->student_nis }}</div>
                                <div class="muted">{{ \Illuminate\Support\Carbon::parse($visit->visited_at, 'Asia/Jakarta')->format('d M Y H:i') }}</div>
                                <div class="muted">{{ $visit->company_name ?: '-' }}</div>
                                <div style="margin-top:4px; font-size:.9rem;">Source: Student check-in photo</div>
                            </div>
                        </article>
                    @empty
                        <p class="muted">No student photo evidence yet.</p>
                    @endforelse
                </div>

                @if ($canContactDirectory)
                    <div class="section-title" id="contact-directory-section" style="margin-top:14px;">Direct Contact</div>
                    <p class="muted" style="margin-bottom:8px;">Quick WhatsApp links to each student's industry mentor/company contact.</p>
                    <div class="wa-links">
                        @forelse ($assignedStudents as $student)
                            <div class="wa-row">
                                <div>
                                    <strong>{{ $student->name }}</strong>
                                    <div class="muted">{{ $student->mentor_contact_name ?: 'Industry Mentor' }}</div>
                                </div>
                                @if (!empty($student->mentor_whatsapp_url))
                                    <a class="wa-link" href="{{ $student->mentor_whatsapp_url }}" target="_blank" rel="noopener noreferrer">Open WhatsApp</a>
                                @else
                                    <span class="muted">No WhatsApp number</span>
                                @endif
                            </div>
                        @empty
                            <p class="muted">No students to contact yet.</p>
                        @endforelse
                    </div>
                @endif
            </section>
            @endif
        </div>

        @if ($canRedFlags)
            <section class="card" id="red-flag-section">
                <div class="section-title">Red Flag System (Scaffold)</div>
                <p class="muted">This section is enabled and reserved for early-warning alerts: low attendance streaks and repeated mentor rejections.</p>
                <p class="muted" style="margin-top:6px;">Data logic is not connected yet in this scaffold stage.</p>
            </section>
        @endif
        </div>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script>
        (() => {
            const mapEl = document.getElementById('teacher-live-map');
            if (!mapEl || typeof window.L === 'undefined') return;

            const rawPoints = @json($mapPoints ?? []);
            const points = rawPoints.filter((point) =>
                point &&
                point.latitude !== null &&
                point.longitude !== null &&
                !Number.isNaN(Number(point.latitude)) &&
                !Number.isNaN(Number(point.longitude))
            );

            const map = L.map(mapEl, {
                zoomControl: true,
                scrollWheelZoom: true,
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            if (!points.length) {
                map.setView([-6.2, 106.8], 5);
                return;
            }

            const bounds = L.latLngBounds([]);
            points.forEach((point) => {
                const lat = Number(point.latitude);
                const lng = Number(point.longitude);
                const marker = L.marker([lat, lng]).addTo(map);
                marker.bindPopup(
                    `<strong>${point.student_name ?? 'Student'}</strong><br>` +
                    `NIS: ${point.student_nis ?? '-'}<br>` +
                    `Class: ${point.class_name ?? '-'}<br>` +
                    `Company: ${point.company_name ?? '-'}<br>` +
                    `Check-in date: ${point.attendance_date ?? '-'}<br>` +
                    `Address: ${point.location_address ?? '-'}`
                );
                bounds.extend([lat, lng]);
            });

            map.fitBounds(bounds, { padding: [16, 16] });
        })();
    </script>

    @include('partials.chatbot')
</body>
</html>

