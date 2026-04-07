<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Check-in / Check-out - {{ config('app.name', 'Kips') }}</title>

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
            text-decoration: none;
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

        .content {
            padding: 22px;
        }

        .topbar {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(30, 41, 59, 0.7);
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .topbar h1 {
            font-size: 1.35rem;
            margin-bottom: 6px;
        }

        .topbar p {
            color: var(--muted);
            font-size: 0.93rem;
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

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(30, 41, 59, 0.72);
            padding: 14px;
        }

        .card h2 {
            font-size: 1.05rem;
            margin-bottom: 10px;
        }

        .today-stat {
            display: grid;
            gap: 8px;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .today-stat strong {
            color: #f8fafc;
            font-weight: 600;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .badge.good {
            background: rgba(34, 197, 94, 0.2);
            color: #bbf7d0;
            border: 1px solid rgba(34, 197, 94, 0.5);
        }

        .badge.warn {
            background: rgba(245, 158, 11, 0.18);
            color: #fde68a;
            border: 1px solid rgba(245, 158, 11, 0.5);
        }

        .action-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 700;
            background: rgba(30, 41, 59, 0.7);
            color: var(--text);
            cursor: pointer;
        }

        .btn.primary {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #f8fafc;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .section {
            margin-top: 14px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(30, 41, 59, 0.7);
            padding: 14px;
        }

        .section h3 {
            font-size: 1.04rem;
            margin-bottom: 8px;
        }

        .history-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .history-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.62);
            padding: 10px;
            text-align: left;
            color: var(--text);
            cursor: pointer;
            transition: border-color 0.2s ease, transform 0.2s ease;
        }

        .history-card:hover {
            border-color: var(--accent);
            transform: translateY(-1px);
        }

        .history-date {
            font-weight: 700;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        .history-meta {
            color: var(--muted);
            font-size: 0.83rem;
            line-height: 1.5;
        }

        .muted {
            color: var(--muted);
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

        .modal {
            width: min(680px, 96vw);
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 16px;
            box-shadow: 0 24px 36px rgba(2, 6, 23, 0.52);
        }

        .modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .modal-close {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 6px 10px;
            cursor: pointer;
        }

        .field {
            margin-top: 12px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.93rem;
        }

        .field input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.65);
            color: var(--text);
            padding: 11px 12px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .field input:focus {
            border-color: rgba(56, 189, 248, 0.9);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
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

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .detail-item {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.6);
            padding: 10px;
        }

        .detail-item strong {
            display: block;
            margin-bottom: 5px;
            font-size: 0.84rem;
            color: var(--muted);
        }

        .detail-photo {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--border);
            margin-top: 10px;
            background: rgba(15, 23, 42, 0.6);
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

        @media (max-width: 980px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                height: auto;
            }

            .grid,
            .history-grid,
            .detail-grid,
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
    </style>
</head>
<body>
@php
    $user = auth()->user();
    $isCheckedIn = !empty($todayAttendance?->check_in_at);
    $isCheckedOut = !empty($todayAttendance?->check_out_at);
    $previousDays = $attendanceHistory->filter(fn ($row) => $row->attendance_date !== $today)->values();
    $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
@endphp
    <div class="app-shell">
        @include('dashboard.partials.student-sidebar', ['user' => $user, 'activePage' => 'checkin'])

        <main class="content page-drift-up">
            <header class="topbar">
                <h1>Check-in / Check-out</h1>
                <p>WIB (Asia/Jakarta) time is used for attendance date and exact check-in/check-out timestamps.</p>
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

            <section class="grid">
                <article class="card">
                    <h2>Today ({{ \Illuminate\Support\Carbon::parse($today)->format('d M Y') }})</h2>
                    @if ($isCheckedIn)
                        <span class="badge good">Checked In</span>
                    @else
                        <span class="badge warn">Not Checked In</span>
                    @endif

                    <div class="today-stat">
                        <div><strong>Check-in:</strong> {{ $todayAttendance?->check_in_at ? \Illuminate\Support\Carbon::parse($todayAttendance->check_in_at, 'Asia/Jakarta')->format('H:i:s') . ' WIB' : '-' }}</div>
                        <div><strong>Check-out:</strong> {{ $todayAttendance?->check_out_at ? \Illuminate\Support\Carbon::parse($todayAttendance->check_out_at, 'Asia/Jakarta')->format('H:i:s') . ' WIB' : '-' }}</div>
                        <div><strong>IP Address:</strong> {{ $todayAttendance->ip_address ?? '-' }}</div>
                        <div><strong>Location:</strong> {{ isset($todayAttendance->latitude) ? $todayAttendance->latitude : '-' }}, {{ isset($todayAttendance->longitude) ? $todayAttendance->longitude : '-' }}</div>
                        <div><strong>Status:</strong> {{ strtoupper($todayAttendance->status ?? 'N/A') }}</div>
                    </div>
                </article>

                <article class="card">
                    <h2>Action</h2>
                    <div class="action-row">
                        @if (!$isCheckedIn)
                            <button class="btn primary" type="button" id="open-checkin-modal">Start My Day (Check In)</button>
                        @elseif (!$isCheckedOut)
                            <form method="POST" action="{{ route('dashboard.student.check-out') }}">
                                @csrf
                                <button class="btn primary" type="submit">Finish My Day (Check Out)</button>
                            </form>
                        @else
                            <button class="btn" type="button" disabled>Day Completed</button>
                        @endif
                    </div>
                    <p class="muted" style="margin-top:10px;">
                        To submit check-in, location + selfie are required. IP is recorded automatically on server.
                    </p>
                </article>
            </section>

            <section class="section">
                <h3>Previous Days</h3>
                @if ($previousDays->isEmpty())
                    <p class="muted">No previous check-in records yet.</p>
                @else
                    <div class="history-grid">
                        @foreach ($previousDays as $row)
                            <button
                                type="button"
                                class="history-card"
                                data-attendance-card
                                data-date="{{ \Illuminate\Support\Carbon::parse($row->attendance_date)->format('d M Y') }}"
                                data-checkin="{{ $row->check_in_at ? \Illuminate\Support\Carbon::parse($row->check_in_at, 'Asia/Jakarta')->format('H:i:s') . ' WIB' : '-' }}"
                                data-checkout="{{ $row->check_out_at ? \Illuminate\Support\Carbon::parse($row->check_out_at, 'Asia/Jakarta')->format('H:i:s') . ' WIB' : '-' }}"
                                data-ip="{{ $row->ip_address ?? '-' }}"
                                data-status="{{ strtoupper($row->status ?? 'N/A') }}"
                                data-lat="{{ isset($row->latitude) ? $row->latitude : '-' }}"
                                data-lng="{{ isset($row->longitude) ? $row->longitude : '-' }}"
                                data-photo="{{ !empty($row->photo_path) ? \Illuminate\Support\Facades\Storage::url($row->photo_path) : '' }}"
                            >
                                <div class="history-date">{{ \Illuminate\Support\Carbon::parse($row->attendance_date)->format('D, d M Y') }}</div>
                                <div class="history-meta">
                                    In: {{ $row->check_in_at ? \Illuminate\Support\Carbon::parse($row->check_in_at, 'Asia/Jakarta')->format('H:i:s') : '-' }} WIB<br>
                                    Out: {{ $row->check_out_at ? \Illuminate\Support\Carbon::parse($row->check_out_at, 'Asia/Jakarta')->format('H:i:s') : '-' }} WIB
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </div>

    <div class="modal-backdrop {{ $openProfileModal ? 'open' : '' }}" id="profile-modal-backdrop" aria-hidden="{{ $openProfileModal ? 'false' : 'true' }}">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title">
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
                        <div class="alert error" style="margin-top:8px;">{{ $message }}</div>
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

    <div class="modal-backdrop" id="attendance-detail-backdrop" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="attendance-detail-title">
            <div class="modal-head">
                <h3 id="attendance-detail-title">Attendance Detail</h3>
                <button type="button" class="modal-close" id="close-attendance-detail">Close</button>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <strong>Date</strong>
                    <div id="detail-date">-</div>
                </div>
                <div class="detail-item">
                    <strong>Status</strong>
                    <div id="detail-status">-</div>
                </div>
                <div class="detail-item">
                    <strong>Check-in Time (WIB)</strong>
                    <div id="detail-checkin">-</div>
                </div>
                <div class="detail-item">
                    <strong>Check-out Time (WIB)</strong>
                    <div id="detail-checkout">-</div>
                </div>
                <div class="detail-item">
                    <strong>IP Address</strong>
                    <div id="detail-ip">-</div>
                </div>
                <div class="detail-item">
                    <strong>Coordinates</strong>
                    <div id="detail-coordinates">-</div>
                </div>
            </div>

            <img id="detail-photo" class="detail-photo" alt="Check-in selfie" style="display:none;">
            <p class="muted" id="detail-photo-empty" style="margin-top:8px;">No selfie photo stored for this record.</p>
        </div>
    </div>

    <div class="modal-backdrop" id="checkin-modal-backdrop" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="checkin-modal-title">
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

                <div style="margin-top:14px;">
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
            const cards = document.querySelectorAll('[data-attendance-card]');
            const backdrop = document.getElementById('attendance-detail-backdrop');
            const closeBtn = document.getElementById('close-attendance-detail');
            const dateEl = document.getElementById('detail-date');
            const statusEl = document.getElementById('detail-status');
            const checkinEl = document.getElementById('detail-checkin');
            const checkoutEl = document.getElementById('detail-checkout');
            const ipEl = document.getElementById('detail-ip');
            const coordinatesEl = document.getElementById('detail-coordinates');
            const photoEl = document.getElementById('detail-photo');
            const photoEmptyEl = document.getElementById('detail-photo-empty');
            if (!cards.length || !backdrop || !closeBtn || !dateEl || !statusEl || !checkinEl || !checkoutEl || !ipEl || !coordinatesEl || !photoEl || !photoEmptyEl) return;

            const open = () => {
                backdrop.classList.add('open');
                backdrop.setAttribute('aria-hidden', 'false');
            };

            const close = () => {
                backdrop.classList.remove('open');
                backdrop.setAttribute('aria-hidden', 'true');
            };

            cards.forEach((card) => {
                card.addEventListener('click', () => {
                    const { date, status, checkin, checkout, ip, lat, lng, photo } = card.dataset;
                    dateEl.textContent = date || '-';
                    statusEl.textContent = status || '-';
                    checkinEl.textContent = checkin || '-';
                    checkoutEl.textContent = checkout || '-';
                    ipEl.textContent = ip || '-';
                    coordinatesEl.textContent = `${lat || '-'}, ${lng || '-'}`;

                    if (photo) {
                        photoEl.src = photo;
                        photoEl.style.display = 'block';
                        photoEmptyEl.style.display = 'none';
                    } else {
                        photoEl.src = '';
                        photoEl.style.display = 'none';
                        photoEmptyEl.style.display = 'block';
                    }

                    open();
                });
            });

            closeBtn.addEventListener('click', close);
            backdrop.addEventListener('click', (event) => {
                if (event.target === backdrop) close();
            });
            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && backdrop.classList.contains('open')) close();
            });
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
                if (!ctx) return;
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
