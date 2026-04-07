<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin Dashboard - {{ config('app.name', 'Kips') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />

    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --surface-2: #0b1222;
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
            color: var(--text);
            background: rgba(15, 23, 42, 0.52);
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
            max-height: 92vh;
            overflow: auto;
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

        .main {
            padding: 20px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.9);
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .topbar h1 { font-size: 1.1rem; }
        .muted { color: var(--muted); font-size: 0.92rem; }

        .nav-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-link {
            text-decoration: none;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            background: rgba(30, 41, 59, 0.65);
            padding: 8px 10px;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .btn-link:hover { border-color: var(--accent); color: var(--accent); }

        .grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 12px;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94));
            padding: 14px;
        }

        .card h2 {
            font-size: 1rem;
            margin-bottom: 8px;
        }

        #live-map {
            height: 360px;
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .table-wrap {
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-top: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 560px;
        }

        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
            font-size: 0.9rem;
        }

        th {
            background: rgba(15, 23, 42, 0.92);
            color: #cbd5e1;
            font-weight: 700;
        }

        .major-bars {
            display: grid;
            gap: 8px;
            margin-top: 10px;
        }

        .major-row {
            display: grid;
            grid-template-columns: 70px 1fr 48px;
            gap: 8px;
            align-items: center;
        }

        .major-row strong { font-size: 0.85rem; }

        .bar-track {
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.8);
            border-radius: 999px;
            overflow: hidden;
            height: 12px;
        }

        .bar-track > span {
            display: block;
            height: 100%;
            width: var(--pct, 0%);
            background: linear-gradient(90deg, #38bdf8, #2563eb);
        }

        .heatmap-grid {
            margin-top: 8px;
            display: grid;
            grid-template-columns: repeat(10, minmax(0, 1fr));
            gap: 6px;
        }

        .heat-cell {
            border-radius: 6px;
            border: 1px solid rgba(51, 65, 85, 0.7);
            background: rgba(15, 23, 42, 0.45);
            min-height: 28px;
        }

        .heat-cell[data-level="1"] { background: rgba(59, 130, 246, 0.22); }
        .heat-cell[data-level="2"] { background: rgba(59, 130, 246, 0.36); }
        .heat-cell[data-level="3"] { background: rgba(37, 99, 235, 0.52); }
        .heat-cell[data-level="4"] { background: rgba(29, 78, 216, 0.72); }

        .activity-feed {
            margin-top: 8px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.7);
            max-height: 210px;
            overflow: auto;
            padding: 10px;
        }

        .activity-item {
            border-bottom: 1px dashed rgba(148, 163, 184, 0.3);
            padding: 8px 0;
        }

        .activity-item:last-child { border-bottom: 0; }

        .timeline {
            display: grid;
            gap: 10px;
            margin-top: 8px;
        }

        .timeline-row {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.65);
            padding: 10px;
        }

        @media (max-width: 1100px) {
            .grid { grid-template-columns: 1fr; }
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .main { padding-top: 0; }
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
            <nav class="sidebar-nav" aria-label="Super admin sections">
                <a class="active" href="#live-map-section">Live Map</a>
                <a href="#stats-section">Partnership Statistics</a>
                <a href="#heatmap-section">Attendance Heatmap</a>
                <a href="#activity-section">Current Activity</a>
                <a href="#timeline-section">Implementation Timeline</a>
                <a href="{{ route('dashboard') }}">Main Dashboard</a>
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
            <h1>Super Admin Dashboard</h1>
            <p class="muted">School-wide operational intelligence for PKL monitoring.</p>
        </div>
        <div class="nav-actions">
            <a class="btn-link" href="#live-map-section">Map</a>
            <a class="btn-link" href="#stats-section">Stats</a>
            <a class="btn-link" href="#timeline-section">Timeline</a>
        </div>
    </header>

    <section class="grid" id="live-map-section">
        <article class="card">
            <h2>Live Map</h2>
            <p class="muted">Company locations currently hosting students.</p>
            <div id="live-map"></div>
            @if ($companyMapPoints->isEmpty())
                <p class="muted" style="margin-top:8px;">No company map coordinates yet. Pins will appear after attendance GPS data is collected.</p>
            @endif
        </article>

        <article class="card" id="stats-section">
            <h2>Partnership Statistics</h2>
            <p class="muted">Public directory and active student distribution by major.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Industry Sector</th>
                            <th>Usual Slots</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companyStats as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row->company_name }}</strong><br>
                                    <span class="muted">{{ $row->company_address ?? '-' }}</span>
                                </td>
                                <td>{{ $row->industry_sector }}</td>
                                <td>{{ $row->slot_capacity }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No active partner companies found for the current date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="major-bars">
                @php
                    $majorMax = max(1, (int) ($majorDistribution->max('total') ?? 0));
                @endphp
                @forelse ($majorDistribution as $major)
                    <div class="major-row">
                        <strong>{{ $major->major }}</strong>
                        <div class="bar-track" style="--pct: {{ (int) round(($major->total / $majorMax) * 100) }}%;">
                            <span></span>
                        </div>
                        <span>{{ $major->total }}</span>
                    </div>
                @empty
                    <p class="muted">No active major distribution data yet.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid" style="margin-top:12px;">
        <article class="card" id="heatmap-section">
            <h2>Attendance Heatmap (Last 30 Days)</h2>
            <p class="muted">Darker blue means higher school-wide attendance for that date.</p>
            <div class="heatmap-grid">
                @foreach ($heatmap as $cell)
                    @php
                        $level = $maxAttendance > 0 ? (int) ceil(($cell['total'] / $maxAttendance) * 4) : 0;
                        $level = max(0, min(4, $level));
                    @endphp
                    <div
                        class="heat-cell"
                        data-level="{{ $level }}"
                        title="{{ \Illuminate\Support\Carbon::parse($cell['date'], 'Asia/Jakarta')->format('d M Y') }}: {{ $cell['total'] }} check-ins"
                    ></div>
                @endforeach
            </div>
        </article>

        <article class="card" id="activity-section">
            <h2>Current Activity Snippets</h2>
            <p class="muted">Anonymized operational feed.</p>
            <div class="activity-feed">
                @forelse ($activityFeed as $item)
                    <div class="activity-item">
                        <div>{{ $item['message'] }}</div>
                        <div class="muted">{{ \Illuminate\Support\Carbon::parse($item['happened_at'], 'Asia/Jakarta')->format('d M Y H:i') }} WIB</div>
                    </div>
                @empty
                    <div class="muted">No recent activity snippets available.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="card" id="timeline-section" style="margin-top:12px;">
        <h2>Implementation Timeline (Current Batch)</h2>
        <div class="timeline">
            <div class="timeline-row">
                <strong>Start Date</strong>
                <div class="muted">{{ \Illuminate\Support\Carbon::parse($timelineStart, 'Asia/Jakarta')->format('d M Y') }}</div>
            </div>
            <div class="timeline-row">
                <strong>Current Status</strong>
                <div class="muted">{{ $timelineStatus }}</div>
            </div>
            <div class="timeline-row">
                <strong>End Date</strong>
                <div class="muted">{{ \Illuminate\Support\Carbon::parse($timelineEnd, 'Asia/Jakarta')->format('d M Y') }}</div>
            </div>
        </div>
    </section>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script>
        (() => {
            const mapEl = document.getElementById('live-map');
            if (!mapEl || typeof window.L === 'undefined') return;

            const points = @json($companyMapPoints);
            const map = L.map(mapEl, {
                center: [-6.2, 106.8],
                zoom: 10,
                scrollWheelZoom: false,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const validPoints = (Array.isArray(points) ? points : []).filter((item) =>
                item &&
                item.latitude !== null &&
                item.longitude !== null &&
                !Number.isNaN(Number(item.latitude)) &&
                !Number.isNaN(Number(item.longitude))
            );

            if (!validPoints.length) return;

            const bounds = [];
            validPoints.forEach((item) => {
                const lat = Number(item.latitude);
                const lng = Number(item.longitude);
                bounds.push([lat, lng]);
                L.marker([lat, lng]).addTo(map).bindPopup(
                    `<strong>${item.company_name ?? 'Company'}</strong><br>${item.company_address ?? '-'}<br>Active students: ${item.active_students ?? 0}`
                );
            });

            map.fitBounds(bounds, { padding: [18, 18] });
        })();
    </script>
</body>
</html>
