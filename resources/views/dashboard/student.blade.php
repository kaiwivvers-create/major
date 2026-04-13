<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard - {{ config('app.name', 'Kips') }}</title>

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
            --good: #22c55e;
            --bad: #ef4444;
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
            margin-bottom: 10px;
        }

        .logout-btn {
            width: 100%;
            text-align: center;
            border: 1px solid rgba(239, 68, 68, 0.6);
            color: #fecaca;
            border-radius: 10px;
            padding: 8px 10px;
            background: rgba(239, 68, 68, 0.14);
            font-weight: 700;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.85);
        }

        .profile-arrow {
            color: var(--muted);
            font-size: 1rem;
            text-align: right;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 16px;
        }

        .modal-backdrop.open {
            display: flex;
        }

        .profile-modal {
            width: min(560px, 96vw);
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 16px;
            box-shadow: 0 24px 36px rgba(2, 6, 23, 0.52);
        }

        .checkin-modal {
            width: min(680px, 96vw);
        }

        .modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .modal-head h3 {
            font-size: 1.05rem;
        }

        .modal-close {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 6px 10px;
            cursor: pointer;
        }

        .modal-close:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .save-btn {
            border: 1px solid var(--primary);
            border-radius: 10px;
            padding: 8px 12px;
            color: #f8fafc;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            font-weight: 700;
            cursor: pointer;
        }

        .checkin-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 8px;
        }

        .checkin-box {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px;
            background: rgba(15, 23, 42, 0.6);
        }

        .checkin-video,
        .checkin-preview {
            width: 100%;
            aspect-ratio: 4 / 3;
            border: 1px dashed rgba(56, 189, 248, 0.5);
            border-radius: 10px;
            background: rgba(2, 6, 23, 0.65);
            object-fit: cover;
        }

        .checkin-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .checkin-btn {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 8px 10px;
            background: rgba(30, 41, 59, 0.65);
            color: var(--text);
            font-weight: 600;
            cursor: pointer;
        }

        .checkin-btn.primary {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #f8fafc;
        }

        .checkin-btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .crop-wrap {
            margin-top: 10px;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px;
            background: rgba(15, 23, 42, 0.55);
        }

        .crop-canvas {
            width: 100%;
            max-width: 420px;
            aspect-ratio: 4 / 3;
            border: 1px dashed rgba(56, 189, 248, 0.45);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            display: block;
            cursor: grab;
            touch-action: none;
            user-select: none;
        }

        .crop-canvas.dragging {
            cursor: grabbing;
        }

        .crop-tools {
            margin-top: 10px;
            display: grid;
            gap: 10px;
            max-width: 420px;
        }

        .crop-tool-row {
            display: grid;
            grid-template-columns: 68px 1fr 58px;
            align-items: center;
            gap: 8px;
        }

        .crop-tool-row label {
            font-size: 0.85rem;
            color: var(--muted);
            font-weight: 600;
        }

        .crop-tool-row input[type="range"] {
            width: 100%;
            accent-color: #38bdf8;
        }

        .crop-tool-value {
            text-align: right;
            font-size: 0.8rem;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
        }

        .crop-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .crop-btn {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 7px 10px;
            background: rgba(30, 41, 59, 0.7);
            color: var(--text);
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            transition: border-color 0.2s ease, color 0.2s ease;
        }

        .crop-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .crop-btn.is-active {
            border-color: var(--accent);
            color: #f8fafc;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.6), rgba(14, 165, 233, 0.55));
        }

        .content {
            padding: 18px;
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
        }

        .topbar p {
            color: var(--muted);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .page {
            display: grid;
            gap: 14px;
        }

        .alert {
            border: 1px solid rgba(56, 189, 248, 0.5);
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            padding: 12px 14px;
        }

        .alert.error {
            border-color: rgba(239, 68, 68, 0.55);
            color: #fecaca;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 14px;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94));
            padding: 16px;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            animation: card-enter 0.55s ease both;
            will-change: transform;
        }

        .card:hover {
            transform: translateY(-6px);
            border-color: rgba(56, 189, 248, 0.55);
            box-shadow: 0 18px 28px rgba(2, 6, 23, 0.42);
        }

        .card h2 {
            font-size: 1.05rem;
            margin-bottom: 10px;
        }

        .status-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 0.86rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .badge.good {
            color: #dcfce7;
            background: rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.5);
        }

        .badge.bad {
            color: #fee2e2;
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
        }

        .cta-btn {
            width: 100%;
            border: 1px solid var(--primary);
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            font-size: 1rem;
            color: #f8fafc;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .cta-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(37, 99, 235, 0.32);
        }

        .cta-btn.checkout {
            border-color: rgba(245, 158, 11, 0.7);
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .cta-btn.done {
            border-color: rgba(34, 197, 94, 0.6);
            background: linear-gradient(135deg, #16a34a, #15803d);
            cursor: not-allowed;
            opacity: 0.85;
        }

        .field {
            margin-top: 10px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .field input,
        .field textarea {
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
            min-height: 132px;
            resize: vertical;
        }

        .field input:focus,
        .field textarea:focus {
            border-color: rgba(56, 189, 248, 0.9);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        .small-btn {
            margin-top: 10px;
            border: 1px solid var(--accent);
            color: var(--text);
            background: rgba(56, 189, 248, 0.12);
            border-radius: 10px;
            padding: 8px 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .small-btn:hover {
            color: #e0f2fe;
            border-color: #67e8f9;
            transform: translateY(-1px);
        }

        .journal-state {
            border-radius: 12px;
            padding: 12px;
            border: 1px solid var(--border);
            margin-top: 8px;
        }

        .journal-state.approved {
            border-color: rgba(34, 197, 94, 0.5);
            background: rgba(34, 197, 94, 0.12);
        }

        .journal-state.correction {
            border-color: rgba(239, 68, 68, 0.5);
            background: rgba(239, 68, 68, 0.12);
        }

        .journal-state.pending {
            border-color: rgba(245, 158, 11, 0.5);
            background: rgba(245, 158, 11, 0.12);
        }

        .progress-wrap {
            margin-top: 8px;
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            color: var(--muted);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .progress {
            height: 12px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(51, 65, 85, 0.55);
            border: 1px solid var(--border);
        }

        .progress > span {
            display: block;
            height: 100%;
            width: var(--progress, 0%);
            background: linear-gradient(90deg, #22c55e, #38bdf8);
            transition: width 0.8s ease;
        }

        .muted {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .error-text {
            color: #fecaca;
            font-size: 0.85rem;
            margin-top: 8px;
        }

        @keyframes card-enter {
            0% {
                opacity: 0;
                transform: translateY(12px) scale(0.99);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .page .card:nth-child(2) { animation-delay: 0.04s; }
        .page .card:nth-child(3) { animation-delay: 0.08s; }
        .page .card:nth-child(4) { animation-delay: 0.12s; }

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

            .grid {
                grid-template-columns: 1fr;
            }

            .checkin-grid {
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
        $isCheckedIn = $todayAttendance && $todayAttendance->check_in_at && !$todayAttendance->check_out_at;
        $isCheckedOut = $todayAttendance && $todayAttendance->check_out_at;
        $user = auth()->user();
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');

        $journalClass = 'pending';
        $journalTitle = 'Awaiting Submission';
        $journalText = 'No weekly journal submitted yet.';

        if ($weeklyJournal) {
            if ($weeklyJournal->status === 'approved') {
                $journalClass = 'approved';
                $journalTitle = 'Approved';
                $journalText = 'Your mentor approved this week\'s notes.';
            } elseif ($weeklyJournal->status === 'needs_revision') {
                $journalClass = 'correction';
                $journalTitle = 'Correction Needed';
                $journalText = $weeklyJournal->missing_info_notes ?: 'Please review mentor notes and update your journal.';
            } else {
                $journalClass = 'pending';
                $journalTitle = 'Under Review';
                $journalText = 'Your weekly journal is submitted and waiting for mentor review.';
            }
        }
    @endphp

    <div class="app-shell">
        @include('dashboard.partials.student-sidebar', ['user' => $user, 'activePage' => 'dashboard'])

        <main class="content page-drift-up">
            <header class="topbar">
                <h1>Student Dashboard</h1>
                <p>Track attendance, submit daily logs, and monitor weekly journal status.</p>
            </header>

            <div class="page">
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
                @foreach (($attendanceAlerts ?? collect()) as $attendanceAlert)
                    @php $alertType = (string) data_get($attendanceAlert, 'type', 'info'); @endphp
                    <div class="alert {{ $alertType === 'error' ? 'error' : '' }}">
                        {{ data_get($attendanceAlert, 'message', '-') }}
                    </div>
                @endforeach

                <section class="grid">
                    <article id="checkin-card" class="card">
                        <h2>Check-in / Check-out</h2>
                        <div class="status-row">
                            @if ($isCheckedIn)
                                <span class="badge good">At Location</span>
                            @else
                                <span class="badge bad">Not Checked In</span>
                            @endif
                            @if ($todayAttendance && $todayAttendance->check_in_at)
                                <span class="muted">Checked in: {{ \Illuminate\Support\Carbon::parse($todayAttendance->check_in_at)->format('H:i') }}</span>
                            @endif
                        </div>

                        @php
                            $todayExcuseStatus = strtolower((string) ($todayExcuse->status ?? ''));
                            $todayExcuseType = strtoupper((string) ($todayExcuse->absence_type ?? '-'));
                        @endphp

                        @if ((!$todayAttendance || !$todayAttendance->check_in_at) && in_array($todayExcuseStatus, ['pending', 'approved'], true))
                            <button class="cta-btn done" type="button" disabled>
                                Absence {{ strtoupper($todayExcuseStatus) }} ({{ $todayExcuseType }})
                            </button>
                            <div class="muted" style="margin-top: 8px;">Check-in is blocked while an absence request is pending/approved.</div>
                        @elseif (!$todayAttendance || !$todayAttendance->check_in_at)
                            <button class="cta-btn" type="button" id="open-checkin-modal">Start My Day (Check In)</button>
                        @elseif (!$isCheckedOut)
                            <form method="POST" action="{{ route('dashboard.student.check-out') }}">
                                @csrf
                                <button class="cta-btn checkout" type="submit">Finish My Day (Check Out)</button>
                            </form>
                            <div class="muted" style="margin-top: 8px;">Complete your task log first before check-out.</div>
                        @else
                            <button class="cta-btn done" type="button" disabled>Day Completed</button>
                            <div class="muted" style="margin-top: 8px;">Checked out at {{ \Illuminate\Support\Carbon::parse($todayAttendance->check_out_at)->format('H:i') }}.</div>
                        @endif
                    </article>

                    <article id="journal-card" class="card">
                        <h2>Weekly Journal Status</h2>
                        <div class="journal-state {{ $journalClass }}">
                            <strong>{{ $journalTitle }}</strong>
                            <p class="muted" style="margin-top: 6px;">{{ $journalText }}</p>
                        </div>
                    </article>
                </section>

                <section class="grid">
                    <article id="task-log-card" class="card">
                        <h2>Today's Task Log</h2>
                        <p class="muted">Use the dedicated page to fill full sections and view mentor scoring.</p>
                        <a href="{{ route('dashboard.student.task-log-page') }}" class="small-btn" style="display: inline-block; text-decoration: none;">
                            Open Task Log Page
                        </a>
                        @error('task_log')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </article>

                    <article id="completion-card" class="card">
                        <h2>Completion Bar</h2>
                        <div class="progress-wrap">
                            <div class="progress-meta">
                                <span>You have completed {{ $completedDays }} out of {{ $targetDays }} days.</span>
                                <span>{{ $progressPercent }}%</span>
                            </div>
                            <div class="progress" style="--progress: {{ $progressPercent }}%;">
                                <span></span>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>

    <div class="modal-backdrop {{ $openProfileModal ? 'open' : '' }}" id="profile-modal-backdrop" aria-hidden="{{ $openProfileModal ? 'false' : 'true' }}">
        <div class="profile-modal" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title">
            <div class="modal-head">
                <h3 id="profile-modal-title">Edit Profile</h3>
                <button type="button" class="modal-close" id="close-profile-modal">Close</button>
            </div>

            <form id="profile-form" method="POST" action="{{ route('dashboard.student.profile') }}">
                @csrf
                <div class="field">
                    <label for="profile_name">Name</label>
                    <input id="profile_name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="field">
                    <label for="profile_nis">NIS</label>
                    <input id="profile_nis" type="text" value="{{ old('nis', $user->nis) }}" readonly disabled>
                    <input type="hidden" name="nis" value="{{ old('nis', $user->nis) }}">
                </div>
                <div class="field">
                    <label for="profile_avatar_file">Profile Picture</label>
                    <input id="profile_avatar_file" type="file" accept="image/*">
                    <input id="avatar_crop_data" name="avatar_crop_data" type="hidden">
                </div>
                <div class="crop-wrap">
                    <canvas id="avatar-crop-canvas" class="crop-canvas" width="420" height="315"></canvas>
                    <div class="crop-tools">
                        <div class="crop-tool-row">
                            <label for="avatar_zoom">Zoom</label>
                            <input id="avatar_zoom" type="range" min="100" max="1600" step="10" value="100">
                            <span class="crop-tool-value" id="avatar_zoom_value">100%</span>
                        </div>
                        <div class="crop-tool-row">
                            <label for="avatar_rotate">Rotate</label>
                            <input id="avatar_rotate" type="range" min="-180" max="180" step="1" value="0">
                            <span class="crop-tool-value" id="avatar_rotate_value">0deg</span>
                        </div>
                        <div class="crop-actions">
                            <button type="button" class="crop-btn" id="avatar_rotate_left">Rotate -90</button>
                            <button type="button" class="crop-btn" id="avatar_rotate_right">Rotate +90</button>
                            <button type="button" class="crop-btn" id="avatar_flip_x">Flip H</button>
                            <button type="button" class="crop-btn" id="avatar_flip_y">Flip V</button>
                            <button type="button" class="crop-btn" id="avatar_reset">Reset</button>
                        </div>
                    </div>
                    <p class="muted" style="margin-top:8px;">Drag image to position. Scroll mouse wheel to zoom quickly.</p>
                    @error('avatar_crop_data')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>
                <div class="field">
                    <label for="profile_password">New Password (optional)</label>
                    <input id="profile_password" name="password" type="password" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="profile_password_confirmation">Confirm New Password</label>
                    <input id="profile_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                </div>

                <div class="modal-actions">
                    <button class="save-btn" type="submit">Save Profile</button>
                </div>
            </form>

            <div class="modal-actions">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-btn" type="submit">Log out</button>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="checkin-modal-backdrop" aria-hidden="true">
        <div class="profile-modal checkin-modal" role="dialog" aria-modal="true" aria-labelledby="checkin-modal-title">
            <div class="modal-head">
                <h3 id="checkin-modal-title">Start My Day - Verification</h3>
                <button type="button" class="modal-close" id="close-checkin-modal">Close</button>
            </div>

            <form method="POST" action="{{ route('dashboard.student.check-in') }}" id="checkin-form">
                @csrf
                <input type="hidden" name="latitude" id="checkin-latitude">
                <input type="hidden" name="longitude" id="checkin-longitude">
                <input type="hidden" name="selfie_data" id="checkin-selfie-data">

                <div class="checkin-grid">
                    <div class="checkin-box">
                        <strong>Location</strong>
                        <p class="muted" id="location-status" style="margin-top:6px;">Waiting for permission...</p>
                        <div class="checkin-actions">
                            <button type="button" class="checkin-btn" id="btn-get-location">Get Location</button>
                        </div>
                    </div>

                    <div class="checkin-box">
                        <strong>Selfie Verification</strong>
                        <video id="selfie-video" class="checkin-video" autoplay muted playsinline></video>
                        <img id="selfie-preview" class="checkin-preview" style="display:none;" alt="Selfie preview">
                        <canvas id="selfie-canvas" width="640" height="480" style="display:none;"></canvas>
                        <div class="checkin-actions">
                            <button type="button" class="checkin-btn" id="btn-start-camera">Start Camera</button>
                            <button type="button" class="checkin-btn" id="btn-capture-selfie">Capture</button>
                            <button type="button" class="checkin-btn" id="btn-retake-selfie">Retake</button>
                        </div>
                        <p class="muted" id="selfie-status" style="margin-top:6px;">No selfie captured.</p>
                    </div>
                </div>

                <div class="modal-actions" style="margin-top:14px;">
                    <button type="submit" class="checkin-btn primary" id="btn-submit-checkin" disabled>Submit Check-in</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const openBtn = document.getElementById('open-profile-modal');
            const closeBtn = document.getElementById('close-profile-modal');
            const backdrop = document.getElementById('profile-modal-backdrop');
            const fileInput = document.getElementById('profile_avatar_file');
            const cropInput = document.getElementById('avatar_crop_data');
            const canvas = document.getElementById('avatar-crop-canvas');
            const zoomInput = document.getElementById('avatar_zoom');
            const zoomValue = document.getElementById('avatar_zoom_value');
            const rotateInput = document.getElementById('avatar_rotate');
            const rotateValue = document.getElementById('avatar_rotate_value');
            const rotateLeftBtn = document.getElementById('avatar_rotate_left');
            const rotateRightBtn = document.getElementById('avatar_rotate_right');
            const flipXBtn = document.getElementById('avatar_flip_x');
            const flipYBtn = document.getElementById('avatar_flip_y');
            const resetBtn = document.getElementById('avatar_reset');
            const form = document.getElementById('profile-form');
            if (
                !openBtn || !closeBtn || !backdrop || !fileInput || !cropInput || !canvas || !form ||
                !zoomInput || !zoomValue || !rotateInput || !rotateValue || !rotateLeftBtn || !rotateRightBtn ||
                !flipXBtn || !flipYBtn || !resetBtn
            ) return;

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            const state = {
                image: null,
                baseScale: 1,
                zoom: 1,
                rotation: 0,
                flipX: 1,
                flipY: 1,
                panX: 0,
                panY: 0,
                dragging: false,
                pointerId: null,
                startX: 0,
                startY: 0,
                startPanX: 0,
                startPanY: 0,
            };
            const frameSize = Math.round(Math.min(canvas.width, canvas.height) * 0.74);
            const frameX = (canvas.width - frameSize) / 2;
            const frameY = (canvas.height - frameSize) / 2;
            const frameCenterX = canvas.width / 2;
            const frameCenterY = canvas.height / 2;
            const zoomMin = 1;
            const zoomMax = 16;
            const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

            const updateFlipButtons = () => {
                flipXBtn.classList.toggle('is-active', state.flipX === -1);
                flipYBtn.classList.toggle('is-active', state.flipY === -1);
            };

            const updateControlLabels = () => {
                zoomValue.textContent = `${Math.round(state.zoom * 100)}%`;
                rotateValue.textContent = `${Math.round(state.rotation)}deg`;
                zoomInput.value = String(Math.round(state.zoom * 100));
                rotateInput.value = String(Math.round(state.rotation));
                updateFlipButtons();
            };

            const openModal = () => {
                backdrop.classList.add('open');
                backdrop.setAttribute('aria-hidden', 'false');
            };

            const closeModal = () => {
                backdrop.classList.remove('open');
                backdrop.setAttribute('aria-hidden', 'true');
            };

            const drawPlaceholder = () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = 'rgba(148, 163, 184, 0.9)';
                ctx.font = '14px Instrument Sans';
                ctx.textAlign = 'center';
                ctx.fillText('Choose an image to crop', canvas.width / 2, canvas.height / 2);
            };

            const drawTransformedImage = (drawCtx) => {
                if (!state.image) return;

                const scale = state.baseScale * state.zoom;
                drawCtx.save();
                drawCtx.translate(frameCenterX + state.panX, frameCenterY + state.panY);
                drawCtx.rotate((state.rotation * Math.PI) / 180);
                drawCtx.scale(scale * state.flipX, scale * state.flipY);
                drawCtx.drawImage(state.image, -state.image.width / 2, -state.image.height / 2);
                drawCtx.restore();
            };

            const draw = () => {
                if (!state.image) {
                    drawPlaceholder();
                    return;
                }

                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = 'rgba(15, 23, 42, 0.92)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                drawTransformedImage(ctx);

                ctx.fillStyle = 'rgba(2, 6, 23, 0.45)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.save();
                ctx.beginPath();
                ctx.rect(frameX, frameY, frameSize, frameSize);
                ctx.clip();
                drawTransformedImage(ctx);
                ctx.restore();

                ctx.strokeStyle = 'rgba(56, 189, 248, 0.95)';
                ctx.lineWidth = 2;
                ctx.strokeRect(frameX, frameY, frameSize, frameSize);
            };

            const setImage = (img) => {
                state.image = img;
                state.baseScale = Math.max(frameSize / img.width, frameSize / img.height);
                state.zoom = zoomMin;
                state.rotation = 0;
                state.flipX = 1;
                state.flipY = 1;
                state.panX = 0;
                state.panY = 0;
                state.dragging = false;
                state.pointerId = null;
                canvas.classList.remove('dragging');
                updateControlLabels();
                draw();
            };

            const getPointer = (event) => {
                const rect = canvas.getBoundingClientRect();
                const scaleX = canvas.width / rect.width;
                const scaleY = canvas.height / rect.height;
                return {
                    x: (event.clientX - rect.left) * scaleX,
                    y: (event.clientY - rect.top) * scaleY,
                };
            };

            fileInput.addEventListener('change', (event) => {
                const file = event.target.files && event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = () => {
                    const img = new Image();
                    img.onload = () => setImage(img);
                    img.src = String(reader.result || '');
                };
                reader.readAsDataURL(file);
            });

            zoomInput.addEventListener('input', () => {
                state.zoom = clamp(Number(zoomInput.value) / 100, zoomMin, zoomMax);
                updateControlLabels();
                draw();
            });

            rotateInput.addEventListener('input', () => {
                state.rotation = clamp(Number(rotateInput.value), -180, 180);
                updateControlLabels();
                draw();
            });

            rotateLeftBtn.addEventListener('click', () => {
                state.rotation = clamp(state.rotation - 90, -180, 180);
                updateControlLabels();
                draw();
            });

            rotateRightBtn.addEventListener('click', () => {
                state.rotation = clamp(state.rotation + 90, -180, 180);
                updateControlLabels();
                draw();
            });

            flipXBtn.addEventListener('click', () => {
                state.flipX *= -1;
                updateControlLabels();
                draw();
            });

            flipYBtn.addEventListener('click', () => {
                state.flipY *= -1;
                updateControlLabels();
                draw();
            });

            resetBtn.addEventListener('click', () => {
                if (!state.image) return;
                state.zoom = zoomMin;
                state.rotation = 0;
                state.flipX = 1;
                state.flipY = 1;
                state.panX = 0;
                state.panY = 0;
                updateControlLabels();
                draw();
            });

            canvas.addEventListener('pointerdown', (event) => {
                if (!state.image || state.dragging) return;
                state.dragging = true;
                state.pointerId = event.pointerId;
                const point = getPointer(event);
                state.startX = point.x;
                state.startY = point.y;
                state.startPanX = state.panX;
                state.startPanY = state.panY;
                canvas.classList.add('dragging');
                if (canvas.setPointerCapture) canvas.setPointerCapture(event.pointerId);
            });

            canvas.addEventListener('pointermove', (event) => {
                if (!state.dragging || event.pointerId !== state.pointerId) return;
                const point = getPointer(event);
                state.panX = state.startPanX + (point.x - state.startX);
                state.panY = state.startPanY + (point.y - state.startY);
                draw();
            });

            const endDrag = (event) => {
                if (!state.dragging) return;
                if (typeof event.pointerId === 'number' && state.pointerId !== event.pointerId) return;
                state.dragging = false;
                state.pointerId = null;
                canvas.classList.remove('dragging');
                if (typeof event.pointerId === 'number' && canvas.releasePointerCapture) {
                    try {
                        canvas.releasePointerCapture(event.pointerId);
                    } catch (error) {
                        // Ignore capture-release mismatch.
                    }
                }
            };

            canvas.addEventListener('pointerup', endDrag);
            canvas.addEventListener('pointercancel', endDrag);
            canvas.addEventListener('lostpointercapture', endDrag);

            canvas.addEventListener('wheel', (event) => {
                if (!state.image) return;
                event.preventDefault();
                const delta = event.deltaY > 0 ? -0.09 : 0.09;
                state.zoom = clamp(state.zoom + delta, zoomMin, zoomMax);
                updateControlLabels();
                draw();
            }, { passive: false });

            form.addEventListener('submit', (event) => {
                cropInput.value = '';
                if (!state.image || !(fileInput.files && fileInput.files.length)) {
                    return;
                }

                const out = document.createElement('canvas');
                out.width = 640;
                out.height = 640;
                const outCtx = out.getContext('2d');
                if (!outCtx) {
                    event.preventDefault();
                    return;
                }

                outCtx.fillStyle = 'rgba(15, 23, 42, 1)';
                outCtx.fillRect(0, 0, out.width, out.height);

                const outputScale = out.width / frameSize;
                outCtx.save();
                outCtx.translate(out.width / 2, out.height / 2);
                outCtx.scale(outputScale, outputScale);
                outCtx.translate(-frameCenterX, -frameCenterY);
                drawTransformedImage(outCtx);
                outCtx.restore();

                cropInput.value = out.toDataURL('image/png');
            });

            openBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);

            backdrop.addEventListener('click', (event) => {
                if (event.target === backdrop) closeModal();
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && backdrop.classList.contains('open')) closeModal();
            });

            updateControlLabels();
            drawPlaceholder();
        })();

        (() => {
            const openBtn = document.getElementById('open-checkin-modal');
            const closeBtn = document.getElementById('close-checkin-modal');
            const backdrop = document.getElementById('checkin-modal-backdrop');
            const form = document.getElementById('checkin-form');
            const latInput = document.getElementById('checkin-latitude');
            const lngInput = document.getElementById('checkin-longitude');
            const selfieInput = document.getElementById('checkin-selfie-data');
            const locationStatus = document.getElementById('location-status');
            const selfieStatus = document.getElementById('selfie-status');
            const submitBtn = document.getElementById('btn-submit-checkin');
            const getLocationBtn = document.getElementById('btn-get-location');
            const startCameraBtn = document.getElementById('btn-start-camera');
            const captureBtn = document.getElementById('btn-capture-selfie');
            const retakeBtn = document.getElementById('btn-retake-selfie');
            const video = document.getElementById('selfie-video');
            const preview = document.getElementById('selfie-preview');
            const canvas = document.getElementById('selfie-canvas');

            if (!closeBtn || !backdrop || !form || !latInput || !lngInput || !selfieInput || !locationStatus || !selfieStatus || !submitBtn || !getLocationBtn || !startCameraBtn || !captureBtn || !retakeBtn || !video || !preview || !canvas) return;

            let stream = null;

            const updateSubmitState = () => {
                const ready = !!latInput.value && !!lngInput.value && !!selfieInput.value;
                submitBtn.disabled = !ready;
            };

            const stopCamera = () => {
                if (!stream) return;
                stream.getTracks().forEach((track) => track.stop());
                stream = null;
            };

            const openModal = () => {
                backdrop.classList.add('open');
                backdrop.setAttribute('aria-hidden', 'false');
            };

            const closeModal = () => {
                backdrop.classList.remove('open');
                backdrop.setAttribute('aria-hidden', 'true');
                stopCamera();
            };

            const resetVerificationState = () => {
                latInput.value = '';
                lngInput.value = '';
                selfieInput.value = '';
                preview.src = '';
                preview.style.display = 'none';
                video.style.display = 'block';
                locationStatus.textContent = 'Waiting for permission...';
                selfieStatus.textContent = 'No selfie captured.';
                updateSubmitState();
            };

            const getLocation = () => {
                if (!navigator.geolocation) {
                    locationStatus.textContent = 'Geolocation is not supported on this browser.';
                    return;
                }

                locationStatus.textContent = 'Getting your location...';
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        latInput.value = String(position.coords.latitude);
                        lngInput.value = String(position.coords.longitude);
                        locationStatus.textContent = `Location captured (${Number(latInput.value).toFixed(5)}, ${Number(lngInput.value).toFixed(5)})`;
                        updateSubmitState();
                    },
                    (error) => {
                        locationStatus.textContent = `Location permission failed: ${error.message}`;
                        updateSubmitState();
                    },
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
                );
            };

            const startCamera = async () => {
                try {
                    stopCamera();
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                    video.srcObject = stream;
                    video.style.display = 'block';
                    preview.style.display = 'none';
                    selfieStatus.textContent = 'Camera ready. Capture your selfie.';
                } catch (error) {
                    selfieStatus.textContent = `Camera permission failed: ${error.message}`;
                }
            };

            const captureSelfie = () => {
                if (!video.videoWidth || !video.videoHeight) {
                    selfieStatus.textContent = 'Camera is not ready yet.';
                    return;
                }
                const ctx = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const data = canvas.toDataURL('image/jpeg', 0.92);
                selfieInput.value = data;
                preview.src = data;
                preview.style.display = 'block';
                video.style.display = 'none';
                selfieStatus.textContent = 'Selfie captured.';
                updateSubmitState();
            };

            const retakeSelfie = async () => {
                selfieInput.value = '';
                preview.src = '';
                preview.style.display = 'none';
                video.style.display = 'block';
                selfieStatus.textContent = 'Retake your selfie.';
                updateSubmitState();
                await startCamera();
            };

            if (openBtn) openBtn.addEventListener('click', () => {
                resetVerificationState();
                openModal();
                getLocation();
                startCamera();
            });

            closeBtn.addEventListener('click', closeModal);
            backdrop.addEventListener('click', (event) => {
                if (event.target === backdrop) closeModal();
            });
            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && backdrop.classList.contains('open')) closeModal();
            });

            getLocationBtn.addEventListener('click', getLocation);
            startCameraBtn.addEventListener('click', startCamera);
            captureBtn.addEventListener('click', captureSelfie);
            retakeBtn.addEventListener('click', retakeSelfie);
            form.addEventListener('submit', () => {
                stopCamera();
            });
        })();
    </script>
</body>
</html>

