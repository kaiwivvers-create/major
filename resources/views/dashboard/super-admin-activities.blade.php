<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Activities - {{ config('app.name', 'Kips') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <style>
        :root {
            --bg: #0f172a; --surface: #1e293b; --primary: #2563eb; --accent: #38bdf8;
            --text: #e2e8f0; --muted: #94a3b8; --border: #334155;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh; font-family: 'Instrument Sans', sans-serif; color: var(--text);
            background:
                radial-gradient(1000px 500px at 10% -10%, rgba(56, 189, 248, 0.2), transparent),
                radial-gradient(900px 450px at 100% 10%, rgba(37, 99, 235, 0.2), transparent),
                var(--bg);
        }
        .app-shell { min-height: 100vh; display: grid; grid-template-columns: 270px 1fr; }
        .sidebar {
            position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column;
            border-right: 1px solid var(--border); background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(10px); padding: 16px 14px;
        }
        .sidebar-brand {
            font-weight: 700; letter-spacing: .02em; padding: 8px 10px; border: 1px solid var(--border);
            border-radius: 12px; background: rgba(30, 41, 59, 0.45); margin-bottom: 14px;
        }
        .sidebar-nav { display: flex; flex-direction: column; gap: 8px; }
        .sidebar-nav a {
            text-decoration: none; color: var(--text); border: 1px solid var(--border); border-radius: 10px;
            padding: 10px 12px; background: rgba(30, 41, 59, 0.6); font-weight: 500; transition: all .2s ease;
        }
        .sidebar-nav a:hover { border-color: var(--accent); color: var(--accent); }
        .sidebar-nav a.active {
            border-color: var(--primary); background: linear-gradient(135deg, rgba(37, 99, 235, 0.32), rgba(29, 78, 216, 0.32));
            color: #f8fafc; font-weight: 700;
        }
        .main { padding: 20px; }
        .topbar { border: 1px solid var(--border); border-radius: 14px; background: rgba(15, 23, 42, 0.9); padding: 14px 16px; margin-bottom: 14px; }
        .topbar h1 { font-size: 1.15rem; margin-bottom: 4px; }
        .muted { color: var(--muted); }
        .card { border: 1px solid var(--border); border-radius: 14px; background: rgba(30, 41, 59, 0.7); padding: 14px; }
        .table-wrap { overflow: auto; border: 1px solid var(--border); border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; min-width: 1060px; }
        th, td { padding: 10px; border-bottom: 1px solid rgba(148,163,184,.2); text-align: left; vertical-align: top; }
        th { background: rgba(15, 23, 42, 0.72); font-size: .84rem; letter-spacing: .02em; text-transform: uppercase; color: #cbd5e1; }
        .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-xs {
            border: 1px solid var(--border); border-radius: 8px; padding: 6px 8px; font-size: .78rem;
            background: rgba(15, 23, 42, .72); color: var(--text); text-decoration: none; cursor: pointer;
        }
        .btn-xs:hover { border-color: var(--accent); color: var(--accent); }
        .btn-xs.danger { border-color: rgba(248,113,113,.55); background: rgba(127,29,29,.4); color: #fecaca; }
        .pill {
            display: inline-block; font-size: .73rem; border: 1px solid var(--border); border-radius: 999px;
            padding: 2px 8px; background: rgba(15, 23, 42, .68);
        }
        .pill.ok { border-color: rgba(34,197,94,.55); color: #86efac; background: rgba(20,83,45,.32); }
        .pill.warn { border-color: rgba(245,158,11,.55); color: #fde68a; background: rgba(120,53,15,.35); }
        .pagination { margin-top: 12px; }
        .page-link {
            border: 1px solid var(--border); color: var(--text); background: rgba(15, 23, 42, 0.65);
            padding: 6px 10px; border-radius: 8px; margin-right: 6px; text-decoration: none;
        }
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(2, 6, 23, .68); display: none;
            align-items: center; justify-content: center; z-index: 2400; padding: 14px;
        }
        .modal-backdrop.open { display: flex; }
        .modal-panel {
            width: min(760px, 96vw); max-height: 90vh; overflow: auto; border: 1px solid var(--border);
            border-radius: 12px; background: linear-gradient(160deg, rgba(30, 41, 59, .97), rgba(15, 23, 42, .98)); padding: 12px;
        }
        .modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .modal-close {
            border: 1px solid var(--border); border-radius: 8px; padding: 7px 10px; cursor: pointer;
            color: var(--text); background: rgba(15, 23, 42, .75);
        }
        .meta-box {
            border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, .7);
            padding: 10px; white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .82rem;
        }
        .modal-actions {
            margin-top: 10px; display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 8px 12px;
            margin-bottom: 12px;
            line-height: 1.45;
        }
        .detail-grid strong {
            color: #cbd5e1;
            font-weight: 700;
        }
        .alert {
            border: 1px solid rgba(248,113,113,.55); border-radius: 10px; padding: 10px;
            background: rgba(127,29,29,.34);
        }
        .sidebar-profile { margin-top: auto; border: 1px solid var(--border); border-radius: 12px; background: rgba(30,41,59,.55); padding: 12px; }
        .profile-trigger {
            width: 100%; text-align: left; appearance: none; border: 1px solid var(--border); border-radius: 12px;
            color: var(--text); background: rgba(15,23,42,.52); cursor: pointer; padding: 10px;
            display: grid; grid-template-columns: 42px 1fr 18px; align-items: center; gap: 10px;
        }
        .profile-avatar {
            width: 42px; height: 42px; border-radius: 999px; border: 1px solid rgba(56, 189, 248, 0.55);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.28), rgba(56, 189, 248, 0.24));
            display: grid; place-items: center; font-size: .82rem; font-weight: 700; overflow: hidden;
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .profile-name { font-weight: 700; margin-bottom: 2px; }
        .profile-meta { font-size: .85rem; color: var(--muted); }
        .profile-arrow { color: var(--muted); text-align: right; }
        .profile-modal-backdrop {
            position: fixed; inset: 0; background: rgba(2, 6, 23, 0.62); display: none;
            align-items: center; justify-content: center; z-index: 2200; padding: 16px;
        }
        .profile-modal-backdrop.open { display: flex; }
        .profile-modal-panel {
            width: min(560px, 96vw); border: 1px solid var(--border); border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 16px; max-height: 92vh; overflow: auto;
        }
        .profile-modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .profile-modal-close, .profile-modal-btn {
            border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.72);
            color: var(--text); padding: 8px 12px; cursor: pointer; font-weight: 600;
        }
        .profile-modal-btn.primary { border-color: var(--primary); background: linear-gradient(135deg, var(--primary), #1d4ed8); color: #f8fafc; }
        .profile-modal-field { margin-bottom: 12px; }
        .profile-modal-field label { display: block; margin-bottom: 6px; font-size: .9rem; font-weight: 600; }
        .profile-modal-field input {
            width: 100%; border: 1px solid var(--border); border-radius: 10px; background: rgba(15, 23, 42, 0.7);
            color: var(--text); padding: 10px 12px; font-size: .95rem;
        }
        .profile-modal-actions { margin-top: 14px; display: flex; justify-content: flex-end; gap: 8px; }
        .profile-modal-alert.error {
            border: 1px solid rgba(248, 113, 113, 0.6); border-radius: 12px; padding: 10px 12px;
            background: rgba(127, 29, 29, 0.25); margin-bottom: 12px;
        }
        @media (max-width: 980px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
        }
    </style>
</head>
<body>
@php
    $user = auth()->user();
    $openProfileModal = false;
    $avatarInitials = collect(explode(' ', trim($user->name ?? 'U')))
        ->filter()
        ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
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
            <a href="{{ route('dashboard.super-admin.permissions') }}">Permissions</a>
            <a class="active" href="{{ route('dashboard.super-admin.activities') }}">Activities</a>
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
            <h1>User Activities</h1>
            <p class="muted">Audit timeline for super admin operations, edits, deletes, and restorations.</p>
        </header>

        @if (session('status'))
            <div style="margin-bottom:10px; border:1px solid rgba(56,189,248,.45); border-radius:10px; padding:10px; background:rgba(14,165,233,.12);">{{ session('status') }}</div>
        @endif
        @if ($errors->has('activities'))
            <div class="alert" style="margin-bottom:10px;">{{ $errors->first('activities') }}</div>
        @endif

        <section class="card">
            @if (!($activityStorageReady ?? false))
                <div class="alert">Activity storage is not ready. Please run database migrations first.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activities as $row)
                                @php
                                    $meta = is_array($row->metadata_decoded ?? null) ? $row->metadata_decoded : [];
                                    $editUrl = data_get($meta, 'edit_url');
                                @endphp
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($row->created_at, 'Asia/Jakarta')->format('d M Y H:i') }}</td>
                                    <td>{{ $row->actor_name ?: 'System' }}<br><span class="muted">{{ $row->actor_nis ?: '-' }}</span></td>
                                    <td><span class="pill">{{ $row->action }}</span></td>
                                    <td>{{ $row->description ?: '-' }}</td>
                                    <td>
                                        @if ($row->purged_at)
                                            <span class="pill warn">Purged</span>
                                        @elseif ($row->reverted_at)
                                            <span class="pill ok">Reverted</span>
                                        @elseif ($row->can_revert)
                                            <span class="pill">Revertable</span>
                                        @else
                                            <span class="pill">Normal</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button
                                                type="button"
                                                class="btn-xs js-view"
                                                data-activity-id="{{ $row->id }}"
                                                data-action="{{ $row->action }}"
                                                data-description="{{ $row->description }}"
                                                data-actor-name="{{ $row->actor_name ?: 'System' }}"
                                                data-actor-nis="{{ $row->actor_nis ?: '-' }}"
                                                data-created-at="{{ \Illuminate\Support\Carbon::parse($row->created_at, 'Asia/Jakarta')->format('d M Y H:i') }}"
                                                data-subject-type="{{ $row->subject_type ?: '-' }}"
                                                data-subject-id="{{ $row->subject_id ?: '-' }}"
                                                data-can-revert="{{ $row->can_revert ? '1' : '0' }}"
                                                data-reverted="{{ $row->reverted_at ? '1' : '0' }}"
                                                data-purged="{{ $row->purged_at ? '1' : '0' }}"
                                                data-revert-url="{{ route('dashboard.super-admin.activities.revert', $row->id) }}"
                                                data-purge-url="{{ route('dashboard.super-admin.activities.purge', $row->id) }}"
                                                data-meta='@json($meta)'
                                            >View</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No activity yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($activities, 'hasPages') && $activities->hasPages())
                    <div class="pagination">
                        @if ($activities->onFirstPage())
                            <span class="page-link">Previous</span>
                        @else
                            <a class="page-link" href="{{ $activities->previousPageUrl() }}">Previous</a>
                        @endif
                        <span class="page-link">Page {{ $activities->currentPage() }} of {{ $activities->lastPage() }}</span>
                        @if ($activities->hasMorePages())
                            <a class="page-link" href="{{ $activities->nextPageUrl() }}">Next</a>
                        @else
                            <span class="page-link">Next</span>
                        @endif
                    </div>
                @endif
            @endif
        </section>
    </main>
