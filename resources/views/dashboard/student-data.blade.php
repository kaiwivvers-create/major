<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Data - {{ config('app.name', 'Kips') }}</title>

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
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .profile-name { font-weight: 700; margin-bottom: 2px; }
        .profile-meta { font-size: 0.85rem; color: var(--muted); }
        .profile-arrow { color: var(--muted); font-size: 1rem; text-align: right; }
        .content { padding: 20px; }
        .topbar, .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.86);
            padding: 14px 16px;
        }
        .card { margin-top: 12px; background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94)); }
        .muted { color: var(--muted); font-size: 0.92rem; }
        .alert { border: 1px solid rgba(56, 189, 248, 0.45); border-radius: 12px; padding: 10px 12px; background: rgba(14, 165, 233, 0.12); margin-top: 12px; }
        .alert.error { border-color: rgba(248, 113, 113, 0.6); background: rgba(127, 29, 29, 0.25); }

        .warning-banner {
            margin-top: 12px;
            border-radius: 12px;
            border: 2px solid rgba(56, 189, 248, 0.9);
            padding: 12px;
            color: #e0f2fe;
            background: rgba(14, 116, 144, 0.22);
            font-weight: 700;
        }

        .incomplete-glow {
            border-color: rgba(56, 189, 248, 0.98) !important;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.35), 0 0 26px rgba(56, 189, 248, 0.62);
            animation: student-data-main-glow 1.1s ease-in-out infinite;
        }

        .form-grid {
            margin-top: 12px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.65);
            color: var(--text);
            padding: 10px 12px;
            font-family: inherit;
            font-size: 0.95rem;
        }

        .field textarea {
            min-height: 96px;
            resize: vertical;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        .actions {
            margin-top: 14px;
            display: flex;
            justify-content: flex-end;
        }

        .btn {
            border: 1px solid var(--primary);
            border-radius: 10px;
            padding: 10px 14px;
            color: #f8fafc;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            font-weight: 700;
            cursor: pointer;
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
        }
        .profile-modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .profile-modal-close,
        .profile-modal-btn {
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

        @keyframes page-drift-up {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes student-data-main-glow {
            0%, 100% {
                box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.35), 0 0 22px rgba(56, 189, 248, 0.55);
            }
            50% {
                box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.6), 0 0 34px rgba(56, 189, 248, 0.92);
            }
        }

        .content.page-drift-up { animation: page-drift-up 0.7s ease-out both; }

        @media (max-width: 900px) {
            .app-shell { grid-template-columns: 1fr; }
            .content { padding-top: 0; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
    @endphp

    <div class="app-shell">
        @include('dashboard.partials.student-sidebar', ['user' => $user, 'activePage' => 'studentdata'])

        <main class="content page-drift-up">
            <header class="topbar {{ !$profileIsComplete ? 'incomplete-glow' : '' }}">
                <h1>Student Data</h1>
                <p class="muted">Complete your student biodata. This is required before everything is considered fully complete.</p>
                @if (!$profileIsComplete)
                    <div class="warning-banner incomplete-glow">
                        Your student data is not complete yet. Please fill all required fields now.
                    </div>
                @endif
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

            <section class="card {{ !$profileIsComplete ? 'incomplete-glow' : '' }}">
                <form method="POST" action="{{ route('dashboard.student.data.save') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="field">
                            <label for="student_name">Student Name *</label>
                            <input id="student_name" name="student_name" type="text" value="{{ old('student_name', $user->name) }}" required>
                        </div>

                        <div class="field">
                            <label for="major_name">Major *</label>
                            <select id="major_name" name="major_name" required>
                                <option value="">Select major</option>
                                <option value="RPL" {{ old('major_name', $profile->major_name ?? '') === 'RPL' ? 'selected' : '' }}>RPL</option>
                                <option value="BDP" {{ old('major_name', $profile->major_name ?? '') === 'BDP' ? 'selected' : '' }}>BDP</option>
                                <option value="AKL" {{ old('major_name', $profile->major_name ?? '') === 'AKL' ? 'selected' : '' }}>AKL</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="birth_place">Student Birthplace *</label>
                            <input id="birth_place" name="birth_place" type="text" value="{{ old('birth_place', $profile->birth_place ?? '') }}" required>
                        </div>

                        <div class="field">
                            <label for="birth_date">Birthdate *</label>
                            <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', $profile->birth_date ?? '') }}" required>
                        </div>

                        <div class="field">
                            <label for="phone_number">Phone Number *</label>
                            <input id="phone_number" name="phone_number" type="text" value="{{ old('phone_number', $profile->phone_number ?? '') }}" required>
                        </div>

                        <div class="field">
                            <label for="pkl_place_phone">PKL Place Phone Number *</label>
                            <input id="pkl_place_phone" name="pkl_place_phone" type="text" value="{{ old('pkl_place_phone', $profile->pkl_place_phone ?? '') }}" required>
                        </div>

                        <div class="field full">
                            <label for="address">Student Address *</label>
                            <textarea id="address" name="address" required>{{ old('address', $profile->address ?? '') }}</textarea>
                        </div>

                        <div class="field">
                            <label for="pkl_place_name">PKL Place *</label>
                            <input id="pkl_place_name" name="pkl_place_name" type="text" value="{{ old('pkl_place_name', $profile->pkl_place_name ?? '') }}" required>
                        </div>

                        <div class="field full">
                            <label for="pkl_place_address">PKL Place Address *</label>
                            <textarea id="pkl_place_address" name="pkl_place_address" required>{{ old('pkl_place_address', $profile->pkl_place_address ?? '') }}</textarea>
                        </div>

                        <div class="field">
                            <label for="pkl_start_date">PKL Start Date *</label>
                            <input id="pkl_start_date" name="pkl_start_date" type="date" value="{{ old('pkl_start_date', $profile->pkl_start_date ?? '') }}" min="{{ now('Asia/Jakarta')->toDateString() }}" required>
                        </div>

                        <div class="field">
                            <label for="pkl_end_date">PKL End Date *</label>
                            <input id="pkl_end_date" name="pkl_end_date" type="date" value="{{ old('pkl_end_date', $profile->pkl_end_date ?? '') }}" min="{{ old('pkl_start_date', $profile->pkl_start_date ?? now('Asia/Jakarta')->toDateString()) }}" required>
                        </div>

                        <div class="field">
                            <label for="mentor_teacher_name">Mentor Teacher Name *</label>
                            <input id="mentor_teacher_name" name="mentor_teacher_name" type="text" value="{{ old('mentor_teacher_name', $profile->mentor_teacher_name ?? '') }}" required>
                        </div>

                        <div class="field">
                            <label for="school_supervisor_teacher_name">School Supervisor Teacher Name *</label>
                            <input id="school_supervisor_teacher_name" name="school_supervisor_teacher_name" type="text" value="{{ old('school_supervisor_teacher_name', $profile->school_supervisor_teacher_name ?? '') }}" required>
                        </div>

                        <div class="field">
                            <label for="company_instructor_position">Company Instructor Position *</label>
                            <input id="company_instructor_position" name="company_instructor_position" type="text" value="{{ old('company_instructor_position', $profile->company_instructor_position ?? '') }}" required>
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn" type="submit">Save Student Data</button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal])
</body>
</html>
