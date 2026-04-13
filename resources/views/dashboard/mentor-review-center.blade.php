<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mentor Review Center - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --surface:#1e293b; --primary:#2563eb; --accent:#38bdf8; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; --ok:#22c55e; --warn:#f59e0b; }
        * { box-sizing: border-box; margin:0; padding:0; }
        body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); background:radial-gradient(1000px 500px at 10% -10%, rgba(56,189,248,.2), transparent), var(--bg); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:270px 1fr; }
        .sidebar { position:sticky; top:0; height:100vh; display:flex; flex-direction:column; border-right:1px solid var(--border); background:rgba(15,23,42,.92); padding:16px 14px; }
        .sidebar-brand { font-weight:700; padding:8px 10px; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.45); margin-bottom:14px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:8px; }
        .sidebar-nav a { text-decoration:none; color:var(--text); border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:rgba(30,41,59,.6); font-weight:500; }
        .sidebar-nav a.active { border-color:var(--primary); background:linear-gradient(135deg, rgba(37,99,235,.32), rgba(29,78,216,.32)); color:#f8fafc; font-weight:700; }
        .sidebar-profile { margin-top:auto; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.55); padding:12px; }
        .profile-trigger { width:100%; text-align:left; appearance:none; border:1px solid var(--border); border-radius:12px; color:var(--text); background:rgba(15,23,42,.52); cursor:pointer; padding:10px; display:grid; grid-template-columns:42px 1fr 18px; align-items:center; gap:10px; }
        .profile-avatar { width:42px; height:42px; border-radius:999px; border:1px solid rgba(56,189,248,.55); background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24)); display:grid; place-items:center; font-size:.82rem; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; } .profile-name { font-weight:700; margin-bottom:2px; } .profile-meta { font-size:.85rem; color:var(--muted); } .profile-arrow { color:var(--muted); text-align:right; }
        .main { padding:20px; }
        .topbar,.card { border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding:14px; }
        .topbar { margin-bottom:12px; } .topbar h1 { font-size:1.12rem; margin-bottom:3px; }
        .muted { color:var(--muted); font-size:.92rem; }
        .alert { border:1px solid rgba(56,189,248,.45); border-radius:10px; padding:8px 10px; margin:10px 0; background:rgba(14,165,233,.12); }
        .alert.error { border-color:rgba(248,113,113,.6); background:rgba(127,29,29,.25); }
        .stack { display:grid; gap:12px; }
        .head { display:flex; justify-content:space-between; align-items:baseline; gap:8px; margin-bottom:8px; }
        .table-wrap { overflow:auto; border:1px solid var(--border); border-radius:12px; margin-top:8px; }
        table { width:100%; border-collapse:collapse; min-width:980px; }
        th,td { text-align:left; padding:9px; border-bottom:1px solid var(--border); vertical-align:top; font-size:.88rem; }
        th { background:rgba(15,23,42,.92); color:#cbd5e1; font-weight:700; }
        select,textarea,input[type=number] { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:8px 10px; font-family:inherit; font-size:.88rem; }
        textarea { resize:vertical; min-height:70px; }
        .grid-5 { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:6px; }
        .btn { border:1px solid var(--border); border-radius:10px; color:var(--text); background:rgba(15,23,42,.7); padding:7px 10px; font-size:.84rem; font-weight:600; cursor:pointer; }
        .btn.ok { border-color:rgba(34,197,94,.85); color:#86efac; background:rgba(21,128,61,.22); }
        .btn:disabled { opacity:.55; cursor:not-allowed; }
        .badge { display:inline-flex; border:1px solid var(--border); border-radius:999px; padding:2px 8px; font-size:.78rem; font-weight:700; }
        .badge.ok { border-color:rgba(34,197,94,.8); color:#86efac; } .badge.warn { border-color:rgba(245,158,11,.8); color:#fcd34d; }
        .profile-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.62); display:none; align-items:center; justify-content:center; z-index:2200; padding:16px; }
        .profile-modal-backdrop.open { display:flex; }
        .profile-modal-panel { width:min(560px,96vw); border:1px solid var(--border); border-radius:16px; background:linear-gradient(160deg, rgba(30,41,59,.96), rgba(15,23,42,.96)); padding:16px; max-height:92vh; overflow:auto; }
        .profile-modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .profile-modal-close,.profile-modal-btn { border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:8px 12px; cursor:pointer; font-weight:600; }
        .profile-modal-btn.primary { border-color:var(--primary); background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#f8fafc; }
        .profile-modal-field { margin-bottom:12px; } .profile-modal-field label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; }
        .profile-modal-field input { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.95rem; }
        .profile-modal-actions { margin-top:14px; display:flex; justify-content:flex-end; gap:8px; }
        .profile-modal-alert.error { border:1px solid rgba(248,113,113,.6); border-radius:12px; padding:10px 12px; background:rgba(127,29,29,.25); margin-bottom:12px; }
        @media (max-width:1100px){ .app-shell{grid-template-columns:1fr;} .sidebar{position:static;height:auto;} .main{padding-top:0;} .grid-5{grid-template-columns:repeat(2,minmax(0,1fr));} }
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
        <nav class="sidebar-nav" aria-label="Mentor menu">
            @if ($canViewMentorDashboard)
                <a href="{{ route('dashboard.mentor.weekly-journal') }}">Mentor Dashboard</a>
                <a href="{{ route('dashboard.mentor.company-settings-page') }}">Company Profile & Proximity</a>
            @endif
            @if ($canViewMentorReviewCenter)
                <a class="active" href="{{ route('dashboard.mentor.review-center') }}">Daily Scoring + Weekly Review</a>
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
            <header class="topbar">
                <h1>Mentor Review Center</h1>
                <p class="muted">Daily scoring only works after check-out + completed work realization. Week: {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</p>
            </header>

            @if (session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="stack">
                <section class="card">
                    <div class="head">
                        <h2>1. Daily Scoring</h2>
                        <span class="muted">Score attitude + performance (1-5)</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Completion Status</th>
                                    <th>Activity</th>
                                    <th>Score Input</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($dailyScoreRows ?? collect()) as $row)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($row->work_date, 'Asia/Jakarta')->format('d M Y') }}</td>
                                        <td>{{ $row->student_name }}<br><span class="muted">NIS: {{ $row->student_nis }}</span></td>
                                        <td>
                                            <span class="badge {{ $row->is_completed ? 'ok' : 'warn' }}">
                                                {{ $row->is_completed ? 'DONE' : 'NOT DONE' }}
                                            </span>
                                            <div class="muted" style="margin-top:4px;">
                                                In: {{ $row->check_in_at ? \Illuminate\Support\Carbon::parse($row->check_in_at, 'Asia/Jakarta')->format('H:i') : '-' }} |
                                                Out: {{ $row->check_out_at ? \Illuminate\Support\Carbon::parse($row->check_out_at, 'Asia/Jakarta')->format('H:i') : '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ $row->title ?? 'Daily Log' }}</strong><br>
                                            <span class="muted">{{ \Illuminate\Support\Str::limit((string) ($row->work_realization ?: $row->description), 180) }}</span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('dashboard.mentor.daily-scoring.save', $row->id) }}">
                                                @csrf
                                                <div class="grid-5">
                                                    <select name="score_smile" {{ $row->is_completed ? '' : 'disabled' }} required>
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <option value="{{ $i }}" {{ (int) ($row->score_smile ?? 0) === $i ? 'selected' : '' }}>Smile {{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                    <select name="score_friendliness" {{ $row->is_completed ? '' : 'disabled' }} required>
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <option value="{{ $i }}" {{ (int) ($row->score_friendliness ?? 0) === $i ? 'selected' : '' }}>Friendly {{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                    <select name="score_appearance" {{ $row->is_completed ? '' : 'disabled' }} required>
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <option value="{{ $i }}" {{ (int) ($row->score_appearance ?? 0) === $i ? 'selected' : '' }}>Appearance {{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                    <select name="score_communication" {{ $row->is_completed ? '' : 'disabled' }} required>
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <option value="{{ $i }}" {{ (int) ($row->score_communication ?? 0) === $i ? 'selected' : '' }}>Comms {{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                    <select name="score_work_realization" {{ $row->is_completed ? '' : 'disabled' }} required>
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <option value="{{ $i }}" {{ (int) ($row->score_work_realization ?? 0) === $i ? 'selected' : '' }}>Work {{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div style="margin-top:8px;">
                                                    <button class="btn ok" type="submit" {{ $row->is_completed ? '' : 'disabled' }}>Save Daily Score</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">No daily logs found for this week.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card">
                    <div class="head">
                        <h2>2. Weekly Journal Review</h2>
                        <span class="muted">Continue with weekly validation</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Journal</th>
                                    <th>Mentor Review</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($weeklyRows ?? collect()) as $row)
                                    <tr>
                                        <td>{{ $row->student_name }}<br><span class="muted">NIS: {{ $row->student_nis }}</span></td>
                                        <td>
                                            <div><strong>Activity:</strong> {{ $row->learning_notes }}</div>
                                            <div class="muted" style="margin-top:4px;"><strong>Student Notes:</strong> {{ $row->student_mentor_notes }}</div>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('dashboard.mentor.weekly-journal.review', $row->id) }}">
                                                @csrf
                                                <select name="mentor_is_correct" required>
                                                    <option value="1" {{ (string) ($row->mentor_is_correct ?? '') === '1' ? 'selected' : '' }}>Approve</option>
                                                    <option value="0" {{ (string) ($row->mentor_is_correct ?? '') === '0' ? 'selected' : '' }}>Needs Revision</option>
                                                </select>
                                                <textarea name="missing_info_notes" placeholder="If revision is needed, explain missing technical details...">{{ old('missing_info_notes', $row->missing_info_notes ?? '') }}</textarea>
                                                <textarea name="mentor_feedback_summary" placeholder="Weekly feedback summary...">{{ old('mentor_feedback_summary', $row->mentor_feedback_summary ?? '') }}</textarea>
                                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-top:6px;">
                                                    <select name="mentor_attitude_rating">
                                                        <option value="">Attitude</option>
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <option value="{{ $i }}" {{ (string) old('mentor_attitude_rating', $row->mentor_attitude_rating ?? '') === (string) $i ? 'selected' : '' }}>Attitude {{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                    <select name="mentor_skill_rating">
                                                        <option value="">Skill</option>
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <option value="{{ $i }}" {{ (string) old('mentor_skill_rating', $row->mentor_skill_rating ?? '') === (string) $i ? 'selected' : '' }}>Skill {{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div style="margin-top:8px;">
                                                    <button class="btn ok" type="submit">Save Weekly Review</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3">No weekly journals for this week.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])
</body>
</html>
