<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Kips') }}</title>

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
            --cursor-x: 50%;
            --cursor-y: 50%;
            --glow-opacity: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            overflow-x: hidden;
            scrollbar-color: #38bdf8 #0f172a;
            scrollbar-width: thin;
            scroll-behavior: smooth;
        }

        body::-webkit-scrollbar {
            width: 12px;
        }

        body::-webkit-scrollbar-track {
            background: #0f172a;
        }

        body::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #38bdf8, #2563eb);
            border-radius: 999px;
            border: 2px solid #0f172a;
        }

        body::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #67e8f9, #3b82f6);
        }

        body {
            position: relative;
            min-height: 100vh;
            font-family: 'Instrument Sans', sans-serif;
            color: var(--text);
            padding-top: 84px;
            padding-bottom: 86px;
            background:
                radial-gradient(1000px 500px at 10% -10%, rgba(56, 189, 248, 0.2), transparent),
                radial-gradient(900px 450px at 100% 10%, rgba(37, 99, 235, 0.2), transparent),
                var(--bg);
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            opacity: var(--glow-opacity);
            background: radial-gradient(
                320px circle at var(--cursor-x) var(--cursor-y),
                rgba(96, 165, 250, 0.14),
                rgba(96, 165, 250, 0.06) 34%,
                transparent 70%
            );
            transition: opacity 0.35s ease;
        }

        .topbar,
        .page,
        .stats-bar,
        .bottombar {
            position: relative;
            z-index: 1;
        }

        .page {
            width: min(1240px, 94vw);
            margin: 24px auto 0;
        }

        .reveal {
            opacity: 0;
            transform: translateY(26px) scale(0.985);
            transition: opacity 0.6s ease, transform 0.6s ease;
            will-change: opacity, transform;
        }

        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(10px);
        }

        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            gap: 16px;
            width: 100%;
            margin: 0;
            padding: 14px 24px;
        }

        .brand {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-left: auto;
            padding-left: 58px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            font-size: 0.95rem;
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 9px 12px;
            transition: all 0.2s ease;
        }

        .nav-links .is-current {
            display: inline-flex;
            align-items: center;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid #0f172a;
            border-radius: 10px;
            padding: 9px 12px;
            background: rgba(2, 6, 23, 0.8);
            cursor: default;
            pointer-events: none;
        }

        .nav-links a:hover {
            background: rgba(37, 99, 235, 0.18);
            border-color: var(--border);
        }

        .nav-links .login-btn {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #f8fafc;
        }

        .nav-links .login-btn:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35);
            animation: action-btn-hover 0.45s ease;
        }

        .navbar-logo {
            position: absolute;
            left: 24px;
            top: 50%;
            transform: translateY(-50%);
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid var(--border);
            display: grid;
            place-items: center;
            font-weight: 700;
            color: #f8fafc;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.3);
            text-decoration: none;
        }

        .hero {
            margin-top: 24px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94));
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: clamp(24px, 4vw, 44px);
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 28px;
            align-items: center;
        }

        .kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--accent);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 14px;
        }

        .hero h1 {
            font-size: clamp(1.9rem, 4vw, 3rem);
            line-height: 1.1;
            max-width: 14ch;
        }

        .hero p {
            color: var(--muted);
            margin-top: 14px;
            max-width: 58ch;
            line-height: 1.6;
        }

        .cta {
            margin-top: 22px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .cta a {
            text-decoration: none;
            border-radius: 12px;
            padding: 11px 16px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .cta .primary {
            background: var(--primary);
            color: #f8fafc;
        }

        .cta .primary:hover {
            background: #1d4ed8;
        }

        .cta .secondary {
            border: 1px solid var(--border);
            color: var(--text);
            background: rgba(15, 23, 42, 0.5);
            animation: learn-more-pulse 2.4s ease-in-out infinite;
        }

        .cta .secondary:hover {
            border-color: var(--accent);
            color: var(--accent);
            animation-play-state: paused;
        }

        .cta .secondary::after {
            content: ' \2193';
            display: inline-block;
            margin-left: 8px;
            animation: learn-more-arrow 1.1s ease-in-out infinite;
        }

        .hero-visual {
            min-height: 300px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background:
                linear-gradient(160deg, rgba(37, 99, 235, 0.24), rgba(56, 189, 248, 0.12)),
                rgba(15, 23, 42, 0.65);
            display: grid;
            place-items: center;
            color: var(--muted);
            font-weight: 600;
            letter-spacing: 0.02em;
            overflow: hidden;
        }

        .hero-visual img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            display: block;
        }

        .feature-section {
            margin-top: 22px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.92));
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
        }

        .feature-header {
            margin-bottom: 14px;
        }

        .feature-header h2 {
            font-size: clamp(1.2rem, 2vw, 1.5rem);
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .feature-header p {
            color: var(--muted);
            font-size: 0.95rem;
        }

        .feature-row {
            overflow: hidden;
            padding-top: 18px;
            padding-bottom: 12px;
        }

        .feature-row:hover .feature-track {
            animation-play-state: paused;
        }

        .feature-track {
            width: max-content;
            display: flex;
            gap: 12px;
            animation: feature-marquee 28s linear infinite;
        }

        .feature-row::-webkit-scrollbar {
            height: 10px;
        }

        .feature-row::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
            border-radius: 999px;
        }

        .feature-row::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 999px;
        }

        .feature-card {
            min-width: 260px;
            max-width: 260px;
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.55);
            border-radius: 14px;
            padding: 14px;
            text-align: center;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            transform-origin: center;
        }

        .feature-card:hover {
            transform: translateY(-8px) scale(1.03);
            border-color: rgba(56, 189, 248, 0.7);
            box-shadow: 0 20px 30px rgba(15, 23, 42, 0.55), 0 0 0 1px rgba(56, 189, 248, 0.22);
        }

        .feature-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            margin: 0 auto 10px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.25), rgba(56, 189, 248, 0.25));
            border: 1px solid rgba(56, 189, 248, 0.45);
            color: #f8fafc;
        }

        .feature-icon svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        .feature-card h3 {
            font-size: 1rem;
            margin-bottom: 6px;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .audience-section {
            margin-top: 22px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.92));
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 22px;
            scroll-margin-top: 96px;
        }

        .audience-section h2 {
            font-size: clamp(1.25rem, 2vw, 1.6rem);
            margin-bottom: 8px;
        }

        .audience-intro {
            color: var(--muted);
            margin-bottom: 14px;
            font-size: 0.95rem;
        }

        .audience-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .audience-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.55);
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .audience-card h3 {
            font-size: 1rem;
            margin-bottom: 6px;
        }

        .audience-card p {
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .audience-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 10px;
            border: 1px solid rgba(56, 189, 248, 0.45);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.24), rgba(56, 189, 248, 0.2));
            display: grid;
            place-items: center;
            color: #f8fafc;
        }

        .audience-icon svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        .how-section {
            margin-top: 22px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.92));
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 22px;
        }

        .how-section h2 {
            font-size: clamp(1.25rem, 2vw, 1.6rem);
            margin-bottom: 8px;
        }

        .how-intro {
            color: var(--muted);
            margin-bottom: 14px;
            font-size: 0.95rem;
        }

        .how-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .how-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.55);
            padding: 14px;
        }

        .how-card h3 {
            font-size: 1rem;
            margin-bottom: 6px;
        }

        .how-card p {
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .school-section {
            margin-top: 22px;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.92));
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 22px;
        }

        .school-section h2 {
            font-size: clamp(1.25rem, 2vw, 1.6rem);
            margin-bottom: 8px;
        }

        .school-intro {
            color: var(--muted);
            margin-bottom: 14px;
            font-size: 0.95rem;
        }

        .school-grid {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 14px;
        }

        .school-logo-card,
        .school-detail-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.55);
            padding: 14px;
        }

        .school-logo-box {
            aspect-ratio: 1 / 1;
            width: 100%;
            min-height: 180px;
            border: 1px dashed rgba(56, 189, 248, 0.45);
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: var(--muted);
            font-weight: 600;
            letter-spacing: 0.02em;
            background: linear-gradient(160deg, rgba(37, 99, 235, 0.16), rgba(56, 189, 248, 0.08));
            overflow: hidden;
            padding: 12px;
        }

        .school-logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            display: block;
        }

        .school-detail-card h3 {
            font-size: 1rem;
            margin-bottom: 6px;
        }

        .school-detail-card p,
        .school-detail-card li {
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .school-links {
            list-style: none;
            margin-top: 8px;
            display: grid;
            gap: 8px;
        }

        .school-links a {
            color: var(--accent);
            text-decoration: none;
            border-bottom: 1px dashed rgba(56, 189, 248, 0.35);
            padding-bottom: 2px;
        }

        .school-links a:hover {
            color: #67e8f9;
            border-bottom-color: rgba(103, 232, 249, 0.6);
        }

        .company-carousel {
            overflow: hidden;
            border-radius: 14px;
            cursor: grab;
            touch-action: pan-y;
            user-select: none;
        }

        .company-carousel.is-dragging {
            cursor: grabbing;
        }

        .company-track {
            display: flex;
            transition: transform 0.55s ease;
            will-change: transform;
        }

        .company-slide {
            flex: 0 0 100%;
            min-width: 100%;
        }

        .company-grid {
            margin: 0;
        }

        .company-logo-mark {
            width: 108px;
            height: 108px;
            border-radius: 24px;
            margin: 0 auto 12px;
            display: grid;
            place-items: center;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #f8fafc;
            border: 1px solid rgba(56, 189, 248, 0.5);
            background: linear-gradient(145deg, #1d4ed8, #0ea5e9);
            box-shadow: 0 14px 24px rgba(14, 165, 233, 0.28);
        }

        .company-logo-name {
            color: #f8fafc;
            font-weight: 600;
            text-align: center;
            font-size: 0.95rem;
        }

        .company-dots {
            margin-top: 12px;
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .company-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            border: 0;
            background: rgba(148, 163, 184, 0.5);
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .company-dot.is-active {
            background: var(--accent);
            transform: scale(1.2);
        }

        .stats-bar {
            margin-top: 22px;
            width: 100vw;
            margin-left: calc(50% - 50vw);
            background: linear-gradient(95deg, #1d4ed8, #2563eb 40%, #0ea5e9);
            color: #f8fafc;
            padding: 20px 0;
        }

        .stats-inner {
            width: min(1240px, 94vw);
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            font-weight: 700;
            line-height: 1.1;
            display: block;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.92rem;
            opacity: 0.9;
        }

        .bottombar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000;
            border-top: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(10px);
        }

        .bottombar-inner {
            width: min(1240px, 94vw);
            margin: 0 auto;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--text);
        }

        .wa-icon {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .wa-icon svg {
            width: 15px;
            height: 15px;
            fill: currentColor;
            transform: translateY(0.5px);
        }

        .wa-line {
            text-decoration: none;
            color: #f8fafc;
            font-weight: 600;
            border: 1px solid rgba(56, 189, 248, 0.35);
            border-radius: 10px;
            padding: 8px 12px;
            background: rgba(30, 41, 59, 0.7);
            transition: all 0.2s ease;
        }

        .wa-line:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        @keyframes feature-marquee {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-50% - 6px));
            }
        }

        @keyframes action-btn-hover {
            0% {
                transform: translateY(0) scale(1);
            }
            60% {
                transform: translateY(-3px) scale(1.04);
            }
            100% {
                transform: translateY(-2px) scale(1.03);
            }
        }

        @keyframes learn-more-pulse {
            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(56, 189, 248, 0);
                transform: translateY(0);
            }
            50% {
                box-shadow: 0 0 0 6px rgba(56, 189, 248, 0.08);
                transform: translateY(-1px);
            }
        }

        @keyframes learn-more-arrow {
            0%,
            100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(2px);
            }
        }

        .section-focus {
            animation: section-focus-glow 0.9s ease;
        }

        @keyframes section-focus-glow {
            0% {
                box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.35);
            }
            100% {
                box-shadow: 0 0 0 12px rgba(56, 189, 248, 0);
            }
        }

        @media (max-width: 760px) {
            .topbar-inner {
                flex-wrap: wrap;
                padding: 14px 16px;
            }

            .topbar-left {
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding-left: 58px;
            }

            .nav-links {
                flex-wrap: wrap;
            }

            .hero-content {
                grid-template-columns: 1fr;
            }

            .hero-visual {
                min-height: 220px;
            }

            .navbar-logo {
                left: 16px;
            }

            .feature-card {
                min-width: 230px;
                max-width: 230px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .bottombar-inner {
                padding: 10px 14px;
                justify-content: flex-start;
            }

            .how-grid {
                grid-template-columns: 1fr;
            }

            .school-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (hover: none), (pointer: coarse) {
            body::before {
                display: none;
            }
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

        body.page-drift-up {
            animation: page-drift-up 0.7s ease-out both;
        }
    </style>
</head>
<body class="page-drift-up">
    <header id="top-anchor" class="topbar">
        <div class="topbar-inner">
            <div class="topbar-left">
                <div class="brand">{{ config('app.name', 'Kips') }}</div>

                <nav class="nav-links" aria-label="Main navigation">
                    <span class="is-current" aria-current="page">Home</span>
                    <a href="#feature-section" data-nav-features>Features</a>
                    <a href="#contact-section" data-nav-contact>Contact</a>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="login-btn">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="login-btn">Log in</a>
                        @endauth
                    @endif
                </nav>
            </div>
            <a href="#" class="navbar-logo" aria-label="Kips logo">K</a>
        </div>
    </header>

    <div class="page">
        @php
            // Set this to an image URL or asset path when you have a real school photo.
            $schoolPhoto = '';
        @endphp

        <main class="hero reveal">
            <div class="hero-content">
                <div>
                    <span class="kicker">Web-Based Internship Monitoring and Attendance Validation Information System</span>
                    <h1>Seamlessly Bridging Education and Industry.</h1>
                    <p>
                        KIPS is a comprehensive Monitoring and Attendance Validation System designed to ensure transparency, accountability, and real-time synchronization between students, schools, and industry partners.
                    </p>

                    <div class="cta">
                        <a href="{{ route('login') }}" class="primary">Get Started</a>
                        <a href="#" id="learn-more-link" class="secondary">Learn More</a>
                    </div>
                </div>

                <div class="hero-visual">Image Area</div>
            </div>
        </main>

        <section class="stats-bar reveal" aria-label="Kips statistics">
            <div class="stats-inner">
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number" data-target="12500" data-suffix="+">0</span>
                        <span class="stat-label">Active Users</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" data-target="98" data-suffix="%">0</span>
                        <span class="stat-label">Satisfaction Rate</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" data-target="420" data-suffix="+">0</span>
                        <span class="stat-label">Projects Completed</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" data-target="24" data-suffix="/7">0</span>
                        <span class="stat-label">Support Coverage</span>
                    </div>
                </div>
            </div>
        </section>

        <section id="feature-section" class="feature-section reveal" aria-labelledby="feature-title">
            <div class="feature-header">
                <h2 id="feature-title">Why Kips</h2>
                <p>Scroll sideways to see quick highlights.</p>
            </div>

            <div class="feature-row">
                <div class="feature-track">
                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m12 2 8 3v6c0 5.25-3.44 9.74-8 11-4.56-1.26-8-5.75-8-11V5Zm0 5.6a3.4 3.4 0 0 0-3.4 3.4 3.4 3.4 0 0 0 6.8 0A3.4 3.4 0 0 0 12 7.6Zm-5 9.06c1.37 1.84 3.15 3.2 5 3.86 1.85-.66 3.63-2.02 5-3.86-.79-1.48-2.9-2.4-5-2.4s-4.21.92-5 2.4Z"/></svg>
                        </div>
                        <h3>Multi-Layered Authentication</h3>
                        <p>Eliminate attendance fraud. The system ensures data integrity by cross-referencing three specific data points: GPS Geofencing (physical location), IP Address Tracking (office network), and Photo Verification (selfie). This ensures students are exactly where they say they are.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M3 3h18v12H3Zm2 2v8h14V5Zm-2 12h18v4H3Zm2 2h6v-0H5Z"/></svg>
                        </div>
                        <h3>Real-Time Activity Transparency</h3>
                        <p>No more lost paper logs or unreadable handwriting. Students record their daily tasks digitally, allowing industry mentors and school supervisors to monitor progress and task quality in real-time from any device.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 3h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8l-6 4V5a2 2 0 0 1 2-2Zm2 4v2h12V7Zm0 4v2h8v-2Z"/></svg>
                        </div>
                        <h3>Constructive Feedback Loop</h3>
                        <p>The system bridge's the communication gap. Through the Weekly Journal feature, mentors can flag incomplete entries or provide specific "Missing Info" notes, ensuring the student's learning stays aligned with industry standards.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M3 3h18v18H3Zm2 2v14h14V5ZM7 15h2v2H7Zm0-4h2v3H7Zm4-6h2v10h-2Zm4 8h2v4h-2Z"/></svg>
                        </div>
                        <h3>Integrated Oversight for Leadership</h3>
                        <p>Department Heads (Kajur) and Principals don't need to visit sites daily to stay informed. The dashboard provides a high-level view, allowing school leaders to see attendance and activity statistics filtered by Major or Class in one clean, automated table.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v15H6Zm2 2v16h10V8h-4V4Zm2 7h6v2h-6Zm0 4h6v2h-6Z"/></svg>
                        </div>
                        <h3>Paperless Administrative Efficiency</h3>
                        <p>Say goodbye to the end-of-semester "logbook mountain." All data is stored securely in a central database, making it effortless to generate PDF or Excel reports for grading, accreditation, or school records with just a few clicks.</p>
                    </article>

                    <article class="feature-card" aria-hidden="true">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m12 2 8 3v6c0 5.25-3.44 9.74-8 11-4.56-1.26-8-5.75-8-11V5Zm0 5.6a3.4 3.4 0 0 0-3.4 3.4 3.4 3.4 0 0 0 6.8 0A3.4 3.4 0 0 0 12 7.6Zm-5 9.06c1.37 1.84 3.15 3.2 5 3.86 1.85-.66 3.63-2.02 5-3.86-.79-1.48-2.9-2.4-5-2.4s-4.21.92-5 2.4Z"/></svg>
                        </div>
                        <h3>Multi-Layered Authentication</h3>
                        <p>Eliminate attendance fraud. The system ensures data integrity by cross-referencing three specific data points: GPS Geofencing (physical location), IP Address Tracking (office network), and Photo Verification (selfie). This ensures students are exactly where they say they are.</p>
                    </article>

                    <article class="feature-card" aria-hidden="true">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M3 3h18v12H3Zm2 2v8h14V5Zm-2 12h18v4H3Zm2 2h6v-0H5Z"/></svg>
                        </div>
                        <h3>Real-Time Activity Transparency</h3>
                        <p>No more lost paper logs or unreadable handwriting. Students record their daily tasks digitally, allowing industry mentors and school supervisors to monitor progress and task quality in real-time from any device.</p>
                    </article>

                    <article class="feature-card" aria-hidden="true">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 3h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8l-6 4V5a2 2 0 0 1 2-2Zm2 4v2h12V7Zm0 4v2h8v-2Z"/></svg>
                        </div>
                        <h3>Constructive Feedback Loop</h3>
                        <p>The system bridge's the communication gap. Through the Weekly Journal feature, mentors can flag incomplete entries or provide specific "Missing Info" notes, ensuring the student's learning stays aligned with industry standards.</p>
                    </article>

                    <article class="feature-card" aria-hidden="true">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M3 3h18v18H3Zm2 2v14h14V5ZM7 15h2v2H7Zm0-4h2v3H7Zm4-6h2v10h-2Zm4 8h2v4h-2Z"/></svg>
                        </div>
                        <h3>Integrated Oversight for Leadership</h3>
                        <p>Department Heads (Kajur) and Principals don't need to visit sites daily to stay informed. The dashboard provides a high-level view, allowing school leaders to see attendance and activity statistics filtered by Major or Class in one clean, automated table.</p>
                    </article>

                    <article class="feature-card" aria-hidden="true">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v15H6Zm2 2v16h10V8h-4V4Zm2 7h6v2h-6Zm0 4h6v2h-6Z"/></svg>
                        </div>
                        <h3>Paperless Administrative Efficiency</h3>
                        <p>Say goodbye to the end-of-semester "logbook mountain." All data is stored securely in a central database, making it effortless to generate PDF or Excel reports for grading, accreditation, or school records with just a few clicks.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="audience-section" class="audience-section reveal" aria-labelledby="audience-title">
            <h2 id="audience-title">Who is this for?</h2>
            <p class="audience-intro">Built to support every role in the school workflow.</p>
            <div class="audience-grid">
                <article class="audience-card">
                    <span class="audience-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/>
                        </svg>
                    </span>
                    <div>
                        <h3>For Students</h3>
                        <p>Easy check-in and clear task logs.</p>
                    </div>
                </article>
                <article class="audience-card">
                    <span class="audience-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2a7 7 0 0 0-4.95 11.95A7 7 0 0 0 12 16a7 7 0 0 0 0-14Zm0 16c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Zm4.3-8.3-4.75 4.75a1 1 0 0 1-1.41 0l-2.44-2.44 1.41-1.41 1.73 1.73 4.04-4.04 1.42 1.41Z"/>
                        </svg>
                    </span>
                    <div>
                        <h3>For Mentors</h3>
                        <p>Simple validation and direct feedback to students.</p>
                    </div>
                </article>
                <article class="audience-card">
                    <span class="audience-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 3 2 8v2h20V8Zm8 9H4v8h16Zm-12 2h2v4H8Zm6 0h2v4h-2ZM2 21h20v-2H2Z"/>
                        </svg>
                    </span>
                    <div>
                        <h3>For School (Kajur/Kepsek)</h3>
                        <p>Real-time data tables and class-wide monitoring.</p>
                    </div>
                </article>
            </div>
        </section>

        <section id="how-section" class="how-section reveal" aria-labelledby="how-title">
            <h2 id="how-title">How it works</h2>
            <p class="how-intro">A simple workflow from check-in to final validation.</p>
            <div class="how-grid">
                <article class="how-card">
                    <h3>Check-in</h3>
                    <p>Ambil lokasi & foto selfie.</p>
                </article>
                <article class="how-card">
                    <h3>Activity</h3>
                    <p>Lakukan pekerjaan dan catat di log harian.</p>
                </article>
                <article class="how-card">
                    <h3>Journaling</h3>
                    <p>Isi catatan mingguan berdasarkan arahan pembimbing.</p>
                </article>
                <article class="how-card">
                    <h3>Validation</h3>
                    <p>Mentor dan Kajur memberikan validasi akhir.</p>
                </article>
            </div>
        </section>

        <section id="school-info-section" class="school-section reveal" aria-labelledby="school-info-title">
            <h2 id="school-info-title">School Logo & Address</h2>
            <p class="school-intro">Official school information and support contacts.</p>
            <div class="school-grid">
                <article class="school-logo-card">
                    <div class="school-logo-box">
                        @if ($schoolPhoto)
                            <img src="https://permataharapanku.sch.id/images/logo_sph.png" alt="School photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                            <span style="display: none;">School Photo</span>
                        @else
                            <span>School Photo</span>
                        @endif
                    </div>
                </article>

                <article class="school-detail-card">
                    <h3>Address</h3>
                    <p>Jl. Contoh Sekolah No. 123, Kecamatan Contoh, Kota Contoh, Indonesia 12345</p>

                    <h3 style="margin-top: 12px;">Contact Person (Tech Support)</h3>
                    <p>IT Admin: +62 812-0000-0000</p>
                    <p>Email: support@sekolah.sch.id</p>

                    <h3 style="margin-top: 12px;">Official Website</h3>
                    <ul class="school-links">
                        <li><a href="https://permataharapanku.sch.id/" target="_blank" rel="noopener noreferrer">Main Website</a></li>
                    </ul>
                </article>
            </div>
        </section>

        <section id="company-info-section" class="school-section reveal" aria-labelledby="company-info-title">
            <h2 id="company-info-title">Company Logo & Address</h2>
            <p class="school-intro">Official company profile and communication channels. Auto-scrolls every 2 seconds.</p>

            <div class="company-carousel" data-company-carousel>
                <div class="company-track" data-company-track>
                    @foreach (($landingCompanies ?? collect()) as $company)
                        @php
                            $companyName = trim((string) ($company->company_name ?? 'Company'));
                            $companyAddress = trim((string) ($company->company_address ?? '-'));
                            $companyInitials = collect(preg_split('/\s+/', $companyName))
                                ->filter()
                                ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                                ->take(2)
                                ->implode('');
                            $companyInitials = $companyInitials !== '' ? $companyInitials : 'CO';
                            $companyWebsite = trim((string) ($company->website_url ?? ''));
                        @endphp
                        <article class="company-slide" data-company-slide>
                            <div class="school-grid company-grid">
                                <div class="school-logo-card">
                                    <div class="school-logo-box">
                                        @if (!empty($company->logo_url))
                                            <img src="{{ $company->logo_url }}" alt="{{ $companyName }} logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                            <div style="display:none;">
                                                <div class="company-logo-mark">{{ $companyInitials }}</div>
                                                <p class="company-logo-name">{{ $companyName }}</p>
                                            </div>
                                        @else
                                            <div>
                                                <div class="company-logo-mark">{{ $companyInitials }}</div>
                                                <p class="company-logo-name">{{ $companyName }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="school-detail-card">
                                    <h3>Address</h3>
                                    <p>{{ $companyAddress !== '' ? $companyAddress : '-' }}</p>

                                    <h3 style="margin-top: 12px;">Contact Person</h3>
                                    <p>PIC: {{ $company->contact_person ?: '-' }}</p>
                                    <p>Phone: {{ $company->contact_phone ?: '-' }}</p>
                                    <p>Email: {{ $company->contact_email ?: '-' }}</p>

                                    <h3 style="margin-top: 12px;">Official Website</h3>
                                    <ul class="school-links">
                                        @if ($companyWebsite !== '')
                                            <li><a href="{{ $companyWebsite }}" target="_blank" rel="noopener noreferrer">{{ $companyWebsite }}</a></li>
                                        @else
                                            <li>-</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="company-dots" data-company-dots aria-hidden="true">
                @foreach (($landingCompanies ?? collect()) as $idx => $company)
                    <span class="company-dot {{ $idx === 0 ? 'is-active' : '' }}"></span>
                @endforeach
            </div>
        </section>
    </div>

    <footer id="contact-section" class="bottombar" aria-label="Customer service">
        <div class="bottombar-inner">
            <span class="wa-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.52 0 .2 5.32.2 11.86c0 2.08.54 4.11 1.57 5.9L0 24l6.39-1.67a11.8 11.8 0 0 0 5.66 1.44h.01c6.54 0 11.86-5.32 11.86-11.86 0-3.17-1.23-6.15-3.4-8.43Zm-8.46 18.27h-.01a9.9 9.9 0 0 1-5.05-1.38l-.36-.21-3.79.99 1.01-3.69-.23-.38a9.84 9.84 0 0 1-1.5-5.22c0-5.45 4.44-9.89 9.9-9.89 2.64 0 5.12 1.03 6.98 2.9a9.81 9.81 0 0 1 2.9 6.99c0 5.45-4.44 9.89-9.85 9.89Zm5.43-7.43c-.29-.14-1.72-.85-1.98-.94-.27-.1-.46-.14-.66.14-.2.29-.76.94-.94 1.13-.17.2-.34.22-.63.07-.29-.14-1.22-.45-2.32-1.43-.86-.77-1.44-1.72-1.61-2.01-.17-.29-.02-.45.13-.59.13-.13.29-.34.43-.51.14-.17.2-.29.29-.49.1-.2.05-.37-.02-.51-.08-.14-.66-1.59-.91-2.18-.24-.56-.48-.48-.66-.49h-.56c-.2 0-.51.07-.78.37-.27.29-1.03 1.01-1.03 2.46 0 1.45 1.05 2.85 1.2 3.05.14.2 2.04 3.12 4.94 4.37.69.3 1.23.48 1.65.61.69.22 1.31.19 1.8.11.55-.08 1.72-.7 1.96-1.38.24-.67.24-1.25.17-1.38-.07-.12-.26-.19-.54-.33Z"/>
                </svg>
            </span>
            <a class="wa-line" href="https://wa.me/6281234567890" target="_blank" rel="noopener noreferrer">
                Customer Service: +62 812-3456-7890
            </a>
        </div>
    </footer>

    <script>
        (() => {
            const counters = document.querySelectorAll('.stat-number');
            if (!counters.length) return;

            const animateCounter = (counter) => {
                const target = Number(counter.dataset.target || 0);
                const suffix = counter.dataset.suffix || '';
                const duration = 1400;
                const startTime = performance.now();

                const step = (now) => {
                    const progress = Math.min((now - startTime) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = Math.floor(target * eased);
                    counter.textContent = current.toLocaleString() + suffix;
                    if (progress < 1) requestAnimationFrame(step);
                };

                requestAnimationFrame(step);
            };

            const observer = new IntersectionObserver(
                (entries, obs) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        counters.forEach((counter) => animateCounter(counter));
                        obs.disconnect();
                    });
                },
                { threshold: 0.4 }
            );

            observer.observe(counters[0]);
        })();

        (() => {
            const reveals = document.querySelectorAll('.reveal');
            if (!reveals.length) return;

            const revealObserver = new IntersectionObserver(
                (entries, obs) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    });
                },
                { threshold: 0.18, rootMargin: '0px 0px -8% 0px' }
            );

            reveals.forEach((el) => revealObserver.observe(el));
        })();

        (() => {
            if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;

            const root = document.documentElement;
            const setGlow = (value) => root.style.setProperty('--glow-opacity', value);
            let lastClientX = null;
            let lastClientY = null;

            const syncGlowPosition = (clientX, clientY) => {
                root.style.setProperty('--cursor-x', `${clientX + window.scrollX}px`);
                root.style.setProperty('--cursor-y', `${clientY + window.scrollY}px`);
            };

            window.addEventListener('mousemove', (event) => {
                lastClientX = event.clientX;
                lastClientY = event.clientY;
                syncGlowPosition(lastClientX, lastClientY);
                setGlow('1');
            }, { passive: true });

            window.addEventListener('scroll', () => {
                if (lastClientX === null || lastClientY === null) return;
                syncGlowPosition(lastClientX, lastClientY);
            }, { passive: true });

            window.addEventListener('mouseleave', () => setGlow('0'));
            window.addEventListener('blur', () => setGlow('0'));
        })();

        (() => {
            const trigger = document.getElementById('learn-more-link');
            const homeNav = document.querySelector('[data-nav-home]');
            const featuresNav = document.querySelector('[data-nav-features]');
            const contactNav = document.querySelector('[data-nav-contact]');
            const topAnchor = document.getElementById('top-anchor');
            const feature = document.getElementById('feature-section');
            const audience = document.getElementById('audience-section');
            const how = document.getElementById('how-section');
            const schoolInfo = document.getElementById('school-info-section');
            const companyInfo = document.getElementById('company-info-section');
            const contact = document.getElementById('contact-section');

            const pulseSection = (el) => {
                if (!el) return;
                el.classList.remove('section-focus');
                void el.offsetWidth;
                el.classList.add('section-focus');
            };

            const sequencePulse = (items, delay = 220, gap = 620) => {
                items.forEach((item, index) => {
                    if (!item) return;
                    window.setTimeout(() => pulseSection(item), delay + (index * gap));
                });
            };

            if (trigger && audience && how) {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();

                    audience.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    setTimeout(() => pulseSection(audience), 180);
                    setTimeout(() => pulseSection(how), 900);
                });
            }

            if (homeNav && topAnchor) {
                homeNav.addEventListener('click', (event) => {
                    event.preventDefault();
                    topAnchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }

            if (featuresNav && feature && audience && how) {
                featuresNav.addEventListener('click', (event) => {
                    event.preventDefault();
                    feature.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    sequencePulse([feature, audience, how]);
                });
            }

            if (contactNav && schoolInfo && companyInfo && contact) {
                contactNav.addEventListener('click', (event) => {
                    event.preventDefault();
                    contact.scrollIntoView({ behavior: 'smooth', block: 'end' });
                    sequencePulse([schoolInfo, companyInfo, contact], 280, 680);
                });
            }
        })();

        (() => {
            const carousel = document.querySelector('[data-company-carousel]');
            const track = document.querySelector('[data-company-track]');
            const slides = document.querySelectorAll('[data-company-slide]');
            const dots = document.querySelectorAll('[data-company-dots] .company-dot');
            if (!carousel || !track || slides.length < 2) return;

            let index = 0;
            let timerId = null;
            let isDragging = false;
            let startX = 0;
            let currentDelta = 0;
            let slideWidth = carousel.clientWidth;

            const applySlide = () => {
                track.style.transition = 'transform 0.55s ease';
                track.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('is-active', dotIndex === index);
                });
            };

            const run = () => {
                index = (index + 1) % slides.length;
                applySlide();
            };

            const start = () => {
                if (timerId) return;
                timerId = window.setInterval(run, 2000);
            };

            const stop = () => {
                if (!timerId) return;
                window.clearInterval(timerId);
                timerId = null;
            };

            carousel.addEventListener('mouseenter', stop);
            carousel.addEventListener('mouseleave', start);
            carousel.addEventListener('focusin', stop);
            carousel.addEventListener('focusout', start);

            const updateDragPosition = () => {
                const baseOffset = -(index * slideWidth);
                track.style.transition = 'none';
                track.style.transform = `translateX(${baseOffset + currentDelta}px)`;
            };

            const onPointerDown = (event) => {
                isDragging = true;
                startX = event.clientX;
                currentDelta = 0;
                slideWidth = carousel.clientWidth;
                carousel.classList.add('is-dragging');
                stop();
                track.style.transition = 'none';
                if (carousel.setPointerCapture) {
                    carousel.setPointerCapture(event.pointerId);
                }
            };

            const onPointerMove = (event) => {
                if (!isDragging) return;
                currentDelta = event.clientX - startX;
                updateDragPosition();
            };

            const onPointerUp = (event) => {
                if (!isDragging) return;
                isDragging = false;
                carousel.classList.remove('is-dragging');

                const threshold = slideWidth * 0.18;
                if (currentDelta <= -threshold) {
                    index = (index + 1) % slides.length;
                } else if (currentDelta >= threshold) {
                    index = (index - 1 + slides.length) % slides.length;
                }

                currentDelta = 0;
                if (carousel.releasePointerCapture && typeof event.pointerId === 'number') {
                    try {
                        carousel.releasePointerCapture(event.pointerId);
                    } catch (error) {
                        // Ignore capture-release mismatches triggered by pointercancel/lostpointercapture.
                    }
                }
                applySlide();
                start();
            };

            carousel.addEventListener('pointerdown', onPointerDown);
            carousel.addEventListener('pointermove', onPointerMove);
            carousel.addEventListener('pointerup', onPointerUp);
            carousel.addEventListener('pointercancel', onPointerUp);
            carousel.addEventListener('lostpointercapture', onPointerUp);

            window.addEventListener('resize', () => {
                slideWidth = carousel.clientWidth;
                applySlide();
            });

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stop();
                    return;
                }
                start();
            });

            applySlide();
            start();
        })();
    </script>
</body>
</html>

