<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Company Detail - {{ config('app.name', 'Kips') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --primary: #2563eb;
            --accent: #38bdf8;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: #334155;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: 'Instrument Sans', sans-serif;
            color: var(--text);
            background:
                radial-gradient(1000px 500px at 10% -10%, rgba(56, 189, 248, 0.2), transparent),
                radial-gradient(900px 450px at 100% 10%, rgba(37, 99, 235, 0.2), transparent),
                var(--bg);
        }
        .app-shell { min-height: 100vh; display: grid; grid-template-columns: 270px 1fr; }
        .sidebar {
            position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column;
            border-right: 1px solid var(--border); background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(10px); padding: 16px 14px;
        }
        .sidebar-brand {
            font-weight: 700; letter-spacing: .02em; padding: 8px 10px;
            border: 1px solid var(--border); border-radius: 12px; background: rgba(30, 41, 59, .45); margin-bottom: 14px;
        }
        .sidebar-nav { display: flex; flex-direction: column; gap: 8px; }
        .sidebar-nav a {
            text-decoration: none; color: var(--text); border: 1px solid var(--border); border-radius: 10px;
            padding: 10px 12px; background: rgba(30, 41, 59, .6); font-weight: 500; transition: all .2s ease;
        }
        .sidebar-nav a:hover { border-color: var(--accent); color: var(--accent); }
        .sidebar-nav a.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.32), rgba(29, 78, 216, 0.32));
            color: #f8fafc;
            font-weight: 700;
        }
        .main { padding: 20px; }
        .topbar {
            border: 1px solid var(--border); border-radius: 14px; background: rgba(15, 23, 42, 0.9);
            padding: 14px 16px; margin-bottom: 14px;
        }
        .topbar h1 { font-size: 1.2rem; margin-bottom: 4px; }
        .muted { color: var(--muted); }
        .card { border: 1px solid var(--border); border-radius: 14px; background: rgba(30, 41, 59, 0.72); padding: 14px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .summary { display: flex; gap: 12px; align-items: center; }
        .logo-badge {
            width: 62px; height: 62px; border-radius: 999px; border: 1px solid rgba(56, 189, 248, .55);
            background: linear-gradient(135deg, rgba(37, 99, 235, .3), rgba(56, 189, 248, .25));
            display: grid; place-items: center; font-weight: 700; overflow: hidden;
        }
        .logo-badge img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .table-wrap { overflow: auto; border: 1px solid var(--border); border-radius: 12px; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; min-width: 760px; }
        th, td { padding: 10px; border-bottom: 1px solid rgba(148, 163, 184, .2); text-align: left; }
        th { background: rgba(15, 23, 42, .72); font-size: .86rem; letter-spacing: .02em; text-transform: uppercase; color: #cbd5e1; }
        .status-pill {
            display: inline-flex; padding: 2px 8px; border-radius: 999px; font-size: .75rem; border: 1px solid var(--border);
            background: rgba(15, 23, 42, .7);
        }
        .btn-secondary {
            border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
            color: var(--text); background: rgba(15, 23, 42, .7); text-decoration: none; display: inline-flex;
        }
        .pagination { margin-top: 12px; }
        .pagination .page-link, .pagination .page-item span {
            border: 1px solid var(--border); color: var(--text); background: rgba(15, 23, 42, 0.65);
            padding: 6px 10px; border-radius: 8px; margin-right: 6px; text-decoration: none;
        }
        .sidebar-profile { margin-top: auto; border: 1px solid var(--border); border-radius: 12px; background: rgba(30, 41, 59, 0.55); padding: 12px; }
        .profile-trigger {
            width: 100%; text-align: left; appearance: none; border: 1px solid var(--border); border-radius: 12px;
            color: var(--text); background: rgba(15, 23, 42, 0.52); cursor: pointer; padding: 10px;
            display: grid; grid-template-columns: 42px 1fr 18px; align-items: center; gap: 10px;
        }
        .profile-avatar {
            width: 42px; height: 42px; border-radius: 999px; border: 1px solid rgba(56, 189, 248, 0.55);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.28), rgba(56, 189, 248, 0.24));
            display: grid; place-items: center; font-size: .82rem; font-weight: 700; overflow: hidden;
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .profile-name { font-weight: 700; margin-bottom: 2px; }
        .profile-meta { font-size: .85rem; color: var(--muted); }
        .profile-arrow { color: var(--muted); text-align: right; }
        .profile-modal-backdrop {
            position: fixed; inset: 0; background: rgba(2, 6, 23, 0.62); display: none;
            align-items: center; justify-content: center; z-index: 2200; padding: 16px;
        }
        .profile-modal-backdrop.open { display: flex; }
        .profile-modal-panel {
            width: min(560px, 96vw); border: 1px solid var(--border); border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 16px; max-height: 92vh; overflow: auto;
        }
        .profile-modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .profile-modal-close, .profile-modal-btn {
            border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.72);
            color: var(--text); padding: 8px 12px; cursor: pointer; font-weight: 600;
        }
        .profile-modal-btn.primary {
            border-color: var(--primary); background: linear-gradient(135deg, var(--primary), #1d4ed8); color: #f8fafc;
        }
        .profile-modal-field { margin-bottom: 12px; }
        .profile-modal-field label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; }
        .profile-modal-field input {
            width: 100%; border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.7);
            color: var(--text); padding: 10px 12px; font-size: 0.95rem;
        }
        .profile-modal-actions { margin-top: 14px; display: flex; justify-content: flex-end; gap: 8px; }
        .profile-modal-alert.error {
            border: 1px solid rgba(248, 113, 113, 0.6); border-radius: 12px; padding: 10px 12px;
            background: rgba(127, 29, 29, 0.25); margin-bottom: 12px;
        }
        @media (max-width: 980px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $openProfileModal = false;
        $avatarInitials = collect(explode(' ', trim($user->name ?? 'U')))
            ->filter()
            ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');
        $avatarSource = !empty($user?->avatar_url)
            ? (\Illuminate\Support\Str::startsWith($user->avatar_url, ['http://', 'https://']) ? $user->avatar_url : \Illuminate\Support\Facades\Storage::url($user->avatar_url))
            : null;
        $avatarSourceWithVersion = $avatarSource
            ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($user->updated_at?->timestamp ?? time())
            : null;
        $today = now('Asia/Jakarta')->toDateString();
    @endphp
    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">{{ config('app.name', 'Kips') }} - Super Admin</div>
            <nav class="sidebar-nav" aria-label="Super admin menu">
                <a href="{{ route('dashboard.super-admin') }}">Dashboard</a>
                <a href="{{ route('dashboard.super-admin.checkins') }}">All Check-ins</a>
                <a href="{{ route('dashboard.super-admin.weekly-journals') }}">All Weekly Journals</a>
                <a href="{{ route('dashboard.super-admin.completion') }}">All Completion Bars</a>
                <a href="{{ route('dashboard.super-admin.mass-edit') }}">Implementation Timeline</a>
                <a class="active" href="{{ route('dashboard.super-admin.companies') }}">Companies</a>
                <a href="{{ route('dashboard.super-admin.users') }}">Users</a>
                <a href="{{ route('dashboard.super-admin.permissions') }}">Permissions</a>
                <a href="{{ route('dashboard.super-admin.activities') }}">Activities</a>
                <a href="{{ route('dashboard.super-admin.mass-edit') }}">Mass Edit</a>
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
                <h1>Company Detail</h1>
                <p class="muted">Breakdown of student placement by major and active period for this company.</p>
            </header>

            <div style="margin-bottom:12px;">
                <a class="btn-secondary" href="{{ route('dashboard.super-admin.companies') }}">Back to Company List</a>
            </div>

            <section class="grid">
                <article class="card">
                    <div class="summary">
                        <div class="logo-badge">
                            @if (!empty(data_get($companyMeta, 'logo_url')))
                                <img src="{{ data_get($companyMeta, 'logo_url') }}" alt="{{ $companyName }} logo" onerror="this.style.display='none'; this.parentElement.textContent='{{ $logoInitials }}';">
                            @else
                                {{ $logoInitials }}
                            @endif
                        </div>
                        <div>
                            <h2 style="font-size:1.04rem;">{{ $companyName }}</h2>
                            <p class="muted">{{ $companyAddress }}</p>
                        </div>
                    </div>
                    <div style="margin-top:12px;">
                        <div><strong>Contact Person:</strong> {{ data_get($companyMeta, 'contact_person') ?: '-' }}</div>
                        <div><strong>Phone:</strong> {{ data_get($companyMeta, 'contact_phone') ?: '-' }}</div>
                        <div><strong>Email:</strong> {{ data_get($companyMeta, 'contact_email') ?: '-' }}</div>
                        <div><strong>Website:</strong>
                            @if (data_get($companyMeta, 'website_url'))
                                <a href="{{ data_get($companyMeta, 'website_url') }}" target="_blank" rel="noopener noreferrer" style="color:#7dd3fc;">{{ data_get($companyMeta, 'website_url') }}</a>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </article>

                <article class="card">
                    <h2 style="font-size:1.02rem; margin-bottom:8px;">Major Distribution</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Major</th>
                                    <th>Total Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($majorSummary as $major)
                                    <tr>
                                        <td>{{ $major->major_name }}</td>
                                        <td>{{ (int) $major->total }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2">No major data found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

            <section class="card">
                <h2 style="font-size:1.02rem; margin-bottom:6px;">Students ({{ $students->total() }})</h2>
                <p class="muted">Paginated 5 students per page.</p>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>NIS</th>
                                <th>Type</th>
                                <th>Major</th>
                                <th>Internship Period</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $row)
                                @php
                                    $start = $row->pkl_start_date ? \Illuminate\Support\Carbon::parse($row->pkl_start_date, 'Asia/Jakarta')->toDateString() : null;
                                    $end = $row->pkl_end_date ? \Illuminate\Support\Carbon::parse($row->pkl_end_date, 'Asia/Jakarta')->toDateString() : null;
                                    $status = 'Planned';
                                    if ($start && $end && $today >= $start && $today <= $end) {
                                        $status = 'Active';
                                    } elseif ($end && $today > $end) {
                                        $status = 'Completed';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $row->student_name }}</td>
                                    <td>{{ $row->student_nis }}</td>
                                    <td>Student</td>
                                    <td>{{ $row->major_name }}</td>
                                    <td>
                                        {{ $start ? \Illuminate\Support\Carbon::parse($start, 'Asia/Jakarta')->format('d M Y') : '-' }}
                                        -
                                        {{ $end ? \Illuminate\Support\Carbon::parse($end, 'Asia/Jakarta')->format('d M Y') : '-' }}
                                    </td>
                                    <td><span class="status-pill">{{ $status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No students found for this company.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    @if ($students->onFirstPage())
                        <span class="page-link">Previous</span>
                    @else
                        <a class="page-link" href="{{ $students->previousPageUrl() }}">Previous</a>
                    @endif
                    <span class="page-link">Page {{ $students->currentPage() }} of {{ $students->lastPage() }}</span>
                    @if ($students->hasMorePages())
                        <a class="page-link" href="{{ $students->nextPageUrl() }}">Next</a>
                    @else
                        <span class="page-link">Next</span>
                    @endif
                </div>
            </section>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])
</body>
</html>


