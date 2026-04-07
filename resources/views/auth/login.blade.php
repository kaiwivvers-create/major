<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Log in - {{ config('app.name', 'Kips') }}</title>

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
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
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

        .nav-links a:hover {
            background: rgba(37, 99, 235, 0.18);
            border: 1px solid var(--border);
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

        .login-wrap {
            width: min(460px, 92vw);
            margin: 44px auto 0;
        }

        .login-card {
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.94));
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: clamp(20px, 3.5vw, 30px);
            box-shadow: 0 18px 30px rgba(2, 6, 23, 0.35);
        }

        .login-card h1 {
            font-size: clamp(1.35rem, 2.2vw, 1.75rem);
            margin-bottom: 8px;
        }

        .login-card p {
            color: var(--muted);
            font-size: 0.95rem;
            margin-bottom: 16px;
        }

        .field {
            margin-top: 12px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.93rem;
        }

        .field input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.65);
            color: var(--text);
            padding: 11px 12px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .field input:focus {
            border-color: rgba(56, 189, 248, 0.9);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        .error {
            color: #fca5a5;
            margin-top: 6px;
            font-size: 0.86rem;
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .remember input {
            accent-color: var(--accent);
        }

        .btn {
            margin-top: 16px;
            width: 100%;
            border: 1px solid var(--primary);
            border-radius: 12px;
            padding: 11px 14px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #f8fafc;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35);
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
    <header class="topbar">
        <div class="topbar-inner">
            <div class="topbar-left">
                <div class="brand">{{ config('app.name', 'Kips') }}</div>
                <nav class="nav-links" aria-label="Main navigation">
                    <a href="{{ url('/') }}">Home</a>
                    <a href="{{ url('/') }}#feature-title">Features</a>
                    <a href="#">Pricing</a>
                    <a href="#">Contact</a>
                    <a href="{{ url('/') }}" class="login-btn">Back to Home</a>
                </nav>
            </div>
            <a href="{{ url('/') }}" class="navbar-logo" aria-label="Kips logo">K</a>
        </div>
    </header>

    <main class="login-wrap">
        <section class="login-card">
            <h1>Log in to your account</h1>
            <p>Use your registered account to access the dashboard.</p>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="field">
                    <label for="nis">NIS</label>
                    <input id="nis" type="text" name="nis" value="{{ old('nis') }}" required autofocus autocomplete="username">
                    @error('nis')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                    @error('password')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <label class="remember" for="remember">
                        <input id="remember" type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                </div>

                <button class="btn" type="submit">Log in</button>
            </form>
        </section>
    </main>

    <footer class="bottombar" aria-label="Customer service">
        <div class="bottombar-inner">
            <a class="wa-line" href="https://wa.me/6281234567890" target="_blank" rel="noopener noreferrer">
                Customer Service: +62 812-3456-7890
            </a>
        </div>
    </footer>
</body>
</html>
