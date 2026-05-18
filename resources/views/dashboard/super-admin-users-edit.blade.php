<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit User - Super Admin - {{ config('app.name', 'Kips') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

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
        .role-section-title {
            margin-top: 10px;
            margin-bottom: 6px;
            font-size: 0.82rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #94a3b8;
            background: rgba(15, 23, 42, 0.55);
            border-bottom: 1px solid rgba(51, 65, 85, 0.75);
            font-weight: 700;
        }
        .module-title {
            font-weight: 600;
            color: #e2e8f0;
        }
        .module-hint {
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .role-chip {
            display: inline-flex;
            align-items: center;
            font-size: 0.72rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border: 1px solid rgba(56, 189, 248, 0.55);
            border-radius: 999px;
            color: #bae6fd;
            background: rgba(14, 116, 144, 0.24);
            padding: 2px 7px;
            font-weight: 700;
        }
        .role-more {
            border: 1px solid rgba(71, 85, 105, 0.75);
            border-radius: 8px;
            color: #cbd5e1;
            background: rgba(15, 23, 42, 0.62);
            width: 20px;
            height: 20px;
            line-height: 16px;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
        }
        .role-all-list {
            font-size: 0.72rem;
            color: #94a3b8;
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
    
        .main { animation: page-drift-up 0.7s ease-out both; }
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
        $avatarInitials = collect(explode(' ', trim($user->name ?? 'U')))->filter()->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('');
        $avatarSource = !empty($user?->avatar_url)
            ? (\Illuminate\Support\Str::startsWith($user->avatar_url, ['http://', 'https://']) ? $user->avatar_url : \Illuminate\Support\Facades\Storage::url($user->avatar_url))
            : null;
        $avatarSourceWithVersion = $avatarSource
            ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($user->updated_at?->timestamp ?? time())
            : null;
    @endphp
    <div class="app-shell">
        @include('dashboard.partials.super-admin-sidebar', ['user' => $user, 'activePage' => 'users'])

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
                                @php
                                    $roleOrder = $roleOptions ?? [];
                                    $groups = $groupedPermissionModules ?? [];
                                @endphp
                                @foreach ($roleOrder as $roleKey)
                                    @php $modulesInGroup = data_get($groups, $roleKey, []); @endphp
                                    @continue(empty($modulesInGroup))
                                    <tr>
                                        <td colspan="5" class="role-section-title">{{ strtoupper($roleKey) }}</td>
                                    </tr>
                                    @foreach ($modulesInGroup as $moduleKey => $moduleMeta)
                                        @php
                                            $moduleLabel = data_get($moduleMeta, 'label', $moduleKey);
                                            $recommendedRoles = collect(data_get($moduleMeta, 'recommended_roles', []))
                                                ->filter(fn ($value) => is_string($value) && $value !== '')
                                                ->values();
                                            $primaryRole = (string) ($recommendedRoles->first() ?? $roleKey);
                                            
                                            $actions = ['view', 'create', 'update', 'delete'];
                                            $isSuperAdmin = (($target->role ?? '') === \App\Models\User::ROLE_SUPER_ADMIN);
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="module-title">{{ $moduleLabel }}</div>
                                                <div class="module-hint">
                                                    <span class="role-chip">{{ strtoupper($primaryRole) }}</span>
                                                    @if ($recommendedRoles->count() > 1)
                                                        <button type="button" class="role-more" data-toggle-roles="roles-{{ $moduleKey }}">+</button>
                                                        <span class="role-all-list" id="roles-{{ $moduleKey }}" style="display:none;">
                                                            {{ $recommendedRoles->map(fn ($value) => strtoupper((string) $value))->implode(', ') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            @foreach ($actions as $action)
                                                @php
                                                    $userOverride = data_get($userPermissions, "{$moduleKey}.{$action}");
                                                    $isChecked = $userOverride !== null 
                                                        ? (bool)$userOverride 
                                                        : (bool)data_get($rolePermissions, "{$moduleKey}.{$action}");
                                                @endphp
                                                <td class="center">
                                                    <input 
                                                        class="perm-checkbox" 
                                                        type="checkbox" 
                                                        name="permissions[{{ $moduleKey }}][{{ $action }}]" 
                                                        value="1" 
                                                        {{ $isChecked ? 'checked' : '' }}
                                                        {{ $isSuperAdmin ? 'disabled' : '' }}
                                                    >
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                        <p class="muted" style="margin-top:8px;">These permissions will override the defaults set for the {{ strtoupper($target->role) }} role for this specific user.</p>
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
            const toggles = Array.from(document.querySelectorAll('[data-toggle-roles]'));
            if (!toggles.length) return;
            toggles.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const targetId = btn.getAttribute('data-toggle-roles');
                    const target = targetId ? document.getElementById(targetId) : null;
                    if (!target) return;
                    const isHidden = target.style.display === 'none' || target.style.display === '';
                    target.style.display = isHidden ? 'inline' : 'none';
                    btn.textContent = isHidden ? '-' : '+';
                });
            });
        })();

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

    @include('partials.chatbot')
</body>
</html>

