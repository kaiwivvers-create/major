<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mass Edit - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --surface:#1e293b; --primary:#2563eb; --accent:#38bdf8; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); background:radial-gradient(1000px 500px at 10% -10%, rgba(56, 189, 248, .2), transparent),radial-gradient(900px 450px at 100% 10%, rgba(37, 99, 235, .2), transparent),var(--bg); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:270px 1fr; }
        .sidebar { position:sticky; top:0; height:100vh; display:flex; flex-direction:column; border-right:1px solid var(--border); background:rgba(15,23,42,.92); backdrop-filter:blur(10px); padding:16px 14px; }
        .sidebar-brand { font-weight:700; letter-spacing:.02em; padding:8px 10px; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.45); margin-bottom:14px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:8px; }
        .sidebar-nav a { text-decoration:none; color:var(--text); border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:rgba(30,41,59,.6); font-weight:500; transition:all .2s ease; }
        .sidebar-nav a:hover { border-color:var(--accent); color:var(--accent); }
        .sidebar-nav a.active { border-color:var(--primary); background:linear-gradient(135deg, rgba(37,99,235,.32), rgba(29,78,216,.32)); color:#f8fafc; font-weight:700; }
        .main { padding:20px; }
        .topbar { border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.9); padding:14px 16px; margin-bottom:14px; }
        .topbar h1 { font-size:1.12rem; margin-bottom:4px; }
        .muted { color:var(--muted); }
        .card { border:1px solid var(--border); border-radius:14px; background:rgba(30,41,59,.72); padding:14px; margin-bottom:12px; }
        .section-title { font-size:1rem; margin-bottom:6px; }
        .table-wrap { overflow:auto; border:1px solid var(--border); border-radius:12px; margin-top:8px; }
        table { width:100%; border-collapse:collapse; min-width:780px; }
        th, td { padding:10px; border-bottom:1px solid rgba(148,163,184,.2); text-align:left; }
        th { background:rgba(15,23,42,.72); font-size:.84rem; letter-spacing:.02em; text-transform:uppercase; color:#cbd5e1; }
        .pager { margin-top: 10px; display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap; }
        .pager-links { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .page-link { border:1px solid var(--border); border-radius:8px; padding:6px 10px; background:rgba(15,23,42,.7); color:var(--text); text-decoration:none; }
        .timeline-list { margin-top:10px; border:1px solid var(--border); border-radius:12px; overflow:hidden; }
        .timeline-item { display:grid; grid-template-columns:120px 1fr 120px; gap:10px; align-items:center; padding:10px; border-bottom:1px solid rgba(148,163,184,.2); }
        .timeline-item:last-child { border-bottom:none; }
        .status-pill { border:1px solid var(--border); border-radius:999px; padding:5px 10px; font-size:.84rem; text-align:center; width:max-content; }
        .status-pill.current { border-color:rgba(56,189,248,.65); color:#7dd3fc; }
        .status-pill.done { border-color:rgba(34,197,94,.6); color:#86efac; }
        .status-pill.upcoming { border-color:rgba(148,163,184,.55); color:#cbd5e1; }
        input, select {
            width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:9px 10px; font-size:.95rem;
        }
        input[readonly] { opacity:.82; }
        .btn { border:1px solid var(--primary); border-radius:10px; padding:9px 12px; color:#f8fafc; background:linear-gradient(135deg,var(--primary),#1d4ed8); font-weight:700; cursor:pointer; }
        .grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:8px; }
        .full { grid-column:1 / -1; }
        .alert { border:1px solid rgba(248,113,113,.55); border-radius:10px; padding:10px; background:rgba(127,29,29,.35); margin-bottom:10px; }
        .ok { border:1px solid rgba(56,189,248,.45); border-radius:10px; padding:10px; background:rgba(14,165,233,.12); margin-bottom:10px; }
        .sidebar-profile { margin-top:auto; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.55); padding:12px; }
        .profile-trigger { width:100%; text-align:left; appearance:none; border:1px solid var(--border); border-radius:12px; color:var(--text); background:rgba(15,23,42,.52); cursor:pointer; padding:10px; display:grid; grid-template-columns:42px 1fr 18px; align-items:center; gap:10px; }
        .profile-avatar { width:42px; height:42px; border-radius:999px; border:1px solid rgba(56,189,248,.55); background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24)); display:grid; place-items:center; font-size:.82rem; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .profile-name { font-weight:700; margin-bottom:2px; }
        .profile-meta { font-size:.85rem; color:var(--muted); }
        .profile-arrow { color:var(--muted); text-align:right; }
        .profile-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.62); display:none; align-items:center; justify-content:center; z-index:2200; padding:16px; }
        .profile-modal-backdrop.open { display:flex; }
        .profile-modal-panel { width:min(560px,96vw); border:1px solid var(--border); border-radius:16px; background:linear-gradient(160deg, rgba(30,41,59,.96), rgba(15,23,42,.96)); padding:16px; max-height:92vh; overflow:auto; }
        .profile-modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .profile-modal-close,.profile-modal-btn { border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:8px 12px; cursor:pointer; font-weight:600; }
        .profile-modal-btn.primary { border-color:var(--primary); background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#f8fafc; }
        .profile-modal-field { margin-bottom:12px; }
        .profile-modal-field label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; }
        .profile-modal-field input { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.95rem; }
        .profile-modal-actions { margin-top:14px; display:flex; justify-content:flex-end; gap:8px; }
        .profile-modal-alert.error { border:1px solid rgba(248,113,113,.6); border-radius:12px; padding:10px 12px; background:rgba(127,29,29,.25); margin-bottom:12px; }
        @media (max-width:980px) { .app-shell { grid-template-columns:1fr; } .sidebar { position:static; height:auto; } .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
@php
    $user = auth()->user();
    $openProfileModal = false;
    $avatarInitials = collect(explode(' ', trim($user->name ?? 'U')))->filter()->map(fn($part) => strtoupper(mb_substr($part,0,1)))->take(2)->implode('');
    $avatarSource = !empty($user?->avatar_url) ? (\Illuminate\Support\Str::startsWith($user->avatar_url, ['http://', 'https://']) ? $user->avatar_url : \Illuminate\Support\Facades\Storage::url($user->avatar_url)) : null;
    $avatarSourceWithVersion = $avatarSource ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($user->updated_at?->timestamp ?? time()) : null;
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
            <a href="{{ route('dashboard.super-admin.companies') }}">Companies</a>
            <a href="{{ route('dashboard.super-admin.users') }}">Users</a>
            <a href="{{ route('dashboard.super-admin.permissions') }}">Permissions</a>
            <a href="{{ route('dashboard.super-admin.activities') }}">Activities</a>
            <a class="active" href="{{ route('dashboard.super-admin.mass-edit') }}">Mass Edit</a>
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
            <h1>Mass Edit</h1>
            <p class="muted">Bulk update capacity and schedule fields quickly without touching unique company identity.</p>
        </header>

        @if (session('status'))
            <div class="ok">{{ session('status') }}</div>
        @endif
        @if ($errors->has('mass_edit'))
            <div class="alert">{{ $errors->first('mass_edit') }}</div>
        @endif
        @if ($errors->any() && !$errors->has('mass_edit'))
            <div class="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <section class="card" id="timeline-section">
            <h2 class="section-title">Implementation Timeline</h2>
            <form method="GET" action="{{ route('dashboard.super-admin.mass-edit') }}" style="margin:8px 0 10px; display:flex; gap:8px; align-items:center; max-width:320px;">
                <label class="muted" for="timeline_major">Major</label>
                <input type="hidden" name="per_page" value="{{ (int) ($perPage ?? 20) }}">
                <select id="timeline_major" name="timeline_major" onchange="this.form.submit()">
                    <option value="ALL" {{ strtoupper((string) ($timelineMajor ?? 'ALL')) === 'ALL' ? 'selected' : '' }}>ALL</option>
                    @foreach (($majorOptions ?? collect()) as $major)
                        @php $majorValue = strtoupper(trim((string) $major)); @endphp
                        <option value="{{ $majorValue }}" {{ strtoupper((string) ($timelineMajor ?? 'ALL')) === $majorValue ? 'selected' : '' }}>{{ $majorValue }}</option>
                    @endforeach
                </select>
            </form>
            @if (!empty($timelineStart) && !empty($timelineEnd) && ($timelineWeeks ?? collect())->isNotEmpty())
                <p class="muted">
                    {{ strtoupper((string) ($timelineMajor ?? 'ALL')) }}
                    |
                    {{ \Illuminate\Support\Carbon::parse($timelineStart, 'Asia/Jakarta')->format('d M Y') }}
                    -
                    {{ \Illuminate\Support\Carbon::parse($timelineEnd, 'Asia/Jakarta')->format('d M Y') }}
                </p>
                <div class="timeline-list">
                    @foreach (($timelineWeeks ?? collect()) as $week)
                        @php
                            $status = strtolower((string) ($week['status_type'] ?? 'upcoming'));
                            if (!in_array($status, ['current', 'done', 'upcoming'], true)) {
                                $status = 'upcoming';
                            }
                        @endphp
                        <div class="timeline-item">
                            <strong>Week {{ (int) ($week['week'] ?? 0) }}</strong>
                            <div class="muted">
                                {{ \Illuminate\Support\Carbon::parse($week['start'], 'Asia/Jakarta')->format('d M Y') }}
                                -
                                {{ \Illuminate\Support\Carbon::parse($week['end'], 'Asia/Jakarta')->format('d M Y') }}
                            </div>
                            <span class="status-pill {{ $status }}">{{ $week['status_label'] ?? ucfirst($status) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="muted">Timeline is not available yet because PKL start/end dates are still empty.</p>
            @endif
        </section>

        <section class="card">
            <h2 class="section-title">Bulk Company Capacity (Max Students)</h2>
            <p class="muted">Company name and address are locked. You can only update capacity values in bulk.</p>
            <form method="GET" action="{{ route('dashboard.super-admin.mass-edit') }}" style="margin-top:8px; display:flex; gap:8px; align-items:center; max-width:300px;">
                <label class="muted" for="per_page">Rows per page</label>
                <input type="hidden" name="timeline_major" value="{{ strtoupper((string) ($timelineMajor ?? 'ALL')) }}">
                <select id="per_page" name="per_page" onchange="this.form.submit()">
                    @foreach (($allowedPerPage ?? [5,10,20,50,100]) as $pp)
                        <option value="{{ $pp }}" {{ (int) ($perPage ?? 20) === (int) $pp ? 'selected' : '' }}>{{ $pp }}</option>
                    @endforeach
                </select>
            </form>
            <form method="POST" action="{{ route('dashboard.super-admin.mass-edit.companies') }}">
                @csrf
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Address</th>
                                <th>Current Students</th>
                                <th>Max Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($companies ?? collect()) as $index => $row)
                                <tr>
                                    <td>
                                        <input type="text" value="{{ $row->name }}" readonly>
                                        <input type="hidden" name="companies[{{ $index }}][name]" value="{{ $row->name }}">
                                    </td>
                                    <td>
                                        <input type="text" value="{{ $row->address }}" readonly>
                                        <input type="hidden" name="companies[{{ $index }}][address]" value="{{ $row->address }}">
                                    </td>
                                    <td>{{ (int) ($row->total_students ?? 0) }}</td>
                                    <td><input type="number" min="1" step="1" name="companies[{{ $index }}][max_students]" value="{{ $row->max_students }}"></td>
                                </tr>
                            @empty
                                <tr><td colspan="4">No companies found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if (method_exists($companies, 'hasPages') && $companies->hasPages())
                    <div class="pager">
                        <span class="muted">Showing {{ $companies->firstItem() }}-{{ $companies->lastItem() }} of {{ $companies->total() }}</span>
                        <div class="pager-links">
                            @if ($companies->onFirstPage())
                                <span class="page-link">Previous</span>
                            @else
                                <a class="page-link" href="{{ $companies->previousPageUrl() }}">Previous</a>
                            @endif
                            <span class="page-link">Page {{ $companies->currentPage() }} / {{ $companies->lastPage() }}</span>
                            @if ($companies->hasMorePages())
                                <a class="page-link" href="{{ $companies->nextPageUrl() }}">Next</a>
                            @else
                                <span class="page-link">Next</span>
                            @endif
                        </div>
                    </div>
                @endif
                <div style="margin-top:10px; display:flex; justify-content:flex-end;">
                    <button class="btn" type="submit">Save Capacity Changes</button>
                </div>
            </form>
        </section>

        <section class="card">
            <h2 class="section-title">Bulk PKL Schedule by Major</h2>
            <p class="muted">Update PKL start/end dates for one major (or all). Optional: assign company in one action.</p>
            <form method="POST" action="{{ route('dashboard.super-admin.mass-edit.major-schedule') }}">
                @csrf
                <div class="grid">
                    <div>
                        <label class="muted" for="major_name">Major</label>
                        <select id="major_name" name="major_name" required>
                            <option value="ALL">ALL</option>
                            @foreach (($majorOptions ?? collect()) as $major)
                                <option value="{{ $major }}">{{ $major }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="muted" for="pkl_start_date">PKL Start Date</label>
                        <input id="pkl_start_date" name="pkl_start_date" type="date" required>
                    </div>
                    <div>
                        <label class="muted" for="pkl_end_date">PKL End Date</label>
                        <input id="pkl_end_date" name="pkl_end_date" type="date" required>
                    </div>
                    <div>
                        <label class="muted" for="company_name">Assign Company (optional)</label>
                        <select id="company_name" name="company_name">
                            <option value="">Do not change company</option>
                            @foreach (($allCompanies ?? $companies ?? collect()) as $row)
                                <option value="{{ $row->name }}" data-address="{{ $row->address }}">{{ $row->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="full">
                        <label class="muted" for="company_address">Company Address (auto)</label>
                        <input id="company_address" name="company_address" type="text" readonly>
                    </div>
                </div>
                <div style="margin-top:10px; display:flex; justify-content:flex-end;">
                    <button class="btn" type="submit">Apply Bulk Schedule</button>
                </div>
            </form>
        </section>
    </main>
</div>

@include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])
<script>
    (() => {
        const companySelect = document.getElementById('company_name');
        const addressInput = document.getElementById('company_address');
        if (!companySelect || !addressInput) return;

        companySelect.addEventListener('change', () => {
            const selected = companySelect.options[companySelect.selectedIndex];
            const address = selected ? String(selected.getAttribute('data-address') || '') : '';
            addressInput.value = address;
        });
    })();
</script>
</body>
</html>



