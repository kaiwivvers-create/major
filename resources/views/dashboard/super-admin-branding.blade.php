<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @if (!empty($appBranding?->logo_url))
        <link rel="icon" href="{{ $appBranding->logo_url }}" type="image/x-icon">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Branding - {{ $appBranding->display_name }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --surface-2: #0b1222;
            --primary: {{ $appBranding->primary_color ?? '#2563eb' }};
            --accent: {{ $appBranding->accent_color ?? '#38bdf8' }};
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
        .app-shell { min-height: 100vh; display: grid; grid-template-columns: 270px 1fr; }
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
            overflow: hidden;
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
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding-right: 2px;
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
        .sidebar-nav a:hover { border-color: var(--accent); color: var(--accent); }
        .sidebar-nav a.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.32), rgba(29, 78, 216, 0.32));
            color: #f8fafc;
            font-weight: 700;
        }
        .sidebar-profile {
            margin-top: 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.55);
            padding: 12px;
            flex: 0 0 auto;
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
        }
        .profile-trigger > div { min-width: 0; }
        .profile-avatar {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            overflow: hidden;
            display: grid;
            place-items: center;
            border: 1px solid rgba(56, 189, 248, 0.55);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.28), rgba(56, 189, 248, 0.24));
            font-size: 0.82rem;
            font-weight: 700;
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .profile-name,
        .profile-meta {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .profile-name { font-weight: 700; margin-bottom: 2px; }
        .profile-meta { font-size: 0.85rem; color: var(--muted); }
        .profile-arrow { color: var(--muted); font-size: 1rem; text-align: right; }
        .main { padding: 24px; }
        .page-header { display: flex; justify-content: space-between; gap: 16px; align-items: flex-end; margin-bottom: 20px; }
        .page-header h1 { font-size: clamp(1.8rem, 3vw, 2.5rem); margin-bottom: 8px; }
        .page-header p { color: var(--muted); max-width: 720px; line-height: 1.6; }
        .status, .error-list { margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--border); }
        .status { background: rgba(34, 197, 94, 0.12); border-color: rgba(34, 197, 94, 0.35); color: #bbf7d0; }
        .error-list { background: rgba(248, 113, 113, 0.12); border-color: rgba(248, 113, 113, 0.35); color: #fecaca; }
        .content-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr); gap: 20px; align-items: start; }
        .card {
            border: 1px solid var(--border);
            border-radius: 18px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.92));
            box-shadow: 0 24px 60px rgba(2, 6, 23, 0.28);
            overflow: hidden;
        }
        .card-header { padding: 18px 20px 0; }
        .card-header h2 { font-size: 1.15rem; margin-bottom: 8px; }
        .card-header p { color: var(--muted); line-height: 1.6; }
        form { padding: 20px; }
        .field { margin-bottom: 18px; }
        .field label { display: block; margin-bottom: 8px; font-size: 0.92rem; font-weight: 600; }
        .field input[type="text"], .field textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.72);
            color: var(--text);
            padding: 11px 13px;
            font-size: 0.96rem;
        }
        .field textarea { min-height: 120px; resize: vertical; }
        .field input[type="file"] {
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
        .field input[type="file"]::file-selector-button {
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
        .field input[type="file"]:hover {
            border-color: rgba(103, 232, 249, 0.7);
        }
        .field input[type="checkbox"] { transform: translateY(1px); margin-right: 8px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .muted { color: var(--muted); font-size: 0.9rem; line-height: 1.5; }
        .branding-crop-shell { margin-top: 12px; display: grid; grid-template-columns: minmax(0, 420px) minmax(260px, 1fr); gap: 16px; align-items: start; }
        .branding-crop-canvas {
            width: 100%;
            border: 1px dashed rgba(148, 163, 184, 0.5);
            border-radius: 14px;
            background: rgba(2, 6, 23, 0.5);
            display: block;
            touch-action: none;
            cursor: grab;
        }
        .branding-crop-canvas.dragging { cursor: grabbing; }
        .branding-crop-tools { border: 1px solid rgba(51, 65, 85, 0.8); border-radius: 14px; padding: 14px; background: rgba(2, 6, 23, 0.32); }
        .branding-crop-tool-row { margin-bottom: 14px; }
        .branding-crop-tool-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            font-size: 0.88rem;
            margin-bottom: 10px;
        }
        .branding-crop-tool-label span:last-child {
            flex: 0 0 auto;
            min-width: 56px;
            text-align: right;
            color: var(--muted);
        }
        .branding-crop-tool-row input[type="range"] {
            width: 100%;
            display: block;
            margin: 0;
        }
        .branding-crop-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .btn, .branding-crop-btn {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.82);
            color: var(--text);
            padding: 10px 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn.primary { border-color: var(--primary); background: linear-gradient(135deg, var(--primary), #1d4ed8); color: #f8fafc; }
        .preview-card { padding: 18px 20px 20px; }
        .brand-preview { display: grid; gap: 18px; }
        .preview-nav, .preview-sidebar-brand { display: flex; align-items: center; gap: 12px; }
        .preview-mark {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            overflow: hidden;
            flex: 0 0 56px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(56, 189, 248, 0.35);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.3), rgba(56, 189, 248, 0.18));
            font-size: 1.15rem;
            font-weight: 800;
        }
        .preview-mark img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .preview-hero { border: 1px solid rgba(51, 65, 85, 0.8); border-radius: 18px; padding: 18px; background: linear-gradient(135deg, rgba(15, 23, 42, 0.92), rgba(30, 41, 59, 0.92)); }
        .preview-hero-image {
            margin-top: 16px;
            width: 100%;
            aspect-ratio: 16 / 9;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(51, 65, 85, 0.8);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.18), rgba(56, 189, 248, 0.12));
            display: grid;
            place-items: center;
            color: var(--muted);
        }
        .preview-hero-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
        html[data-theme="light"] .card {
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.95), rgba(255, 247, 237, 0.92));
            border-color: rgba(251, 146, 60, 0.5);
            box-shadow: 0 18px 40px rgba(124, 45, 18, 0.12);
        }
        html[data-theme="light"] .field input[type="text"],
        html[data-theme="light"] .field textarea {
            background: #fffaf4;
            border-color: rgba(251, 146, 60, 0.55);
            color: #7c2d12;
        }
        html[data-theme="light"] .field input[type="file"] {
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.96), rgba(255, 247, 237, 0.92));
            border-color: rgba(251, 146, 60, 0.55);
            color: #9a3412;
        }
        html[data-theme="light"] .field input[type="file"]::file-selector-button {
            background: #f97316;
            border-color: #ea580c;
            color: #fff7ed;
        }
        html[data-theme="light"] .field input[type="file"]:hover {
            border-color: #ea580c;
        }
        html[data-theme="light"] .branding-crop-canvas {
            border-color: rgba(251, 146, 60, 0.55);
            background: rgba(255, 247, 237, 0.95);
        }
        html[data-theme="light"] .branding-crop-tools {
            border-color: rgba(251, 146, 60, 0.55);
            background: rgba(255, 250, 244, 0.95);
        }
        html[data-theme="light"] .preview-mark {
            border-color: rgba(251, 146, 60, 0.6);
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.28), rgba(251, 146, 60, 0.22));
            color: #fff7ed;
        }
        html[data-theme="light"] .preview-hero {
            border-color: rgba(251, 146, 60, 0.55);
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.96), rgba(255, 247, 237, 0.93));
        }
        html[data-theme="light"] .preview-hero-image {
            border-color: rgba(251, 146, 60, 0.55);
            background: linear-gradient(135deg, rgba(255, 237, 213, 0.8), rgba(255, 247, 237, 0.92));
            color: #9a3412;
        }
        body > .profile-modal-backdrop {
            position: fixed !important;
            inset: 0 !important;
            display: none !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 2200 !important;
            padding: 16px !important;
            background: rgba(2, 6, 23, 0.62) !important;
        }
        body > .profile-modal-backdrop.open { display: flex !important; }
        body > .profile-modal-backdrop .profile-modal-panel {
            width: min(560px, 96vw) !important;
            max-height: 92vh !important;
            overflow: auto !important;
        }
        @media (max-width: 1180px) { .content-grid { grid-template-columns: 1fr; } }
        @media (max-width: 960px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .main { padding-top: 0; }
            .branding-crop-shell { grid-template-columns: 1fr; }
            .grid-2 { grid-template-columns: 1fr; }
        }        .main { animation: page-drift-up 0.7s ease-out both; }
        .profile-modal-backdrop.open .profile-modal-panel { animation: page-drift-up 0.7s ease-out 0.15s both; }
        @keyframes page-drift-up {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }    </style>
