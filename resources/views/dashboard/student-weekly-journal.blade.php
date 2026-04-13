<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weekly Journal - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --surface:#1e293b; --primary:#2563eb; --accent:#38bdf8; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); background:radial-gradient(1000px 500px at 10% -10%, rgba(56, 189, 248, 0.2), transparent),radial-gradient(900px 450px at 100% 10%, rgba(37, 99, 235, 0.2), transparent),var(--bg); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:270px 1fr; }
        .sidebar { position:sticky; top:0; height:100vh; display:flex; flex-direction:column; border-right:1px solid var(--border); background:rgba(15,23,42,.92); backdrop-filter:blur(10px); padding:16px 14px; }
        .sidebar-brand { font-weight:700; letter-spacing:.02em; padding:8px 10px; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.45); margin-bottom:14px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:8px; }
        .sidebar-nav a { text-decoration:none; color:var(--text); border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:rgba(30,41,59,.6); font-weight:500; transition:all .2s ease; }
        .sidebar-nav a:hover { border-color:var(--accent); color:var(--accent); }
        .sidebar-nav a.active { border-color:var(--primary); background:linear-gradient(135deg, rgba(37, 99, 235, 0.32), rgba(29, 78, 216, 0.32)); color:#f8fafc; font-weight:700; }
        .sidebar-profile { margin-top:auto; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.55); padding:12px; }
        .profile-trigger { width:100%; text-align:left; appearance:none; border:1px solid var(--border); border-radius:12px; background:rgba(15,23,42,.52); color:var(--text); cursor:pointer; padding:10px; display:grid; grid-template-columns:42px 1fr 18px; align-items:center; gap:10px; }
        .profile-avatar { width:42px; height:42px; border-radius:999px; border:1px solid rgba(56,189,248,.55); background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24)); display:grid; place-items:center; font-size:.82rem; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .profile-name { font-weight:700; margin-bottom:2px; }
        .profile-meta { font-size:.85rem; color:var(--muted); }
        .profile-arrow { color:var(--muted); font-size:1rem; text-align:right; }
        .profile-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.62); display:none; align-items:center; justify-content:center; z-index:2200; padding:16px; }
        .profile-modal-backdrop.open { display:flex; }
        .profile-modal-panel { width:min(560px,96vw); border:1px solid var(--border); border-radius:16px; background:linear-gradient(160deg, rgba(30,41,59,.96), rgba(15,23,42,.96)); padding:16px; }
        .profile-modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .profile-modal-close, .profile-modal-btn { border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:8px 12px; cursor:pointer; font-weight:600; }
        .profile-modal-btn.primary { border-color:var(--primary); background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#f8fafc; }
        .profile-modal-field { margin-bottom:12px; }
        .profile-modal-field label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; }
        .profile-modal-field input { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.95rem; }
        .profile-modal-field input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(56,189,248,.15); }
        .profile-modal-actions { margin-top:14px; display:flex; justify-content:flex-end; gap:8px; }
        .profile-modal-alert.error { border:1px solid rgba(248,113,113,.6); border-radius:12px; padding:10px 12px; background:rgba(127,29,29,.25); margin-bottom:12px; }
        .content { padding:20px; }
        .topbar, .card { border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.86); padding:14px 16px; }
        .card { margin-top:12px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); }
        .muted { color:var(--muted); font-size:.92rem; }
        .alert { border:1px solid rgba(56,189,248,.45); border-radius:12px; padding:10px 12px; background:rgba(14,165,233,.12); margin-top:12px; }
        .alert.error { border-color:rgba(248,113,113,.6); background:rgba(127,29,29,.25); }
        .field { margin-top:10px; }
        .field label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; }
        .field textarea { width:100%; min-height:120px; border:1px solid var(--border); border-radius:12px; background:rgba(15,23,42,.65); color:var(--text); padding:10px 12px; font-family:inherit; resize:vertical; }
        .field textarea:focus { outline:none; border-color:rgba(56,189,248,.9); box-shadow:0 0 0 3px rgba(56,189,248,.15); }
        .btn { margin-top:12px; border:1px solid var(--primary); border-radius:10px; padding:10px 14px; color:#f8fafc; background:linear-gradient(135deg,var(--primary),#1d4ed8); font-weight:700; cursor:pointer; }
        .info-grid { margin-top:10px; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .pill { display:inline-flex; border-radius:999px; padding:4px 10px; font-size:.8rem; font-weight:700; border:1px solid var(--border); margin-bottom:6px; }
        @media (max-width:900px) { .app-shell { grid-template-columns:1fr; } .sidebar { position:static; height:auto; } .content { padding-top:0; } .info-grid { grid-template-columns:1fr; } }
    
        @keyframes page-drift-up {
            from {
                opacity: 0;
                transform: translateY(22px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content.page-drift-up {
            animation: page-drift-up 0.7s ease-out both;
        }
    
        /* Themed scrollbar */
        * {
            scrollbar-width: thin;
            scrollbar-color: #38bdf8 #0f172a;
        }

        *::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        *::-webkit-scrollbar-track {
            background: #0f172a;
        }

        *::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #38bdf8, #2563eb);
            border: 2px solid #0f172a;
            border-radius: 999px;
        }

        *::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #67e8f9, #3b82f6);
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
    @endphp
    <div class="app-shell">
        @include('dashboard.partials.student-sidebar', ['user' => $user, 'activePage' => 'journal'])
        <main class="content page-drift-up">
            <header class="topbar">
                <h1>Weekly Journal</h1>
                <p class="muted">Fill in your weekly internship activities and notes shared by your mentor.</p>
                <div class="info-grid">
                    <div><strong>Week Start:</strong> {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }}</div>
                    <div><strong>Week End:</strong> {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</div>
                </div>
            </header>

            @if (session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert error">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <section class="card">
                <form method="POST" action="{{ route('dashboard.student.weekly-journal.save') }}">
                    @csrf
                    <div class="field">
                        <label for="learning_notes">What you did during internship this week</label>
                        <textarea id="learning_notes" name="learning_notes" required>{{ old('learning_notes', $weeklyJournal->learning_notes ?? '') }}</textarea>
                    </div>
                    <div class="field">
                        <label for="student_mentor_notes">Student notes (what the mentor told you)</label>
                        <textarea id="student_mentor_notes" name="student_mentor_notes" required>{{ old('student_mentor_notes', $weeklyJournal->student_mentor_notes ?? '') }}</textarea>
                    </div>
                    <button class="btn" type="submit">Submit Weekly Journal</button>
                </form>
            </section>

            <section class="card">
                <h2>Validation</h2>
                <span class="pill">
                    {{
                        ($weeklyJournal->status ?? 'draft') === 'approved'
                            ? 'Approved'
                            : ((($weeklyJournal->status ?? 'draft') === 'needs_revision')
                                ? 'Needs Revision'
                                : ((($weeklyJournal->status ?? 'draft') === 'submitted') ? 'Submitted' : 'Draft'))
                    }}
                </span>
                <div class="info-grid">
                    <div><strong>Mentor Check:</strong> {{ is_null($weeklyJournal->mentor_is_correct ?? null) ? '-' : (($weeklyJournal->mentor_is_correct ?? false) ? 'Correct' : 'Not Complete') }}</div>
                    <div><strong>Missing Info:</strong> {{ $weeklyJournal->missing_info_notes ?? '-' }}</div>
                    <div><strong>Kajur Notes:</strong> {{ $weeklyJournal->kajur_notes ?? '-' }}</div>
                    <div><strong>Indonesian Teacher Notes:</strong> {{ $weeklyJournal->bindo_notes ?? '-' }}</div>
                </div>
            </section>
        </main>
    </div>
    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal])
</body>
</html>

