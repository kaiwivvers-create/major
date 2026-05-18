<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database - Super Admin - {{ config('app.name', 'Kips') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <style>
        :root { --bg:#0f172a; --primary: {{ $appBranding->primary_color ?? '#2563eb' }}; --accent: {{ $appBranding->accent_color ?? '#38bdf8' }}; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text);
            background:
                radial-gradient(1000px 500px at 10% -10%, rgba(56,189,248,.2), transparent),
                radial-gradient(900px 450px at 100% 10%, rgba(37,99,235,.2), transparent),
                var(--bg);
        }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:270px 1fr; }
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
        .main { padding:20px; display:grid; gap:12px; }
        .topbar, .card { border:1px solid var(--border); border-radius:14px; padding:14px 16px; }
        .topbar { background:rgba(15,23,42,.9); }
        .card { background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); }
        .muted { color:var(--muted); font-size:.92rem; }
        .alert { border:1px solid rgba(56,189,248,.45); border-radius:12px; padding:10px 12px; background:rgba(14,165,233,.12); }
        .alert.error { border-color:rgba(248,113,113,.6); background:rgba(127,29,29,.25); }
        .grid { display:grid; grid-template-columns:1.4fr 1fr; gap:12px; }
        .field { margin-top:10px; }
        .field label { display:block; margin-bottom:6px; color:var(--muted); font-size:.86rem; font-weight:600; }
        select {
            width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7);
            color:var(--text); padding:10px 12px; font-size:.92rem;
        }
        input[type="file"] {
            width: 100%;
            border: 1px solid rgba(56, 189, 248, 0.35);
            border-radius: 12px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.92));
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
        .btn {
            border:1px solid var(--primary); border-radius:10px;
            background:linear-gradient(135deg, var(--primary), #1d4ed8); color:#fff;
            padding:10px 12px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center;
        }
        .btn.secondary { border-color:var(--border); background:rgba(15,23,42,.78); color:var(--text); }
        .btn.warn { border-color:rgba(245,158,11,.55); background:rgba(120,53,15,.36); color:#fde68a; }
        .btn-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
        .table-selector { margin-top:10px; border:1px solid var(--border); border-radius:12px; padding:10px; max-height:220px; overflow:auto; background:rgba(15,23,42,.52); }
        .table-selector-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:8px; }
        .check { display:flex; align-items:center; gap:8px; font-size:.9rem; }
        .check input { accent-color:#38bdf8; }
        .table-wrap { overflow:auto; border:1px solid var(--border); border-radius:12px; margin-top:10px; }
        table { width:100%; border-collapse:collapse; min-width:720px; }
        th, td { text-align:left; padding:10px; border-bottom:1px solid var(--border); vertical-align:top; font-size:.9rem; }
        th { background:rgba(15,23,42,.95); color:#cbd5e1; font-weight:700; }
        .pill { display:inline-block; border:1px solid var(--border); border-radius:999px; padding:2px 8px; font-size:.78rem; }
        .pill.ok { border-color:rgba(34,197,94,.55); color:#86efac; background:rgba(20,83,45,.32); }
        .pill.err { border-color:rgba(248,113,113,.55); color:#fecaca; background:rgba(127,29,29,.34); }
        @media (max-width:1080px) { .app-shell { grid-template-columns:1fr; } .sidebar { position: static; height: auto; } .grid { grid-template-columns:1fr; } }
        @media (max-width:760px) { .table-selector-grid { grid-template-columns:1fr; } }
    
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
    $downloadableBackupFiles = collect($backupFiles ?? [])
        ->map(fn ($row) => (string) data_get($row, 'file_name'))
        ->filter()
        ->values()
        ->all();
@endphp
<div class="app-shell">
    @include('dashboard.partials.super-admin-sidebar', ['user' => $user, 'activePage' => 'database'])

    <main class="main">
        <header class="topbar">
            <h1>Database Backup Center</h1>
            <p class="muted">Create and download backups, export data snapshots, and import backup files.</p>
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
            <div class="card">
                <h2>Backup / Export</h2>
                <p class="muted">Pick tables then create a stored backup or export an on-demand JSON file.</p>

                <form id="backup-form" method="POST" action="{{ route('dashboard.super-admin.database.backup') }}">
                    @csrf
                    <div class="field">
                        <label for="backup_format">Backup Format</label>
                        <select id="backup_format" name="format">
                            <option value="json" selected>JSON</option>
                            <option value="sql">SQL</option>
                        </select>
                    </div>
                    <div class="btn-row">
                        <button type="button" class="btn secondary" id="select-all-tables">Select All</button>
                        <button type="button" class="btn secondary" id="clear-all-tables">Clear</button>
                    </div>
                    <div class="table-selector">
                        <div class="table-selector-grid">
                            @foreach ($availableTables as $table)
                                <label class="check">
                                    <input type="checkbox" name="tables[]" value="{{ $table }}" class="table-checkbox" checked>
                                    <span>{{ $table }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="btn-row">
                        <button class="btn" type="submit">Create Backup</button>
                    </div>
                </form>

                <form id="export-form" method="POST" action="{{ route('dashboard.super-admin.database.export') }}">
                    @csrf
                    <div class="field">
                        <label for="export_format">Export Format</label>
                        <select id="export_format" name="format">
                            <option value="json" selected>JSON</option>
                            <option value="sql">SQL</option>
                        </select>
                    </div>
                    <div class="btn-row">
                        <button class="btn warn" type="submit">Export Selected as JSON</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Import Backup</h2>
                <p class="muted">Import a JSON backup. Use <strong>upsert</strong> for safer merges; use <strong>replace</strong> to overwrite table content.</p>
                <form method="POST" action="{{ route('dashboard.super-admin.database.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="field">
                        <label for="import_format">Import Format</label>
                        <select id="import_format" name="format" required>
                            <option value="json" selected>JSON</option>
                            <option value="sql">SQL</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="backup_file">Backup File</label>
                        <input id="backup_file" type="file" name="backup_file" accept=".json,.sql,application/json,text/plain,application/sql" required>
                    </div>
                    <div class="field" id="json-import-mode-wrap">
                        <label for="mode">Import Mode</label>
                        <select id="mode" name="mode" required>
                            <option value="upsert" selected>Upsert (Recommended)</option>
                            <option value="replace">Replace Existing Rows</option>
                        </select>
                    </div>
                    <div class="btn-row">
                        <button class="btn" type="submit" onclick="return confirm('Import this backup now?')">Import Backup</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <h2>Stored Backups</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Created</th>
                            <th>Size</th>
                            <th>Tables</th>
                            <th>Rows</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backupFiles as $row)
                            <tr>
                                <td>{{ $row['file_name'] }}</td>
                                <td>{{ optional($row['created_at'])->format('d M Y H:i') }}</td>
                                <td>{{ number_format(((int) ($row['size_bytes'] ?? 0)) / 1024, 2) }} KB</td>
                                <td>{{ $row['table_count'] ?? '-' }}</td>
                                <td>{{ $row['row_count'] ?? '-' }}</td>
                                <td>
                                    <a class="btn secondary" href="{{ route('dashboard.super-admin.database.download', $row['file_name']) }}">Download</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No backup files yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2>Backup Logs</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>File</th>
                            <th>Message</th>
                            <th>User</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backupLogs as $log)
                            @php
                                $logFile = (string) data_get($log, 'file_name', '');
                                $canDownload = $logFile !== '' && in_array($logFile, $downloadableBackupFiles, true);
                            @endphp
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse(data_get($log, 'timestamp', now('Asia/Jakarta')), 'Asia/Jakarta')->format('d M Y H:i') }}</td>
                                <td>
                                    <span class="pill {{ data_get($log, 'status') === 'success' ? 'ok' : (data_get($log, 'status') === 'error' ? 'err' : '') }}">
                                        {{ strtoupper((string) data_get($log, 'status', 'info')) }}
                                    </span>
                                </td>
                                <td>{{ data_get($log, 'action', '-') }}</td>
                                <td>{{ data_get($log, 'file_name', '-') }}</td>
                                <td>{{ data_get($log, 'message', '-') }}</td>
                                <td>{{ data_get($log, 'performed_by', '-') }}</td>
                                <td>
                                    @if ($canDownload)
                                        <a class="btn secondary" href="{{ route('dashboard.super-admin.database.download', $logFile) }}">Download</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No log entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

@include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])