</div>

<div class="modal-backdrop" id="activity-modal" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="activity-modal-title">
        <div class="modal-head">
            <h3 id="activity-modal-title">Activity Details</h3>
            <button type="button" class="modal-close" id="activity-modal-close">Close</button>
        </div>
        <div id="activity-modal-summary" style="margin-bottom:8px;"></div>
        <div class="meta-box" id="activity-modal-meta">-</div>
        <div class="modal-actions" id="activity-modal-actions"></div>
    </div>
</div>

@include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])
<script>
    (() => {
        const modal = document.getElementById('activity-modal');
        const closeBtn = document.getElementById('activity-modal-close');
        const summary = document.getElementById('activity-modal-summary');
        const metaBox = document.getElementById('activity-modal-meta');
        const actionsBox = document.getElementById('activity-modal-actions');
        if (!modal || !closeBtn || !summary || !metaBox || !actionsBox) return;

        const openModal = () => {
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        };
        const closeModal = () => {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        };

        document.querySelectorAll('.js-view').forEach((btn) => {
            btn.addEventListener('click', () => {
                const action = String(btn.getAttribute('data-action') || '-');
                const description = String(btn.getAttribute('data-description') || '-');
                const actorName = String(btn.getAttribute('data-actor-name') || 'System');
                const actorNis = String(btn.getAttribute('data-actor-nis') || '-');
                const createdAt = String(btn.getAttribute('data-created-at') || '-');
                const subjectType = String(btn.getAttribute('data-subject-type') || '-');
                const subjectId = String(btn.getAttribute('data-subject-id') || '-');
                const canRevert = btn.getAttribute('data-can-revert') === '1';
                const isReverted = btn.getAttribute('data-reverted') === '1';
                const isPurged = btn.getAttribute('data-purged') === '1';
                const revertUrl = String(btn.getAttribute('data-revert-url') || '');
                const purgeUrl = String(btn.getAttribute('data-purge-url') || '');
                let meta = {};
                try {
                    const raw = btn.getAttribute('data-meta') || '{}';
                    meta = JSON.parse(raw);
                } catch (error) {
                    meta = {};
                }
                const statusText = isPurged ? 'Purged' : (isReverted ? 'Reverted' : (canRevert ? 'Revertable' : 'Normal'));
                summary.innerHTML = `
                    <div class="detail-grid">
                        <strong>Actor</strong><div>${actorName} (${actorNis})</div>
                        <strong>Time</strong><div>${createdAt} WIB</div>
                        <strong>Action</strong><div>${action}</div>
                        <strong>Subject</strong><div>${subjectType} #${subjectId}</div>
                        <strong>Status</strong><div>${statusText}</div>
                        <strong>Description</strong><div>${description}</div>
                    </div>
                `;
                metaBox.textContent = JSON.stringify(meta, null, 2);

                actionsBox.innerHTML = '';
                if (canRevert && !isReverted && !isPurged) {
                    const revertForm = document.createElement('form');
                    revertForm.method = 'POST';
                    revertForm.action = revertUrl;
                    revertForm.innerHTML = `
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button class="btn-xs" type="submit">Revert</button>
                    `;

                    const purgeForm = document.createElement('form');
                    purgeForm.method = 'POST';
                    purgeForm.action = purgeUrl;
                    purgeForm.onsubmit = () => window.confirm('Permanently remove archived deleted data? This cannot be undone.');
                    purgeForm.innerHTML = `
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button class="btn-xs danger" type="submit">Permanently Delete</button>
                    `;

                    actionsBox.appendChild(revertForm);
                    actionsBox.appendChild(purgeForm);
                }
                openModal();
            });
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });
        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('open')) closeModal();
        });
    })();
</script>
</body>
</html>