</head>
<body>
@php
    $pageUser = $user ?? auth()->user();
@endphp

<div class="app-shell">
    @include('dashboard.partials.super-admin-sidebar', ['user' => $pageUser, 'activePage' => 'branding'])

    <main class="main">
        <div class="page-header">
            <div>
                <h1>Branding</h1>
                <p>Update the app name, the logo shown in dashboard sidebars, and the landing page hero copy and image from one place.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-list">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="content-grid">
            <section class="card">
                <div class="card-header">
                    <h2>Brand Settings</h2>
                    <p>Square crops are used for the app logo. Wide crops are used for the public home page hero image.</p>
                </div>

                <form id="branding-form" method="POST" action="{{ route('dashboard.super-admin.branding.update') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="grid-2">
                        <div class="field">
                            <label for="app_name">App Name</label>
                            <input id="app_name" name="app_name" type="text" value="{{ old('app_name', $branding->app_name) }}" maxlength="120" required>
                        </div>
                        <div class="field">
                            <label for="short_name">Logo Fallback Letter(s)</label>
                            <input id="short_name" name="short_name" type="text" value="{{ old('short_name', $branding->short_name) }}" maxlength="10" required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="brand_logo_file">Logo</label>
                        <input id="brand_logo_file" name="logo_file" type="file" accept="image/*">
                        <input id="brand_logo_crop_data" name="logo_crop_data" type="hidden">
                        <div class="branding-crop-shell">
                            <canvas id="brand-logo-crop-canvas" class="branding-crop-canvas" width="420" height="315"></canvas>
                            <div class="branding-crop-tools">
                                <div class="branding-crop-tool-row">
                                    <div class="branding-crop-tool-label"><span>Zoom</span><span id="brand_logo_zoom_value">100%</span></div>
                                    <input id="brand_logo_zoom" type="range" min="100" max="1600" step="10" value="100">
                                </div>
                                <div class="branding-crop-tool-row">
                                    <div class="branding-crop-tool-label"><span>Rotate</span><span id="brand_logo_rotate_value">0deg</span></div>
                                    <input id="brand_logo_rotate" type="range" min="-180" max="180" step="1" value="0">
                                </div>
                                <div class="branding-crop-actions">
                                    <button type="button" class="branding-crop-btn" id="brand_logo_rotate_left">Rotate -90</button>
                                    <button type="button" class="branding-crop-btn" id="brand_logo_rotate_right">Rotate +90</button>
                                    <button type="button" class="branding-crop-btn" id="brand_logo_reset">Reset</button>
                                </div>
                                <label class="muted" style="display:block; margin-top:14px;">
                                    <input type="checkbox" name="remove_logo" value="1">
                                    Remove current logo
                                </label>
                                <p class="muted" style="margin-top:10px;">Drag the image to frame it. The saved output is a 640x640 PNG.</p>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label for="hero_kicker">Landing Page Kicker</label>
                        <input id="hero_kicker" name="hero_kicker" type="text" value="{{ old('hero_kicker', $branding->hero_kicker) }}" maxlength="255" required>
                    </div>

                    <div class="field">
                        <label for="hero_heading">Landing Page Heading</label>
                        <input id="hero_heading" name="hero_heading" type="text" value="{{ old('hero_heading', $branding->hero_heading) }}" maxlength="255" required>
                    </div>

                    <div class="field">
                        <label for="hero_body">Landing Page Description</label>
                        <textarea id="hero_body" name="hero_body" maxlength="5000" required>{{ old('hero_body', $branding->hero_body) }}</textarea>
                    </div>

                    <div class="field">
                        <label for="brand_hero_file">Landing Page Hero Image</label>
                        <input id="brand_hero_file" name="hero_file" type="file" accept="image/*">
                        <input id="brand_hero_crop_data" name="hero_crop_data" type="hidden">
                        <div class="branding-crop-shell">
                            <canvas id="brand-hero-crop-canvas" class="branding-crop-canvas" width="640" height="360"></canvas>
                            <div class="branding-crop-tools">
                                <div class="branding-crop-tool-row">
                                    <div class="branding-crop-tool-label"><span>Zoom</span><span id="brand_hero_zoom_value">100%</span></div>
                                    <input id="brand_hero_zoom" type="range" min="100" max="1600" step="10" value="100">
                                </div>
                                <div class="branding-crop-tool-row">
                                    <div class="branding-crop-tool-label"><span>Rotate</span><span id="brand_hero_rotate_value">0deg</span></div>
                                    <input id="brand_hero_rotate" type="range" min="-180" max="180" step="1" value="0">
                                </div>
                                <div class="branding-crop-actions">
                                    <button type="button" class="branding-crop-btn" id="brand_hero_rotate_left">Rotate -90</button>
                                    <button type="button" class="branding-crop-btn" id="brand_hero_rotate_right">Rotate +90</button>
                                    <button type="button" class="branding-crop-btn" id="brand_hero_reset">Reset</button>
                                </div>
                                <label class="muted" style="display:block; margin-top:14px;">
                                    <input type="checkbox" name="remove_hero_image" value="1">
                                    Remove current hero image
                                </label>
                                <p class="muted" style="margin-top:10px;">This crop is exported as a 1600x900 PNG for the public home page.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="login_heading">Login Page Heading</label>
                            <input id="login_heading" name="login_heading" type="text" value="{{ old('login_heading', $branding->login_heading) }}" maxlength="255" required>
                        </div>
                        <div class="field">
                            <label for="login_subheading">Login Page Subheading</label>
                            <input id="login_subheading" name="login_subheading" type="text" value="{{ old('login_subheading', $branding->login_subheading) }}" maxlength="255" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 24px;">
                        <div class="field">
                            <label for="primary_color">Primary Color</label>
                            <input id="primary_color" name="primary_color" type="color" value="{{ old('primary_color', $branding->primary_color) }}" style="width: 100%; height: 44px; padding: 4px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface);">
                        </div>
                        <div class="field">
                            <label for="secondary_color">Secondary Color</label>
                            <input id="secondary_color" name="secondary_color" type="color" value="{{ old('secondary_color', $branding->secondary_color) }}" style="width: 100%; height: 44px; padding: 4px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface);">
                        </div>
                        <div class="field">
                            <label for="accent_color">Accent Color</label>
                            <input id="accent_color" name="accent_color" type="color" value="{{ old('accent_color', $branding->accent_color) }}" style="width: 100%; height: 44px; padding: 4px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface);">
                        </div>
                    </div>

                    <div class="field" style="margin-bottom: 24px;">
                        <label class="muted" style="display:flex; align-items:center; gap: 8px;">
                            <input type="checkbox" name="chatbot_enabled" value="1" {{ old('chatbot_enabled', $branding->chatbot_enabled) ? 'checked' : '' }}>
                            Enable Site-Wide Chatbot Assistant
                        </label>
                    </div>

                    <div class="actions">
                        <button class="btn primary" type="submit">Save Branding</button>
                    </div>
                </form>
            </section>

            <aside class="card preview-card">
                <div class="brand-preview">
                    <div>
                        <div class="muted" style="margin-bottom:10px;">Sidebar Preview</div>
                        <div class="preview-sidebar-brand">
                            <span class="preview-mark" id="branding-logo-preview">
                                @if (!empty($branding->logo_url))
                                    <img src="{{ $branding->logo_url }}" alt="{{ $branding->display_name }} logo">
                                @else
                                    {{ $branding->short_display_name }}
                                @endif
                            </span>
                            <div>
                                <div style="font-weight:700;" id="branding-name-preview">{{ $branding->display_name }}</div>
                                <div class="muted">Super Admin</div>
                            </div>
                        </div>
                    </div>

                    <div class="preview-hero">
                        <div class="preview-nav">
                            <span class="preview-mark" id="branding-logo-preview-secondary">
                                @if (!empty($branding->logo_url))
                                    <img src="{{ $branding->logo_url }}" alt="{{ $branding->display_name }} logo">
                                @else
                                    {{ $branding->short_display_name }}
                                @endif
                            </span>
                            <div>
                                <div style="font-weight:700;" id="branding-nav-name-preview">{{ $branding->display_name }}</div>
                                <div class="muted">Landing page</div>
                            </div>
                        </div>
                        <div style="margin-top:18px;">
                            <div class="muted" id="branding-kicker-preview">{{ $branding->hero_kicker }}</div>
                            <h3 style="font-size:1.6rem; margin-top:10px;" id="branding-heading-preview">{{ $branding->hero_heading }}</h3>
                            <p class="muted" style="margin-top:10px;" id="branding-body-preview">{{ $branding->hero_body }}</p>
                            <div class="preview-hero-image" id="branding-hero-preview">
                                @if (!empty($branding->hero_image_url))
                                    <img src="{{ $branding->hero_image_url }}" alt="Landing page hero image">
                                @else
                                    Hero image preview
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</div>

