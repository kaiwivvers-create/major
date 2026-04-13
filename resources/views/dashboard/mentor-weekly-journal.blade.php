<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mentor Dashboard - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root{--bg:#0f172a;--surface:#1e293b;--primary:#2563eb;--accent:#38bdf8;--ok:#22c55e;--warn:#f59e0b;--danger:#ef4444;--text:#e2e8f0;--muted:#94a3b8;--border:#334155}
        *{box-sizing:border-box;margin:0;padding:0}
        body{min-height:100vh;font-family:'Instrument Sans',sans-serif;color:var(--text);background:radial-gradient(1000px 500px at 10% -10%, rgba(56,189,248,.2), transparent),var(--bg)}
        .app-shell{min-height:100vh;display:grid;grid-template-columns:270px 1fr}.sidebar{position:sticky;top:0;height:100vh;display:flex;flex-direction:column;border-right:1px solid var(--border);background:rgba(15,23,42,.92);padding:16px 14px}
        .sidebar-brand{font-weight:700;padding:8px 10px;border:1px solid var(--border);border-radius:12px;background:rgba(30,41,59,.45);margin-bottom:14px}
        .sidebar-nav{display:flex;flex-direction:column;gap:8px}.sidebar-nav a{text-decoration:none;color:var(--text);border:1px solid var(--border);border-radius:10px;padding:10px 12px;background:rgba(30,41,59,.6);font-weight:500}
        .sidebar-nav a.active{border-color:var(--primary);background:linear-gradient(135deg, rgba(37,99,235,.32), rgba(29,78,216,.32));color:#f8fafc}
        .sidebar-profile{margin-top:auto;border:1px solid var(--border);border-radius:12px;background:rgba(30,41,59,.55);padding:12px}
        .profile-trigger{width:100%;text-align:left;appearance:none;border:1px solid var(--border);border-radius:12px;color:var(--text);background:rgba(15,23,42,.52);cursor:pointer;padding:10px;display:grid;grid-template-columns:42px 1fr 18px;gap:10px;align-items:center}
        .profile-avatar{width:42px;height:42px;border-radius:999px;border:1px solid rgba(56,189,248,.55);background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24));display:grid;place-items:center;font-size:.82rem;font-weight:700;overflow:hidden}
        .profile-avatar img{width:100%;height:100%;object-fit:cover;display:block}.profile-name{font-weight:700}.profile-meta{font-size:.85rem;color:var(--muted)}.profile-arrow{color:var(--muted);text-align:right}
        .main{padding:20px}.topbar,.card{border:1px solid var(--border);border-radius:14px;background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94));padding:14px}
        .topbar{margin-bottom:12px}.topbar h1{font-size:1.12rem;margin-bottom:3px}.muted{color:var(--muted);font-size:.92rem}
        .alert{border:1px solid rgba(56,189,248,.45);border-radius:10px;padding:8px 10px;margin:10px 0;background:rgba(14,165,233,.12)}.alert.error{border-color:rgba(248,113,113,.6);background:rgba(127,29,29,.25)}
        .stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:12px}.stat{border:1px solid var(--border);border-radius:14px;background:rgba(15,23,42,.72);padding:12px;position:relative}.v{font-size:1.6rem;font-weight:700}.bubble{position:absolute;top:10px;right:10px;border-radius:999px;min-width:22px;min-height:22px;padding:1px 7px;display:grid;place-items:center;background:rgba(239,68,68,.9);border:1px solid rgba(248,113,113,.9);font-size:.78rem;font-weight:700}
        .stack{display:grid;gap:12px}.card h2{font-size:1rem;margin-bottom:6px}.head{display:flex;justify-content:space-between;gap:8px;margin-bottom:8px}
        .presence{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.presence-card{border:1px solid var(--border);border-radius:12px;background:rgba(15,23,42,.72);padding:10px}
        .chip{display:inline-flex;border:1px solid var(--border);border-radius:999px;padding:2px 8px;font-size:.78rem;font-weight:700}.chip.ok{border-color:rgba(34,197,94,.8);color:#86efac}.chip.pending{border-color:rgba(245,158,11,.8);color:#fcd34d}
        .actions{margin-top:8px;display:flex;gap:7px;flex-wrap:wrap}.btn,.btn-link{text-decoration:none;border:1px solid var(--border);border-radius:10px;color:var(--text);background:rgba(15,23,42,.7);padding:7px 10px;font-size:.84rem;font-weight:600;cursor:pointer}.btn.ok{border-color:rgba(34,197,94,.85);color:#86efac}.btn.warn{border-color:rgba(245,158,11,.85);color:#fcd34d}
        .table-wrap{overflow:auto;border:1px solid var(--border);border-radius:12px;margin-top:8px}table{width:100%;border-collapse:collapse;min-width:860px}th,td{text-align:left;padding:9px;border-bottom:1px solid var(--border);vertical-align:top;font-size:.88rem}th{background:rgba(15,23,42,.92);color:#cbd5e1}
        textarea,select,input[type=text],input[type=number]{width:100%;border:1px solid var(--border);border-radius:10px;background:rgba(15,23,42,.72);color:var(--text);padding:8px 10px;font-family:inherit;font-size:.88rem}textarea{resize:vertical;min-height:80px}.ratings{margin-top:8px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
        .modal{position:fixed;inset:0;background:rgba(2,6,23,.62);display:none;align-items:center;justify-content:center;z-index:2300;padding:16px}.modal.open{display:flex}.panel{width:min(560px,96vw);border:1px solid var(--border);border-radius:16px;background:linear-gradient(160deg, rgba(30,41,59,.96), rgba(15,23,42,.96));padding:14px}.modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
        .media-panel{width:min(760px,96vw)}.media-wrap{margin-top:8px;border:1px solid var(--border);border-radius:12px;background:rgba(15,23,42,.75);overflow:hidden}
        .media-wrap img{display:block;width:100%;max-height:72vh;object-fit:contain;background:#020617}
        .media-wrap iframe{display:block;width:100%;height:min(68vh,480px);border:0}
        .media-meta{margin-top:8px;font-size:.9rem;color:var(--muted)}
        .profile-modal-backdrop{position:fixed;inset:0;background:rgba(2,6,23,.62);display:none;align-items:center;justify-content:center;z-index:2200;padding:16px}.profile-modal-backdrop.open{display:flex}
        .profile-modal-panel{width:min(560px,96vw);border:1px solid var(--border);border-radius:16px;background:linear-gradient(160deg, rgba(30,41,59,.96), rgba(15,23,42,.96));padding:16px;max-height:92vh;overflow:auto}
        .profile-modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}.profile-modal-close,.profile-modal-btn{border:1px solid var(--border);border-radius:10px;background:rgba(15,23,42,.72);color:var(--text);padding:8px 12px;cursor:pointer;font-weight:600}.profile-modal-btn.primary{border-color:var(--primary);background:linear-gradient(135deg,var(--primary),#1d4ed8);color:#f8fafc}
        .profile-modal-field{margin-bottom:12px}.profile-modal-field label{display:block;margin-bottom:6px;font-size:.9rem;font-weight:600}.profile-modal-field input{width:100%;border:1px solid var(--border);border-radius:10px;background:rgba(15,23,42,.7);color:var(--text);padding:10px 12px;font-size:.95rem}
        .profile-modal-actions{margin-top:14px;display:flex;justify-content:flex-end;gap:8px}.profile-modal-alert.error{border:1px solid rgba(248,113,113,.6);border-radius:12px;padding:10px 12px;background:rgba(127,29,29,.25);margin-bottom:12px}
        @media (max-width:1100px){.app-shell{grid-template-columns:1fr}.sidebar{position:static;height:auto}.main{padding-top:0}.stats,.presence,.ratings{grid-template-columns:1fr}}
    </style>
</head>
<body>
@php
    $user = auth()->user();
    $canViewMentorDashboard = function_exists('user_can_access') ? user_can_access($user, 'mentor_dashboard', 'view') : true;
    $canViewMentorReviewCenter = function_exists('user_can_access') ? user_can_access($user, 'mentor_review_center', 'view') : true;
    $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
    $avatarInitials = collect(explode(' ', trim($user->name ?? 'U')))->filter()->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('');
    $avatarSource = !empty($user?->avatar_url) ? (\Illuminate\Support\Str::startsWith($user->avatar_url, ['http://', 'https://']) ? $user->avatar_url : \Illuminate\Support\Facades\Storage::url($user->avatar_url)) : null;
    $avatarSourceWithVersion = $avatarSource ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($user->updated_at?->timestamp ?? time()) : null;
@endphp
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">{{ config('app.name', 'Kips') }} - Mentor</div>
        <nav class="sidebar-nav" aria-label="Mentor sections">
            @if ($canViewMentorDashboard)
                <a class="active" href="#overview-section">Overview</a>
                <a href="{{ route('dashboard.mentor.company-settings-page') }}">Company Profile & Proximity</a>
            @endif
            @if ($canViewMentorReviewCenter)
                <a href="{{ route('dashboard.mentor.review-center') }}">Daily Scoring + Weekly Review</a>
            @endif
            <a href="{{ url('/') }}">Back to Home</a>
        </nav>
        <div class="sidebar-profile">
            <button type="button" class="profile-trigger" id="open-profile-modal" aria-label="Open profile modal">
                <span class="profile-avatar">@if (!empty($avatarSourceWithVersion))<img src="{{ $avatarSourceWithVersion }}" alt="Profile picture" onerror="this.style.display='none'; this.parentElement.textContent='{{ $avatarInitials }}';">@else{{ $avatarInitials }}@endif</span>
                <span><div class="profile-name">{{ $user->name }}</div><div class="profile-meta">NIS: {{ $user->nis ?? '-' }} &middot; {{ strtoupper($user->role) }}</div></span>
                <span class="profile-arrow">></span>
            </button>
        </div>
    </aside>

    <main class="main">
        <header class="topbar" id="overview-section">
            <h1>Mentor Dashboard</h1>
            <p class="muted">Week: {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</p>
        </header>
        @if (session('status'))<div class="alert">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert error">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        @if (($recentAlerts ?? collect())->isNotEmpty())
            <div class="alert">
                <strong>Auto Attendance Alerts</strong>
                @foreach (($recentAlerts ?? collect()) as $alertRow)
                    <div class="muted" style="margin-top:4px;">
                        {{ \Illuminate\Support\Carbon::parse($alertRow->alert_date, 'Asia/Jakarta')->format('d M Y') }}:
                        {{ $alertRow->message }}
                    </div>
                @endforeach
            </div>
        @endif

        <section class="stats">
            <article class="stat"><div class="muted">Total Active Students</div><div class="v">{{ (int) ($totalActiveStudents ?? 0) }}</div><div class="muted">Students in active PKL period.</div></article>
            <article class="stat"><div class="muted">Today's Check-ins</div><div class="v">{{ (int) ($todaysCheckins ?? 0) }}</div><div class="muted">{{ \Illuminate\Support\Carbon::parse($today, 'Asia/Jakarta')->format('d M Y') }}</div></article>
            <article class="stat"><div class="muted">Pending Journals</div><span class="bubble">{{ (int) ($pendingJournals ?? 0) }}</span><div class="v">{{ (int) ($pendingJournals ?? 0) }}</div><div class="muted">Waiting for mentor decision.</div></article>
        </section>

        <section class="card">
            <div class="head"><h2>Absence Visibility (Today)</h2><span class="muted">Read-only for mentor</span></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Student</th><th>Class</th><th>Type</th><th>Status</th><th>Reason</th></tr></thead>
                    <tbody>
                        @forelse (($absenceRows ?? collect()) as $row)
                            <tr>
                                <td>{{ $row->student_name }}<br><span class="muted">NIS: {{ $row->student_nis ?? '-' }}</span></td>
                                <td>{{ $row->class_name ?? '-' }}</td>
                                <td>{{ strtoupper((string) ($row->absence_type ?? '-')) }}</td>
                                <td>{{ strtoupper((string) ($row->status ?? '-')) }}</td>
                                <td>{{ \Illuminate\Support\Str::limit((string) ($row->reason ?? '-'), 120) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No absence requests for today.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="stack">
            <section class="card" id="presence-section">
                <div class="head"><h2>Presence Monitor</h2><span class="muted">Live status feed</span></div>
                <div class="presence">
                    @forelse (($presenceRows ?? collect()) as $row)
                        @php
                            $checkedIn = !empty($row->check_in_at);
                            $photoUrl = !empty($row->photo_path) ? \Illuminate\Support\Facades\Storage::url($row->photo_path) : null;
                            $hasCoords = !empty($row->latitude) && !empty($row->longitude);
                            $mapUrl = $hasCoords ? ('https://www.google.com/maps?q=' . $row->latitude . ',' . $row->longitude) : null;
                            $phoneDigits = preg_replace('/\D+/', '', (string) ($row->phone_number ?? ''));
                            $waUrl = $phoneDigits !== '' ? ('https://wa.me/' . $phoneDigits . '?text=' . rawurlencode('Reminder: Please check in for today\'s PKL attendance.')) : null;
                        @endphp
                        <article class="presence-card">
                            <div style="display:flex;justify-content:space-between;gap:8px"><div><strong>{{ $row->student_name }}</strong><div class="muted">NIS: {{ $row->student_nis ?? '-' }} | {{ $row->major_name ?? '-' }} {{ !empty($row->class_name) ? ('| ' . $row->class_name) : '' }}</div></div><span class="chip {{ $checkedIn ? 'ok' : 'pending' }}">{{ $checkedIn ? 'Checked In' : 'Not Checked In Yet' }}</span></div>
                            <div class="muted" style="margin-top:6px;">@if ($checkedIn) Checked in @ {{ \Illuminate\Support\Carbon::parse($row->check_in_at, 'Asia/Jakarta')->format('H:i') }} WIB @else No attendance record yet for today. @endif</div>
                            <div class="actions">
                                @if ($photoUrl)<button class="btn-link" type="button" data-open-selfie-modal data-student="{{ $row->student_name }}" data-photo-url="{{ $photoUrl }}">View Selfie</button>@endif
                                @if ($mapUrl)<button class="btn-link" type="button" data-open-location-modal data-student="{{ $row->student_name }}" data-map-url="{{ $mapUrl }}" data-lat="{{ $row->latitude }}" data-lng="{{ $row->longitude }}">View Location</button>@endif
                                @if (!$checkedIn && $waUrl)<a class="btn-link" href="{{ $waUrl }}" target="_blank" rel="noopener">Send Reminder / WA</a>@endif
                            </div>
                        </article>
                    @empty
                        <p class="muted">No active students found under your supervision.</p>
                    @endforelse
                </div>
            </section>

            <section class="card" id="validation-section">
                <div class="head"><h2>Validation Hub</h2><span class="muted">Daily logs with one-click actions</span></div>
                <div class="table-wrap"><table><thead><tr><th>Date</th><th>Student</th><th>Activity</th><th>Status</th><th>Action</th></tr></thead><tbody>
                    @forelse (($dailyValidationRows ?? collect()) as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->work_date, 'Asia/Jakarta')->format('d M Y') }}</td>
                            <td>{{ $row->student_name }}<br><span class="muted">NIS: {{ $row->student_nis }}</span></td>
                            <td><strong>{{ $row->title ?? 'Daily Log' }}</strong><br><span class="muted">{{ \Illuminate\Support\Str::limit((string) ($row->work_realization ?: $row->description), 170) }}</span></td>
                            <td>@php $status = strtolower((string) ($row->mentor_review_status ?? 'pending')); @endphp<span class="chip {{ $status === 'approved' ? 'ok' : 'pending' }}">{{ strtoupper($status) }}</span></td>
                            <td><div class="actions"><form method="POST" action="{{ route('dashboard.mentor.daily-log.review', $row->id) }}">@csrf<input type="hidden" name="action" value="approve"><button class="btn ok" type="submit">Approve</button></form><button class="btn warn" type="button" data-open-revise-modal data-log-id="{{ $row->id }}" data-student="{{ $row->student_name }}">Revise</button></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No daily logs found for this week.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>

            <section class="card" id="weekly-center-section">
                <div class="head"><h2>Weekly Journal Correction Center</h2><span class="muted">Feedback and ratings</span></div>
                <div class="table-wrap"><table><thead><tr><th>Student</th><th>Journal</th><th>Review</th></tr></thead><tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->student_name }}<br><span class="muted">NIS: {{ $row->student_nis }}</span></td>
                            <td><div><strong>PKL Activity:</strong> {{ $row->learning_notes }}</div><div class="muted" style="margin-top:4px;"><strong>Student Notes:</strong> {{ $row->student_mentor_notes }}</div></td>
                            <td>
                                <form method="POST" action="{{ route('dashboard.mentor.weekly-journal.review', $row->id) }}">
                                    @csrf
                                    <select name="mentor_is_correct" required><option value="1" {{ (string) ($row->mentor_is_correct ?? '') === '1' ? 'selected' : '' }}>Approve</option><option value="0" {{ (string) ($row->mentor_is_correct ?? '') === '0' ? 'selected' : '' }}>Needs Revision</option></select>
                                    <textarea name="missing_info_notes" placeholder="Example: Please explain the technical steps you took for server maintenance.">{{ old('missing_info_notes', $row->missing_info_notes ?? '') }}</textarea>
                                    <textarea name="mentor_feedback_summary" placeholder="Weekly performance summary...">{{ old('mentor_feedback_summary', $row->mentor_feedback_summary ?? '') }}</textarea>
                                    <div class="ratings">
                                        <div><label class="muted">Attitude (1-5)</label><select name="mentor_attitude_rating"><option value="">Not Rated</option>@for ($i = 1; $i <= 5; $i++)<option value="{{ $i }}" {{ (string) old('mentor_attitude_rating', $row->mentor_attitude_rating ?? '') === (string) $i ? 'selected' : '' }}>{{ $i }}</option>@endfor</select></div>
                                        <div><label class="muted">Skill (1-5)</label><select name="mentor_skill_rating"><option value="">Not Rated</option>@for ($i = 1; $i <= 5; $i++)<option value="{{ $i }}" {{ (string) old('mentor_skill_rating', $row->mentor_skill_rating ?? '') === (string) $i ? 'selected' : '' }}>{{ $i }}</option>@endfor</select></div>
                                    </div>
                                    <div style="margin-top:8px;"><button class="btn" type="submit">Save Weekly Review</button></div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No weekly journals for this week.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>

        </div>
    </main>
</div>

<div class="modal" id="revise-modal" aria-hidden="true">
    <div class="panel" role="dialog" aria-modal="true" aria-labelledby="revise-modal-title">
        <div class="modal-head"><h3 id="revise-modal-title">Request Revision</h3><button type="button" class="btn-link" id="revise-close">Close</button></div>
        <p class="muted" id="revise-student-label">Student: -</p>
        <form id="revise-form" method="POST" action="">@csrf<input type="hidden" name="action" value="revise"><textarea id="revision_notes" name="revision_notes" placeholder="Please explain the technical steps you took for server maintenance." required></textarea><div style="margin-top:10px;display:flex;justify-content:flex-end;gap:8px;"><button type="button" class="btn-link" id="revise-cancel">Cancel</button><button class="btn warn" type="submit">Send Revision</button></div></form>
    </div>
</div>

<div class="modal" id="selfie-modal" aria-hidden="true">
    <div class="panel media-panel" role="dialog" aria-modal="true" aria-labelledby="selfie-modal-title">
        <div class="modal-head"><h3 id="selfie-modal-title">Student Selfie</h3><button type="button" class="btn-link" id="selfie-close">Close</button></div>
        <p class="muted" id="selfie-student-label">Student: -</p>
        <div class="media-wrap"><img id="selfie-image" src="" alt="Student check-in selfie"></div>
    </div>
</div>

<div class="modal" id="location-modal" aria-hidden="true">
    <div class="panel media-panel" role="dialog" aria-modal="true" aria-labelledby="location-modal-title">
        <div class="modal-head"><h3 id="location-modal-title">Student Location</h3><button type="button" class="btn-link" id="location-close">Close</button></div>
        <p class="muted" id="location-student-label">Student: -</p>
        <div class="media-wrap"><iframe id="location-frame" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>
        <div class="media-meta" id="location-meta">Coordinates: -</div>
        <div style="margin-top:8px;display:flex;justify-content:flex-end;"><a id="location-open-tab" class="btn-link" href="#" target="_blank" rel="noopener">Open in Google Maps</a></div>
    </div>
</div>

@include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])
<script>
    (() => {
        const modal = document.getElementById('revise-modal');
        const closeBtn = document.getElementById('revise-close');
        const cancelBtn = document.getElementById('revise-cancel');
        const form = document.getElementById('revise-form');
        const notes = document.getElementById('revision_notes');
        const label = document.getElementById('revise-student-label');
        const openButtons = Array.from(document.querySelectorAll('[data-open-revise-modal]'));
        const routeTemplate = @json(route('dashboard.mentor.daily-log.review', ['log' => '__ID__']));
        if (!modal || !closeBtn || !cancelBtn || !form || !notes || !label || !openButtons.length) return;
        const open = (logId, studentName) => { form.action = routeTemplate.replace('__ID__', String(logId || '')); label.textContent = `Student: ${studentName || '-'}`; notes.value = ''; modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); notes.focus(); };
        const close = () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); };
        openButtons.forEach((btn) => btn.addEventListener('click', () => open(btn.getAttribute('data-log-id'), btn.getAttribute('data-student'))));
        closeBtn.addEventListener('click', close); cancelBtn.addEventListener('click', close);
        modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
        window.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal.classList.contains('open')) close(); });
    })();

    (() => {
        const selfieModal = document.getElementById('selfie-modal');
        const selfieClose = document.getElementById('selfie-close');
        const selfieImage = document.getElementById('selfie-image');
        const selfieLabel = document.getElementById('selfie-student-label');
        const selfieButtons = Array.from(document.querySelectorAll('[data-open-selfie-modal]'));
        if (selfieModal && selfieClose && selfieImage && selfieLabel && selfieButtons.length) {
            const open = (studentName, photoUrl) => {
                selfieLabel.textContent = `Student: ${studentName || '-'}`;
                selfieImage.src = photoUrl || '';
                selfieModal.classList.add('open');
                selfieModal.setAttribute('aria-hidden', 'false');
            };
            const close = () => {
                selfieModal.classList.remove('open');
                selfieModal.setAttribute('aria-hidden', 'true');
                selfieImage.removeAttribute('src');
            };
            selfieButtons.forEach((btn) => btn.addEventListener('click', () => open(btn.getAttribute('data-student'), btn.getAttribute('data-photo-url'))));
            selfieClose.addEventListener('click', close);
            selfieModal.addEventListener('click', (event) => { if (event.target === selfieModal) close(); });
            window.addEventListener('keydown', (event) => { if (event.key === 'Escape' && selfieModal.classList.contains('open')) close(); });
        }
    })();

    (() => {
        const locationModal = document.getElementById('location-modal');
        const locationClose = document.getElementById('location-close');
        const locationFrame = document.getElementById('location-frame');
        const locationLabel = document.getElementById('location-student-label');
        const locationMeta = document.getElementById('location-meta');
        const locationOpenTab = document.getElementById('location-open-tab');
        const locationButtons = Array.from(document.querySelectorAll('[data-open-location-modal]'));
        if (locationModal && locationClose && locationFrame && locationLabel && locationMeta && locationOpenTab && locationButtons.length) {
            const open = (studentName, mapUrl, lat, lng) => {
                locationLabel.textContent = `Student: ${studentName || '-'}`;
                const latText = (lat || '').toString().trim();
                const lngText = (lng || '').toString().trim();
                const embedUrl = latText && lngText
                    ? `https://www.google.com/maps?q=${encodeURIComponent(`${latText},${lngText}`)}&z=17&output=embed`
                    : '';
                locationFrame.src = embedUrl || '';
                locationOpenTab.href = mapUrl || '#';
                locationMeta.textContent = latText && lngText ? `Coordinates: ${latText}, ${lngText}` : 'Coordinates: -';
                locationModal.classList.add('open');
                locationModal.setAttribute('aria-hidden', 'false');
            };
            const close = () => {
                locationModal.classList.remove('open');
                locationModal.setAttribute('aria-hidden', 'true');
                locationFrame.removeAttribute('src');
                locationOpenTab.href = '#';
                locationMeta.textContent = 'Coordinates: -';
            };
            locationButtons.forEach((btn) => btn.addEventListener('click', () => open(
                btn.getAttribute('data-student'),
                btn.getAttribute('data-map-url'),
                btn.getAttribute('data-lat'),
                btn.getAttribute('data-lng'),
            )));
            locationClose.addEventListener('click', close);
            locationModal.addEventListener('click', (event) => { if (event.target === locationModal) close(); });
            window.addEventListener('keydown', (event) => { if (event.key === 'Escape' && locationModal.classList.contains('open')) close(); });
        }
    })();
</script>
</body>
</html>
