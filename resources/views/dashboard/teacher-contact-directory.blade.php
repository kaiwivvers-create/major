<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Directory - {{ config('app.name', 'Kips') }}</title>
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
        .main { padding:20px; display:grid; gap:14px; }
        .topbar, .card { border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding:14px; }
        .topbar h1 { font-size:1.1rem; margin-bottom:4px; }
        .muted { color:var(--muted); font-size:.9rem; line-height:1.45; }
        .summary-grid { margin-top:10px; display:grid; gap:8px; grid-template-columns:repeat(4, minmax(0,1fr)); }
        .summary-item { border:1px solid rgba(148,163,184,.24); border-radius:10px; background:rgba(15,23,42,.55); padding:10px; }
        .summary-item .k { color:var(--muted); font-size:.75rem; }
        .summary-item .v { margin-top:2px; font-size:1rem; font-weight:700; }
        .toolbar { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; flex-wrap:wrap; }
        .search { width:min(340px,100%); border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.75); color:var(--text); padding:9px 12px; font-size:.9rem; }
        .table-wrap { border:1px solid rgba(148,163,184,.24); border-radius:12px; overflow:auto; background:rgba(15,23,42,.45); }
        .table { width:100%; min-width:940px; border-collapse:collapse; }
        .table th, .table td { border-bottom:1px solid rgba(148,163,184,.2); text-align:left; padding:10px 12px; font-size:.88rem; vertical-align:top; }
        .table th { color:#cbd5e1; font-size:.76rem; text-transform:uppercase; letter-spacing:.05em; background:rgba(15,23,42,.78); }
        .cell-title { font-weight:700; color:#f8fafc; }
        .actions { display:flex; gap:6px; flex-wrap:wrap; }
        .link-btn { text-decoration:none; border:1px solid rgba(56,189,248,.45); border-radius:999px; background:rgba(14,165,233,.16); color:#bae6fd; padding:6px 10px; font-size:.78rem; font-weight:700; }
        .link-btn:hover { border-color:rgba(56,189,248,.8); background:rgba(14,165,233,.25); }
        .empty { min-height:240px; border:1px dashed rgba(56,189,248,.28); border-radius:12px; display:grid; place-items:center; text-align:center; }
        .mobile-list { display:none; gap:8px; }
        .mobile-item { border:1px solid rgba(148,163,184,.24); border-radius:12px; background:rgba(15,23,42,.5); padding:10px; display:grid; gap:6px; }
        .mobile-head { display:flex; justify-content:space-between; gap:8px; }
        .badge { border:1px solid rgba(148,163,184,.32); border-radius:999px; padding:2px 8px; font-size:.75rem; color:#cbd5e1; }
        @media (max-width:980px) {
            .app-shell { grid-template-columns:1fr; }
            .sidebar { position:static; height:auto; }
            .summary-grid { grid-template-columns:repeat(2, minmax(0,1fr)); }
            .table-wrap { display:none; }
            .mobile-list { display:grid; }
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
    @endphp

    <div class="app-shell">
        @include('dashboard.partials.teacher-sidebar', ['user' => $user, 'activePage' => 'contact-directory'])

        <main class="main">
            <header class="topbar">
                <h1>Contact Directory</h1>
                <p class="muted">Access mentor and company contact references for teacher communication workflows.</p>
                <div class="summary-grid">
                    <div class="summary-item"><div class="k">Supervised Students</div><div class="v">{{ $summary['total_students'] ?? 0 }}</div></div>
                    <div class="summary-item"><div class="k">Company References</div><div class="v">{{ $summary['company_count'] ?? 0 }}</div></div>
                    <div class="summary-item"><div class="k">WhatsApp Ready</div><div class="v">{{ $summary['students_with_whatsapp'] ?? 0 }}</div></div>
                    <div class="summary-item"><div class="k">Missing Contact</div><div class="v">{{ $summary['students_without_contact'] ?? 0 }}</div></div>
                </div>
            </header>

            <section class="card">
                @if ($students->isEmpty())
                    <div class="empty">
                        <div>
                            <strong>No supervised students found.</strong>
                            <p class="muted" style="margin-top:6px;">Students assigned to this teacher will appear here automatically.</p>
                        </div>
                    </div>
                @else
                    <div class="toolbar">
                        <p class="muted">Use search to filter by student, class, company, or contact person.</p>
                        <input id="contact-search" class="search" type="search" placeholder="Search contact directory..." autocomplete="off">
                    </div>

                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Company</th>
                                    <th>Mentor Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="contact-table-body">
                                @foreach ($students as $student)
                                    @php
                                        $website = trim((string) ($student->website_url ?? ''));
                                        if ($website !== '' && !\Illuminate\Support\Str::startsWith($website, ['http://', 'https://'])) {
                                            $website = 'https://' . $website;
                                        }
                                    @endphp
                                    <tr data-search-row>
                                        <td>
                                            <div class="cell-title">{{ $student->name }}</div>
                                            <div class="muted">NIS: {{ $student->nis ?? '-' }}</div>
                                        </td>
                                        <td>{{ $student->class_name }}</td>
                                        <td>
                                            <div class="cell-title">{{ $student->company_name }}</div>
                                            <div class="muted">{{ $student->company_address }}</div>
                                        </td>
                                        <td>
                                            <div class="cell-title">{{ $student->contact_person !== '' ? $student->contact_person : '-' }}</div>
                                            <div class="muted">{{ trim((string) ($student->contact_phone ?? '')) !== '' ? $student->contact_phone : 'No phone number' }}</div>
                                            <div class="muted">{{ trim((string) ($student->contact_email ?? '')) !== '' ? $student->contact_email : 'No email address' }}</div>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                @if (!empty($student->whatsapp_url))
                                                    <a class="link-btn" href="{{ $student->whatsapp_url }}" target="_blank" rel="noopener noreferrer">Open WhatsApp</a>
                                                @endif
                                                @if (trim((string) ($student->contact_phone ?? '')) !== '')
                                                    <a class="link-btn" href="tel:{{ preg_replace('/\D+/', '', (string) $student->contact_phone) }}">Call</a>
                                                @endif
                                                @if (trim((string) ($student->contact_email ?? '')) !== '')
                                                    <a class="link-btn" href="mailto:{{ $student->contact_email }}">Email</a>
                                                @endif
                                                @if ($website !== '')
                                                    <a class="link-btn" href="{{ $website }}" target="_blank" rel="noopener noreferrer">Website</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mobile-list" id="contact-mobile-list">
                        @foreach ($students as $student)
                            @php
                                $website = trim((string) ($student->website_url ?? ''));
                                if ($website !== '' && !\Illuminate\Support\Str::startsWith($website, ['http://', 'https://'])) {
                                    $website = 'https://' . $website;
                                }
                            @endphp
                            <article class="mobile-item" data-search-row>
                                <div class="mobile-head">
                                    <div>
                                        <div class="cell-title">{{ $student->name }}</div>
                                        <div class="muted">NIS {{ $student->nis ?? '-' }}</div>
                                    </div>
                                    <span class="badge">{{ $student->class_name }}</span>
                                </div>
                                <div class="muted"><strong style="color:#e2e8f0;">Company:</strong> {{ $student->company_name }}</div>
                                <div class="muted">{{ $student->company_address }}</div>
                                <div class="muted"><strong style="color:#e2e8f0;">Contact:</strong> {{ $student->contact_person !== '' ? $student->contact_person : '-' }}</div>
                                <div class="muted">Phone: {{ trim((string) ($student->contact_phone ?? '')) !== '' ? $student->contact_phone : '-' }}</div>
                                <div class="muted">Email: {{ trim((string) ($student->contact_email ?? '')) !== '' ? $student->contact_email : '-' }}</div>
                                <div class="actions">
                                    @if (!empty($student->whatsapp_url))
                                        <a class="link-btn" href="{{ $student->whatsapp_url }}" target="_blank" rel="noopener noreferrer">Open WhatsApp</a>
                                    @endif
                                    @if (trim((string) ($student->contact_phone ?? '')) !== '')
                                        <a class="link-btn" href="tel:{{ preg_replace('/\D+/', '', (string) $student->contact_phone) }}">Call</a>
                                    @endif
                                    @if (trim((string) ($student->contact_email ?? '')) !== '')
                                        <a class="link-btn" href="mailto:{{ $student->contact_email }}">Email</a>
                                    @endif
                                    @if ($website !== '')
                                        <a class="link-btn" href="{{ $website }}" target="_blank" rel="noopener noreferrer">Website</a>
                                    @endif
                                </div>
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
            const search = document.getElementById('contact-search');
            if (!search) return;

            const rows = Array.from(document.querySelectorAll('[data-search-row]'));
            const normalize = (value) => (value || '').toLowerCase();

            const applySearch = () => {
                const q = normalize(search.value.trim());
                rows.forEach((row) => {
                    const text = normalize(row.textContent || '');
                    row.style.display = q === '' || text.includes(q) ? '' : 'none';
                });
            };

            search.addEventListener('input', applySearch);
        })();
    </script>

    @include('partials.chatbot')
</body>
</html>