<script>
    (() => {
        const tableCheckboxes = Array.from(document.querySelectorAll('.table-checkbox'));
        const selectAllBtn = document.getElementById('select-all-tables');
        const clearAllBtn = document.getElementById('clear-all-tables');
        const exportForm = document.getElementById('export-form');
        const exportBtn = exportForm ? exportForm.querySelector('button[type="submit"]') : null;
        const exportFormat = document.getElementById('export_format');
        const importFormat = document.getElementById('import_format');
        const importModeWrap = document.getElementById('json-import-mode-wrap');
        const importMode = document.getElementById('mode');
        if (!tableCheckboxes.length || !selectAllBtn || !clearAllBtn || !exportForm) return;

        const setAll = (checked) => {
            tableCheckboxes.forEach((checkbox) => {
                checkbox.checked = checked;
            });
        };

        selectAllBtn.addEventListener('click', () => setAll(true));
        clearAllBtn.addEventListener('click', () => setAll(false));

        exportForm.addEventListener('submit', () => {
            exportForm.querySelectorAll('input[name="tables[]"][data-cloned="1"]').forEach((el) => el.remove());
            tableCheckboxes.forEach((checkbox) => {
                if (!checkbox.checked) return;
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'tables[]';
                hidden.value = checkbox.value;
                hidden.setAttribute('data-cloned', '1');
                exportForm.appendChild(hidden);
            });
        });

        const syncExportLabel = () => {
            if (!exportBtn || !exportFormat) return;
            const format = String(exportFormat.value || 'json').toUpperCase();
            exportBtn.textContent = `Export Selected as ${format}`;
        };

        const syncImportModeVisibility = () => {
            if (!importFormat || !importModeWrap || !importMode) return;
            const isJson = String(importFormat.value || 'json') === 'json';
            importModeWrap.style.display = isJson ? '' : 'none';
            if (isJson) {
                importMode.setAttribute('required', 'required');
            } else {
                importMode.removeAttribute('required');
            }
        };

        syncExportLabel();
        syncImportModeVisibility();
        if (exportFormat) exportFormat.addEventListener('change', syncExportLabel);
        if (importFormat) importFormat.addEventListener('change', syncImportModeVisibility);
    })();
</script>

    @include('partials.chatbot')
</body>
</html>

