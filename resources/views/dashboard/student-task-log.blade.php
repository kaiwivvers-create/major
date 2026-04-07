<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Task Log - {{ config('app.name', 'Kips') }}</title>

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
            --warn: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: 'Instrument Sans', sans-serif;
            color: var(--text);
            background:
                radial-gradient(1000px 500px at 10% -10%, rgba(56, 189, 248, 0.2), transparent),
                radial-gradient(900px 450px at 100% 10%, rgba(37, 99, 235, 0.2), transparent),
                var(--bg);
        }

        .app-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 270px 1fr;
        }

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

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

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

        .sidebar-nav a:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

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

        .profile-name {
            font-weight: 700;
            margin-bottom: 2px;
        }

        .profile-meta {
            font-size: 0.85rem;
            color: var(--muted);
        }

        .profile-arrow {
            color: var(--muted);
            font-size: 1rem;
            text-align: right;
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

        .profile-modal-backdrop.open {
            display: flex;
        }

        .profile-modal-panel {
            width: min(560px, 96vw);
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 16px;
        }

        .profile-modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

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

        .profile-modal-btn.primary {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #f8fafc;
        }

        .profile-modal-field {
            margin-bottom: 12px;
        }

        .profile-modal-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .profile-modal-field input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 10px 12px;
            font-size: 0.95rem;
        }

        .profile-modal-field input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        .profile-modal-actions {
            margin-top: 14px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .profile-modal-alert.error {
            border: 1px solid rgba(248, 113, 113, 0.6);
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(127, 29, 29, 0.25);
            margin-bottom: 12px;
        }

        .content {
            padding: 20px;
        }

        .topbar {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.86);
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .topbar h1 {
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .top-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            color: var(--muted);
            font-size: 0.93rem;
        }

        .top-meta strong {
            color: #f8fafc;
        }

        .alert {
            border: 1px solid rgba(56, 189, 248, 0.45);
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(14, 165, 233, 0.12);
            margin-bottom: 12px;
        }

        .alert.error {
            border-color: rgba(248, 113, 113, 0.6);
            background: rgba(127, 29, 29, 0.25);
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94));
            padding: 16px;
        }

        .field {
            margin-bottom: 12px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .field textarea,
        .field input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.65);
            color: var(--text);
            padding: 10px 12px;
            font-family: inherit;
            outline: none;
        }

        .field textarea {
            min-height: 110px;
            resize: vertical;
        }

        .field textarea:focus,
        .field input:focus {
            border-color: rgba(56, 189, 248, 0.9);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        .save-btn {
            border: 1px solid var(--primary);
            border-radius: 10px;
            padding: 10px 14px;
            color: #f8fafc;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            font-weight: 700;
            cursor: pointer;
        }

        .scores {
            margin-top: 16px;
            border-top: 1px solid var(--border);
            padding-top: 14px;
        }

        .scores h2 {
            font-size: 1rem;
            margin-bottom: 6px;
        }

        .muted {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .score-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .score-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.56);
            padding: 10px;
        }

        .score-item label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        .score-item input[readonly] {
            opacity: 0.85;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 0.8rem;
            font-weight: 700;
            border: 1px solid rgba(245, 158, 11, 0.5);
            color: #fde68a;
            background: rgba(245, 158, 11, 0.15);
            margin-top: 8px;
            margin-bottom: 8px;
        }

        @media (max-width: 900px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                height: auto;
            }

            .content {
                padding-top: 0;
            }

            .top-meta {
                grid-template-columns: 1fr;
            }

            .score-grid {
                grid-template-columns: 1fr;
            }
        }
    
        @keyframes page-drift-up {
            from {
                opacity: 0;
                transform: translateY(22px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content.page-drift-up {
            animation: page-drift-up 0.7s ease-out both;
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $checkInTime = $todayAttendance?->check_in_at
            ? \Illuminate\Support\Carbon::parse($todayAttendance->check_in_at, 'Asia/Jakarta')->format('H:i:s') . ' WIB'
            : '-';
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
    @endphp

    <div class="app-shell">
        @include('dashboard.partials.student-sidebar', ['user' => $user, 'activePage' => 'tasklog'])

        <main class="content page-drift-up">
            <header class="topbar">
                <h1>Daily Task Log</h1>
                <div class="top-meta">
                    <div><strong>Date:</strong> {{ \Illuminate\Support\Carbon::parse($today, 'Asia/Jakarta')->translatedFormat('l, d F Y') }}</div>
                    <div><strong>Check-in Time:</strong> {{ $checkInTime }}</div>
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
                <form method="POST" action="{{ route('dashboard.student.task-log') }}">
                    @csrf

                    <div class="field">
                        <label for="planned_today">What We Plan On Doing Today</label>
                        <textarea id="planned_today" name="planned_today" required>{{ old('planned_today', $todayLog->planned_today ?? $todayLog->title ?? '') }}</textarea>
                    </div>

                    <div class="field">
                        <label for="work_realization">Work Realization</label>
                        <textarea id="work_realization" name="work_realization" required>{{ old('work_realization', $todayLog->work_realization ?? $todayLog->description ?? '') }}</textarea>
                    </div>

                    <div class="field">
                        <label for="assigned_work">Specific Work Given By Higher Ups</label>
                        <textarea id="assigned_work" name="assigned_work">{{ old('assigned_work', $todayLog->assigned_work ?? '') }}</textarea>
                    </div>

                    <div class="field">
                        <label for="field_problems">Problems We Found In The Field</label>
                        <textarea id="field_problems" name="field_problems">{{ old('field_problems', $todayLog->field_problems ?? '') }}</textarea>
                    </div>

                    <div class="field">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes">{{ old('notes', $todayLog->notes ?? '') }}</textarea>
                    </div>

                    <button class="save-btn" type="submit">Save Daily Log</button>
                </form>

                <div class="scores">
                    <h2>Daily Scoring</h2>
                    <div class="badge">Filled by Pembimbing (Mentor)</div>
                    <p class="muted">Scale used: 1-5. Students can view scores here after mentor input.</p>

                    <div class="score-grid">
                        <div class="score-item">
                            <label for="score_smile">1. Smile</label>
                            <input id="score_smile" type="number" min="1" max="5" value="{{ $todayLog->score_smile ?? '' }}" readonly>
                        </div>
                        <div class="score-item">
                            <label for="score_friendliness">2. Friendliness</label>
                            <input id="score_friendliness" type="number" min="1" max="5" value="{{ $todayLog->score_friendliness ?? '' }}" readonly>
                        </div>
                        <div class="score-item">
                            <label for="score_appearance">3. Appearance</label>
                            <input id="score_appearance" type="number" min="1" max="5" value="{{ $todayLog->score_appearance ?? '' }}" readonly>
                        </div>
                        <div class="score-item">
                            <label for="score_communication">4. Communication</label>
                            <input id="score_communication" type="number" min="1" max="5" value="{{ $todayLog->score_communication ?? '' }}" readonly>
                        </div>
                        <div class="score-item">
                            <label for="score_work_realization">5. Work Realization</label>
                            <input id="score_work_realization" type="number" min="1" max="5" value="{{ $todayLog->score_work_realization ?? '' }}" readonly>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal])
</body>
</html>