@include('dashboard.partials.student-profile-modal', ['user' => $pageUser, 'openProfileModal' => false, 'profileUpdateRoute' => route('dashboard.super-admin.profile')])

<script>
    (() => {
        const profileBackdrop = document.getElementById('profile-modal-backdrop');
        if (profileBackdrop && profileBackdrop.parentElement !== document.body) {
            document.body.appendChild(profileBackdrop);
        }

        const createCropper = (config) => {
            const fileInput = document.getElementById(config.fileInputId);
            const cropInput = document.getElementById(config.cropInputId);
            const canvas = document.getElementById(config.canvasId);
            const zoomInput = document.getElementById(config.zoomInputId);
            const zoomValue = document.getElementById(config.zoomValueId);
            const rotateInput = document.getElementById(config.rotateInputId);
            const rotateValue = document.getElementById(config.rotateValueId);
            const rotateLeftBtn = document.getElementById(config.rotateLeftBtnId);
            const rotateRightBtn = document.getElementById(config.rotateRightBtnId);
            const resetBtn = document.getElementById(config.resetBtnId);

            if (!fileInput || !cropInput || !canvas || !zoomInput || !zoomValue || !rotateInput || !rotateValue || !rotateLeftBtn || !rotateRightBtn || !resetBtn) {
                return null;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) return null;

            const state = {
                image: null,
                baseScale: 1,
                zoom: 1,
                rotation: 0,
                panX: 0,
                panY: 0,
                dragging: false,
                pointerId: null,
                startX: 0,
                startY: 0,
                startPanX: 0,
                startPanY: 0,
            };

            const frameWidth = Math.round(canvas.width * config.frameWidthRatio);
            const frameHeight = Math.round(frameWidth / config.aspectRatio);
            const frameX = (canvas.width - frameWidth) / 2;
            const frameY = (canvas.height - frameHeight) / 2;
            const frameCenterX = canvas.width / 2;
            const frameCenterY = canvas.height / 2;
            const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
            let previewFrame = null;

            const updateLabels = () => {
                zoomValue.textContent = `${Math.round(state.zoom * 100)}%`;
                rotateValue.textContent = `${Math.round(state.rotation)}deg`;
                zoomInput.value = String(Math.round(state.zoom * 100));
                rotateInput.value = String(Math.round(state.rotation));
            };

            const buildOutputDataUrl = (persistSelection = false) => {
                cropInput.value = '';
                if (!state.image) return null;
                if (persistSelection && !(fileInput.files && fileInput.files.length)) return null;

                const out = document.createElement('canvas');
                out.width = config.outputWidth;
                out.height = config.outputHeight;
                const outCtx = out.getContext('2d');
                if (!outCtx) return null;

                outCtx.fillStyle = 'rgba(15, 23, 42, 1)';
                outCtx.fillRect(0, 0, out.width, out.height);

                const outputScale = out.width / frameWidth;
                outCtx.save();
                outCtx.translate(out.width / 2, out.height / 2);
                outCtx.scale(outputScale, outputScale);
                outCtx.translate(-frameCenterX, -frameCenterY);
                drawTransformed(outCtx);
                outCtx.restore();

                const dataUrl = out.toDataURL('image/png');
                if (persistSelection) {
                    cropInput.value = dataUrl;
                }

                return dataUrl;
            };

            const queuePreviewUpdate = () => {
                if (typeof config.onChange !== 'function') return;
                if (previewFrame !== null) cancelAnimationFrame(previewFrame);
                previewFrame = requestAnimationFrame(() => {
                    previewFrame = null;
                    config.onChange(buildOutputDataUrl(false));
                });
            };

            const drawPlaceholder = () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = 'rgba(148, 163, 184, 0.9)';
                ctx.font = '14px Instrument Sans';
                ctx.textAlign = 'center';
                ctx.fillText(config.placeholder, canvas.width / 2, canvas.height / 2);
                queuePreviewUpdate();
            };

            const drawTransformed = (targetCtx) => {
                if (!state.image) return;
                const scale = state.baseScale * state.zoom;
                targetCtx.save();
                targetCtx.translate(frameCenterX + state.panX, frameCenterY + state.panY);
                targetCtx.rotate((state.rotation * Math.PI) / 180);
                targetCtx.scale(scale, scale);
                targetCtx.drawImage(state.image, -state.image.width / 2, -state.image.height / 2);
                targetCtx.restore();
            };

            const draw = () => {
                if (!state.image) {
                    drawPlaceholder();
                    return;
                }

                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = 'rgba(15, 23, 42, 0.92)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                drawTransformed(ctx);
                ctx.fillStyle = 'rgba(2, 6, 23, 0.45)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.save();
                ctx.beginPath();
                ctx.rect(frameX, frameY, frameWidth, frameHeight);
                ctx.clip();
                drawTransformed(ctx);
                ctx.restore();
                ctx.strokeStyle = 'rgba(56, 189, 248, 0.95)';
                ctx.lineWidth = 2;
                ctx.strokeRect(frameX, frameY, frameWidth, frameHeight);
                queuePreviewUpdate();
            };

            const setImage = (img) => {
                state.image = img;
                state.baseScale = Math.max(frameWidth / img.width, frameHeight / img.height);
                state.zoom = 1;
                state.rotation = 0;
                state.panX = 0;
                state.panY = 0;
                updateLabels();
                draw();
            };

            const getPointer = (event) => {
                const rect = canvas.getBoundingClientRect();
                const scaleX = canvas.width / rect.width;
                const scaleY = canvas.height / rect.height;
                return { x: (event.clientX - rect.left) * scaleX, y: (event.clientY - rect.top) * scaleY };
            };

            fileInput.addEventListener('change', (event) => {
                cropInput.value = '';
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
                state.zoom = clamp(Number(zoomInput.value) / 100, 1, 16);
                updateLabels();
                draw();
            });

            rotateInput.addEventListener('input', () => {
                state.rotation = clamp(Number(rotateInput.value), -180, 180);
                updateLabels();
                draw();
            });

            rotateLeftBtn.addEventListener('click', () => {
                state.rotation = clamp(state.rotation - 90, -180, 180);
                updateLabels();
                draw();
            });

            rotateRightBtn.addEventListener('click', () => {
                state.rotation = clamp(state.rotation + 90, -180, 180);
                updateLabels();
                draw();
            });

            resetBtn.addEventListener('click', () => {
                if (!state.image) return;
                state.zoom = 1;
                state.rotation = 0;
                state.panX = 0;
                state.panY = 0;
                updateLabels();
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
                state.zoom = clamp(state.zoom + (event.deltaY > 0 ? -0.09 : 0.09), 1, 16);
                updateLabels();
                draw();
            }, { passive: false });

            const exportImage = () => buildOutputDataUrl(true);

            updateLabels();
            drawPlaceholder();

            return { exportImage };
        };

        const appNameInput = document.getElementById('app_name');
        const shortNameInput = document.getElementById('short_name');
        const kickerInput = document.getElementById('hero_kicker');
        const headingInput = document.getElementById('hero_heading');
        const bodyInput = document.getElementById('hero_body');
        const form = document.getElementById('branding-form');
        const logoFileInput = document.getElementById('brand_logo_file');
        const heroFileInput = document.getElementById('brand_hero_file');
        const removeLogoInput = document.querySelector('input[name="remove_logo"]');
        const removeHeroInput = document.querySelector('input[name="remove_hero_image"]');
        const logoPreviewEls = [document.getElementById('branding-logo-preview'), document.getElementById('branding-logo-preview-secondary')].filter(Boolean);
        const namePreviewEls = [document.getElementById('branding-name-preview'), document.getElementById('branding-nav-name-preview')].filter(Boolean);
        const kickerPreview = document.getElementById('branding-kicker-preview');
        const headingPreview = document.getElementById('branding-heading-preview');
        const bodyPreview = document.getElementById('branding-body-preview');
        const heroPreview = document.getElementById('branding-hero-preview');
        const existingLogoUrl = @json($branding->logo_url);
        const existingHeroUrl = @json($branding->hero_image_url);

        const renderLogoPreview = (src, fallbackText) => {
            logoPreviewEls.forEach((el) => {
                if (!el) return;
                if (src) {
                    el.innerHTML = `<img src="${src}" alt="Brand logo preview">`;
                } else {
                    el.textContent = fallbackText || 'K';
                }
            });
        };

        const renderHeroPreview = (src) => {
            if (!heroPreview) return;
            if (src) {
                heroPreview.innerHTML = `<img src="${src}" alt="Hero image preview">`;
                return;
            }

            heroPreview.textContent = 'Hero image preview';
        };

        const syncTextPreview = () => {
            const appName = (appNameInput?.value || '').trim() || '{{ addslashes($branding->display_name) }}';
            const shortName = (shortNameInput?.value || '').trim() || 'K';
            namePreviewEls.forEach((el) => { el.textContent = appName; });
            if (kickerPreview && kickerInput) kickerPreview.textContent = kickerInput.value;
            if (headingPreview && headingInput) headingPreview.textContent = headingInput.value;
            if (bodyPreview && bodyInput) bodyPreview.textContent = bodyInput.value;
            return shortName;
        };

        const refreshExistingPreviews = () => {
            const shortName = syncTextPreview();
            const logoSrc = removeLogoInput?.checked ? '' : existingLogoUrl;
            const heroSrc = removeHeroInput?.checked ? '' : existingHeroUrl;
            renderLogoPreview(logoSrc, shortName);
            renderHeroPreview(heroSrc);
        };

        [appNameInput, shortNameInput, kickerInput, headingInput, bodyInput].forEach((input) => {
            input?.addEventListener('input', () => {
                const shortName = syncTextPreview();
                if (!logoPreviewEls[0]?.querySelector('img')) renderLogoPreview('', shortName);
            });
        });

        logoFileInput?.addEventListener('change', () => {
            if (removeLogoInput) removeLogoInput.checked = false;
        });

        heroFileInput?.addEventListener('change', () => {
            if (removeHeroInput) removeHeroInput.checked = false;
        });

        removeLogoInput?.addEventListener('change', () => {
            if (removeLogoInput.checked) {
                renderLogoPreview('', syncTextPreview());
            } else if (!(logoFileInput?.files && logoFileInput.files.length)) {
                refreshExistingPreviews();
            }
        });

        removeHeroInput?.addEventListener('change', () => {
            if (removeHeroInput.checked) {
                renderHeroPreview('');
            } else if (!(heroFileInput?.files && heroFileInput.files.length)) {
                refreshExistingPreviews();
            }
        });

        form?.addEventListener('submit', () => {
            const shortName = syncTextPreview();
            const logoData = logoCropper?.exportImage();
            const heroData = heroCropper?.exportImage();
            if (logoData) renderLogoPreview(logoData, shortName);
            if (heroData) renderHeroPreview(heroData);
        });

        refreshExistingPreviews();
        const logoCropper = createCropper({
            fileInputId: 'brand_logo_file',
            cropInputId: 'brand_logo_crop_data',
            canvasId: 'brand-logo-crop-canvas',
            zoomInputId: 'brand_logo_zoom',
            zoomValueId: 'brand_logo_zoom_value',
            rotateInputId: 'brand_logo_rotate',
            rotateValueId: 'brand_logo_rotate_value',
            rotateLeftBtnId: 'brand_logo_rotate_left',
            rotateRightBtnId: 'brand_logo_rotate_right',
            resetBtnId: 'brand_logo_reset',
            placeholder: 'Choose a logo image to crop',
            aspectRatio: 1,
            frameWidthRatio: 0.74,
            outputWidth: 640,
            outputHeight: 640,
            onChange: (data) => {
                if (removeLogoInput?.checked) {
                    renderLogoPreview('', syncTextPreview());
                    return;
                }

                if (data) {
                    renderLogoPreview(data, syncTextPreview());
                    return;
                }

                if (!(logoFileInput?.files && logoFileInput.files.length)) {
                    refreshExistingPreviews();
                }
            },
        });

        const heroCropper = createCropper({
            fileInputId: 'brand_hero_file',
            cropInputId: 'brand_hero_crop_data',
            canvasId: 'brand-hero-crop-canvas',
            zoomInputId: 'brand_hero_zoom',
            zoomValueId: 'brand_hero_zoom_value',
            rotateInputId: 'brand_hero_rotate',
            rotateValueId: 'brand_hero_rotate_value',
            rotateLeftBtnId: 'brand_hero_rotate_left',
            rotateRightBtnId: 'brand_hero_rotate_right',
            resetBtnId: 'brand_hero_reset',
            placeholder: 'Choose a hero image to crop',
            aspectRatio: 16 / 9,
            frameWidthRatio: 0.84,
            outputWidth: 1600,
            outputHeight: 900,
            onChange: (data) => {
                if (removeHeroInput?.checked) {
                    renderHeroPreview('');
                    return;
                }

                if (data) {
                    renderHeroPreview(data);
                    return;
                }

                if (!(heroFileInput?.files && heroFileInput.files.length)) {
                    refreshExistingPreviews();
                }
            },
        });
    })();
</script>

    @include('partials.chatbot')
</body>
</html>

