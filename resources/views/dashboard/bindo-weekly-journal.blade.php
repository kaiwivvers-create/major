<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher Dashboard - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root {
            --bg:#0f172a;
            --bg-soft:#111f3b;
            --panel:#16223f;
            --border:#334155;
            --primary:#2563eb;
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
            padding:16px;
        }
        .shell { max-width:1300px; margin:0 auto; display:grid; gap:14px; }
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
        .profile-trigger { appearance:none; border:1px solid var(--border); border-radius:12px; color:var(--text); background:rgba(15,23,42,0.52); cursor:pointer; padding:8px 10px; display:grid; grid-template-columns:40px 1fr 16px; align-items:center; gap:8px; min-width:230px; text-align:left; }
        .profile-trigger:hover { border-color:var(--primary); }
        .profile-avatar { width:40px; height:40px; border-radius:999px; border:1px solid rgba(56, 189, 248, 0.55); background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24)); display:grid; place-items:center; font-size:.8rem; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .profile-name { font-weight:700; margin-bottom:2px; }
        .profile-meta { font-size:.8rem; color:var(--muted); }
        .profile-arrow { color:var(--muted); font-size:.95rem; text-align:right; }
        @media (max-width:980px) {
            .grid-main { grid-template-columns:1fr; }
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

    <div class="shell">
        <section class="card">
            <div class="page-head">
                <div>
                    <h1>Teacher Dashboard</h1>
                    <p class="muted">Assigned students, weekly journal review, site-visit proof, and mentor contact shortcuts.</p>
                </div>
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

            @if (session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif
            @if ($errors->has('site_visit'))
                <div class="alert error">{{ $errors->first('site_visit') }}</div>
            @endif
        </section>

        <section class="card">
            <div class="section-title">Assigned Student List</div>
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

        <div class="grid-main">
            <section class="card">
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

            <section class="card">
                <div class="section-title">Visit Log</div>
                @if (!$visitLogReady)
                    <p class="muted">Visit log table is not ready yet. Run migrations to enable site-visit records.</p>
                @else
                    <form method="POST" action="{{ route('dashboard.bindo.site-visit.store') }}" enctype="multipart/form-data" class="visit-form">
                        @csrf
                        <label class="muted">Student</label>
                        <select name="student_id" required>
                            @foreach ($assignedStudents as $student)
                                <option value="{{ $student->id }}" @selected((int) old('student_id', $selectedStudentId) === (int) $student->id)>
                                    {{ $student->name }} ({{ $student->nis ?? '-' }})
                                </option>
                            @endforeach
                        </select>

                        <label class="muted">Visit Date</label>
                        <input type="date" name="visited_at" value="{{ old('visited_at', $today) }}" max="{{ $today }}" required>

                        <label class="muted">Proof Photo (teacher + student at company)</label>
                        <input type="file" name="visit_photo" accept="image/png,image/jpeg,image/webp" required>

                        <label class="muted">Visit Notes (optional)</label>
                        <textarea name="visit_notes" placeholder="Short note about what was checked...">{{ old('visit_notes') }}</textarea>

                        <button class="btn" type="submit">Record Site Visit</button>
                    </form>
                @endif

                <div class="section-title" style="margin-top:12px;">Recent Visit Records</div>
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
                                @if (!empty($visit->visit_notes))
                                    <div style="margin-top:4px; font-size:.9rem;">{{ $visit->visit_notes }}</div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="muted">No visit records yet.</p>
                    @endforelse
                </div>

                <div class="section-title" style="margin-top:14px;">Direct Contact</div>
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
            </section>
        </div>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])
</body>
</html>
