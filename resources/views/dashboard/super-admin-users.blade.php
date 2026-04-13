<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users - Super Admin - {{ config('app.name', 'Kips') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <style>
        :root {
            --bg: #0f172a;
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
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(10px);
            padding: 16px 14px;
        }
        .sidebar-brand {
            font-weight: 700;
            letter-spacing: 0.02em;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.45);
            margin-bottom: 14px;
        }
        .sidebar-nav { display: flex; flex-direction: column; gap: 8px; }
        .sidebar-nav a {
            text-decoration: none;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            background: rgba(30, 41, 59, 0.6);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .sidebar-nav a:hover { border-color: var(--accent); color: var(--accent); }
        .sidebar-nav a.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.32), rgba(29, 78, 216, 0.32));
            color: #f8fafc;
            font-weight: 700;
        }
        .sidebar-profile {
            margin-top: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.55);
            padding: 12px;
        }
        .profile-trigger {
            width: 100%;
            text-align: left;
            appearance: none;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.52);
            color: var(--text);
            cursor: pointer;
            padding: 10px;
            display: grid;
            grid-template-columns: 42px 1fr 18px;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
        }
        .profile-trigger:hover {
            border-color: var(--accent);
            box-shadow: 0 8px 18px rgba(2, 6, 23, 0.35);
            transform: translateY(-1px);
        }
        .profile-avatar {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid rgba(56, 189, 248, 0.55);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.28), rgba(56, 189, 248, 0.24));
            display: grid;
            place-items: center;
            font-size: 0.82rem;
            font-weight: 700;
            overflow: hidden;
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .profile-name { font-weight: 700; margin-bottom: 2px; }
        .profile-meta { font-size: 0.85rem; color: var(--muted); }
        .profile-arrow { color: var(--muted); font-size: 1rem; text-align: right; }

        .main { padding: 20px; }
        .topbar, .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.9);
            padding: 14px 16px;
        }
        .card { margin-top: 12px; background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94)); }
        .muted { color: var(--muted); font-size: 0.92rem; }
        .alert {
            border: 1px solid rgba(56, 189, 248, 0.45);
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(14, 165, 233, 0.12);
            margin-top: 12px;
        }
        .alert.error { border-color: rgba(248, 113, 113, 0.6); background: rgba(127, 29, 29, 0.25); }

        .filters {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr 180px auto;
            gap: 10px;
            align-items: end;
        }
        .field label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.86rem;
            color: var(--muted);
            font-weight: 600;
        }
        .field input, .field select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 10px 12px;
            font-size: 0.92rem;
        }
        .btn {
            border: 1px solid var(--primary);
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #fff;
            padding: 10px 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn.secondary {
            border-color: var(--border);
            background: rgba(15, 23, 42, 0.8);
            color: var(--text);
        }

        .table-wrap {
            margin-top: 12px;
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
        }
        table { width: 100%; border-collapse: collapse; min-width: 840px; }
        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
            font-size: 0.9rem;
        }
        th { background: rgba(15, 23, 42, 0.95); color: #cbd5e1; font-weight: 700; }
        .role-badge {
            display: inline-block;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.7);
            padding: 3px 9px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .action-btn {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 9px;
            background: rgba(15, 23, 42, 0.75);
            color: var(--text);
            text-decoration: none;
            font-size: 0.82rem;
            cursor: pointer;
        }
        .action-btn:hover { border-color: var(--accent); color: var(--accent); }
        .action-btn.danger { border-color: rgba(248, 113, 113, 0.45); color: #fecaca; }
        .action-btn.warn { border-color: rgba(250, 204, 21, 0.45); color: #fef08a; }

        .pagination {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .profile-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2200;
            padding: 16px;
        }
        .profile-modal-backdrop.open { display: flex; }
        .profile-modal-panel {
            width: min(560px, 96vw);
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 16px;
            max-height: 92vh;
            overflow: auto;
        }
        .profile-modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .profile-modal-close, .profile-modal-btn {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.72);
            color: var(--text);
            padding: 8px 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .profile-modal-btn.primary { border-color: var(--primary); background: linear-gradient(135deg, var(--primary), #1d4ed8); color: #f8fafc; }
        .profile-modal-field { margin-bottom: 12px; }
        .profile-modal-field label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; }
        .profile-modal-field input { width: 100%; border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.7); color: var(--text); padding: 10px 12px; font-size: 0.95rem; }
        .profile-modal-field input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15); }
        .profile-modal-actions { margin-top: 14px; display: flex; justify-content: flex-end; gap: 8px; }
        .profile-modal-alert.error { border: 1px solid rgba(248, 113, 113, 0.6); border-radius: 12px; padding: 10px 12px; background: rgba(127, 29, 29, 0.25); margin-bottom: 12px; }

        .user-edit-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2300;
            padding: 16px;
        }
        .user-edit-modal-backdrop.open { display: flex; }
        .user-edit-modal {
            width: min(900px, 96vw);
            max-height: 92vh;
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 16px;
        }
        .user-edit-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .user-edit-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .user-edit-grid .full { grid-column: 1 / -1; }
        .student-only.hidden, .mentor-only.hidden, .kajur-only.hidden, .teacher-only.hidden { display: none; }
        .user-edit-actions {
            margin-top: 14px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }
        .user-create-actions {
            display: flex;
            justify-content: flex-end;
        }
        .nis-hint {
            margin-top: 8px;
            border: 1px dashed rgba(56, 189, 248, 0.5);
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 0.85rem;
            color: #bae6fd;
            background: rgba(14, 165, 233, 0.12);
        }
        .permission-box {
            grid-column: 1 / -1;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.62);
            padding: 10px;
            margin-top: 4px;
        }
        .permission-box h4 {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .permission-table {
            width: 100%;
            border-collapse: collapse;
        }
        .permission-table th,
        .permission-table td {
            border-bottom: 1px solid rgba(51, 65, 85, 0.7);
            padding: 7px 6px;
            text-align: left;
            font-size: 0.85rem;
        }
        .permission-table th {
            color: #cbd5e1;
            font-weight: 700;
        }
        .permission-table td.center {
            text-align: center;
        }
        .permission-table input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #38bdf8;
        }
        @media (max-width: 760px) {
            .user-edit-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 1100px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .main { padding-top: 0; }
        }
        @media (max-width: 800px) {
            .filters { grid-template-columns: 1fr; }
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
        $avatarInitials = collect(explode(' ', trim($user->name ?? 'U')))
            ->filter()
            ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');
        $avatarSource = !empty($user?->avatar_url)
            ? (\Illuminate\Support\Str::startsWith($user->avatar_url, ['http://', 'https://'])
                ? $user->avatar_url
                : \Illuminate\Support\Facades\Storage::url($user->avatar_url))
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
                <a href="{{ route('dashboard.super-admin.companies') }}">Companies</a>
                <a class="active" href="{{ route('dashboard.super-admin.users') }}">Users</a>
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
                <div>
                    <h1>Users Management</h1>
                    <p class="muted">Search, filter, and manage all users.</p>
                </div>
                <div class="user-create-actions">
                    <button class="btn" type="button" id="open-create-user">Add User</button>
                </div>
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

            <section class="card">
                <form method="GET" action="{{ route('dashboard.super-admin.users') }}">
                    <div class="filters">
                        <div class="field">
                            <label for="q">Search by Name / NIS / Email</label>
                            <input id="q" name="q" type="text" value="{{ $q }}" placeholder="Type to search...">
                        </div>
                        <div class="field">
                            <label for="role">Role</label>
                            <select id="role" name="role">
                                <option value="">All Roles</option>
                                @foreach ($roleOptions as $option)
                                    <option value="{{ $option }}" {{ $roleFilter === $option ? 'selected' : '' }}>{{ strtoupper($option) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button class="btn" type="submit">Apply</button>
                            <a class="btn secondary" href="{{ route('dashboard.super-admin.users') }}" style="text-decoration:none; display:inline-flex; align-items:center;">Reset</a>
                        </div>
                    </div>
                </form>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>NIS</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $row)
                                <tr>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->nis ?? '-' }}</td>
                                    <td><span class="role-badge">{{ $row->role }}</span></td>
                                    <td>{{ $row->email }}</td>
                                    <td>
                                        <div class="actions">
                                            <button class="action-btn" type="button" data-open-user-edit data-user-id="{{ $row->id }}">Edit</button>

                                            <form method="POST" action="{{ route('dashboard.super-admin.users.reset-password', $row->id) }}" onsubmit="return confirm('Reset password for this user?');">
                                                @csrf
                                                <button class="action-btn warn" type="submit">Reset PW</button>
                                            </form>

                                            <form method="POST" action="{{ route('dashboard.super-admin.users.delete', $row->id) }}" onsubmit="return confirm('Delete this user permanently?');">
                                                @csrf
                                                <button class="action-btn danger" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">No users found for this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <div class="muted">Showing {{ $users->count() }} of {{ $users->total() }} users</div>
                    <div>{{ $users->links() }}</div>
                </div>
            </section>
        </main>
    </div>

    <div class="user-edit-modal-backdrop" id="user-edit-backdrop" aria-hidden="true">
        <div class="user-edit-modal" role="dialog" aria-modal="true" aria-labelledby="user-edit-title">
            <div class="user-edit-head">
                <h3 id="user-edit-title">Edit User</h3>
                <button type="button" class="action-btn" id="close-user-edit">Close</button>
            </div>

            <form id="user-edit-form" method="POST" action="">
                @csrf
                <div class="user-edit-grid">
                    <div class="field">
                        <label for="edit_name">Name</label>
                        <input id="edit_name" name="name" type="text" required>
                    </div>
                    <div class="field">
                        <label for="edit_email">Email</label>
                        <input id="edit_email" name="email" type="email" required>
                    </div>
                    <div class="field">
                        <label for="edit_nis">NIS</label>
                        <input id="edit_nis" name="nis" type="text" required>
                    </div>
                    <div class="field">
                        <label for="edit_role">Role</label>
                        <select id="edit_role" name="role" required>
                            @foreach ($roleOptions as $option)
                                <option value="{{ $option }}">{{ strtoupper($option) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="permission-box">
                        <h4>Permissions (Checkbox-based Access Control)</h4>
                        <table class="permission-table">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>View</th>
                                    <th>Create</th>
                                    <th>Update</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($availablePermissionModules as $moduleKey => $moduleLabel)
                                    <tr>
                                        <td>{{ $moduleLabel }}</td>
                                        <td class="center"><input type="checkbox" name="permissions[{{ $moduleKey }}][view]" data-perm="{{ $moduleKey }}.view" value="1"></td>
                                        <td class="center"><input type="checkbox" name="permissions[{{ $moduleKey }}][create]" data-perm="{{ $moduleKey }}.create" value="1"></td>
                                        <td class="center"><input type="checkbox" name="permissions[{{ $moduleKey }}][update]" data-perm="{{ $moduleKey }}.update" value="1"></td>
                                        <td class="center"><input type="checkbox" name="permissions[{{ $moduleKey }}][delete]" data-perm="{{ $moduleKey }}.delete" value="1"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="muted" style="margin-top:8px;">If none are checked for a module, user cannot do that action on that module.</p>
                    </div>
                    <div class="field mentor-only">
                        <label for="edit_mentor_company_name">Mentor Company (PKL Place)</label>
                        <select id="edit_mentor_company_name" name="mentor_company_name">
                            <option value="">Select company</option>
                            @foreach (($companyOptions ?? collect()) as $company)
                                @php
                                    $companyName = trim((string) data_get($company, 'name', ''));
                                    $companyAddress = trim((string) data_get($company, 'address', ''));
                                @endphp
                                @if ($companyName !== '')
                                    <option value="{{ $companyName }}" data-address="{{ $companyAddress }}">{{ $companyName }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="field full mentor-only">
                        <label for="edit_mentor_company_address">Mentor Company Address</label>
                        <input id="edit_mentor_company_address" name="mentor_company_address" type="text">
                    </div>
                    <div class="field student-only">
                        <label for="edit_major_name">Major</label>
                        <select id="edit_major_name" name="major_name">
                            <option value="">Select major</option>
                            @foreach (($majorOptions ?? collect(['RPL', 'BDP', 'AKL'])) as $majorOption)
                                @php $majorValue = strtoupper(trim((string) $majorOption)); @endphp
                                @if ($majorValue !== '')
                                    <option value="{{ $majorValue }}">{{ $majorValue }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="field kajur-only">
                        <label for="edit_kajur_major_name">Kajur Managed Major</label>
                        <select id="edit_kajur_major_name" name="kajur_major_name">
                            <option value="">Select major</option>
                            @foreach (($majorOptions ?? collect(['RPL', 'BDP', 'AKL'])) as $majorOption)
                                @php $majorValue = strtoupper(trim((string) $majorOption)); @endphp
                                @if ($majorValue !== '')
                                    <option value="{{ $majorValue }}">{{ $majorValue }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="field kajur-only">
                        <label for="edit_kajur_red_flag_days">Kajur Red-Flag Threshold (days)</label>
                        <input id="edit_kajur_red_flag_days" name="kajur_red_flag_days" type="number" min="1" max="14">
                    </div>
                    <div class="field teacher-only">
                        <label for="edit_teacher_class_name">Teacher Class Scope</label>
                        <select id="edit_teacher_class_name" name="teacher_class_name">
                            @foreach (($classOptions ?? collect(['ALL'])) as $classOption)
                                @php $classValue = trim((string) $classOption); @endphp
                                @if ($classValue !== '')
                                    <option value="{{ $classValue }}">{{ strtoupper($classValue) === 'ALL' ? 'ALL Classes' : $classValue }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="field student-only">
                        <label for="edit_class_name">Class</label>
                        <input id="edit_class_name" name="class_name" type="text" placeholder="Example: XI RPL 1">
                    </div>
                    <div class="field student-only">
                        <label for="edit_birth_place">Birth Place</label>
                        <input id="edit_birth_place" name="birth_place" type="text">
                    </div>
                    <div class="field student-only">
                        <label for="edit_birth_date">Birth Date</label>
                        <input id="edit_birth_date" name="birth_date" type="date">
                    </div>
                    <div class="field full student-only">
                        <label for="edit_address">Address</label>
                        <input id="edit_address" name="address" type="text">
                    </div>
                    <div class="field student-only">
                        <label for="edit_phone_number">Phone Number</label>
                        <input id="edit_phone_number" name="phone_number" type="text">
                    </div>
                    <div class="field student-only">
                        <label for="edit_pkl_place_phone">PKL Place Phone</label>
                        <input id="edit_pkl_place_phone" name="pkl_place_phone" type="text">
                    </div>
                    <div class="field student-only">
                        <label for="edit_pkl_place_name">PKL Place</label>
                        <select id="edit_pkl_place_name" name="pkl_place_name">
                            <option value="">Select company</option>
                            @foreach (($companyOptions ?? collect()) as $company)
                                @php
                                    $companyName = trim((string) data_get($company, 'name', ''));
                                    $companyAddress = trim((string) data_get($company, 'address', ''));
                                @endphp
                                @if ($companyName !== '')
                                    <option value="{{ $companyName }}" data-address="{{ $companyAddress }}">{{ $companyName }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="field full student-only">
                        <label for="edit_pkl_place_address">PKL Place Address</label>
                        <input id="edit_pkl_place_address" name="pkl_place_address" type="text">
                    </div>
                    <div class="field student-only">
                        <label for="edit_pkl_start_date">PKL Start Date</label>
                        <input id="edit_pkl_start_date" name="pkl_start_date" type="date">
                    </div>
                    <div class="field student-only">
                        <label for="edit_pkl_end_date">PKL End Date</label>
                        <input id="edit_pkl_end_date" name="pkl_end_date" type="date">
                    </div>
                    <div class="field student-only">
                        <label for="edit_mentor_teacher_name">Mentor Teacher Name</label>
                        <input id="edit_mentor_teacher_name" name="mentor_teacher_name" type="text">
                    </div>
                    <div class="field student-only">
                        <label for="edit_school_supervisor_teacher_name">School Supervisor Teacher Name</label>
                        <input id="edit_school_supervisor_teacher_name" name="school_supervisor_teacher_name" type="text">
                    </div>
                    <div class="field full student-only">
                        <label for="edit_company_instructor_position">Company Instructor Position</label>
                        <input id="edit_company_instructor_position" name="company_instructor_position" type="text">
                    </div>
                    <div class="field">
                        <label for="edit_password">New Password (optional)</label>
                        <input id="edit_password" name="password" type="password" autocomplete="new-password">
                    </div>
                    <div class="field">
                        <label for="edit_password_confirmation">Confirm New Password</label>
                        <input id="edit_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                    </div>
                </div>
                <div class="user-edit-actions">
                    <button type="button" class="btn secondary" id="cancel-user-edit">Cancel</button>
                    <button type="submit" class="btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="user-edit-modal-backdrop" id="user-create-backdrop" aria-hidden="true">
        <div class="user-edit-modal" role="dialog" aria-modal="true" aria-labelledby="user-create-title">
            <div class="user-edit-head">
                <h3 id="user-create-title">Add User</h3>
                <button type="button" class="action-btn" id="close-user-create">Close</button>
            </div>

            <form method="POST" action="{{ route('dashboard.super-admin.users.create') }}">
                @csrf
                <div class="user-edit-grid">
                    <div class="field">
                        <label for="create_name">Name</label>
                        <input id="create_name" name="name" type="text" required>
                    </div>
                    <div class="field">
                        <label for="create_email">Email</label>
                        <input id="create_email" name="email" type="email" required>
                    </div>
                    <div class="field">
                        <label for="create_role">Role</label>
                        <select id="create_role" name="role" required>
                            @foreach ($roleOptions as $option)
                                <option value="{{ $option }}">{{ strtoupper($option) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field full">
                        <div class="nis-hint">NIS is generated automatically in sequence starting from <strong>250510</strong>.</div>
                    </div>
                    <div class="field">
                        <label for="create_password">Password</label>
                        <input id="create_password" name="password" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="field">
                        <label for="create_password_confirmation">Confirm Password</label>
                        <input id="create_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="user-edit-actions">
                    <button type="button" class="btn secondary" id="cancel-user-create">Cancel</button>
                    <button type="submit" class="btn">Create User</button>
                </div>
            </form>
        </div>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])

    <script>
        (() => {
            const data = @json($userFormData);
            const editButtons = Array.from(document.querySelectorAll('[data-open-user-edit]'));
            const backdrop = document.getElementById('user-edit-backdrop');
            const closeBtn = document.getElementById('close-user-edit');
            const cancelBtn = document.getElementById('cancel-user-edit');
            const form = document.getElementById('user-edit-form');

            if (!editButtons.length || !backdrop || !closeBtn || !cancelBtn || !form) return;

            const updateRouteTemplate = @json(route('dashboard.super-admin.users.update', ['managedUser' => '__ID__']));
            const get = (id) => document.getElementById(id);

            const fields = {
                name: get('edit_name'),
                email: get('edit_email'),
                nis: get('edit_nis'),
                role: get('edit_role'),
                mentor_company_name: get('edit_mentor_company_name'),
                mentor_company_address: get('edit_mentor_company_address'),
                major_name: get('edit_major_name'),
                class_name: get('edit_class_name'),
                birth_place: get('edit_birth_place'),
                birth_date: get('edit_birth_date'),
                address: get('edit_address'),
                phone_number: get('edit_phone_number'),
                pkl_place_name: get('edit_pkl_place_name'),
                pkl_place_address: get('edit_pkl_place_address'),
                pkl_place_phone: get('edit_pkl_place_phone'),
                pkl_start_date: get('edit_pkl_start_date'),
                pkl_end_date: get('edit_pkl_end_date'),
                mentor_teacher_name: get('edit_mentor_teacher_name'),
                school_supervisor_teacher_name: get('edit_school_supervisor_teacher_name'),
                company_instructor_position: get('edit_company_instructor_position'),
                kajur_major_name: get('edit_kajur_major_name'),
                kajur_red_flag_days: get('edit_kajur_red_flag_days'),
                teacher_class_name: get('edit_teacher_class_name'),
                password: get('edit_password'),
                password_confirmation: get('edit_password_confirmation'),
            };
            const studentOnlyBlocks = Array.from(document.querySelectorAll('.student-only'));
            const mentorOnlyBlocks = Array.from(document.querySelectorAll('.mentor-only'));
            const kajurOnlyBlocks = Array.from(document.querySelectorAll('.kajur-only'));
            const teacherOnlyBlocks = Array.from(document.querySelectorAll('.teacher-only'));
            const permissionCheckboxes = Array.from(form.querySelectorAll('input[type="checkbox"][data-perm]'));
            const pklPlaceSelect = fields.pkl_place_name;
            const pklAddressInput = fields.pkl_place_address;
            const mentorCompanySelect = fields.mentor_company_name;
            const mentorCompanyAddressInput = fields.mentor_company_address;
            const teacherClassSelect = fields.teacher_class_name;

            const ensurePklPlaceOption = (name, address = '') => {
                if (!pklPlaceSelect || !name) return;
                const existing = Array.from(pklPlaceSelect.options).find((opt) => opt.value === name);
                if (existing) {
                    if (address && !existing.getAttribute('data-address')) {
                        existing.setAttribute('data-address', address);
                    }
                    return;
                }
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;
                option.setAttribute('data-address', address || '');
                pklPlaceSelect.appendChild(option);
            };
            const ensureMentorCompanyOption = (name, address = '') => {
                if (!mentorCompanySelect || !name) return;
                const existing = Array.from(mentorCompanySelect.options).find((opt) => opt.value === name);
                if (existing) {
                    if (address && !existing.getAttribute('data-address')) {
                        existing.setAttribute('data-address', address);
                    }
                    return;
                }
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;
                option.setAttribute('data-address', address || '');
                mentorCompanySelect.appendChild(option);
            };
            const ensureTeacherClassOption = (className) => {
                const scopedClass = String(className || '').trim();
                if (!teacherClassSelect || !scopedClass) return;
                const existing = Array.from(teacherClassSelect.options).find((opt) => opt.value === scopedClass);
                if (existing) return;
                const option = document.createElement('option');
                option.value = scopedClass;
                option.textContent = scopedClass.toUpperCase() === 'ALL' ? 'ALL Classes' : scopedClass;
                teacherClassSelect.appendChild(option);
            };

            const toggleRoleFields = (role) => {
                const isStudent = role === 'student';
                const isMentor = role === 'mentor';
                const isKajur = role === 'kajur';
                const isTeacher = role === 'teacher';
                studentOnlyBlocks.forEach((block) => {
                    block.classList.toggle('hidden', !isStudent);
                    block.querySelectorAll('input, select, textarea').forEach((el) => {
                        if (isStudent) {
                            el.removeAttribute('disabled');
                        } else {
                            el.setAttribute('disabled', 'disabled');
                        }
                    });
                });
                mentorOnlyBlocks.forEach((block) => {
                    block.classList.toggle('hidden', !isMentor);
                    block.querySelectorAll('input, select, textarea').forEach((el) => {
                        if (isMentor) {
                            el.removeAttribute('disabled');
                        } else {
                            el.setAttribute('disabled', 'disabled');
                        }
                    });
                });
                kajurOnlyBlocks.forEach((block) => {
                    block.classList.toggle('hidden', !isKajur);
                    block.querySelectorAll('input, select, textarea').forEach((el) => {
                        if (isKajur) {
                            el.removeAttribute('disabled');
                        } else {
                            el.setAttribute('disabled', 'disabled');
                        }
                    });
                });
                teacherOnlyBlocks.forEach((block) => {
                    block.classList.toggle('hidden', !isTeacher);
                    block.querySelectorAll('input, select, textarea').forEach((el) => {
                        if (isTeacher) {
                            el.removeAttribute('disabled');
                        } else {
                            el.setAttribute('disabled', 'disabled');
                        }
                    });
                });
            };

            if (pklPlaceSelect && pklAddressInput) {
                pklPlaceSelect.addEventListener('change', () => {
                    const selected = pklPlaceSelect.options[pklPlaceSelect.selectedIndex];
                    const selectedAddress = selected ? String(selected.getAttribute('data-address') || '') : '';
                    if (selectedAddress) {
                        pklAddressInput.value = selectedAddress;
                    }
                });
            }
            if (mentorCompanySelect && mentorCompanyAddressInput) {
                mentorCompanySelect.addEventListener('change', () => {
                    const selected = mentorCompanySelect.options[mentorCompanySelect.selectedIndex];
                    const selectedAddress = selected ? String(selected.getAttribute('data-address') || '') : '';
                    if (selectedAddress) {
                        mentorCompanyAddressInput.value = selectedAddress;
                    }
                });
            }

            const openModal = () => {
                backdrop.classList.add('open');
                backdrop.setAttribute('aria-hidden', 'false');
            };
            const closeModal = () => {
                backdrop.classList.remove('open');
                backdrop.setAttribute('aria-hidden', 'true');
                permissionCheckboxes.forEach((checkbox) => {
                    checkbox.checked = false;
                });
            };

            editButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const userId = String(btn.getAttribute('data-user-id') || '');
                    if (!userId || !data[userId]) return;

                    const row = data[userId];
                    form.action = updateRouteTemplate.replace('__ID__', userId);

                    Object.keys(fields).forEach((key) => {
                        if (!fields[key]) return;
                        if (key === 'password' || key === 'password_confirmation') {
                            fields[key].value = '';
                        } else {
                            fields[key].value = row[key] ?? '';
                        }
                    });

                    ensurePklPlaceOption(String(row.pkl_place_name ?? ''), String(row.pkl_place_address ?? ''));
                    ensureMentorCompanyOption(String(row.mentor_company_name ?? ''), String(row.mentor_company_address ?? ''));
                    ensureTeacherClassOption(String(row.teacher_class_name ?? ''));

                    const permissions = row.permissions_json && typeof row.permissions_json === 'object'
                        ? row.permissions_json
                        : {};
                    permissionCheckboxes.forEach((checkbox) => {
                        const permKey = checkbox.getAttribute('data-perm') || '';
                        const [moduleKey, action] = permKey.split('.');
                        checkbox.checked = Boolean(permissions?.[moduleKey]?.[action]);
                    });

                    toggleRoleFields(String(fields.role?.value || ''));

                    openModal();
                });
            });

            if (fields.role) {
                fields.role.addEventListener('change', () => {
                    toggleRoleFields(String(fields.role.value || ''));
                });
            }

            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            backdrop.addEventListener('click', (event) => {
                if (event.target === backdrop) closeModal();
            });
            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && backdrop.classList.contains('open')) closeModal();
            });
        })();

        (() => {
            const openBtn = document.getElementById('open-create-user');
            const backdrop = document.getElementById('user-create-backdrop');
            const closeBtn = document.getElementById('close-user-create');
            const cancelBtn = document.getElementById('cancel-user-create');
            if (!openBtn || !backdrop || !closeBtn || !cancelBtn) return;

            const openModal = () => {
                backdrop.classList.add('open');
                backdrop.setAttribute('aria-hidden', 'false');
            };
            const closeModal = () => {
                backdrop.classList.remove('open');
                backdrop.setAttribute('aria-hidden', 'true');
            };

            openBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            backdrop.addEventListener('click', (event) => {
                if (event.target === backdrop) closeModal();
            });
            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && backdrop.classList.contains('open')) closeModal();
            });
        })();
    </script>
</body>
</html>


