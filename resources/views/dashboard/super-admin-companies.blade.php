<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Company Directory - {{ config('app.name', 'Kips') }}</title>

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
            font-weight: 700; letter-spacing: 0.02em; padding: 8px 10px;
            border: 1px solid var(--border); border-radius: 12px;
            background: rgba(30, 41, 59, 0.45); margin-bottom: 14px;
        }
        .sidebar-nav { display: flex; flex-direction: column; gap: 8px; }
        .sidebar-nav a {
            text-decoration: none; color: var(--text); border: 1px solid var(--border); border-radius: 10px;
            padding: 10px 12px; background: rgba(30, 41, 59, 0.6); font-weight: 500; transition: all .2s ease;
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
        .card {
            border: 1px solid var(--border); border-radius: 14px; background: rgba(30, 41, 59, 0.7); padding: 14px;
        }
        .filters { margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .filters input {
            min-width: 260px; border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.65);
            color: var(--text); padding: 10px 12px;
        }
        .btn, .btn-secondary {
            border: 1px solid var(--primary); border-radius: 10px; padding: 10px 12px; cursor: pointer;
            font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;
        }
        .btn { color: #f8fafc; background: linear-gradient(135deg, var(--primary), #1d4ed8); }
        .btn-secondary { border-color: var(--border); color: var(--text); background: rgba(15, 23, 42, 0.7); }
        .table-wrap { overflow: auto; border: 1px solid var(--border); border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; min-width: 860px; }
        th, td { padding: 10px; border-bottom: 1px solid rgba(148, 163, 184, 0.2); text-align: left; }
        th { background: rgba(15, 23, 42, 0.72); font-size: 0.86rem; letter-spacing: .02em; text-transform: uppercase; color: #cbd5e1; }
        tbody tr:hover { background: rgba(30, 41, 59, 0.48); }
        .logo-badge {
            width: 42px; height: 42px; border-radius: 999px; border: 1px solid rgba(56, 189, 248, .55);
            background: linear-gradient(135deg, rgba(37, 99, 235, .3), rgba(56, 189, 248, .25));
            display: grid; place-items: center; font-weight: 700; overflow: hidden;
        }
        .logo-badge img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .pagination { margin-top: 12px; }
        .pagination .page-link, .pagination .page-item span {
            border: 1px solid var(--border); color: var(--text); background: rgba(15, 23, 42, 0.65);
            padding: 6px 10px; border-radius: 8px; margin-right: 6px; text-decoration: none;
        }
        .action-row { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-xs {
            border: 1px solid var(--border); border-radius: 8px; padding: 6px 8px; font-size: .78rem;
            background: rgba(15, 23, 42, .72); color: var(--text); text-decoration: none; cursor: pointer;
        }
        .btn-xs:hover { border-color: var(--accent); color: var(--accent); }
        .btn-xs.danger {
            border-color: rgba(248, 113, 113, 0.55);
            background: rgba(127, 29, 29, 0.4);
            color: #fecaca;
        }
        .inline-form { display: inline; }
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(2, 6, 23, .68); display: none;
            align-items: center; justify-content: center; z-index: 2400; padding: 14px;
        }
        .modal-backdrop.open { display: flex; }
        .modal-panel {
            width: min(920px, 98vw); max-height: 92vh; overflow: auto; border: 1px solid var(--border);
            border-radius: 14px; background: linear-gradient(160deg, rgba(30, 41, 59, .97), rgba(15, 23, 42, .98)); padding: 14px;
        }
        .modal-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
        .modal-close { border: 1px solid var(--border); border-radius: 8px; padding: 7px 10px; background: rgba(15, 23, 42, .75); color: var(--text); cursor: pointer; }
        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
        .modal-card { border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.62); padding: 10px; }
        .modal-table-wrap { overflow: auto; border: 1px solid var(--border); border-radius: 10px; margin-top: 8px; }
        .modal-pager { margin-top: 8px; display: flex; gap: 8px; align-items: center; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .form-grid .full { grid-column: 1 / -1; }
        .form-grid input {
            width: 100%; border: 1px solid var(--border); border-radius: 8px; background: rgba(15, 23, 42, .75);
            color: var(--text); padding: 8px 10px;
        }
        @media (max-width: 980px) {
            .modal-grid, .form-grid { grid-template-columns: 1fr; }
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
            .filters input { min-width: 100%; width: 100%; }
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
                <h1>Connected Companies</h1>
                <p class="muted">List of internship partner companies with current student distribution.</p>
            </header>

            <section class="card">
                @if (session('status'))
                    <div style="margin-bottom:10px; border:1px solid rgba(56,189,248,.45); border-radius:10px; padding:10px; background:rgba(14,165,233,.12);">{{ session('status') }}</div>
                @endif
                @if ($errors->has('companies'))
                    <div style="margin-bottom:10px; border:1px solid rgba(248,113,113,.55); border-radius:10px; padding:10px; background:rgba(127,29,29,.35);">{{ $errors->first('companies') }}</div>
                @endif
                <form method="GET" action="{{ route('dashboard.super-admin.companies') }}" class="filters">
                    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search company name or address...">
                    <button class="btn" type="submit">Search</button>
                    <button class="btn-secondary" type="button" id="open-company-create">Add Company</button>
                    <a class="btn-secondary" href="{{ route('dashboard.super-admin.companies') }}">Reset</a>
                </form>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Company</th>
                                <th>Address</th>
                                <th>Students</th>
                                <th>Max Students</th>
                                <th>Majors</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($companies as $row)
                                <tr>
                                    <td>
                                        <div class="logo-badge">
                                            @if (!empty($row->logo_url))
                                                <img src="{{ $row->logo_url }}" alt="{{ $row->company_name }} logo" onerror="this.style.display='none'; this.parentElement.textContent='{{ $row->logo_initials }}';">
                                            @else
                                                {{ $row->logo_initials }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $row->company_name }}</strong>
                                        @if (!empty($row->website_url))
                                            <div class="muted" style="font-size:.85rem; margin-top:4px;">
                                                <a href="{{ $row->website_url }}" target="_blank" rel="noopener noreferrer" style="color:#7dd3fc;">{{ $row->website_url }}</a>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $row->company_address }}</td>
                                    <td>{{ (int) $row->total_students }}</td>
                                    <td>{{ $row->max_students !== null ? (int) $row->max_students : '-' }}</td>
                                    <td>{{ (int) $row->total_majors }}</td>
                                    <td>
                                        <div class="action-row">
                                            <button
                                                type="button"
                                                class="btn-xs js-view-company"
                                                data-company-name="{{ $row->company_name }}"
                                                data-company-address="{{ $row->company_address }}"
                                            >View</button>
                                            <button
                                                type="button"
                                                class="btn-xs js-edit-company"
                                                data-company-name="{{ $row->company_name }}"
                                                data-company-address="{{ $row->company_address }}"
                                                data-logo-url="{{ $row->logo_url }}"
                                                data-contact-person="{{ $row->contact_person }}"
                                                data-contact-phone="{{ $row->contact_phone }}"
                                                data-contact-email="{{ $row->contact_email }}"
                                                data-website-url="{{ $row->website_url }}"
                                                data-max-students="{{ $row->max_students }}"
                                            >Edit</button>
                                            <form class="inline-form" method="POST" action="{{ route('dashboard.super-admin.companies.meta.delete') }}" onsubmit="return confirm('Delete company profile data for this company?');">
                                                @csrf
                                                <input type="hidden" name="company_name" value="{{ $row->company_name }}">
                                                <input type="hidden" name="company_address" value="{{ $row->company_address }}">
                                                <button type="submit" class="btn-xs danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">No connected companies found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($companies, 'hasPages') && $companies->hasPages())
                    <div class="pagination">
                        @if ($companies->onFirstPage())
                            <span class="page-link">Previous</span>
                        @else
                            <a class="page-link" href="{{ $companies->previousPageUrl() }}">Previous</a>
                        @endif
                        <span class="page-link">Page {{ $companies->currentPage() }} of {{ $companies->lastPage() }}</span>
                        @if ($companies->hasMorePages())
                            <a class="page-link" href="{{ $companies->nextPageUrl() }}">Next</a>
                        @else
                            <span class="page-link">Next</span>
                        @endif
                    </div>
                @endif
            </section>
        </main>
    </div>

    <div class="modal-backdrop" id="company-view-modal" aria-hidden="true">
        <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="company-view-title">
            <div class="modal-head">
                <div>
                    <h3 id="company-view-title">Company Detail</h3>
                    <p class="muted" id="company-view-subtitle">-</p>
                </div>
                <button type="button" class="modal-close" id="company-view-close">Close</button>
            </div>
            <div class="modal-grid">
                <article class="modal-card">
                    <h4 style="margin-bottom:6px;">Meta</h4>
                    <div id="company-view-meta">-</div>
                </article>
                <article class="modal-card">
                    <h4 style="margin-bottom:6px;">Major Distribution</h4>
                    <div id="company-view-majors">-</div>
                </article>
            </div>
            <article class="modal-card" style="margin-top:10px;">
                <h4>Students <span class="muted" id="company-view-students-total"></span></h4>
                <div class="modal-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>NIS</th>
                                <th>Major</th>
                                <th>Period</th>
                            </tr>
                        </thead>
                        <tbody id="company-view-students-body">
                            <tr><td colspan="4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-pager">
                    <button type="button" class="btn-xs" id="company-view-prev">Previous</button>
                    <span class="muted" id="company-view-page">Page 1 / 1</span>
                    <button type="button" class="btn-xs" id="company-view-next">Next</button>
                </div>
            </article>
        </div>
    </div>

    <div class="modal-backdrop" id="company-edit-modal" aria-hidden="true">
        <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="company-edit-title">
            <div class="modal-head">
                <div>
                    <h3 id="company-edit-title">Edit Company Profile</h3>
                    <p class="muted" id="company-edit-subtitle">-</p>
                </div>
                <button type="button" class="modal-close" id="company-edit-close">Close</button>
            </div>
            <form method="POST" action="{{ route('dashboard.super-admin.companies.meta.save') }}">
                @csrf
                <div class="form-grid">
                    <div class="full">
                        <label class="muted" for="edit_company_name">Company Name</label>
                        <input id="edit_company_name" name="company_name" type="text" required>
                    </div>
                    <div class="full">
                        <label class="muted" for="edit_company_address">Address</label>
                        <input id="edit_company_address" name="company_address" type="text" required>
                    </div>
                    <div class="full">
                        <label class="muted" for="edit_logo_url">Logo URL</label>
                        <input id="edit_logo_url" name="logo_url" type="url" placeholder="https://...">
                    </div>
                    <div>
                        <label class="muted" for="edit_contact_person">Contact Person</label>
                        <input id="edit_contact_person" name="contact_person" type="text">
                    </div>
                    <div>
                        <label class="muted" for="edit_contact_phone">Contact Phone</label>
                        <input id="edit_contact_phone" name="contact_phone" type="text">
                    </div>
                    <div>
                        <label class="muted" for="edit_contact_email">Contact Email</label>
                        <input id="edit_contact_email" name="contact_email" type="email">
                    </div>
                    <div>
                        <label class="muted" for="edit_website_url">Website URL</label>
                        <input id="edit_website_url" name="website_url" type="url" placeholder="https://...">
                    </div>
                    <div>
                        <label class="muted" for="edit_max_students">Max Students</label>
                        <input id="edit_max_students" name="max_students" type="number" min="1" step="1" placeholder="e.g. 40">
                    </div>
                </div>
                <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="btn-secondary" id="company-edit-cancel">Cancel</button>
                    <button type="submit" class="btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])
    <script>
        (() => {
            const viewModal = document.getElementById('company-view-modal');
            const viewClose = document.getElementById('company-view-close');
            const viewSubtitle = document.getElementById('company-view-subtitle');
            const viewMeta = document.getElementById('company-view-meta');
            const viewMajors = document.getElementById('company-view-majors');
            const viewStudentsBody = document.getElementById('company-view-students-body');
            const viewStudentsTotal = document.getElementById('company-view-students-total');
            const viewPage = document.getElementById('company-view-page');
            const prevBtn = document.getElementById('company-view-prev');
            const nextBtn = document.getElementById('company-view-next');

            const editModal = document.getElementById('company-edit-modal');
            const openCreateBtn = document.getElementById('open-company-create');
            const editClose = document.getElementById('company-edit-close');
            const editCancel = document.getElementById('company-edit-cancel');
            const editSubtitle = document.getElementById('company-edit-subtitle');
            const editFields = {
                companyName: document.getElementById('edit_company_name'),
                companyAddress: document.getElementById('edit_company_address'),
                logoUrl: document.getElementById('edit_logo_url'),
                contactPerson: document.getElementById('edit_contact_person'),
                contactPhone: document.getElementById('edit_contact_phone'),
                contactEmail: document.getElementById('edit_contact_email'),
                websiteUrl: document.getElementById('edit_website_url'),
                maxStudents: document.getElementById('edit_max_students'),
            };

            const modalDataUrl = @json(route('dashboard.super-admin.companies.modal-data'));
            let currentCompanyName = '';
            let currentCompanyAddress = '';
            let currentPage = 1;
            let lastPage = 1;

            const openModal = (el) => {
                el?.classList.add('open');
                el?.setAttribute('aria-hidden', 'false');
            };
            const closeModal = (el) => {
                el?.classList.remove('open');
                el?.setAttribute('aria-hidden', 'true');
            };

            const formatDate = (value) => {
                if (!value) return '-';
                const d = new Date(`${value}T00:00:00`);
                if (Number.isNaN(d.getTime())) return value;
                return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            };

            const renderData = (data) => {
                viewSubtitle.textContent = `${data.company_name} | ${data.company_address}`;
                viewMeta.innerHTML = `
                    <div><strong>Contact Person:</strong> ${data.meta?.contact_person || '-'}</div>
                    <div><strong>Phone:</strong> ${data.meta?.contact_phone || '-'}</div>
                    <div><strong>Email:</strong> ${data.meta?.contact_email || '-'}</div>
                    <div><strong>Website:</strong> ${data.meta?.website_url ? `<a href="${data.meta.website_url}" target="_blank" rel="noopener noreferrer" style="color:#7dd3fc;">${data.meta.website_url}</a>` : '-'}</div>
                    <div><strong>Max Students:</strong> ${data.meta?.max_students ?? '-'}</div>
                `;

                if (Array.isArray(data.major_summary) && data.major_summary.length) {
                    viewMajors.innerHTML = data.major_summary
                        .map((row) => `<div>${row.major_name}: <strong>${row.total}</strong></div>`)
                        .join('');
                } else {
                    viewMajors.textContent = 'No major data.';
                }

                viewStudentsTotal.textContent = `(${data.students_total || 0})`;
                viewPage.textContent = `Page ${data.students_page} / ${data.students_last_page}`;
                prevBtn.disabled = data.students_page <= 1;
                nextBtn.disabled = data.students_page >= data.students_last_page;

                if (!Array.isArray(data.students) || !data.students.length) {
                    viewStudentsBody.innerHTML = '<tr><td colspan="4">No students found.</td></tr>';
                    return;
                }

                viewStudentsBody.innerHTML = data.students.map((row) => `
                    <tr>
                        <td>${row.student_name || '-'}</td>
                        <td>${row.student_nis || '-'}</td>
                        <td>${row.major_name || '-'}</td>
                        <td>${formatDate(row.pkl_start_date)} - ${formatDate(row.pkl_end_date)}</td>
                    </tr>
                `).join('');
            };

            const loadModalData = async () => {
                if (!currentCompanyName) return;
                viewStudentsBody.innerHTML = '<tr><td colspan="4">Loading...</td></tr>';
                try {
                    const params = new URLSearchParams({
                        company_name: currentCompanyName,
                        company_address: currentCompanyAddress,
                        page: String(currentPage),
                    });
                    const resp = await fetch(`${modalDataUrl}?${params.toString()}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!resp.ok) throw new Error('Failed');
                    const data = await resp.json();
                    lastPage = Number(data.students_last_page || 1);
                    renderData(data);
                } catch (error) {
                    viewStudentsBody.innerHTML = '<tr><td colspan="4">Failed to load company data.</td></tr>';
                }
            };

            document.querySelectorAll('.js-view-company').forEach((btn) => {
                btn.addEventListener('click', () => {
                    currentCompanyName = String(btn.getAttribute('data-company-name') || '');
                    currentCompanyAddress = String(btn.getAttribute('data-company-address') || '-');
                    currentPage = 1;
                    openModal(viewModal);
                    loadModalData();
                });
            });

            document.querySelectorAll('.js-edit-company').forEach((btn) => {
                btn.addEventListener('click', () => {
                    editFields.companyName.value = String(btn.getAttribute('data-company-name') || '');
                    editFields.companyAddress.value = String(btn.getAttribute('data-company-address') || '-');
                    editFields.logoUrl.value = String(btn.getAttribute('data-logo-url') || '');
                    editFields.contactPerson.value = String(btn.getAttribute('data-contact-person') || '');
                    editFields.contactPhone.value = String(btn.getAttribute('data-contact-phone') || '');
                    editFields.contactEmail.value = String(btn.getAttribute('data-contact-email') || '');
                    editFields.websiteUrl.value = String(btn.getAttribute('data-website-url') || '');
                    editFields.maxStudents.value = String(btn.getAttribute('data-max-students') || '');
                    editSubtitle.textContent = `${editFields.companyName.value} | ${editFields.companyAddress.value}`;
                    openModal(editModal);
                });
            });

            openCreateBtn?.addEventListener('click', () => {
                editFields.companyName.value = '';
                editFields.companyAddress.value = '';
                editFields.logoUrl.value = '';
                editFields.contactPerson.value = '';
                editFields.contactPhone.value = '';
                editFields.contactEmail.value = '';
                editFields.websiteUrl.value = '';
                editFields.maxStudents.value = '';
                editSubtitle.textContent = 'Create new company profile';
                openModal(editModal);
            });

            prevBtn?.addEventListener('click', () => {
                if (currentPage <= 1) return;
                currentPage--;
                loadModalData();
            });
            nextBtn?.addEventListener('click', () => {
                if (currentPage >= lastPage) return;
                currentPage++;
                loadModalData();
            });

            viewClose?.addEventListener('click', () => closeModal(viewModal));
            editClose?.addEventListener('click', () => closeModal(editModal));
            editCancel?.addEventListener('click', () => closeModal(editModal));
            viewModal?.addEventListener('click', (event) => { if (event.target === viewModal) closeModal(viewModal); });
            editModal?.addEventListener('click', (event) => { if (event.target === editModal) closeModal(editModal); });
        })();
    </script>
</body>
</html>


