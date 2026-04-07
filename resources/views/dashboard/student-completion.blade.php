<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Completion Bar - {{ config('app.name', 'Kips') }}</title>

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

        .app-shell { min-height: 100vh; display: grid; grid-template-columns: 270px 1fr; }
        .sidebar { position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; border-right: 1px solid var(--border); background: rgba(15, 23, 42, 0.92); backdrop-filter: blur(10px); padding: 16px 14px; }
        .sidebar-brand { font-weight: 700; letter-spacing: 0.02em; padding: 8px 10px; border: 1px solid var(--border); border-radius: 12px; background: rgba(30, 41, 59, 0.45); margin-bottom: 14px; }
        .sidebar-nav { display: flex; flex-direction: column; gap: 8px; }
        .sidebar-nav a { text-decoration: none; color: var(--text); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; background: rgba(30, 41, 59, 0.6); font-weight: 500; transition: all 0.2s ease; }
        .sidebar-nav a:hover { border-color: var(--accent); color: var(--accent); }
        .sidebar-nav a.active { border-color: var(--primary); background: linear-gradient(135deg, rgba(37, 99, 235, 0.32), rgba(29, 78, 216, 0.32)); color: #f8fafc; font-weight: 700; }
        .sidebar-profile { margin-top: auto; border: 1px solid var(--border); border-radius: 12px; background: rgba(30, 41, 59, 0.55); padding: 12px; }
        .profile-trigger { width: 100%; text-align: left; appearance: none; border: 1px solid var(--border); border-radius: 12px; background: rgba(15, 23, 42, 0.52); color: var(--text); cursor: pointer; padding: 10px; display: grid; grid-template-columns: 42px 1fr 18px; align-items: center; gap: 10px; }
        .profile-avatar { width: 42px; height: 42px; border-radius: 999px; border: 1px solid rgba(56, 189, 248, 0.55); background: linear-gradient(135deg, rgba(37, 99, 235, 0.28), rgba(56, 189, 248, 0.24)); display: grid; place-items: center; font-size: 0.82rem; font-weight: 700; overflow: hidden; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
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
        .profile-modal-backdrop.open { display: flex; }
        .profile-modal-panel {
            width: min(560px, 96vw);
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 16px;
        }
        .profile-modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
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
        .profile-modal-field { margin-bottom: 12px; }
        .profile-modal-field label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; }
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
        .profile-modal-actions { margin-top: 14px; display: flex; justify-content: flex-end; gap: 8px; }
        .profile-modal-alert.error {
            border: 1px solid rgba(248, 113, 113, 0.6);
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(127, 29, 29, 0.25);
            margin-bottom: 12px;
        }

        .content { padding: 20px; }
        .topbar, .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.86);
            padding: 14px 16px;
        }
        .card { margin-top: 12px; background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94)); }
        .muted { color: var(--muted); font-size: 0.92rem; }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            color: var(--muted);
            font-size: 0.9rem;
            margin: 8px 0;
        }

        .progress {
            height: 14px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(51, 65, 85, 0.55);
            border: 1px solid var(--border);
        }

        .progress > span {
            display: block;
            height: 100%;
            width: var(--progress, 0%);
            background: linear-gradient(90deg, #22c55e, #38bdf8);
        }

        .day-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .day-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.62);
            padding: 10px;
            cursor: pointer;
            text-align: left;
            color: var(--text);
        }

        .day-card:hover { border-color: var(--accent); }
        .day-title { font-weight: 700; margin-bottom: 5px; }
        .badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 0.75rem;
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #bbf7d0;
            background: rgba(34, 197, 94, 0.12);
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

        .modal-backdrop.open { display: flex; }
        .modal {
            width: min(860px, 96vw);
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.96), rgba(15, 23, 42, 0.96));
            padding: 16px;
            max-height: 90vh;
            overflow: auto;
        }

        .modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .close-btn {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.7);
            color: var(--text);
            padding: 6px 10px;
            cursor: pointer;
        }

        .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .detail-box { border: 1px solid var(--border); border-radius: 12px; padding: 10px; background: rgba(15, 23, 42, 0.58); }
        .detail-box h4 { margin-bottom: 6px; font-size: 0.92rem; }
        .detail-text { white-space: pre-wrap; color: #f8fafc; font-size: 0.9rem; }

        @media (max-width: 900px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .content { padding-top: 0; }
            .day-grid { grid-template-columns: 1fr; }
            .detail-grid { grid-template-columns: 1fr; }
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
        $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
    @endphp
    <div class="app-shell">
        @include('dashboard.partials.student-sidebar', ['user' => $user, 'activePage' => 'completion'])

        <main class="content page-drift-up">
            <header class="topbar">
                <h1>Completion Bar</h1>
                <p class="muted">Click any day to see attendance details, task log entries, notes, and mentor scoring.</p>
                <p class="muted">Target days are calculated from your PKL start and end dates, excluding Friday, Saturday, and Sunday.</p>
                <div class="progress-meta">
                    <span>You have completed {{ $completedDays }} out of {{ $targetDays }} days.</span>
                    <span>{{ $progressPercent }}%</span>
                </div>
                <div class="progress" style="--progress: {{ $progressPercent }}%;">
                    <span></span>
                </div>
            </header>

            <section class="card">
                <h2>Checked-in Days</h2>
                <p class="muted">Showing latest <?php echo e($rows->count()); ?> records.</p>
                <div class="day-grid">
                    <?php if ($rows->isEmpty()): ?>
                        <p class="muted">No check-in days yet.</p>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $dateLabel = \Illuminate\Support\Carbon::parse($row->attendance_date, 'Asia/Jakarta')->format('D, d M Y');
                                $checkIn = $row->check_in_at ? \Illuminate\Support\Carbon::parse($row->check_in_at, 'Asia/Jakarta')->format('H:i:s') . ' WIB' : '-';
                                $checkOut = $row->check_out_at ? \Illuminate\Support\Carbon::parse($row->check_out_at, 'Asia/Jakarta')->format('H:i:s') . ' WIB' : '-';
                            ?>
                            <button
                                type="button"
                                class="day-card js-day-card"
                                data-date="<?php echo e($dateLabel); ?>"
                                data-status="<?php echo e(strtoupper($row->status ?? 'pending')); ?>"
                                data-checkin="<?php echo e($checkIn); ?>"
                                data-checkout="<?php echo e($checkOut); ?>"
                                data-ip="<?php echo e($row->ip_address ?? '-'); ?>"
                                data-coordinates="<?php echo e($row->latitude && $row->longitude ? $row->latitude . ', ' . $row->longitude : '-'); ?>"
                                data-planned="<?php echo e($row->planned_today ?? '-'); ?>"
                                data-realization="<?php echo e($row->work_realization ?? '-'); ?>"
                                data-assigned="<?php echo e($row->assigned_work ?? '-'); ?>"
                                data-problems="<?php echo e($row->field_problems ?? '-'); ?>"
                                data-notes="<?php echo e($row->notes ?? '-'); ?>"
                                data-score-smile="<?php echo e($row->score_smile ?? '-'); ?>"
                                data-score-friendliness="<?php echo e($row->score_friendliness ?? '-'); ?>"
                                data-score-appearance="<?php echo e($row->score_appearance ?? '-'); ?>"
                                data-score-communication="<?php echo e($row->score_communication ?? '-'); ?>"
                                data-score-work="<?php echo e($row->score_work_realization ?? '-'); ?>"
                            >
                                <div class="day-title"><?php echo e($dateLabel); ?></div>
                                <div class="muted">In: <?php echo e($checkIn); ?></div>
                                <div class="muted">Out: <?php echo e($checkOut); ?></div>
                                <div style="margin-top: 8px;"><span class="badge"><?php echo e(strtoupper($row->status ?? 'pending')); ?></span></div>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    @include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal])

    <div class="modal-backdrop" id="day-detail-modal" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="day-detail-title">
            <div class="modal-head">
                <h3 id="day-detail-title">Day Detail</h3>
                <button type="button" class="close-btn" id="close-day-detail">Close</button>
            </div>

            <div class="detail-grid">
                <div class="detail-box">
                    <h4>Attendance</h4>
                    <div class="muted">Date</div>
                    <div class="detail-text" id="d-date">-</div>
                    <div class="muted" style="margin-top:6px;">Status</div>
                    <div class="detail-text" id="d-status">-</div>
                    <div class="muted" style="margin-top:6px;">Check-in</div>
                    <div class="detail-text" id="d-checkin">-</div>
                    <div class="muted" style="margin-top:6px;">Check-out</div>
                    <div class="detail-text" id="d-checkout">-</div>
                    <div class="muted" style="margin-top:6px;">IP</div>
                    <div class="detail-text" id="d-ip">-</div>
                    <div class="muted" style="margin-top:6px;">Coordinates</div>
                    <div class="detail-text" id="d-coordinates">-</div>
                </div>

                <div class="detail-box">
                    <h4>Task Log</h4>
                    <div class="muted">Plan Today</div>
                    <div class="detail-text" id="d-planned">-</div>
                    <div class="muted" style="margin-top:6px;">Work Realization</div>
                    <div class="detail-text" id="d-realization">-</div>
                    <div class="muted" style="margin-top:6px;">Assigned Work</div>
                    <div class="detail-text" id="d-assigned">-</div>
                    <div class="muted" style="margin-top:6px;">Field Problems</div>
                    <div class="detail-text" id="d-problems">-</div>
                    <div class="muted" style="margin-top:6px;">Notes</div>
                    <div class="detail-text" id="d-notes">-</div>
                </div>

                <div class="detail-box" style="grid-column: 1 / -1;">
                    <h4>Mentor Daily Scoring</h4>
                    <div class="detail-grid">
                        <div><strong>1. Smile:</strong> <span id="d-score-smile">-</span></div>
                        <div><strong>2. Friendliness:</strong> <span id="d-score-friendliness">-</span></div>
                        <div><strong>3. Appearance:</strong> <span id="d-score-appearance">-</span></div>
                        <div><strong>4. Communication:</strong> <span id="d-score-communication">-</span></div>
                        <div><strong>5. Work Realization:</strong> <span id="d-score-work">-</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const cards = Array.from(document.querySelectorAll('.js-day-card'));
            const modal = document.getElementById('day-detail-modal');
            const closeBtn = document.getElementById('close-day-detail');
            if (!cards.length || !modal || !closeBtn) return;

            const get = (id) => document.getElementById(id);

            const fields = {
                date: get('d-date'),
                status: get('d-status'),
                checkin: get('d-checkin'),
                checkout: get('d-checkout'),
                ip: get('d-ip'),
                coordinates: get('d-coordinates'),
                planned: get('d-planned'),
                realization: get('d-realization'),
                assigned: get('d-assigned'),
                problems: get('d-problems'),
                notes: get('d-notes'),
                scoreSmile: get('d-score-smile'),
                scoreFriendliness: get('d-score-friendliness'),
                scoreAppearance: get('d-score-appearance'),
                scoreCommunication: get('d-score-communication'),
                scoreWork: get('d-score-work'),
            };

            const openModal = () => {
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            };

            const closeModal = () => {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            };

            cards.forEach((card) => {
                card.addEventListener('click', () => {
                    const d = card.dataset;
                    fields.date.textContent = d.date || '-';
                    fields.status.textContent = d.status || '-';
                    fields.checkin.textContent = d.checkin || '-';
                    fields.checkout.textContent = d.checkout || '-';
                    fields.ip.textContent = d.ip || '-';
                    fields.coordinates.textContent = d.coordinates || '-';
                    fields.planned.textContent = d.planned || '-';
                    fields.realization.textContent = d.realization || '-';
                    fields.assigned.textContent = d.assigned || '-';
                    fields.problems.textContent = d.problems || '-';
                    fields.notes.textContent = d.notes || '-';
                    fields.scoreSmile.textContent = d.scoreSmile || '-';
                    fields.scoreFriendliness.textContent = d.scoreFriendliness || '-';
                    fields.scoreAppearance.textContent = d.scoreAppearance || '-';
                    fields.scoreCommunication.textContent = d.scoreCommunication || '-';
                    fields.scoreWork.textContent = d.scoreWork || '-';
                    openModal();
                });
            });

            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('open')) closeModal();
            });
        })();
    </script>
</body>
</html>

