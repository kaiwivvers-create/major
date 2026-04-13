<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Principal Master Report</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 24px; }
        h1, h2 { margin: 0 0 8px; }
        p { margin: 0 0 8px; }
        .section { margin-top: 22px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d0d7de; padding: 6px 8px; font-size: 12px; text-align: left; vertical-align: top; }
        th { background: #f6f8fa; }
        .muted { color: #666; font-size: 12px; }
        .summary { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
        .box { border: 1px solid #d0d7de; border-radius: 8px; padding: 10px; }
        .box strong { display: block; font-size: 24px; margin-top: 4px; }
        @media print {
            .no-print { display: none; }
            body { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 12px;">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>

    <h1>Master Report - School PKL Season</h1>
    <p class="muted">
        Generated: {{ \Illuminate\Support\Carbon::now('Asia/Jakarta')->format('d M Y H:i') }} WIB
        | Week: {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}
    </p>
    <p class="muted">
        Season Window: {{ \Illuminate\Support\Carbon::parse($seasonStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($today, 'Asia/Jakarta')->format('d M Y') }}
    </p>

    <section class="section">
        <h2>Executive Summary</h2>
        <div class="summary">
            <div class="box">
                <span class="muted">Total Students in School</span>
                <strong>{{ (int) ($totalStudentsInSchool ?? 0) }}</strong>
            </div>
            <div class="box">
                <span class="muted">Total Students Placed</span>
                <strong>{{ (int) ($totalStudentsPlaced ?? 0) }}</strong>
            </div>
        </div>
    </section>

    <section class="section">
        <h2>Top 5 Industry Partners</h2>
        <table>
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Address</th>
                    <th>Students</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($topIndustryPartners ?? collect()) as $partner)
                    <tr>
                        <td>{{ $partner->company_name }}</td>
                        <td>{{ $partner->company_address }}</td>
                        <td>{{ (int) ($partner->total_students ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Department Attendance Comparison (Last 30 Days)</h2>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Students</th>
                    <th>Checked Student-Days</th>
                    <th>Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($departmentAttendance ?? collect()) as $dept)
                    <tr>
                        <td>{{ data_get($dept, 'label', '-') }}</td>
                        <td>{{ (int) data_get($dept, 'students', 0) }}</td>
                        <td>{{ (int) data_get($dept, 'checked_days', 0) }}</td>
                        <td>{{ number_format((float) data_get($dept, 'rate', 0), 1) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>MOU Tracker</h2>
        <table>
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Address</th>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th>Expiry</th>
                    <th>Source</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($mouTracker ?? collect()) as $row)
                    <tr>
                        <td>{{ data_get($row, 'company_name', '-') }}</td>
                        <td>{{ data_get($row, 'company_address', '-') }}</td>
                        <td>{{ data_get($row, 'contact_person', '-') }}</td>
                        <td>{{ data_get($row, 'contact_phone', '-') }}</td>
                        <td>
                            @if (data_get($row, 'expiry_date'))
                                {{ \Illuminate\Support\Carbon::parse((string) data_get($row, 'expiry_date'), 'Asia/Jakarta')->format('d M Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ data_get($row, 'expiry_source', '-') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No company data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</body>
</html>
