<!DOCTYPE html>
<html lang="en">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Principal Master Report</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            font-size: 12px;
            line-height: 1.45;
        }
        h1, h2, h3, p { margin: 0; }
        .report {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
        }
        .report-head {
            padding: 16px 18px;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #f8fafc;
        }
        .report-title {
            font-size: 21px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .report-subtitle {
            margin-top: 4px;
            color: #cbd5e1;
            font-size: 12px;
        }
        .meta-list {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .meta-pill {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 11px;
            color: #e2e8f0;
        }
        .content { padding: 14px 16px 16px; background: #ffffff; }
        .section { margin-top: 16px; page-break-inside: avoid; }
        .section:first-child { margin-top: 0; }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
        }
        .muted { color: #475569; font-size: 11px; }
        .summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 8px;
        }
        .box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }
        .box strong {
            display: block;
            font-size: 28px;
            margin-top: 3px;
            color: #0f172a;
            line-height: 1.1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 7px 8px;
            font-size: 11px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
        }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        td.num { text-align: right; white-space: nowrap; }
        .footer-note {
            margin-top: 14px;
            font-size: 10px;
            color: #64748b;
            text-align: right;
        }
        @media print {
            .no-print { display: none; }
            .report { border-color: #94a3b8; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 12px;">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>

    <article class="report">
        <header class="report-head">
            <h1 class="report-title">Master Report - School PKL Season</h1>
            <p class="report-subtitle">Principal Dashboard Export</p>
            <div class="meta-list">
                <span class="meta-pill">Generated: {{ \Illuminate\Support\Carbon::now('Asia/Jakarta')->format('d M Y H:i') }} WIB</span>
                <span class="meta-pill">Week: {{ \Illuminate\Support\Carbon::parse($weekStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEnd, 'Asia/Jakarta')->format('d M Y') }}</span>
                <span class="meta-pill">Season: {{ \Illuminate\Support\Carbon::parse($seasonStart, 'Asia/Jakarta')->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($today, 'Asia/Jakarta')->format('d M Y') }}</span>
            </div>
        </header>

        <div class="content">
            <section class="section">
                <h2 class="section-title">Executive Summary</h2>
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
                <h2 class="section-title">Top 5 Industry Partners</h2>
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
                                <td class="num">{{ (int) ($partner->total_students ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="section">
                <h2 class="section-title">Department Attendance Comparison (Last 30 Days)</h2>
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
                                <td class="num">{{ (int) data_get($dept, 'students', 0) }}</td>
                                <td class="num">{{ (int) data_get($dept, 'checked_days', 0) }}</td>
                                <td class="num">{{ number_format((float) data_get($dept, 'rate', 0), 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="section">
                <h2 class="section-title">MOU Tracker</h2>
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
                                <td class="num">
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

            <p class="footer-note">Generated by {{ config('app.name', 'Kips') }}</p>
        </div>
    </article>

    @include('partials.chatbot')
</body>
</html>

