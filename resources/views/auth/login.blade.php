<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trivo — Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            display: flex;
            background: #0f1117;
        }

        /* ── Left panel ────────────────────────────────────── */
        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 56px;
            background: linear-gradient(145deg, #0f1117 0%, #141824 60%, #1a2035 100%);
            position: relative;
            overflow: hidden;
            min-height: 100vh;
        }

        /* Subtle grid pattern */
        .left-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(59,91,219,.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,91,219,.06) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        /* Glow accent */
        .left-panel::after {
            content: '';
            position: absolute;
            top: -120px;
            left: -80px;
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, rgba(59,91,219,.18) 0%, transparent 70%);
            pointer-events: none;
        }

        .left-top { position: relative; z-index: 1; }
        .left-mid  { position: relative; z-index: 1; flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .left-bot  { position: relative; z-index: 1; }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        .brand-logo .logo-icon {
            width: 42px; height: 42px;
            background: #3b5bdb;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #fff;
        }
        .brand-logo .logo-name {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.3px;
        }

        .hero-heading {
            font-size: 42px;
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            letter-spacing: -.8px;
            margin-bottom: 20px;
        }
        .hero-heading span {
            background: linear-gradient(90deg, #6b8aff, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: 16px;
            color: rgba(255,255,255,.5);
            line-height: 1.7;
            max-width: 380px;
            margin-bottom: 44px;
        }

        /* Stats strip */
        .stats-strip {
            display: flex;
            gap: 32px;
        }
        .stat-item .stat-val {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
        }
        .stat-item .stat-lbl {
            font-size: 12px;
            color: rgba(255,255,255,.4);
            margin-top: 2px;
        }

        /* Feature pills */
        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 48px;
        }
        .feat-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 20px;
            padding: 7px 14px;
            font-size: 12px;
            color: rgba(255,255,255,.7);
        }
        .feat-pill i { color: #6b8aff; font-size: 13px; }

        /* Testimonial */
        .testimonial {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
            padding: 20px 24px;
        }
        .testimonial .quote {
            font-size: 14px;
            color: rgba(255,255,255,.65);
            line-height: 1.7;
            margin-bottom: 14px;
        }
        .testimonial .author {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .author-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b5bdb, #7c3aed);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; color: #fff;
        }
        .author-name  { font-size: 13px; font-weight: 600; color: #fff; }
        .author-title { font-size: 11px; color: rgba(255,255,255,.4); }

        .copyright {
            font-size: 11px;
            color: rgba(255,255,255,.25);
            margin-top: 24px;
        }

        /* ── Right panel ───────────────────────────────────── */
        .right-panel {
            width: 480px;
            flex-shrink: 0;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 56px 52px;
            position: relative;
        }

        .signin-label {
            font-size: 11px;
            font-weight: 600;
            color: #3b5bdb;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .signin-title {
            font-size: 28px;
            font-weight: 800;
            color: #0f1117;
            letter-spacing: -.5px;
            margin-bottom: 6px;
        }
        .signin-sub {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 36px;
            line-height: 1.6;
        }

        /* Form fields */
        .field-group { margin-bottom: 20px; }
        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 7px;
        }
        .field-wrap { position: relative; }
        .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 15px;
            color: #9ca3af;
            pointer-events: none;
        }
        .field-input {
            width: 100%;
            padding: 12px 44px 12px 42px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            color: #111827;
            background: #fafafa;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .field-input:focus {
            border-color: #3b5bdb;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(59,91,219,.1);
        }
        .field-input.is-invalid {
            border-color: #ef4444;
        }
        .field-input::placeholder { color: #9ca3af; }
        .field-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            padding: 4px;
            font-size: 15px;
            line-height: 1;
        }
        .field-toggle:hover { color: #374151; }

        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            font-size: 13px;
            color: #991b1b;
            margin-bottom: 20px;
        }
        .error-box i { font-size: 15px; flex-shrink: 0; margin-top: 1px; }

        /* Remember + help row */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }
        .remember-check {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .remember-check input { cursor: pointer; accent-color: #3b5bdb; }
        .remember-check span { font-size: 13px; color: #6b7280; }

        /* Submit button */
        .btn-signin {
            width: 100%;
            padding: 13px;
            background: #3b5bdb;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: -.1px;
            transition: background .15s, transform .1s;
        }
        .btn-signin:hover  { background: #2f4bbd; }
        .btn-signin:active { transform: scale(.99); }

        /* Trust badges */
        .trust-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #f3f4f6;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #9ca3af;
        }
        .trust-item i { font-size: 13px; color: #d1d5db; }

        /* Responsive */
        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; padding: 40px 28px; }
        }
    </style>
</head>
<body>

<!-- ── Left Panel ──────────────────────────────────────────────────────── -->
<div class="left-panel">

    <div class="left-top">
        <div class="brand-logo">
            <div class="logo-icon"><i class="bi bi-shop-window"></i></div>
            <span class="logo-name">Trivo</span>
        </div>
    </div>

    <div class="left-mid">
        <div class="hero-heading">
            Manage your<br>ledger <span>smarter.</span>
        </div>
        <p class="hero-sub">
            Complete wholesale ledger management — track customers,
            transactions, balances, and reports all in one place.
        </p>

        <div class="features">
            <div class="feat-pill"><i class="bi bi-people"></i> Customer Ledgers</div>
            <div class="feat-pill"><i class="bi bi-arrow-left-right"></i> Dr / Cr Entries</div>
            <div class="feat-pill"><i class="bi bi-bar-chart-line"></i> Live Reports</div>
            <div class="feat-pill"><i class="bi bi-geo-alt"></i> City-wise Analysis</div>
            <div class="feat-pill"><i class="bi bi-file-earmark-excel"></i> Excel Export</div>
            <div class="feat-pill"><i class="bi bi-clock-history"></i> Activity Logs</div>
        </div>

        <div class="stats-strip">
            <div class="stat-item">
                <div class="stat-val">100%</div>
                <div class="stat-lbl">Data accuracy</div>
            </div>
            <div class="stat-item">
                <div class="stat-val">Real-time</div>
                <div class="stat-lbl">Balance updates</div>
            </div>
            <div class="stat-item">
                <div class="stat-val">Secure</div>
                <div class="stat-lbl">Role-based access</div>
            </div>
        </div>
    </div>

    <div class="left-bot">
        <div class="testimonial">
            <div class="quote">
                "Finally a ledger system that shows the right outstanding balance every time.
                No more manual khata errors."
            </div>
            <div class="author">
                <div class="author-avatar">AT</div>
                <div>
                    <div class="author-name">Aman Traders</div>
                    <div class="author-title">Wholesale Trading, Nagpur</div>
                </div>
            </div>
        </div>
        <div class="copyright">© {{ date('Y') }} Trivo. All rights reserved.</div>
    </div>

</div>

<!-- ── Right Panel ─────────────────────────────────────────────────────── -->
<div class="right-panel">

    <div class="signin-label">Welcome back</div>
    <h1 class="signin-title">Sign in to your account</h1>
    <p class="signin-sub">Enter your credentials to access the ledger system.</p>

    @if($errors->any())
    <div class="error-box">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>{{ $errors->first() }}</div>
    </div>
    @endif

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;font-size:13px;color:#166534;margin-bottom:20px;display:flex;gap:10px;">
        <i class="bi bi-check-circle-fill" style="flex-shrink:0;"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" novalidate>
        @csrf

        <!-- Email -->
        <div class="field-group">
            <label class="field-label" for="email">Email address</label>
            <div class="field-wrap">
                <i class="bi bi-envelope field-icon"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="field-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                    value="{{ old('email') }}"
                    placeholder="you@example.com"
                    autocomplete="email"
                    autofocus
                    required>
            </div>
        </div>

        <!-- Password -->
        <div class="field-group">
            <label class="field-label" for="password">Password</label>
            <div class="field-wrap">
                <i class="bi bi-lock field-icon"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="field-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required>
                <button type="button" class="field-toggle" onclick="togglePwd()" aria-label="Toggle password">
                    <i class="bi bi-eye" id="pwd-icon"></i>
                </button>
            </div>
        </div>

        <!-- Remember me -->
        <div class="remember-row">
            <label class="remember-check">
                <input type="checkbox" name="remember" id="remember">
                <span>Keep me signed in</span>
            </label>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-signin">
            <i class="bi bi-arrow-right-circle-fill"></i>
            Sign In
        </button>
    </form>

    <!-- Trust badges -->
    <div class="trust-row">
        <div class="trust-item">
            <i class="bi bi-shield-lock-fill"></i>
            Secure login
        </div>
        <div class="trust-item">
            <i class="bi bi-eye-slash-fill"></i>
            Private data
        </div>
        <div class="trust-item">
            <i class="bi bi-clock-fill"></i>
            Session protected
        </div>
    </div>

</div>

<script>
function togglePwd() {
    const p = document.getElementById('password');
    const i = document.getElementById('pwd-icon');
    if (p.type === 'password') {
        p.type = 'text';
        i.className = 'bi bi-eye-slash';
    } else {
        p.type = 'password';
        i.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
