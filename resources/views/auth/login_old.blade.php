<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Login — Aman Traders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{background:#f4f6fb;font-family:'Segoe UI',sans-serif;}
        .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .card{border-radius:16px;border:1px solid #e8ecf0;padding:40px;width:100%;max-width:400px;}
        .brand-icon{width:52px;height:52px;border-radius:12px;background:#3b5bdb;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin-bottom:20px;}
        .form-control{border:1px solid #e8ecf0;border-radius:8px;padding:10px 14px;font-size:14px;}
        .form-control:focus{border-color:#3b5bdb;box-shadow:0 0 0 3px rgba(59,91,219,.12);}
        label{font-size:13px;font-weight:500;color:#374151;margin-bottom:5px;}
        .btn-login{background:#3b5bdb;color:#fff;border:none;border-radius:8px;padding:11px;font-size:14px;font-weight:500;width:100%;}
        .btn-login:hover{background:#2f4bbd;color:#fff;}
    </style>
</head>
<body>
<div class="wrap">
<div class="card shadow-sm">
    <div class="brand-icon"><i class="bi bi-shop"></i></div>
    <h5 class="mb-1" style="font-weight:700;">Aman Traders</h5>
    <p class="text-muted mb-4" style="font-size:13px;">Sign in to Ledger Management System</p>

    @if($errors->any())
    <div class="alert alert-danger d-flex gap-2" style="font-size:13px;">
        <i class="bi bi-exclamation-circle-fill mt-1 flex-shrink-0"></i>{{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <div class="mb-3">
            <label>Email address</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="you@example.com" autofocus required>
        </div>
        <div class="mb-4">
            <label>Password</label>
            <div class="input-group">
                <input type="password" id="pwd" name="password" class="form-control" placeholder="••••••••" required style="border-radius:8px 0 0 8px;">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePwd()" style="border:1px solid #e8ecf0;border-left:none;border-radius:0 8px 8px 0;">
                    <i class="bi bi-eye" id="pwd-icon"></i>
                </button>
            </div>
        </div>
        <div class="d-flex align-items-center mb-4">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="remember" id="rem">
                <label class="form-check-label" for="rem" style="font-size:13px;">Remember me</label>
            </div>
        </div>
        <button class="btn-login">Sign In</button>
    </form>
    <p class="text-center text-muted mt-4 mb-0" style="font-size:12px;">Aman Traders &copy; {{ date('Y') }}</p>
</div>
</div>
<script>
function togglePwd(){const p=document.getElementById('pwd'),i=document.getElementById('pwd-icon');p.type=p.type==='password'?'text':'password';i.className=p.type==='text'?'bi bi-eye-slash':'bi bi-eye';}
</script>
</body>
</html>
