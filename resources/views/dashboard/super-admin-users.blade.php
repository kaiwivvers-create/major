<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users - Super Admin - {{ config('app.name', 'Kips') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

    <style>
        :root {
            --bg: #0f172a;
            --primary: {{ $appBranding->primary_color ?? '#2563eb' }};
            --accent: {{ $appBranding->accent_color ?? '#38bdf8' }};
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
    
        .main { animation: page-drift-up 0.7s ease-out both; }
        @keyframes page-drift-up {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Choices.js Customization */
        .kips-choices .choices__inner {
            background: rgba(15, 23, 42, 0.65) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            color: var(--text) !important;
            min-height: 44px !important;
            padding: 4px 8px !important;
        }
        .kips-choices .choices__list--dropdown {
            background: #1e293b !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            z-index: 2500 !important;
        }
        .kips-choices .choices__item--selectable.is-highlighted {
            background-color: var(--primary) !important;
        }
        .kips-choices .choices__input {
            background: transparent !important;
            color: var(--text) !important;
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
        @include('dashboard.partials.super-admin-sidebar', ['user' => $user, 'activePage' => 'users'])

        <main class="main">
            <header class="topbar">
                <div>
                    <h1>Users Management</h1>
                    <p class="muted">Search, filter, and manage all users.</p>
                </div>
                <div class="user-create-actions" style="display: flex; gap: 8px;">
                    <form action="{{ route('dashboard.super-admin.users.mass-sync') }}" method="POST" onsubmit="return confirm('Sync missing NIS from email prefixes for all users?');">
                        @csrf
                        <button class="btn secondary" type="submit">Sync All NIS</button>
                    </form>
                    <button class="btn secondary" type="button" id="open-import-users">Import Users</button>
                    <button class="btn" type="button" id="open-create-user">Add User</button>
                </div>
            </header>

            @if (session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif
            @if (session('success'))
                <div class="alert">{{ session('success') }}</div>
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
                        <div style="display: flex; gap: 8px;">
                            <input id="edit_nis" name="nis" type="text" required>
                            <button type="button" class="action-btn" id="sync-nis-from-email" title="Match NIS with Email prefix" style="flex-shrink: 0; white-space: nowrap;">Sync Email</button>
                        </div>
                    </div>
                    <div class="field">
                        <label for="edit_role">Role</label>
                        <select id="edit_role" name="role" required>
                            @foreach ($roleOptions as $option)
                                <option value="{{ $option }}">{{ strtoupper($option) }}</option>
                            @endforeach
                        </select>
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
                    <div class="field mentor-only full" style="display: none;">
                        <label for="create_mentor_company_name">Mentor Company (PKL Place) *</label>
                        <select id="create_mentor_company_name" name="mentor_company_name">
                            <option value="">Select company</option>
                            @foreach (($companyOptions ?? collect()) as $company)
                                @php
                                    $companyName = trim((string) data_get($company, 'name', ''));
                                @endphp
                                @if ($companyName !== '')
                                    <option value="{{ $companyName }}">{{ $companyName }}</option>
                                @endif
                            @endforeach
                        </select>
                        <p class="muted" style="margin-top:6px; font-size:0.85rem;">Select the company where this mentor will supervise students.</p>
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

    <div class="user-edit-modal-backdrop" id="user-import-backdrop" aria-hidden="true">
        <div class="user-edit-modal" role="dialog" aria-modal="true" aria-labelledby="user-import-title">
            <div class="user-edit-head">
                <h3 id="user-import-title">Import Users from Excel/CSV</h3>
                <button type="button" class="action-btn" id="close-user-import">Close</button>
            </div>

            <form method="POST" action="{{ route('dashboard.super-admin.users.import-excel') }}" enctype="multipart/form-data">
                @csrf
                <div class="alert" style="margin-bottom: 16px;">
                    <p><strong>Instructions:</strong></p>
                    <p>Upload a CSV or Excel file with the following headers: <code>name, email, nis, role, password</code></p>
                    <p>Role should be one of: <code>student, mentor, kajur, teacher, principal, super_admin, kesiswaan</code></p>
                </div>
                <div class="field">
                    <label for="import_file">Choose File (.csv, .xlsx, .xls)</label>
                    <input id="import_file" name="file" type="file" accept=".csv,.xlsx,.xls" required style="border: 1px dashed var(--border); padding: 20px; text-align: center;">
                </div>
                <div class="user-edit-actions">
                    <button type="button" class="btn secondary" id="cancel-user-import">Cancel</button>
                    <button type="submit" class="btn">Start Import</button>
                </div>
            </form>
        </div>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])

    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        (function() {
            const data = @json($userFormData);
            
            function init() {
                const backdrop = document.getElementById('user-edit-backdrop');
                const form = document.getElementById('user-edit-form');
                if (!backdrop || !form) return;

                const updateRouteTemplate = @json(route('dashboard.super-admin.users.update', ['managedUser' => '__ID__']));

                const fields = {};
                form.querySelectorAll('input, select, textarea').forEach(el => {
                    if (el.name) fields[el.name] = el;
                });

                document.addEventListener('click', function(e) {
                    const btn = e.target.closest('[data-open-user-edit]');
                    if (!btn) return;

                    const userId = btn.getAttribute('data-user-id');
                    const row = data[userId];
                    
                    if (!row) {
                        console.error('User data not found for ID:', userId);
                        return;
                    }

                    form.action = updateRouteTemplate.replace('__ID__', userId);

                    // Reset and fill fields
                    for (const key in fields) {
                        const el = fields[key];
                        if (el.name === '_token' || el.name === '_method') {
                            continue;
                        }
                        if (el.type === 'password') {
                            el.value = '';
                        } else {
                            el.value = row[key] !== undefined ? row[key] : '';
                        }
                    }

                    // Toggle roles
                    const role = row.role || 'student';
                    document.querySelectorAll('.student-only').forEach(b => b.classList.toggle('hidden', role !== 'student'));
                    document.querySelectorAll('.mentor-only').forEach(b => b.classList.toggle('hidden', role !== 'mentor'));
                    document.querySelectorAll('.kajur-only').forEach(b => b.classList.toggle('hidden', role !== 'kajur'));
                    document.querySelectorAll('.teacher-only').forEach(b => b.classList.toggle('hidden', role !== 'teacher'));

                    backdrop.classList.add('open');
                    backdrop.style.display = 'flex'; // Force display just in case
                });

                // Close logic
                const closeBtns = [
                    document.getElementById('close-user-edit'),
                    document.getElementById('cancel-user-edit'),
                    backdrop
                ];
                closeBtns.forEach(btn => {
                    if (!btn) return;
                    btn.addEventListener('click', function(e) {
                        if (e.target !== btn && btn === backdrop) return;
                        backdrop.classList.remove('open');
                        backdrop.style.display = 'none';
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

            // Sync NIS logic
            document.addEventListener('click', function(e) {
                if (e.target.id === 'sync-nis-from-email') {
                    const email = document.getElementById('edit_email')?.value || '';
                    const nisField = document.getElementById('edit_nis');
                    if (email && nisField) {
                        nisField.value = email.split('@')[0];
                    }
                }
            });

            // Re-add Create/Import logic simply
            function setupModal(openId, backdropId, closeId, cancelId) {
                const open = document.getElementById(openId);
                const backdrop = document.getElementById(backdropId);
                const close = document.getElementById(closeId);
                const cancel = document.getElementById(cancelId);
                if (!open || !backdrop) return;
                const openFn = () => { backdrop.classList.add('open'); backdrop.style.display = 'flex'; };
                const closeFn = () => { backdrop.classList.remove('open'); backdrop.style.display = 'none'; };
                open.addEventListener('click', openFn);
                if (close) close.addEventListener('click', closeFn);
                if (cancel) cancel.addEventListener('click', closeFn);
                backdrop.addEventListener('click', (e) => { if (e.target === backdrop) closeFn(); });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    setupModal('open-create-user', 'user-create-backdrop', 'close-user-create', 'cancel-user-create');
                    setupModal('open-import-users', 'user-import-backdrop', 'close-user-import', 'cancel-user-import');
                });
            } else {
                setupModal('open-create-user', 'user-create-backdrop', 'close-user-create', 'cancel-user-create');
                setupModal('open-import-users', 'user-import-backdrop', 'close-user-import', 'cancel-user-import');
            }
        })();
    </script>
    @include('partials.chatbot')
</body></html>

