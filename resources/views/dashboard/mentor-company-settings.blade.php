<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mentor Company Settings - {{ config('app.name', 'Kips') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { --bg:#0f172a; --surface:#1e293b; --primary:#2563eb; --accent:#38bdf8; --text:#e2e8f0; --muted:#94a3b8; --border:#334155; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { min-height:100vh; font-family:'Instrument Sans',sans-serif; color:var(--text); background:radial-gradient(1000px 500px at 10% -10%, rgba(56,189,248,.2), transparent), var(--bg); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:270px 1fr; }
        .sidebar { position:sticky; top:0; height:100vh; display:flex; flex-direction:column; border-right:1px solid var(--border); background:rgba(15,23,42,.92); padding:16px 14px; }
        .sidebar-brand { font-weight:700; padding:8px 10px; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.45); margin-bottom:14px; }
        .sidebar-nav { display:flex; flex-direction:column; gap:8px; }
        .sidebar-nav a { text-decoration:none; color:var(--text); border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:rgba(30,41,59,.6); font-weight:500; }
        .sidebar-nav a.active { border-color:var(--primary); background:linear-gradient(135deg, rgba(37,99,235,.32), rgba(29,78,216,.32)); color:#f8fafc; font-weight:700; }
        .sidebar-profile { margin-top:auto; border:1px solid var(--border); border-radius:12px; background:rgba(30,41,59,.55); padding:12px; }
        .profile-trigger { width:100%; text-align:left; appearance:none; border:1px solid var(--border); border-radius:12px; color:var(--text); background:rgba(15,23,42,.52); cursor:pointer; padding:10px; display:grid; grid-template-columns:42px 1fr 18px; align-items:center; gap:10px; }
        .profile-avatar { width:42px; height:42px; border-radius:999px; border:1px solid rgba(56,189,248,.55); background:linear-gradient(135deg, rgba(37,99,235,.28), rgba(56,189,248,.24)); display:grid; place-items:center; font-size:.82rem; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; } .profile-name { font-weight:700; margin-bottom:2px; } .profile-meta { font-size:.85rem; color:var(--muted); } .profile-arrow { color:var(--muted); text-align:right; }
        .main { padding:20px; }
        .topbar,.card { border:1px solid var(--border); border-radius:14px; background:linear-gradient(160deg, rgba(30,41,59,.94), rgba(15,23,42,.94)); padding:14px; }
        .topbar { margin-bottom:12px; } .topbar h1 { font-size:1.12rem; margin-bottom:3px; }
        .muted { color:var(--muted); font-size:.92rem; }
        .alert { border:1px solid rgba(56,189,248,.45); border-radius:10px; padding:8px 10px; margin:10px 0; background:rgba(14,165,233,.12); }
        .alert.error { border-color:rgba(248,113,113,.6); background:rgba(127,29,29,.25); }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .field { margin-top:8px; }
        label { display:block; margin-bottom:6px; font-size:.9rem; color:var(--muted); }
        input[type="text"], input[type="number"] { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:9px 10px; font-family:inherit; font-size:.9rem; }
        input[type="file"] { width:100%; border:1px solid rgba(56,189,248,.35); border-radius:12px; background:linear-gradient(160deg, rgba(30,41,59,.92), rgba(15,23,42,.92)); color:#cbd5e1; padding:8px; font-family:inherit; font-size:.9rem; cursor:pointer; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
        input[type="file"]::file-selector-button { border:1px solid rgba(56,189,248,.45); border-radius:10px; background:linear-gradient(135deg, rgba(37,99,235,.92), rgba(14,165,233,.82)); color:#f8fafc; padding:8px 12px; margin-right:12px; font-weight:700; font-size:.84rem; cursor:pointer; }
        input[type="file"]:hover { border-color:rgba(103,232,249,.7); }
        .btn { border:1px solid var(--primary); border-radius:10px; color:#fff; background:linear-gradient(135deg,var(--primary),#1d4ed8); padding:9px 12px; font-size:.88rem; font-weight:700; cursor:pointer; }
        .crop-wrap { margin-top:10px; border:1px solid rgba(51,65,85,.7); border-radius:12px; padding:10px; background:rgba(15,23,42,.55); }
        .crop-canvas { width:100%; max-width:420px; border:1px dashed rgba(148,163,184,.5); border-radius:10px; background:rgba(2,6,23,.5); display:block; touch-action:none; cursor:grab; }
        .crop-canvas.dragging { cursor:grabbing; }
        .crop-tools { margin-top:8px; display:grid; gap:8px; max-width:420px; }
        .crop-tool-row { display:grid; grid-template-columns:64px 1fr 56px; gap:8px; align-items:center; }
        .crop-tool-row label { margin:0; font-size:.82rem; color:var(--muted); }
        .crop-tool-row input[type="range"] { width:100%; }
        .crop-tool-value { font-size:.8rem; color:#cbd5e1; text-align:right; }
        .crop-actions { display:flex; gap:6px; flex-wrap:wrap; }
        .crop-btn { border:1px solid var(--border); border-radius:8px; background:rgba(15,23,42,.7); color:var(--text); padding:6px 9px; font-size:.76rem; font-weight:600; cursor:pointer; }
        .logo-preview { margin-top:8px; width:90px; height:90px; border-radius:12px; border:1px solid var(--border); overflow:hidden; background:rgba(15,23,42,.7); display:grid; place-items:center; color:var(--muted); font-size:.75rem; }
        .logo-preview img { width:100%; height:100%; object-fit:cover; display:block; }
        .profile-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.62); display:none; align-items:center; justify-content:center; z-index:2200; padding:16px; }
        .profile-modal-backdrop.open { display:flex; }
        .profile-modal-panel { width:min(560px,96vw); border:1px solid var(--border); border-radius:16px; background:linear-gradient(160deg, rgba(30,41,59,.96), rgba(15,23,42,.96)); padding:16px; max-height:92vh; overflow:auto; }
        .profile-modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .profile-modal-close,.profile-modal-btn { border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.72); color:var(--text); padding:8px 12px; cursor:pointer; font-weight:600; }
        .profile-modal-btn.primary { border-color:var(--primary); background:linear-gradient(135deg,var(--primary),#1d4ed8); color:#f8fafc; }
        .profile-modal-field { margin-bottom:12px; } .profile-modal-field label { display:block; margin-bottom:6px; font-size:.9rem; font-weight:600; }
        .profile-modal-field input { width:100%; border:1px solid var(--border); border-radius:10px; background:rgba(15,23,42,.7); color:var(--text); padding:10px 12px; font-size:.95rem; }
        .profile-modal-actions { margin-top:14px; display:flex; justify-content:flex-end; gap:8px; }
        .profile-modal-alert.error { border:1px solid rgba(248,113,113,.6); border-radius:12px; padding:10px 12px; background:rgba(127,29,29,.25); margin-bottom:12px; }
        @media (max-width:1100px){ .app-shell{grid-template-columns:1fr;} .sidebar{position:static;height:auto;} .main{padding-top:0;} .grid{grid-template-columns:1fr;} }
    </style>
</head>
<body>
@php
    $user = auth()->user();
    $canViewMentorDashboard = function_exists('user_can_access') ? user_can_access($user, 'mentor_dashboard', 'view') : true;
    $canViewMentorReviewCenter = function_exists('user_can_access') ? user_can_access($user, 'mentor_review_center', 'view') : true;
    $openProfileModal = $errors->has('name') || $errors->has('nis') || $errors->has('avatar_crop_data') || $errors->has('password');
    $avatarInitials = collect(explode(' ', trim($user->name ?? 'U')))->filter()->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('');
    $avatarSource = !empty($user?->avatar_url) ? (\Illuminate\Support\Str::startsWith($user->avatar_url, ['http://', 'https://']) ? $user->avatar_url : \Illuminate\Support\Facades\Storage::url($user->avatar_url)) : null;
    $avatarSourceWithVersion = $avatarSource ? $avatarSource . (str_contains($avatarSource, '?') ? '&' : '?') . 'v=' . ($user->updated_at?->timestamp ?? time()) : null;
@endphp
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">{{ config('app.name', 'Kips') }} - Mentor</div>
        <nav class="sidebar-nav" aria-label="Mentor menu">
            @if ($canViewMentorDashboard)
                <a href="{{ route('dashboard.mentor.weekly-journal') }}">Mentor Dashboard</a>
                <a class="active" href="{{ route('dashboard.mentor.company-settings-page') }}">Company Profile & Proximity</a>
            @endif
            @if ($canViewMentorReviewCenter)
                <a href="{{ route('dashboard.mentor.review-center') }}">Daily Scoring + Weekly Review</a>
            @endif
            <a href="{{ url('/') }}">Back to Home</a>
        </nav>
        <div class="sidebar-profile">
            <button type="button" class="profile-trigger" id="open-profile-modal" aria-label="Open profile modal">
                <span class="profile-avatar">@if (!empty($avatarSourceWithVersion))<img src="{{ $avatarSourceWithVersion }}" alt="Profile picture" onerror="this.style.display='none'; this.parentElement.textContent='{{ $avatarInitials }}';">@else{{ $avatarInitials }}@endif</span>
                <span><div class="profile-name">{{ $user->name }}</div><div class="profile-meta">NIS: {{ $user->nis ?? '-' }} &middot; {{ strtoupper($user->role) }}</div></span>
                <span class="profile-arrow">></span>
            </button>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>Company Profile & Proximity</h1>
            <p class="muted">Manage your company coordinates and geofence radius for attendance validation.</p>
        </header>
        @if (session('status'))<div class="alert">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert error">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

        <section class="card">
            <form method="POST" action="{{ route('dashboard.mentor.company-settings') }}">
                @csrf
                <div class="field">
                    <label for="company_name">Company Name</label>
                    <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $mentorCompanyName) }}" required>
                </div>
                <div class="field">
                    <label for="company_address">Company Address</label>
                    <input id="company_address" name="company_address" type="text" value="{{ old('company_address', $mentorCompanyAddress) }}" required>
                </div>

                <div class="field">
                    <label for="company_logo_file">Company Logo (Croppable)</label>
                    <input id="company_logo_file" type="file" accept="image/*">
                    <input id="company_logo_crop_data" name="logo_crop_data" type="hidden">
                    <div class="logo-preview" id="company_logo_preview">
                        @php
                            $logoUrl = data_get($companyProfile, 'logo_url');
                            $logoSource = !empty($logoUrl)
                                ? (\Illuminate\Support\Str::startsWith($logoUrl, ['http://', 'https://'])
                                    ? $logoUrl
                                    : \Illuminate\Support\Facades\Storage::url($logoUrl))
                                : null;
                        @endphp
                        @if (!empty($logoSource))
                            <img src="{{ $logoSource }}" alt="Company logo">
                        @else
                            No logo
                        @endif
                    </div>
                    <div class="crop-wrap">
                        <canvas id="company-logo-crop-canvas" class="crop-canvas" width="420" height="315"></canvas>
                        <div class="crop-tools">
                            <div class="crop-tool-row">
                                <label for="company_logo_zoom">Zoom</label>
                                <input id="company_logo_zoom" type="range" min="100" max="1600" step="10" value="100">
                                <span class="crop-tool-value" id="company_logo_zoom_value">100%</span>
                            </div>
                            <div class="crop-tool-row">
                                <label for="company_logo_rotate">Rotate</label>
                                <input id="company_logo_rotate" type="range" min="-180" max="180" step="1" value="0">
                                <span class="crop-tool-value" id="company_logo_rotate_value">0deg</span>
                            </div>
                            <div class="crop-actions">
                                <button type="button" class="crop-btn" id="company_logo_rotate_left">Rotate -90</button>
                                <button type="button" class="crop-btn" id="company_logo_rotate_right">Rotate +90</button>
                                <button type="button" class="crop-btn" id="company_logo_reset">Reset</button>
                            </div>
                        </div>
                        <p class="muted" style="margin-top:8px;">Drag image to position. Cropped result will be saved as PNG.</p>
                    </div>
                </div>
                <div class="grid">
                    <div class="field">
                        <label for="office_latitude">Office Latitude</label>
                        <input id="office_latitude" name="office_latitude" type="text" value="{{ old('office_latitude', data_get($companyProfile, 'office_latitude')) }}" placeholder="-6.2000000">
                    </div>
                    <div class="field">
                        <label for="office_longitude">Office Longitude</label>
                        <input id="office_longitude" name="office_longitude" type="text" value="{{ old('office_longitude', data_get($companyProfile, 'office_longitude')) }}" placeholder="106.8166667">
                    </div>
                </div>
                <div class="field">
                    <label for="geofence_radius_meters">Geofence Radius (meters)</label>
                    <input id="geofence_radius_meters" name="geofence_radius_meters" type="number" min="10" max="5000" value="{{ old('geofence_radius_meters', data_get($companyProfile, 'geofence_radius_meters', 50)) }}">
                </div>
                @if (!($partnerHasGeoColumns ?? false))
                    <p class="muted" style="margin-top:6px;">Run latest migrations to persist coordinate/radius values.</p>
                @endif
                <div style="margin-top:12px;"><button class="btn" type="submit">Save Company Settings</button></div>
            </form>
        </section>
    </main>
</div>

@include('dashboard.partials.student-profile-modal', ['user' => $user, 'openProfileModal' => $openProfileModal, 'profileUpdateRoute' => route('dashboard.profile.update')])
<script>
    (() => {
        const fileInput = document.getElementById('company_logo_file');
        const cropInput = document.getElementById('company_logo_crop_data');
        const canvas = document.getElementById('company-logo-crop-canvas');
        const zoomInput = document.getElementById('company_logo_zoom');
        const zoomValue = document.getElementById('company_logo_zoom_value');
        const rotateInput = document.getElementById('company_logo_rotate');
        const rotateValue = document.getElementById('company_logo_rotate_value');
        const rotateLeftBtn = document.getElementById('company_logo_rotate_left');
        const rotateRightBtn = document.getElementById('company_logo_rotate_right');
        const resetBtn = document.getElementById('company_logo_reset');
        const logoPreview = document.getElementById('company_logo_preview');
        const form = document.querySelector('form[action*="mentor/company-settings"]');
        if (!fileInput || !cropInput || !canvas || !zoomInput || !zoomValue || !rotateInput || !rotateValue || !rotateLeftBtn || !rotateRightBtn || !resetBtn || !form) return;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const state = {
            image: null, baseScale: 1, zoom: 1, rotation: 0, panX: 0, panY: 0,
            dragging: false, pointerId: null, startX: 0, startY: 0, startPanX: 0, startPanY: 0,
        };
        const frameSize = Math.round(Math.min(canvas.width, canvas.height) * 0.74);
        const frameX = (canvas.width - frameSize) / 2;
        const frameY = (canvas.height - frameSize) / 2;
        const frameCenterX = canvas.width / 2;
        const frameCenterY = canvas.height / 2;
        const zoomMin = 1;
        const zoomMax = 16;
        const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

        const updateControlLabels = () => {
            zoomValue.textContent = `${Math.round(state.zoom * 100)}%`;
            rotateValue.textContent = `${Math.round(state.rotation)}deg`;
            zoomInput.value = String(Math.round(state.zoom * 100));
            rotateInput.value = String(Math.round(state.rotation));
        };

        const drawPlaceholder = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'rgba(148, 163, 184, 0.9)';
            ctx.font = '14px Instrument Sans';
            ctx.textAlign = 'center';
            ctx.fillText('Choose a logo image to crop', canvas.width / 2, canvas.height / 2);
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

        const setImage = (img) => {
            state.image = img;
            state.baseScale = Math.max(frameSize / img.width, frameSize / img.height);
            state.zoom = zoomMin;
            state.rotation = 0;
            state.panX = 0;
            state.panY = 0;
            updateControlLabels();
            draw();
        };

        const getPointer = (event) => {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            return { x: (event.clientX - rect.left) * scaleX, y: (event.clientY - rect.top) * scaleY };
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
        resetBtn.addEventListener('click', () => {
            if (!state.image) return;
            state.zoom = zoomMin;
            state.rotation = 0;
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

        form.addEventListener('submit', () => {
            cropInput.value = '';
            if (!state.image || !(fileInput.files && fileInput.files.length)) {
                return;
            }
            const out = document.createElement('canvas');
            out.width = 640;
            out.height = 640;
            const outCtx = out.getContext('2d');
            if (!outCtx) return;
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
            if (logoPreview) {
                logoPreview.innerHTML = '';
                const img = document.createElement('img');
                img.src = cropInput.value;
                img.alt = 'Cropped logo preview';
                logoPreview.appendChild(img);
            }
        });

        updateControlLabels();
        drawPlaceholder();
    })();
</script>
</body>
</html>
