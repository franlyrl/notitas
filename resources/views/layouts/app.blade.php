<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mi Journal')</title>
    <style>
        :root {
            --bg: #f6f1ea;
            --panel: #fffdf9;
            --panel-strong: #f1e4d3;
            --primary: #8c5a3c;
            --primary-soft: #d7b28d;
            --text: #2f241e;
            --muted: #7a655d;
            --border: rgba(140, 90, 60, 0.12);
            --shadow: 0 18px 45px rgba(87, 65, 52, 0.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f3ece2 0%, #efe4d7 48%, #e7d8c3 100%);
            color: var(--text);
            min-height: 100vh;
        }

        a { text-decoration: none; }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px 20px 40px;
        }

        .site-header {
            background: rgba(255, 253, 249, 0.9);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border-radius: 22px;
            padding: 18px 24px;
            margin-bottom: 24px;
        }

        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--primary-soft), #f2d6a5);
            color: var(--primary);
            font-size: 1.3rem;
            box-shadow: inset 0 1px 1px rgba(255,255,255,0.5);
        }

        .brand-copy h1 {
            font-size: clamp(1.5rem, 2vw, 2.1rem);
            color: var(--primary);
            font-weight: 700;
            letter-spacing: -0.03em;
            margin: 0;
        }

        .brand-copy p {
            color: var(--muted);
            font-size: 0.9rem;
            margin-top: 2px;
        }

        .site-nav {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .site-nav a,
        .site-nav button {
            padding: 11px 18px;
            border-radius: 12px;
            border: 1px solid transparent;
            background: #f5efe7;
            color: var(--primary);
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .site-nav form { margin: 0; }

        .site-nav a:hover,
        .site-nav button:hover {
            background: var(--primary);
            color: #fff;
            transform: translateY(-1px);
        }

        .site-nav a.primary {
            background: linear-gradient(135deg, var(--primary), #a86c4a);
            color: #fff;
            box-shadow: 0 10px 20px rgba(140, 90, 60, 0.18);
        }

        main {
            background: rgba(255, 253, 249, 0.94);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 28px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .page-header h2 {
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            color: var(--text);
            margin: 0;
        }

        .page-header .meta {
            color: var(--muted);
            font-size: 0.95rem;
            background: #f5eee7;
            border: 1px solid var(--border);
            padding: 8px 12px;
            border-radius: 999px;
        }

        .note-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 22px;
            margin-top: 22px;
        }

        .note-card {
            background: linear-gradient(135deg, #fffefd 0%, #f9f4ee 100%);
            border: 1px solid var(--border);
            border-left: 5px solid var(--primary-soft);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 12px 24px rgba(94, 67, 53, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .note-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 30px rgba(94, 67, 53, 0.12);
        }

        .note-card h3 {
            color: var(--primary);
            font-size: 1.2rem;
            margin-bottom: 12px;
        }

        .note-card p {
            color: var(--muted);
            line-height: 1.6;
        }

        .note-card .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
        }

        .mood, .mood-chip {
            display: inline-block;
            padding: 6px 10px;
            background: #f3e7db;
            border-radius: 999px;
            font-size: 0.8rem;
            color: var(--primary);
            font-weight: 600;
        }

        .note-card .date {
            font-size: 0.8rem;
            color: #9a8170;
            margin-top: 14px;
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: var(--primary); font-weight: 700; }
        input[type="text"], input[type="email"], input[type="password"], input[type="file"], select, textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(140, 90, 60, 0.18);
            border-radius: 12px;
            font-size: 1rem;
            background: #fff;
            color: var(--text);
        }
        textarea { resize: vertical; min-height: 200px; }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, select:focus, textarea:focus {
            outline: none;
            border-color: rgba(140, 90, 60, 0.5);
            box-shadow: 0 0 0 4px rgba(140, 90, 60, 0.08);
        }

        .button-group { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        button, .btn {
            padding: 12px 22px;
            border: none;
            border-radius: 12px;
            font-size: 0.98rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .btn-primary { background: linear-gradient(135deg, var(--primary), #af7857); color: white; }
        .btn-primary:hover { transform: translateY(-1px); }
        .btn-danger { background: #d87a6d; color: white; }
        .btn-danger:hover { background: #c8665d; }
        .btn-secondary { background: #eee4d9; color: var(--text); }
        .btn-secondary:hover { background: #e2d7ca; }
        .login-panel { max-width: 520px; margin: 0 auto; }
        .google-login { width: 100%; background: #fff; border: 1px solid var(--border); color: var(--text); }
        .google-login:hover { background: #f8f5f1; }
        .google-mark { font: 700 1.1rem Arial, sans-serif; color: #4285f4; }
        .login-divider { display: flex; align-items: center; gap: 12px; margin: 22px 0; color: var(--muted); font-size: 0.85rem; }
        .login-divider::before, .login-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        .auth-description { color: var(--muted); line-height: 1.6; margin-bottom: 20px; }
        .auth-switch { color: var(--muted); margin-top: 22px; text-align: center; }
        .auth-switch a { color: var(--primary); font-weight: 700; }
        .verification-logout { margin-top: 12px; }
        .remember-option { display: flex; align-items: center; gap: 10px; color: var(--muted); font-weight: 500; }
        .remember-option input { width: 18px; height: 18px; accent-color: var(--primary); }

        .message {
            padding: 14px 16px;
            margin-bottom: 18px;
            border-radius: 12px;
            background: #edf9f0;
            color: #1e6b3a;
            border: 1px solid #cfead8;
        }
        .error {
            background: #fff1f0;
            color: #9a3a2a;
            border: 1px solid #f0c8c2;
        }

        footer {
            text-align: center;
            padding: 20px 10px 0;
            color: var(--muted);
            font-size: 0.92rem;
        }

        @media (max-width: 640px) {
            .container { padding: 16px 14px 30px; }
            .site-header { padding: 14px 16px; }
            .header-row { align-items: flex-start; }
            .site-nav { width: 100%; }
            .site-nav a { flex: 1; }
            main { padding: 20px 16px; }
            .page-header { margin-bottom: 18px; }
        }
    </style>
    @stack('extra_css')
</head>
<body>
    <div class="container">
        <header class="site-header">
            <div class="header-row">
                <div class="brand">
                    <div class="brand-mark">J</div>
                    <div class="brand-copy">
                        <h1>Mi Journal</h1>
                        <p>Espacio personal para escribir y crear</p>
                    </div>
                </div>

                <nav class="site-nav">
                    @auth
                        <a href="{{ route('notes.index') }}">Inicio</a>
                        <a href="{{ route('notes.create') }}" class="primary">Nueva nota</a>
                        <a href="{{ route('settings.google-drive') }}">Drive</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Cerrar sesión</button>
                        </form>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            @if ($errors->any())
                <div class="message error">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="message">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>

        <footer>
            <p>&copy; 2026 Mi Journal — Un espacio personal para crear</p>
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
