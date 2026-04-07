<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kepala Sekolah Weekly Overview - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; }
        * { box-sizing:border-box; margin:0; padding:0; } body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); padding:16px; background:var(--bg); }
        .card { border:1px solid var(--border); border-radius:14px; background:rgba(15,23,42,.9); padding:14px; margin-bottom:14px; }
        .muted { color:var(--muted); font-size:.92rem; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid var(--border); padding:8px; vertical-align:top; text-align:left; font-size:.88rem; }
        h2 { margin-top:4px; font-size:1.02rem; }
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
    <section class="card">
        <h1>Kepala Sekolah - Weekly Journal Overview</h1>
        <p class="muted">Week: {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</p>
    </section>

    <section class="card">
        <h2>PKL Table</h2>
        <p class="muted">Student PKL activities and mentor validation.</p>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>PKL Activity</th>
                    <th>Student Notes</th>
                    <th>Mentor Check</th>
                    <th>Missing Info</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->student_name }}<br><span class="muted">{{ $row->student_nis }}</span></td>
                        <td>{{ $row->learning_notes }}</td>
                        <td>{{ $row->student_mentor_notes }}</td>
                        <td>{{ is_null($row->mentor_is_correct) ? '-' : ($row->mentor_is_correct ? 'Correct' : 'Not Complete') }}</td>
                        <td>{{ $row->missing_info_notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No weekly journals for this week.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>School Table</h2>
        <p class="muted">Notes from Kajur and Guru Bindo for school monitoring.</p>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Kajur Notes</th>
                    <th>Guru Bindo Notes</th>
                    <th>Reviewers</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->student_name }}<br><span class="muted">{{ $row->student_nis }}</span></td>
                        <td>{{ strtoupper($row->status) }}</td>
                        <td>{{ $row->kajur_notes ?? '-' }}</td>
                        <td>{{ $row->bindo_notes ?? '-' }}</td>
                        <td>
                            Mentor: {{ $row->mentor_name ?? '-' }}<br>
                            Kajur: {{ $row->kajur_name ?? '-' }}<br>
                            Bindo: {{ $row->bindo_name ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No weekly journals for this week.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</body>
</html>
