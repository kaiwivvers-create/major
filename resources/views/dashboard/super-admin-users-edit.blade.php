<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit User - Super Admin - {{ config('app.name', 'Kips') }}</title>

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
        .card { margin-top: 12px; background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94)); max-width: 760px; }
        .muted { color: var(--muted); font-size: 0.92rem; }
        .alert { border: 1px solid rgba(248, 113, 113, 0.6); border-radius: 12px; padding: 10px 12px; background: rgba(127, 29, 29, 0.25); margin-top: 12px; }

        .field { margin-top: 12px; }
        .field label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; }
        .field input, .field select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 10px 12px;
            font-size: 0.95rem;
        }
        .field input:focus, .field select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }
        .actions {
            margin-top: 16px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .permission-box {
            margin-top: 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.62);
            padding: 10px;
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
        .permission-table td.center {
            text-align: center;
        }
        .permission-table input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #38bdf8;
        }
        .btn {
            text-decoration: none;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.72);
            color: var(--text);
            padding: 9px 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn.primary {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #fff;
        }

        @media (max-width: 1100px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .main { padding-top: 0; }
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
                <h1>Edit User</h1>
                <p class="muted">Update user identity, role, and optional password.</p>
            </header>

            @if ($errors->any())
                <div class="alert">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <section class="card">
                <form method="POST" action="{{ route('dashboard.super-admin.users.update', $target->id) }}">
                    @csrf

                    <div class="field">
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $target->name) }}" required>
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $target->email) }}" required>
                    </div>

                    <div class="field">
                        <label for="nis">NIS</label>
                        <input id="nis" name="nis" type="text" value="{{ old('nis', $target->nis) }}" required>
                    </div>

                    <div class="field">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            @foreach ($roleOptions as $role)
                                <option value="{{ $role }}" {{ old('role', $target->role) === $role ? 'selected' : '' }}>{{ strtoupper($role) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field role-setting role-setting-student">
                        <label for="major_name">Student Major</label>
                        <select id="major_name" name="major_name">
                            <option value="">Select major</option>
                            @foreach (($majorOptions ?? collect(['RPL', 'BDP', 'AKL'])) as $majorOption)
                                @php $majorValue = strtoupper(trim((string) $majorOption)); @endphp
                                @if ($majorValue !== '')
                                    <option value="{{ $majorValue }}" {{ strtoupper((string) old('major_name', data_get($targetProfile, 'major_name', ''))) === $majorValue ? 'selected' : '' }}>{{ $majorValue }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="field role-setting role-setting-student">
                        <label for="class_name">Student Class</label>
                        <select id="class_name" name="class_name">
                            <option value="">Select class</option>
                            @foreach (($classOptions ?? collect()) as $classOption)
                                @php $classValue = trim((string) $classOption); @endphp
                                @if ($classValue !== '' && strtoupper($classValue) !== 'ALL')
                                    <option value="{{ $classValue }}" {{ trim((string) old('class_name', data_get($targetProfile, 'class_name', ''))) === $classValue ? 'selected' : '' }}>{{ $classValue }}</option>
                                @endif
                            @endforeach
                        </select>
                        <p class="muted" style="margin-top:6px;">Used for class grouping and reporting.</p>
                    </div>

                    <div class="field role-setting role-setting-kajur">
                        <label for="kajur_major_name">Kajur Managed Major</label>
                        <select id="kajur_major_name" name="kajur_major_name">
                            <option value="">Select major</option>
                            @foreach (($majorOptions ?? collect(['RPL', 'BDP', 'AKL'])) as $majorOption)
                                @php $majorValue = strtoupper(trim((string) $majorOption)); @endphp
                                @if ($majorValue !== '')
                                    <option value="{{ $majorValue }}" {{ strtoupper((string) old('kajur_major_name', $target->kajur_major_name ?? '')) === $majorValue ? 'selected' : '' }}>{{ $majorValue }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="field role-setting role-setting-kajur">
                        <label for="kajur_red_flag_days">Kajur Red-Flag Threshold (days)</label>
                        <input id="kajur_red_flag_days" name="kajur_red_flag_days" type="number" min="1" max="14" value="{{ old('kajur_red_flag_days', (int) ($target->kajur_red_flag_days ?? 2)) }}">
                    </div>

                    <div class="field role-setting role-setting-teacher">
                        <label for="teacher_class_name">Teacher Class Scope</label>
                        <select id="teacher_class_name" name="teacher_class_name">
                            @php $teacherClassSelected = trim((string) old('teacher_class_name', $target->teacher_class_name ?? 'ALL')); @endphp
                            <option value="ALL" {{ strtoupper($teacherClassSelected) === 'ALL' ? 'selected' : '' }}>ALL Classes</option>
                            @foreach (($classOptions ?? collect()) as $classOption)
                                @php $classValue = trim((string) $classOption); @endphp
                                @if ($classValue !== '' && strtoupper($classValue) !== 'ALL')
                                    <option value="{{ $classValue }}" {{ $teacherClassSelected === $classValue ? 'selected' : '' }}>{{ $classValue }}</option>
                                @endif
                            @endforeach
                        </select>
                        <p class="muted" style="margin-top:6px;">Teacher dashboard will only show students from this class scope.</p>
                    </div>

                    <div class="field role-setting role-setting-mentor">
                        <label for="mentor_company_name">Mentor Company (PKL Place)</label>
                        <select id="mentor_company_name" name="mentor_company_name">
                            <option value="">Select company</option>
                            @foreach (($companyOptions ?? collect()) as $company)
                                @php
                                    $companyName = trim((string) data_get($company, 'name', ''));
                                    $companyAddress = trim((string) data_get($company, 'address', ''));
                                    $selectedMentorCompany = old('mentor_company_name', data_get($targetMentorCompany, 'name'));
                                @endphp
                                @if ($companyName !== '')
                                    <option value="{{ $companyName }}" data-address="{{ $companyAddress }}" {{ $selectedMentorCompany === $companyName ? 'selected' : '' }}>{{ $companyName }}</option>
                                @endif
                            @endforeach
                        </select>
                        <p class="muted" style="margin-top:6px;">Used for mentor assignment to PKL company. Works best when role is `MENTOR`.</p>
                    </div>

                    <div class="field role-setting role-setting-mentor">
                        <label for="mentor_company_address">Mentor Company Address</label>
                        <input id="mentor_company_address" name="mentor_company_address" type="text" value="{{ old('mentor_company_address', data_get($targetMentorCompany, 'address', '-')) }}">
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
                                        <td class="center"><input type="checkbox" name="permissions[{{ $moduleKey }}][view]" value="1" {{ old("permissions.$moduleKey.view", data_get($targetPermissions, "$moduleKey.view")) ? 'checked' : '' }}></td>
                                        <td class="center"><input type="checkbox" name="permissions[{{ $moduleKey }}][create]" value="1" {{ old("permissions.$moduleKey.create", data_get($targetPermissions, "$moduleKey.create")) ? 'checked' : '' }}></td>
                                        <td class="center"><input type="checkbox" name="permissions[{{ $moduleKey }}][update]" value="1" {{ old("permissions.$moduleKey.update", data_get($targetPermissions, "$moduleKey.update")) ? 'checked' : '' }}></td>
                                        <td class="center"><input type="checkbox" name="permissions[{{ $moduleKey }}][delete]" value="1" {{ old("permissions.$moduleKey.delete", data_get($targetPermissions, "$moduleKey.delete")) ? 'checked' : '' }}></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="muted" style="margin-top:8px;">Unchecked means denied for that action.</p>
                    </div>

                    <div class="field">
                        <label for="password">New Password (optional)</label>
                        <input id="password" name="password" type="password" autocomplete="new-password">
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm New Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                    </div>

                    <div class="actions">
                        <a class="btn" href="{{ route('dashboard.super-admin.users') }}">Cancel</a>
                        <button class="btn primary" type="submit">Save Changes</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])
    <script>
        (() => {
            const companySelect = document.getElementById('mentor_company_name');
            const addressInput = document.getElementById('mentor_company_address');
            if (!companySelect || !addressInput) return;

            companySelect.addEventListener('change', () => {
                const selected = companySelect.options[companySelect.selectedIndex];
                const selectedAddress = selected ? String(selected.getAttribute('data-address') || '') : '';
                if (selectedAddress !== '') {
                    addressInput.value = selectedAddress;
                }
            });
        })();

        (() => {
            const roleSelect = document.getElementById('role');
            if (!roleSelect) return;

            const allSections = Array.from(document.querySelectorAll('.role-setting'));
            const syncRoleSections = () => {
                const role = String(roleSelect.value || '').toLowerCase();
                allSections.forEach((section) => {
                    const show = section.classList.contains(`role-setting-${role}`);
                    section.style.display = show ? '' : 'none';
                });
            };

            roleSelect.addEventListener('change', syncRoleSections);
            syncRoleSections();
        })();
    </script>
</body>
</html>


