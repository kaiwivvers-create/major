<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Journal Review & Language Audit - {{ config('app.name', 'Kips') }}</title>
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
        .alert { border:1px solid rgba(56,189,248,.45); border-radius:10px; padding:10px 12px; margin-bottom:10px; background:rgba(14,165,233,.12); }
        .journal-feed { display:grid; gap:10px; }
        .journal-item { border:1px solid rgba(148,163,184,.24); border-radius:12px; background:rgba(15,23,42,.55); padding:12px; }
        .journal-top { display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; margin-bottom:8px; }
        .journal-student { font-weight:700; }
        .badge { font-size:.75rem; border:1px solid rgba(148,163,184,.5); color:#cbd5e1; border-radius:999px; padding:2px 8px; }
        .notes { display:grid; gap:6px; margin:8px 0; font-size:.9rem; line-height:1.45; }
        .notes-item { color:#dbeafe; }
        .notes-item strong { color:#f8fafc; }
        .actions { display:flex; justify-content:flex-end; }
        .btn { border:1px solid rgba(56,189,248,.45); border-radius:10px; background:rgba(14,165,233,.14); color:#bae6fd; padding:7px 10px; font-weight:700; font-size:.84rem; cursor:pointer; }
        .btn:hover { border-color:rgba(56,189,248,.8); background:rgba(14,165,233,.24); }
        .comment-form { display:none; margin-top:10px; border-top:1px solid rgba(148,163,184,.2); padding-top:10px; }
        .comment-form.open { display:block; }
        textarea { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:9px 10px; font-family:inherit; font-size:.92rem; min-height:100px; resize:vertical; }
        .form-actions { margin-top:8px; display:flex; justify-content:flex-end; }
        .empty { min-height:220px; border:1px dashed rgba(56,189,248,.28); border-radius:12px; display:grid; place-items:center; text-align:center; }
        .main { animation: page-drift-up 0.7s ease-out both; }
        .profile-modal-backdrop.open .profile-modal-panel { animation: page-drift-up 0.7s ease-out 0.15s both; }
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
        @include('dashboard.partials.teacher-sidebar', ['user' => $user, 'activePage' => 'journal-audit'])

        <main class="main">
            <header class="topbar">
                <h1>Journal Review & Language Audit</h1>
                <p class="muted">For Guru Bindo or general supervisor focus: review writing quality, clarity, spelling, punctuation, and sentence structure.</p>
                <p class="muted" style="margin-top:6px;">Example note: "Tolong perbaiki penggunaan huruf kapital dan jelaskan lebih detail alat yang digunakan."</p>
            </header>

            <section class="card">
                @if (session('status'))
                    <div class="alert">{{ session('status') }}</div>
                @endif

                @if ($rows->isEmpty())
                    <div class="empty">
                        <div>
                            <strong>No weekly journals found yet.</strong>
                            <p class="muted" style="margin-top:6px;">Submitted journals from your supervised students will appear here.</p>
                        </div>
                    </div>
                @else
                    <div class="journal-feed">
                        @foreach ($rows as $row)
                            <article class="journal-item">
                                <div class="journal-top">
                                    <div>
                                        <div class="journal-student">{{ $row->student_name }}</div>
                                        <div class="muted">NIS: {{ $row->student_nis ?? '-' }} &middot; Class: {{ $row->class_name }}</div>
                                    </div>
                                    <div style="text-align:right;">
                                        <span class="badge">{{ strtoupper((string) ($row->status ?? 'draft')) }}</span>
                                        <div class="muted" style="margin-top:4px;">
                                            {{ \Illuminate\Support\Carbon::parse($row->week_start_date, 'Asia/Jakarta')->format('d M') }} - {{ \Illuminate\Support\Carbon::parse($row->week_end_date, 'Asia/Jakarta')->format('d M Y') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="notes">
                                    <div class="notes-item"><strong>Student Learning Notes:</strong> {{ $row->learning_notes ?: '-' }}</div>
                                    <div class="notes-item"><strong>Mentor Technical Feedback:</strong> {{ $row->student_mentor_notes ?: '-' }}</div>
                                    <div class="notes-item"><strong>Teacher Language/Writing Note:</strong> {{ $row->bindo_notes ?: '-' }}</div>
                                </div>

                                <div class="actions">
                                    <button type="button" class="btn" data-toggle-comment="{{ $row->id }}">Add Comment</button>
                                </div>

                                <form method="POST" action="{{ route('dashboard.bindo.weekly-journal.note', $row->id) }}" class="comment-form {{ old('journal_id') == $row->id ? 'open' : '' }}" id="comment-form-{{ $row->id }}">
                                    @csrf
                                    <input type="hidden" name="journal_id" value="{{ $row->id }}">
                                    <textarea name="teacher_comment" placeholder="Write writing-quality note...">{{ old('journal_id') == $row->id ? old('teacher_comment') : ($row->bindo_notes ?? '') }}</textarea>
                                    <div class="form-actions">
                                        <button type="submit" class="btn">Save Comment</button>
                                    </div>
                                </form>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])
    <script>
        (() => {
            document.querySelectorAll('[data-toggle-comment]').forEach((button) => {
                button.addEventListener('click', () => {
                    const journalId = button.getAttribute('data-toggle-comment');
                    const target = document.getElementById(`comment-form-${journalId}`);
                    if (!target) return;
                    target.classList.toggle('open');
                });
            });
        })();
    </script>

    @include('partials.chatbot')
</body>
</html>

