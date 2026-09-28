<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111827">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Refugio Agostino Rocca</title>
    <style>
        :root { --body:#111827; --card:#1f2937; --panel:#111827; --panel-alt:#0f172a; --border:#374151; --border-strong:#4b5563; --text:#e5e7eb; --secondary:#cbd5e1; --muted:#9ca3af; --button:#4b5563; --button-hover:#6b7280; --button-secondary:#334155; --danger:#7f1d1d; --danger-hover:#991b1b; --success:#6ee7b7; --shadow:0 16px 45px #00000052; }
        * { box-sizing:border-box; }
        html { min-height:100%; scroll-behavior:smooth; }
        body { min-height:100vh; margin:0; color:var(--text); background:var(--body); font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; -webkit-font-smoothing:antialiased; }
        button,input { font:inherit; }
        button { cursor:pointer; }
        .wrap { width:min(1120px,calc(100% - 32px)); margin:0 auto; padding:40px 0; }
        .card { padding:32px; background:var(--card); border:1px solid var(--border); border-radius:20px; box-shadow:var(--shadow); }
        .top-actions { display:flex; align-items:center; justify-content:space-between; gap:12px; padding-bottom:22px; border-bottom:1px solid var(--border); }
        .top-actions__brand { min-width:0; }
        .top-actions__brand strong { display:block; color:var(--text); font-size:1rem; }
        .top-actions__brand span { display:block; margin-top:3px; color:var(--muted); font-size:.82rem; }
        .eyebrow { margin:0 0 8px; color:var(--muted); font-size:.72rem; font-weight:800; letter-spacing:.16em; text-transform:uppercase; }
        .page-heading { padding:28px 0 4px; }
        .page-title,.login-card h1 { margin:0; color:var(--text); font-size:clamp(1.75rem,4vw,2.35rem); line-height:1.15; }
        .page-intro,.login-intro { max-width:680px; margin:10px 0 0; color:var(--secondary); line-height:1.6; }
        .section { margin-top:24px; }
        .section-heading { margin:0 0 12px; display:flex; align-items:end; justify-content:space-between; gap:12px; }
        .section-heading h2,.panel__header h2 { margin:0; color:var(--text); font-size:1.1rem; }
        .section-heading span { color:var(--muted); font-size:.82rem; }
        .panel { background:var(--panel); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
        .panel__header { padding:20px 22px 16px; border-bottom:1px solid var(--border); }
        .panel__header p { margin:6px 0 0; color:var(--muted); font-size:.88rem; line-height:1.5; }
        .panel__body { padding:22px; }
        .form-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; align-items:end; }
        .field { display:block; min-width:0; }
        .field span { display:block; margin-bottom:7px; color:var(--secondary); font-size:.76rem; font-weight:800; }
        .field input { width:100%; min-height:44px; padding:10px 12px; color:var(--text); background:var(--panel-alt); border:1px solid var(--border-strong); border-radius:10px; transition:border-color .2s,box-shadow .2s; }
        .field input[type="file"] { padding:8px; font-size:.8rem; }
        .field input::file-selector-button { margin:-8px 10px -8px -8px; padding:10px 12px; color:var(--text); background:var(--button-secondary); border:0; border-right:1px solid var(--border-strong); font-weight:700; cursor:pointer; }
        .field input:focus { outline:0; border-color:#94a3b8; box-shadow:0 0 0 3px #94a3b826; }
        .button-primary,.button-link,.secondary,.button-danger,.header-action { min-height:42px; padding:10px 18px; display:inline-flex; align-items:center; justify-content:center; color:#fff; background:var(--button); border:0; border-radius:999px; font-weight:800; text-decoration:none; transition:background .2s,transform .2s; }
        .button-primary:hover,.button-link:hover,.header-action:hover { background:var(--button-hover); }
        .secondary { background:var(--button-secondary); }
        .secondary:hover { background:var(--button); }
        .button-danger { background:var(--danger); }
        .button-danger:hover { background:var(--danger-hover); }
        .button-primary:active,.button-link:active,.secondary:active,.button-danger:active,.header-action:active { transform:translateY(1px); }
        .alert { margin:20px 0 0; padding:14px 16px; color:var(--secondary); background:var(--panel-alt); border:1px solid var(--border); border-radius:14px; line-height:1.5; }
        .alert--success { color:var(--success); border-color:#065f46; }
        .alert--danger { color:#fecaca; border-color:var(--danger); }
        .alert ul { margin:6px 0 0; padding-left:20px; }
        .profile-cards { display:grid; gap:16px; }
        .profile-card { padding:22px; }
        .profile-layout { display:grid; grid-template-columns:minmax(220px,.7fr) minmax(0,1.6fr); gap:26px; }
        .profile-summary h3 { margin:4px 0 5px; color:var(--text); font-size:1.3rem; }
        .meta { margin:0; color:var(--muted); font-size:.8rem; line-height:1.5; }
        .renewal { margin:20px 0 0; padding-top:18px; border-top:1px solid var(--border); }
        .renewal__label { color:var(--muted); font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .countdown { display:block; margin:6px 0; color:var(--success); font-size:1.8rem; font-weight:800; font-variant-numeric:tabular-nums; }
        .renewal small { color:var(--muted); line-height:1.45; }
        .profile-form { padding-left:26px; border-left:1px solid var(--border); }
        .profile-form .button-primary { justify-self:start; }
        .empty-state { padding:42px 20px; color:var(--muted); text-align:center; background:var(--panel); border:1px dashed var(--border-strong); border-radius:16px; }
        .empty-state strong { display:block; margin-bottom:6px; color:var(--text); font-size:1.1rem; }
        .info-panel .panel__body { color:var(--secondary); line-height:1.7; }
        .info-panel p { margin:0; }
        code { padding:2px 6px; color:var(--secondary); background:var(--panel-alt); border:1px solid var(--border); border-radius:5px; font-size:.88em; }
        .terminal { max-height:360px; overflow:auto; padding:18px 20px; color:var(--secondary); background:var(--panel-alt); border:1px solid var(--border); border-radius:14px; font:13px/1.65 ui-monospace,SFMono-Regular,Consolas,monospace; }
        .terminal-line { padding:5px 0; border-bottom:1px solid #37415180; overflow-wrap:anywhere; }
        .terminal-line:last-child { border:0; }
        .terminal time { color:var(--muted); }
        .terminal .warn { color:#fde68a; }
        .table-wrap { overflow-x:auto; background:var(--panel-alt); }
        .trace { width:100%; border-collapse:collapse; color:var(--text); font-size:.8rem; }
        .trace th,.trace td { padding:12px 13px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; }
        .trace th { color:var(--muted); background:var(--panel); font-size:.68rem; letter-spacing:.06em; text-transform:uppercase; }
        .trace tbody tr:hover { background:#1e293b; }
        .trace tbody tr:last-child td { border-bottom:0; }
        .login-wrap { min-height:100vh; display:grid; place-items:center; padding:24px; }
        .login-card { width:min(100%,420px); padding:32px; background:var(--card); border:1px solid var(--border); border-radius:20px; box-shadow:var(--shadow); }
        .login-card .field + .field { margin-top:16px; }
        .login-card form { margin-top:24px; }
        .login-card .button-primary { width:100%; min-height:48px; margin-top:22px; }
        .login-help { margin:16px 0 0; display:flex; align-items:center; gap:8px; color:var(--muted); font-size:.78rem; }
        .login-help::before { content:""; width:7px; height:7px; flex:0 0 auto; border-radius:50%; background:var(--success); }
        @media (max-width:900px) { .form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.profile-layout{grid-template-columns:1fr}.profile-form{padding:22px 0 0;border-left:0;border-top:1px solid var(--border)} }
        @media (max-width:600px) { .wrap{width:min(100% - 20px,1120px);padding:10px 0}.card,.login-card{padding:22px}.top-actions{align-items:stretch;flex-direction:column}.top-actions form,.top-actions .header-action{width:100%}.page-heading{padding-top:22px}.section-heading{align-items:start;flex-direction:column}.form-grid{grid-template-columns:1fr}.profile-card,.panel__body{padding:16px}.panel__header{padding:17px 16px}.profile-form .button-primary{width:100%}.trace th,.trace td{padding:10px} }
        @media (prefers-reduced-motion:reduce) { * { scroll-behavior:auto!important; transition:none!important; } }
    </style>
</head>
<body>
@yield('content')
@yield('scripts')
</body>
</html>
