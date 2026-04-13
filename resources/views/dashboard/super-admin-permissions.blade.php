<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Permissions - Super Admin - {{ config('app.name', 'Kips') }}</title>

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
        .profile-modal-actions { margin-top: 14px; display: flex; justify-content: flex-end; gap: 8px; }
        .profile-modal-alert.error { border: 1px solid rgba(248, 113, 113, 0.6); border-radius: 12px; padding: 10px 12px; background: rgba(127, 29, 29, 0.25); margin-bottom: 12px; }

        .main { padding: 20px; }
        .topbar, .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.9);
            padding: 14px 16px;
        }
        .card {
            margin-top: 12px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94));
        }
        .muted { color: var(--muted); font-size: 0.92rem; }
        .alert {
            border: 1px solid rgba(56, 189, 248, 0.45);
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(14, 165, 233, 0.12);
            margin-top: 12px;
        }
        .alert.error {
            border-color: rgba(248, 113, 113, 0.6);
            background: rgba(127, 29, 29, 0.25);
        }

        .filters {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 320px auto;
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
        .field input,
        .field select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 10px 12px;
            font-size: 0.92rem;
        }

        .btn {
            text-decoration: none;
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

        .target-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .target-user {
            font-size: 1rem;
            font-weight: 700;
        }
        .target-meta {
            color: var(--muted);
            font-size: 0.88rem;
        }

        .permission-box {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.62);
            padding: 10px;
            margin-top: 8px;
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
            vertical-align: middle;
        }
        .permission-table th {
            color: #cbd5e1;
            font-weight: 700;
        }
        .permission-table th.center,
        .permission-table td.center {
            text-align: center;
        }
        .perm-checkbox {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 1px solid #475569;
            border-radius: 6px;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.95), rgba(2, 6, 23, 0.95));
            display: inline-grid;
            place-content: center;
            cursor: pointer;
            transition: all 0.18s ease;
            box-shadow: inset 0 1px 0 rgba(148, 163, 184, 0.18), 0 0 0 0 rgba(56, 189, 248, 0);
        }
        .perm-checkbox::before {
            content: "";
            width: 9px;
            height: 9px;
            clip-path: polygon(14% 52%, 0 66%, 40% 100%, 100% 22%, 85% 8%, 40% 68%);
            transform: scale(0);
            transform-origin: center;
            transition: transform 0.12s ease-in-out;
            background: #ecfeff;
        }
        .perm-checkbox:hover {
            border-color: #67e8f9;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }
        .perm-checkbox:checked {
            border-color: #38bdf8;
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }
        .perm-checkbox:checked::before {
            transform: scale(1);
        }
        .perm-checkbox:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.28);
        }
        .perm-checkbox:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            filter: grayscale(0.2);
        }
        .permission-actions {
            margin-top: 14px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        @media (max-width: 1100px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .main { padding-top: 0; }
        }
        @media (max-width: 900px) {
            .filters { grid-template-columns: 1fr; }
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
                <a href="{{ route('dashboard.super-admin.users') }}">Users</a>
                <a class="active" href="{{ route('dashboard.super-admin.permissions') }}">Permissions</a>
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
                <h1>Permissions Management</h1>
                <p class="muted">Choose a role, then check what that role can view/create/update/delete.</p>
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
            @if (!($rolePermissionsStorageReady ?? false))
                <div class="alert error">
                    Permissions storage is not ready because `role_permissions.permissions_json` does not exist yet.
                    Run `php artisan migrate` first.
                </div>
            @endif

            <section class="card">
                <form method="GET" action="{{ route('dashboard.super-admin.permissions') }}">
                    <div class="filters">
                        <div class="field">
                            <label for="role">Role</label>
                            <select id="role" name="role">
                                @foreach ($roleOptions as $role)
                                    <option value="{{ $role }}" {{ ($selectedRole ?? '') === $role ? 'selected' : '' }}>{{ strtoupper($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn" type="submit">Load</button>
                    </div>
                </form>
            </section>

            <section class="card">
                <div class="target-header">
                    <div>
                        <div class="target-user">Role: {{ strtoupper($selectedRole ?? '-') }}</div>
                        <div class="target-meta">
                            This matrix applies to all users with this role.
                        </div>
                    </div>
                </div>

                @if (($selectedRole ?? '') === \App\Models\User::ROLE_SUPER_ADMIN)
                    <div class="alert">Super Admin always has full access. Permission checkboxes are not required for this role.</div>
                @endif

                <form method="POST" action="{{ route('dashboard.super-admin.permissions.update', ['targetRole' => $selectedRole]) }}">
                    @csrf
                    <div class="permission-box">
                        <h4>Access Matrix</h4>
                        <table class="permission-table">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th class="center">View</th>
                                    <th class="center">Create</th>
                                    <th class="center">Update</th>
                                    <th class="center">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (($availablePermissionModules ?? []) as $moduleKey => $moduleLabel)
                                    <tr>
                                        <td>{{ $moduleLabel }}</td>
                                        <td class="center"><input class="perm-checkbox" type="checkbox" name="permissions[{{ $moduleKey }}][view]" value="1" {{ data_get($targetPermissions, "{$moduleKey}.view") ? 'checked' : '' }} {{ (($selectedRole ?? '') === \App\Models\User::ROLE_SUPER_ADMIN || !($rolePermissionsStorageReady ?? false)) ? 'disabled' : '' }}></td>
                                        <td class="center"><input class="perm-checkbox" type="checkbox" name="permissions[{{ $moduleKey }}][create]" value="1" {{ data_get($targetPermissions, "{$moduleKey}.create") ? 'checked' : '' }} {{ (($selectedRole ?? '') === \App\Models\User::ROLE_SUPER_ADMIN || !($rolePermissionsStorageReady ?? false)) ? 'disabled' : '' }}></td>
                                        <td class="center"><input class="perm-checkbox" type="checkbox" name="permissions[{{ $moduleKey }}][update]" value="1" {{ data_get($targetPermissions, "{$moduleKey}.update") ? 'checked' : '' }} {{ (($selectedRole ?? '') === \App\Models\User::ROLE_SUPER_ADMIN || !($rolePermissionsStorageReady ?? false)) ? 'disabled' : '' }}></td>
                                        <td class="center"><input class="perm-checkbox" type="checkbox" name="permissions[{{ $moduleKey }}][delete]" value="1" {{ data_get($targetPermissions, "{$moduleKey}.delete") ? 'checked' : '' }} {{ (($selectedRole ?? '') === \App\Models\User::ROLE_SUPER_ADMIN || !($rolePermissionsStorageReady ?? false)) ? 'disabled' : '' }}></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="permission-actions">
                        <button class="btn" type="submit" {{ (($selectedRole ?? '') === \App\Models\User::ROLE_SUPER_ADMIN || !($rolePermissionsStorageReady ?? false)) ? 'disabled' : '' }}>Save Permissions</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])
</body>
</html>


