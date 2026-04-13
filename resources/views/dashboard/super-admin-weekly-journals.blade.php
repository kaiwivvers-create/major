<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Weekly Journals - Super Admin - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; --primary:#2563eb; --accent:#38bdf8; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); background:var(--bg); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:270px 1fr; }
        .sidebar { position:sticky; top:0; height:100vh; display:flex; flex-direction:column; border-right:1px solid var(--border); background:rgba(15,23,42,.92); padding:16px 14px; }
        .sidebar-brand { font-weight:700; letter-spacing:.02em; padding:8px 10px; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.45); margin-bottom:14px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:8px; }
        .sidebar-nav a { text-decoration:none; color:var(--text); border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:rgba(30,41,59,.6); font-weight:500; }
        .sidebar-nav a.active { border-color:var(--primary); background:linear-gradient(135deg, rgba(37,99,235,.32), rgba(29,78,216,.32)); font-weight:700; }
        .sidebar-profile { margin-top:auto; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.55); padding:12px; }
        .profile-trigger { width:100%; text-align:left; appearance:none; border:1px solid var(--border); border-radius:12px; color:var(--text); background:rgba(15,23,42,.52); cursor:pointer; padding:10px; display:grid; grid-template-columns:42px 1fr 18px; align-items:center; gap:10px; }
        .profile-avatar { width:42px; height:42px; border-radius:999px; border:1px solid rgba(56,189,248,.55); background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24)); display:grid; place-items:center; font-size:.82rem; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .profile-name { font-weight:700; margin-bottom:2px; } .profile-meta { font-size:.85rem; color:var(--muted); } .profile-arrow { color:var(--muted); font-size:1rem; text-align:right; }
        .profile-modal-backdrop { position: fixed; inset: 0; background: rgba(2, 6, 23, 0.62); display: none; align-items: center; justify-content: center; z-index: 2200; padding: 16px; }
        .profile-modal-backdrop.open { display: flex; }
        .profile-modal-panel { width: min(560px, 96vw); border: 1px solid var(--border); border-radius: 16px; background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96)); padding: 16px; max-height: 92vh; overflow: auto; }
        .profile-modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .profile-modal-close, .profile-modal-btn { border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.72); color: var(--text); padding: 8px 12px; cursor: pointer; font-weight: 600; }
        .profile-modal-btn.primary { border-color: var(--primary); background: linear-gradient(135deg, var(--primary), #1d4ed8); color: #f8fafc; }
        .profile-modal-field { margin-bottom: 12px; }
        .profile-modal-field label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; }
        .profile-modal-field input { width: 100%; border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.7); color: var(--text); padding: 10px 12px; font-size: 0.95rem; }
        .profile-modal-actions { margin-top: 14px; display: flex; justify-content: flex-end; gap: 8px; }
        .profile-modal-alert.error { border: 1px solid rgba(248, 113, 113, 0.6); border-radius: 12px; padding: 10px 12px; background: rgba(127, 29, 29, 0.25); margin-bottom: 12px; }
        .main { padding:20px; }
        .topbar,.card { border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.9); padding:14px 16px; }
        .card { margin-top:12px; }
        .muted { color:var(--muted); font-size:.92rem; }
        .filters { margin-top:10px; display:grid; grid-template-columns:1.2fr repeat(4,1fr) auto; gap:10px; align-items:end; }
        .field label { display:block; margin-bottom:6px; font-size:.85rem; color:var(--muted); font-weight:600; }
        .field input,.field select { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.9rem; }
        .btn { border:1px solid var(--primary); border-radius:10px; background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#fff; padding:10px 12px; font-weight:700; text-decoration:none; cursor:pointer; }
        .cards { display:grid; gap:10px; }
        .student-card { border:1px solid var(--border); border-radius:12px; background:rgba(15,23,42,.62); padding:12px; }
        .card-head { display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; }
        .badge { display:inline-block; border-radius:999px; border:1px solid var(--border); padding:4px 8px; font-size:.76rem; font-weight:700; text-transform:uppercase; }
        .detail-grid { margin-top:8px; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .detail-box { border:1px solid var(--border); border-radius:10px; background:rgba(30,41,59,.4); padding:8px; font-size:.88rem; }
        .detail-box .label { color:var(--muted); font-size:.8rem; display:block; margin-bottom:4px; }
        .pagination { margin-top:12px; }
        @media (max-width:1100px){ .filters{grid-template-columns:1fr 1fr;} .app-shell{grid-template-columns:1fr;} .sidebar{position:static; height:auto;} .main{padding-top:0;} .detail-grid{grid-template-columns:1fr;} }
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
        <aside class="sidebar">
            <div class="sidebar-brand">{{ config('app.name', 'Kips') }} - Super Admin</div>
            <nav class="sidebar-nav" aria-label="Super admin menu">
                <a href="{{ route('dashboard.super-admin') }}">Dashboard</a>
                <a href="{{ route('dashboard.super-admin.checkins') }}">All Check-ins</a>
                <a class="active" href="{{ route('dashboard.super-admin.weekly-journals') }}">All Weekly Journals</a>
                <a href="{{ route('dashboard.super-admin.completion') }}">All Completion Bars</a>
                <a href="{{ route('dashboard.super-admin.mass-edit') }}">Implementation Timeline</a>
                <a href="{{ route('dashboard.super-admin.companies') }}">Companies</a>
                <a href="{{ route('dashboard.super-admin.users') }}">Users</a>
                <a href="{{ route('dashboard.super-admin.permissions') }}">Permissions</a>
                <a href="{{ route('dashboard.super-admin.activities') }}">Activities</a>
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
            <section class="topbar">
                <h1>All Student Weekly Journals</h1>
                <p class="muted">Filtered by week, major, class, and status.</p>
            </section>

            <section class="card">
                <form method="GET" action="{{ route('dashboard.super-admin.weekly-journals') }}">
                    <div class="filters">
                        <div class="field">
                            <label for="q">Search Student</label>
                            <input id="q" name="q" value="{{ $search }}" placeholder="Name or NIS">
                        </div>
                        <div class="field">
                            <label for="major">Major</label>
                            <select id="major" name="major">
                                @foreach (($majorOptions ?? collect(['ALL'])) as $major)
                                    <option value="{{ $major }}" {{ (string) $selectedMajor === (string) $major ? 'selected' : '' }}>{{ $major }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="class">Class</label>
                            <select id="class" name="class">
                                @foreach (($classOptions ?? collect(['ALL'])) as $class)
                                    <option value="{{ $class }}" {{ (string) $selectedClass === (string) $class ? 'selected' : '' }}>{{ $class }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                @php $statusOptions = ['all' => 'All', 'submitted' => 'Submitted', 'approved' => 'Approved', 'needs_revision' => 'Needs Revision', 'draft' => 'Draft', 'no_submission' => 'No Submission']; @endphp
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ (string) $statusFilter === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="week_start">Week Start</label>
                            <input id="week_start" name="week_start" type="date" value="{{ $selectedWeekStart }}">
                        </div>
                        <button class="btn" type="submit">Apply</button>
                    </div>
                </form>
                <p class="muted" style="margin-top:8px;">Week range: {{ \Illuminate\Support\Carbon::parse($selectedWeekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($selectedWeekEnd, 'Asia/Jakarta')->format('d M Y') }}</p>
            </section>

            <section class="card">
                <div class="cards">
                    @forelse (($students ?? collect()) as $student)
                        <article class="student-card">
                            <div class="card-head">
                                <div>
                                    <strong>{{ $student->student_name }}</strong>
                                    <div class="muted">NIS: {{ $student->student_nis ?? '-' }} | {{ $student->major_name }} | {{ $student->class_name }}</div>
                                </div>
                                <span class="badge">{{ $student->journal_status ? strtoupper($student->journal_status) : 'NO SUBMISSION' }}</span>
                            </div>
                            @if ($student->journal_id)
                                <div class="detail-grid">
                                    <div class="detail-box"><span class="label">Learning Notes</span>{{ $student->learning_notes ?: '-' }}</div>
                                    <div class="detail-box"><span class="label">Student Notes</span>{{ $student->student_mentor_notes ?: '-' }}</div>
                                    <div class="detail-box"><span class="label">Mentor Validation</span>{{ is_null($student->mentor_is_correct) ? '-' : ($student->mentor_is_correct ? 'Correct' : 'Not Complete') }}</div>
                                    <div class="detail-box"><span class="label">Missing Info</span>{{ $student->missing_info_notes ?: '-' }}</div>
                                    <div class="detail-box"><span class="label">Kajur Notes</span>{{ $student->kajur_notes ?: '-' }}</div>
                                    <div class="detail-box"><span class="label">Bindo Notes</span>{{ $student->bindo_notes ?: '-' }}</div>
                                </div>
                                <div class="muted" style="margin-top:8px;">Reviewers: Mentor {{ $student->mentor_name ?? '-' }}, Kajur {{ $student->kajur_name ?? '-' }}, Bindo {{ $student->bindo_name ?? '-' }}</div>
                            @else
                                <p class="muted" style="margin-top:8px;">No weekly journal submitted for this week.</p>
                            @endif
                        </article>
                    @empty
                        <p class="muted">No students found for this filter.</p>
                    @endforelse
                </div>
                <div class="pagination">{{ $students->links() }}</div>
            </section>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])
</body>
</html>
