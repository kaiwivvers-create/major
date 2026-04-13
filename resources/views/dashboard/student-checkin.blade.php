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
        .alert.warn {
            border-color: rgba(245, 158, 11, 0.65);
            background: rgba(120, 53, 15, 0.28);
        }
        .alert.info {
            border-color: rgba(56, 189, 248, 0.6);
            background: rgba(30, 64, 175, 0.2);
        }

        input[type="file"] {
            width: 100%;
            border: 1px solid rgba(56, 189, 248, 0.35);
            border-radius: 12px;
            background:
                linear-gradient(160deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.92));
            color: #cbd5e1;
            padding: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
        }

        input[type="file"]::file-selector-button {
            border: 1px solid rgba(56, 189, 248, 0.45);
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.92), rgba(14, 165, 233, 0.82));
            color: #f8fafc;
            padding: 8px 12px;
            margin-right: 12px;
            font-weight: 700;
            font-size: 0.84rem;
            cursor: pointer;
        }

        input[type="file"]:hover {
            border-color: rgba(103, 232, 249, 0.7);
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

        .timer-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 8px 10px;
            border: 1px solid rgba(245, 158, 11, 0.5);
            border-radius: 999px;
            background: rgba(120, 53, 15, 0.24);
            color: #fde68a;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .timer-chip.expired {
            border-color: rgba(248, 113, 113, 0.5);
            background: rgba(127, 29, 29, 0.24);
            color: #fecaca;
        }

        .secondary-btn,
        .link-btn {
            border: 1px solid rgba(56, 189, 248, 0.35);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.72);
            color: #cbd5e1;
            padding: 9px 12px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .secondary-btn:hover,
        .link-btn:hover {
            border-color: rgba(103, 232, 249, 0.7);
            color: #f8fafc;
        }

        .attachment-preview-box {
            margin-top: 10px;
            border: 1px solid rgba(51, 65, 85, 0.8);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.55);
            padding: 10px;
        }

        .attachment-preview-image {
            width: 100%;
            max-width: 260px;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid rgba(56, 189, 248, 0.28);
            background: rgba(2, 6, 23, 0.7);
            display: none;
        }

        .attachment-preview-empty {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .attachment-meta {
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.85rem;
        }

        .viewer-modal {
            width: min(760px, 96vw);
        }

        .attachment-viewer-frame {
            width: 100%;
            min-height: 520px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.92);
        }

        .attachment-viewer-image {
            width: 100%;
            max-height: 72vh;
            object-fit: contain;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.92);
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
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.65);
            color: var(--text);
            padding: 11px 12px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            font-family: inherit;
        }
        .field textarea {
            min-height: 100px;
            resize: vertical;
        }
        .field select:focus,
        .field textarea:focus {
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
    $isCheckedIn = !empty($todayAttendance?->check_in_at);
    $isCheckedOut = !empty($todayAttendance?->check_out_at);
    $previousDays = $attendanceHistory->filter(fn ($row) => $row->attendance_date !== $today)->values();
    $todayExcuseStatus = strtolower((string) ($todayExcuse->status ?? ''));
    $todayExcuseType = strtolower((string) ($todayExcuse->absence_type ?? ''));
    $todayExcuseCreatedAt = !empty($todayExcuse?->created_at)
        ? \Illuminate\Support\Carbon::parse($todayExcuse->created_at, 'Asia/Jakarta')
        : null;
    $todayExcuseEditableUntil = $todayExcuseCreatedAt?->copy()->addMinutes(30);
    $todayExcuseIsEditable = !$isCheckedIn
        && $todayExcuseStatus === 'pending'
        && $todayExcuseEditableUntil
        && now('Asia/Jakarta')->lessThanOrEqualTo($todayExcuseEditableUntil);
    $todayExcuseAttachmentUrl = !empty($todayExcuse?->attachment_path)
        ? \Illuminate\Support\Facades\Storage::url($todayExcuse->attachment_path)
        : null;
    $todayExcuseAttachmentExtension = strtolower(pathinfo((string) ($todayExcuse->attachment_path ?? ''), PATHINFO_EXTENSION));
    $todayExcuseAttachmentIsPdf = $todayExcuseAttachmentExtension === 'pdf';
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
            @foreach (($attendanceAlerts ?? collect()) as $alertText)
                <div class="alert warn">{{ $alertText }}</div>
            @endforeach

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
                        <div><strong>Late Minutes:</strong> {{ (int) ($todayAttendance->late_minutes ?? 0) }}</div>
                        <div><strong>Check-in Cutoff:</strong> {{ \Illuminate\Support\Str::substr((string) ($checkInCutoffTime ?? '08:00:00'), 0, 5) }} WIB</div>
                    </div>
                </article>

                <article class="card">
                    <h2>Action</h2>
                    <div class="action-row">
                        @if (!$isCheckedIn && in_array($todayExcuseStatus, ['pending', 'approved'], true))
                            <button class="btn" type="button" disabled>
                                Absence {{ strtoupper($todayExcuseStatus) }} ({{ strtoupper($todayExcuseType ?: '-') }})
                            </button>
                        @elseif (!$isCheckedIn)
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
                <h3>Absence Request (SICK / PERMIT)</h3>
                @if (!empty($todayAttendance?->check_in_at))
                    <p class="muted">You already checked in today, so absence request is disabled.</p>
                @elseif (empty($todayExcuse) || $todayExcuseIsEditable)
                    <form method="POST" action="{{ route('dashboard.student.absence-request') }}" enctype="multipart/form-data" id="absence-request-form">
                        @csrf
                        <div class="field">
                            <label for="absence_type">Type</label>
                            <select id="absence_type" name="absence_type" required>
                                @php $absenceTypeOld = old('absence_type', $todayExcuse->absence_type ?? 'sick'); @endphp
                                <option value="sick" {{ $absenceTypeOld === 'sick' ? 'selected' : '' }}>SICK</option>
                                <option value="permit" {{ $absenceTypeOld === 'permit' ? 'selected' : '' }}>PERMIT</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="absence_reason">Reason</label>
                            <textarea id="absence_reason" name="reason" required>{{ old('reason', $todayExcuse->reason ?? '') }}</textarea>
                        </div>
                        <div class="field">
                            <label for="absence_attachment">Attachment (optional: JPG/PNG/PDF, max 4MB)</label>
                            <input id="absence_attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf,application/pdf">
                            <input id="absence_attachment_crop_data" name="attachment_crop_data" type="hidden">
                            <div class="attachment-preview-box">
                                <img id="absence_attachment_preview" class="attachment-preview-image" alt="Attachment preview">
                                <div id="absence_attachment_empty" class="attachment-preview-empty">Choose an image to preview and crop before you submit. PDF files keep their original document preview.</div>
                                <div id="absence_attachment_meta" class="attachment-meta"></div>
                            </div>
                        </div>
                        <div class="crop-wrap" id="absence-crop-wrap" style="display:none;">
                            <canvas id="absence-crop-canvas" class="crop-canvas" width="420" height="315"></canvas>
                            <div class="crop-tools">
                                <div class="crop-tool-row">
                                    <label for="absence_zoom">Zoom</label>
                                    <input id="absence_zoom" type="range" min="100" max="1600" step="10" value="100">
                                    <span class="crop-tool-value" id="absence_zoom_value">100%</span>
                                </div>
                                <div class="crop-tool-row">
                                    <label for="absence_rotate">Rotate</label>
                                    <input id="absence_rotate" type="range" min="-180" max="180" step="1" value="0">
                                    <span class="crop-tool-value" id="absence_rotate_value">0deg</span>
                                </div>
                                <div class="crop-actions">
                                    <button type="button" class="crop-btn" id="absence_rotate_left">Rotate -90</button>
                                    <button type="button" class="crop-btn" id="absence_rotate_right">Rotate +90</button>
                                    <button type="button" class="crop-btn" id="absence_reset">Reset</button>
                                </div>
                            </div>
                            <p class="muted" style="margin-top:8px;">Image attachments can be previewed and cropped before submission. PDFs stay unchanged.</p>
                        </div>
                        <div class="action-row" style="margin-top:10px;">
                            <button class="btn primary" type="submit">{{ empty($todayExcuse) ? 'Submit Absence Request' : 'Update Today\'s Request' }}</button>
                        </div>
                    </form>
                @else
                    <p class="muted">Today's request can no longer be edited. The 30-minute edit window has ended or the request was already reviewed.</p>
                @endif

                @if (!empty($todayExcuse))
                    <div class="card" style="margin-top:10px;">
                        <h2 style="margin-bottom:8px;">Today's Request</h2>
                        <div class="today-stat">
                            <div><strong>Status:</strong> {{ strtoupper((string) ($todayExcuse->status ?? '-')) }}</div>
                            <div><strong>Type:</strong> {{ strtoupper((string) ($todayExcuse->absence_type ?? '-')) }}</div>
                            <div><strong>Reason:</strong> {{ $todayExcuse->reason ?? '-' }}</div>
                            @if ($todayExcuseEditableUntil)
                                <div>
                                    <strong>Edit Window:</strong>
                                    <span
                                        id="today-excuse-timer"
                                        class="timer-chip {{ $todayExcuseIsEditable ? '' : 'expired' }}"
                                        data-deadline="{{ $todayExcuseEditableUntil->toIso8601String() }}"
                                    >
                                        {{ $todayExcuseIsEditable ? 'Calculating remaining edit time...' : 'Editing closed' }}
                                    </span>
                                </div>
                            @endif
                            @if (!empty($todayExcuse->rejection_notes))
                                <div><strong>Rejection Notes:</strong> {{ $todayExcuse->rejection_notes }}</div>
                            @endif
                            @if ($todayExcuseAttachmentUrl)
                                <div class="action-row">
                                    <button
                                        type="button"
                                        class="link-btn"
                                        id="open-attachment-modal"
                                        data-attachment-url="{{ $todayExcuseAttachmentUrl }}"
                                        data-attachment-type="{{ $todayExcuseAttachmentIsPdf ? 'pdf' : 'image' }}"
                                    >
                                        View Attachment
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
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

            <section class="section">
                <h3>Recent Absence Requests</h3>
                @if (($recentExcuses ?? collect())->isEmpty())
                    <p class="muted">No absence requests yet.</p>
                @else
                    <div class="history-grid">
                        @foreach (($recentExcuses ?? collect()) as $row)
                            <div class="history-card" style="cursor:default;">
                                <div class="history-date">{{ \Illuminate\Support\Carbon::parse($row->attendance_date)->format('D, d M Y') }}</div>
                                <div class="history-meta">
                                    Type: {{ strtoupper((string) $row->absence_type) }}<br>
                                    Status: {{ strtoupper((string) $row->status) }}
                                </div>
                            </div>
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

    <div class="modal-backdrop" id="attachment-viewer-backdrop" aria-hidden="true">
        <div class="modal viewer-modal" role="dialog" aria-modal="true" aria-labelledby="attachment-viewer-title">
            <div class="modal-head">
                <h3 id="attachment-viewer-title">Today's Request Attachment</h3>
                <button type="button" class="modal-close" id="close-attachment-viewer">Close</button>
            </div>

            <img id="attachment-viewer-image" class="attachment-viewer-image" alt="Request attachment" style="display:none;">
            <iframe id="attachment-viewer-frame" class="attachment-viewer-frame" style="display:none;"></iframe>
            <p id="attachment-viewer-empty" class="muted">No attachment available.</p>
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
            const timerEl = document.getElementById('today-excuse-timer');
            const absenceForm = document.getElementById('absence-request-form');
            if (!timerEl) return;

            const deadlineText = timerEl.getAttribute('data-deadline');
            if (!deadlineText) return;

            const deadline = new Date(deadlineText);
            if (Number.isNaN(deadline.getTime())) return;

            const render = () => {
                const diff = deadline.getTime() - Date.now();
                if (diff <= 0) {
                    timerEl.textContent = 'Editing closed';
                    timerEl.classList.add('expired');
                    if (absenceForm) {
                        absenceForm.querySelectorAll('input, select, textarea, button').forEach((el) => {
                            if (el instanceof HTMLInputElement && el.type === 'hidden') return;
                            el.disabled = true;
                        });
                    }
                    return false;
                }

                const totalSeconds = Math.floor(diff / 1000);
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;
                timerEl.textContent = `Editable for ${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                return true;
            };

            if (!render()) return;
            const interval = window.setInterval(() => {
                if (!render()) window.clearInterval(interval);
            }, 1000);
        })();

        (() => {
            const form = document.getElementById('absence-request-form');
            const fileInput = document.getElementById('absence_attachment');
            const cropInput = document.getElementById('absence_attachment_crop_data');
            const previewImage = document.getElementById('absence_attachment_preview');
            const emptyState = document.getElementById('absence_attachment_empty');
            const metaEl = document.getElementById('absence_attachment_meta');
            const cropWrap = document.getElementById('absence-crop-wrap');
            const canvas = document.getElementById('absence-crop-canvas');
            const zoomInput = document.getElementById('absence_zoom');
            const zoomValue = document.getElementById('absence_zoom_value');
            const rotateInput = document.getElementById('absence_rotate');
            const rotateValue = document.getElementById('absence_rotate_value');
            const rotateLeftBtn = document.getElementById('absence_rotate_left');
            const rotateRightBtn = document.getElementById('absence_rotate_right');
            const resetBtn = document.getElementById('absence_reset');

            if (
                !form || !fileInput || !cropInput || !previewImage || !emptyState || !metaEl || !cropWrap || !canvas ||
                !zoomInput || !zoomValue || !rotateInput || !rotateValue || !rotateLeftBtn || !rotateRightBtn || !resetBtn
            ) return;

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            const state = {
                image: null,
                baseScale: 1,
                zoom: 1,
                rotation: 0,
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
            const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

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
                drawCtx.scale(scale, scale);
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

            const updateLabels = () => {
                zoomValue.textContent = `${Math.round(state.zoom * 100)}%`;
                rotateValue.textContent = `${Math.round(state.rotation)}deg`;
                zoomInput.value = String(Math.round(state.zoom * 100));
                rotateInput.value = String(Math.round(state.rotation));
            };

            const resetState = () => {
                state.image = null;
                state.baseScale = 1;
                state.zoom = 1;
                state.rotation = 0;
                state.panX = 0;
                state.panY = 0;
                state.dragging = false;
                state.pointerId = null;
                cropInput.value = '';
                cropWrap.style.display = 'none';
                previewImage.style.display = 'none';
                previewImage.src = '';
                emptyState.style.display = 'block';
                emptyState.textContent = 'Choose an image to preview and crop before you submit. PDF files keep their original document preview.';
                metaEl.textContent = '';
                updateLabels();
                drawPlaceholder();
            };

            const setImage = (img, dataUrl, file) => {
                state.image = img;
                state.baseScale = Math.max(frameSize / img.width, frameSize / img.height);
                state.zoom = 1;
                state.rotation = 0;
                state.panX = 0;
                state.panY = 0;
                cropWrap.style.display = 'block';
                previewImage.src = dataUrl;
                previewImage.style.display = 'block';
                emptyState.style.display = 'none';
                metaEl.textContent = `${file.name} • preview before submit`;
                updateLabels();
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
                cropInput.value = '';
                if (!file) {
                    resetState();
                    return;
                }

                const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
                metaEl.textContent = `${file.name} selected`;
                if (isPdf) {
                    cropWrap.style.display = 'none';
                    previewImage.style.display = 'none';
                    previewImage.src = '';
                    emptyState.style.display = 'block';
                    emptyState.textContent = 'PDF selected. It will be uploaded as-is and can be viewed in the attachment modal.';
                    drawPlaceholder();
                    return;
                }

                const reader = new FileReader();
                reader.onload = () => {
                    const dataUrl = String(reader.result || '');
                    const img = new Image();
                    img.onload = () => setImage(img, dataUrl, file);
                    img.src = dataUrl;
                };
                reader.readAsDataURL(file);
            });

            zoomInput.addEventListener('input', () => {
                state.zoom = clamp(Number(zoomInput.value) / 100, 1, 16);
                updateLabels();
                draw();
            });

            rotateInput.addEventListener('input', () => {
                state.rotation = clamp(Number(rotateInput.value), -180, 180);
                updateLabels();
                draw();
            });

            rotateLeftBtn.addEventListener('click', () => {
                state.rotation = clamp(state.rotation - 90, -180, 180);
                updateLabels();
                draw();
            });

            rotateRightBtn.addEventListener('click', () => {
                state.rotation = clamp(state.rotation + 90, -180, 180);
                updateLabels();
                draw();
            });

            resetBtn.addEventListener('click', () => {
                if (!state.image) return;
                state.zoom = 1;
                state.rotation = 0;
                state.panX = 0;
                state.panY = 0;
                updateLabels();
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
            };

            canvas.addEventListener('pointerup', endDrag);
            canvas.addEventListener('pointercancel', endDrag);
            canvas.addEventListener('lostpointercapture', endDrag);
            canvas.addEventListener('wheel', (event) => {
                if (!state.image) return;
                event.preventDefault();
                state.zoom = clamp(state.zoom + (event.deltaY > 0 ? -0.09 : 0.09), 1, 16);
                updateLabels();
                draw();
            }, { passive: false });

            form.addEventListener('submit', (event) => {
                cropInput.value = '';
                if (!state.image || !(fileInput.files && fileInput.files.length)) return;

                const out = document.createElement('canvas');
                out.width = 900;
                out.height = 900;
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

            updateLabels();
            drawPlaceholder();
        })();

        (() => {
            const openBtn = document.getElementById('open-attachment-modal');
            const backdrop = document.getElementById('attachment-viewer-backdrop');
            const closeBtn = document.getElementById('close-attachment-viewer');
            const imageEl = document.getElementById('attachment-viewer-image');
            const frameEl = document.getElementById('attachment-viewer-frame');
            const emptyEl = document.getElementById('attachment-viewer-empty');

            if (!openBtn || !backdrop || !closeBtn || !imageEl || !frameEl || !emptyEl) return;

            const open = () => {
                backdrop.classList.add('open');
                backdrop.setAttribute('aria-hidden', 'false');
            };

            const close = () => {
                backdrop.classList.remove('open');
                backdrop.setAttribute('aria-hidden', 'true');
                imageEl.src = '';
                frameEl.src = 'about:blank';
            };

            openBtn.addEventListener('click', () => {
                const url = openBtn.getAttribute('data-attachment-url') || '';
                const type = openBtn.getAttribute('data-attachment-type') || 'image';
                emptyEl.style.display = url ? 'none' : 'block';
                imageEl.style.display = 'none';
                frameEl.style.display = 'none';

                if (url) {
                    if (type === 'pdf') {
                        frameEl.src = url;
                        frameEl.style.display = 'block';
                    } else {
                        imageEl.src = url;
                        imageEl.style.display = 'block';
                    }
                }

                open();
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
