<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kajur Weekly Notes - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --primary:#2563eb; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; }
        * { box-sizing:border-box; margin:0; padding:0; } body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); padding:16px; background:var(--bg); }
        .card { border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.9); padding:14px; }
        .muted { color:var(--muted); font-size:.92rem; } .alert { border:1px solid rgba(56,189,248,.45); border-radius:10px; padding:8px 10px; margin:10px 0; background:rgba(14,165,233,.12); }
        table { width:100%; border-collapse:collapse; margin-top:10px; } th, td { border:1px solid var(--border); padding:8px; vertical-align:top; text-align:left; font-size:.9rem; }
        textarea { width:100%; min-height:90px; border:1px solid var(--border); border-radius:8px; background:rgba(15,23,42,.65); color:var(--text); padding:8px; font-family:inherit; resize:vertical; }
        .btn { margin-top:8px; border:1px solid var(--primary); border-radius:8px; background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#fff; padding:8px 10px; font-weight:700; cursor:pointer; }
    
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
        <h1>Kajur Weekly Notes</h1>
        <p class="muted">Week: {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</p>
        @if (session('status'))<div class="alert">{{ session('status') }}</div>@endif
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Mentor Validation</th>
                    <th>Kajur Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->student_name }}<br><span class="muted">NIS: {{ $row->student_nis }}</span></td>
                        <td>
                            <div>Check: {{ is_null($row->mentor_is_correct) ? '-' : ($row->mentor_is_correct ? 'Correct' : 'Not Complete') }}</div>
                            <div>Missing: {{ $row->missing_info_notes ?? '-' }}</div>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('dashboard.kajur.weekly-journal.note', $row->id) }}">
                                @csrf
                                <textarea name="kajur_notes" placeholder="Write kajur notes...">{{ old('kajur_notes', $row->kajur_notes ?? '') }}</textarea>
                                <button class="btn" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">No weekly journals for this week.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>

