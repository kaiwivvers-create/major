<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mentor Weekly Validation - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --surface:#1e293b; --primary:#2563eb; --accent:#38bdf8; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; }
        * { box-sizing:border-box; margin:0; padding:0; } body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); padding:16px; background:radial-gradient(1000px 500px at 10% -10%, rgba(56,189,248,.2), transparent), var(--bg); }
        .card { border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.88); padding:14px; margin-bottom:12px; }
        .muted { color:var(--muted); font-size:.92rem; } .alert { border:1px solid rgba(56,189,248,.45); border-radius:10px; padding:8px 10px; margin:10px 0; background:rgba(14,165,233,.12); }
        .alert.error { border-color:rgba(248,113,113,.6); background:rgba(127,29,29,.25); }
        table { width:100%; border-collapse:collapse; margin-top:10px; } th, td { border:1px solid var(--border); padding:8px; vertical-align:top; text-align:left; font-size:.9rem; }
        textarea, select { width:100%; border:1px solid var(--border); border-radius:8px; background:rgba(15,23,42,.65); color:var(--text); padding:8px; font-family:inherit; }
        textarea { min-height:90px; resize:vertical; } .btn { border:1px solid var(--primary); border-radius:8px; background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#fff; padding:8px 10px; font-weight:700; cursor:pointer; }
    
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

        body.page-drift-up {
            animation: page-drift-up 0.7s ease-out both;
        }
    </style>
</head>
<body class="page-drift-up">
    <div class="card">
        <h1>Mentor Weekly Validation</h1>
        <p class="muted">Week: {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</p>
        @if (session('status'))<div class="alert">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert error">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>PKL Activity</th>
                    <th>Student Notes (from mentor)</th>
                    <th>Validation</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->student_name }}<br><span class="muted">NIS: {{ $row->student_nis }}</span></td>
                        <td>{{ $row->learning_notes }}</td>
                        <td>{{ $row->student_mentor_notes }}</td>
                        <td>
                            <form method="POST" action="{{ route('dashboard.mentor.weekly-journal.review', $row->id) }}">
                                @csrf
                                <select name="mentor_is_correct" required>
                                    <option value="1" {{ (string) ($row->mentor_is_correct ?? '') === '1' ? 'selected' : '' }}>Correct</option>
                                    <option value="0" {{ (string) ($row->mentor_is_correct ?? '') === '0' ? 'selected' : '' }}>Not Complete</option>
                                </select>
                                <textarea name="missing_info_notes" placeholder="If not complete, write missing info...">{{ old('missing_info_notes', $row->missing_info_notes ?? '') }}</textarea>
                                <button class="btn" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No weekly journals for this week.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>

