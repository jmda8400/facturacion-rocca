<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17362e">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Refugio Agostino Rocca</title>
    <style>
        :root { --ink:#18332d; --pine:#244b40; --pine-dark:#17362e; --cream:#f5f1e7; --paper:#fffdf8; --gold:#c89c55; --gold-dark:#9a7133; --muted:#738079; --line:#dedbd0; --success:#5b9572; --danger:#9b433b; --shadow:0 12px 30px rgba(32,49,42,.06); }
        * { box-sizing:border-box; }
        html { min-height:100%; scroll-behavior:smooth; }
        body { min-height:100vh; margin:0; display:flex; flex-direction:column; color:var(--ink); background:var(--cream); font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; -webkit-font-smoothing:antialiased; }
        button,input { font:inherit; }
        button { cursor:pointer; }
        .site-header { min-height:76px; padding:0 max(1.25rem,calc((100vw - 1180px)/2)); display:flex; align-items:center; justify-content:space-between; gap:1rem; color:#fff; background:var(--pine-dark); border-bottom:3px solid var(--gold); }
        .site-brand { display:flex; align-items:center; gap:.85rem; color:#fff; text-decoration:none; letter-spacing:.04em; text-transform:uppercase; }
        .site-brand__mark { width:42px; height:42px; display:grid; place-items:center; flex:0 0 auto; color:var(--gold); border:1px solid #ffffff55; border-radius:50%; font-size:1.15rem; }
        .site-brand__name { display:block; font-family:Georgia,"Times New Roman",serif; font-size:1rem; font-weight:700; letter-spacing:.08em; }
        .site-brand__area { display:block; margin-top:.12rem; color:#dbe5df; font-size:.62rem; letter-spacing:.18em; }
        .header-action { padding:.6rem 1rem; color:#fff; background:transparent; border:1px solid #ffffff66; border-radius:999px; transition:color .2s,background .2s; }
        .header-action:hover { color:var(--pine-dark); background:#fff; }
        .page { width:min(1180px,calc(100% - 2rem)); margin:0 auto; padding:clamp(2rem,5vw,4.5rem) 0; flex:1; }
        .eyebrow { margin:0 0 .65rem; color:var(--gold-dark); font-size:.73rem; font-weight:800; letter-spacing:.18em; text-transform:uppercase; }
        .page-title { margin:0; font-family:Georgia,"Times New Roman",serif; font-size:clamp(2.25rem,5vw,4.2rem); font-weight:400; line-height:1; }
        .page-intro { max-width:680px; margin:1rem 0 0; color:var(--muted); font-size:1.02rem; line-height:1.65; }
        .section { margin-top:3.2rem; }
        .section-heading { margin:0 0 1.15rem; display:flex; align-items:end; justify-content:space-between; gap:1rem; }
        .section-heading h2,.panel__header h2 { margin:0; font-family:Georgia,"Times New Roman",serif; font-size:1.55rem; font-weight:400; }
        .section-heading span { color:var(--muted); font-size:.85rem; }
        .panel { background:var(--paper); border:1px solid var(--line); border-radius:4px; box-shadow:var(--shadow); }
        .panel__header { padding:1.4rem 1.5rem 1.1rem; border-bottom:1px solid var(--line); }
        .panel__header p { margin:.4rem 0 0; color:var(--muted); font-size:.9rem; line-height:1.5; }
        .panel__body { padding:1.5rem; }
        .form-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; align-items:end; }
        .field { display:block; min-width:0; }
        .field span { display:block; margin-bottom:.45rem; color:var(--pine); font-size:.76rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
        .field input { width:100%; min-height:44px; padding:.65rem .75rem; color:var(--ink); background:#fff; border:1px solid #bfc6bf; border-radius:3px; transition:border-color .2s,box-shadow .2s; }
        .field input[type="file"] { padding:.52rem; font-size:.82rem; }
        .field input::file-selector-button { margin:-.52rem .7rem -.52rem -.52rem; padding:.62rem .72rem; color:var(--pine); background:#edf0eb; border:0; border-right:1px solid var(--line); font-weight:700; cursor:pointer; }
        .field input:focus { outline:0; border-color:var(--gold); box-shadow:0 0 0 3px #c89c5529; }
        .button-primary { min-height:44px; padding:.65rem 1.15rem; color:#fff; background:var(--pine); border:0; border-radius:3px; font-weight:750; transition:background .2s,transform .2s; }
        .button-primary:hover { background:var(--pine-dark); }
        .button-primary:active { transform:translateY(1px); }
        .alert { margin:1.5rem 0 0; padding:1rem 1.15rem; border-left:3px solid; border-radius:4px; line-height:1.5; }
        .alert--success { color:#1f563e; background:#e1efe5; border-color:var(--success); }
        .alert--danger { color:#7d312b; background:#f7e5e2; border-color:var(--danger); }
        .alert ul { margin:.35rem 0 0; padding-left:1.2rem; }
        .profile-cards { display:grid; gap:1rem; }
        .profile-card { position:relative; padding:1.5rem; overflow:hidden; }
        .profile-card::before { content:""; position:absolute; inset:0 auto 0 0; width:4px; background:var(--gold); }
        .profile-layout { display:grid; grid-template-columns:minmax(240px,.7fr) minmax(0,1.6fr); gap:2rem; }
        .profile-summary h3 { margin:.3rem 0 .25rem; font-family:Georgia,"Times New Roman",serif; font-size:1.55rem; font-weight:400; }
        .meta { margin:0; color:var(--muted); font-size:.8rem; line-height:1.5; }
        .renewal { margin:1.7rem 0 0; padding-top:1.2rem; border-top:1px solid var(--line); }
        .renewal__label { color:var(--muted); font-size:.72rem; font-weight:750; letter-spacing:.1em; text-transform:uppercase; }
        .countdown { display:block; margin:.38rem 0; color:var(--pine); font-family:Georgia,"Times New Roman",serif; font-size:2rem; font-variant-numeric:tabular-nums; }
        .renewal small { color:var(--muted); line-height:1.45; }
        .profile-form { padding-left:2rem; border-left:1px solid var(--line); }
        .profile-form .button-primary { justify-self:start; }
        .empty-state { padding:3.5rem 1.5rem; color:var(--muted); text-align:center; background:#fffaf1; border:1px dashed #cfc7b6; border-radius:4px; }
        .empty-state strong { display:block; margin-bottom:.4rem; color:var(--ink); font-family:Georgia,serif; font-size:1.35rem; font-weight:400; }
        .info-panel .panel__body { color:#41554f; line-height:1.75; }
        .info-panel p { margin:0; }
        code { padding:.15rem .35rem; color:var(--pine-dark); background:#eee9dc; border-radius:3px; font-size:.88em; }
        .terminal { max-height:360px; overflow:auto; padding:1.2rem 1.4rem; color:#d9e3de; background:var(--pine-dark); border:1px solid #294a42; border-radius:4px; box-shadow:var(--shadow); font:13px/1.65 ui-monospace,SFMono-Regular,Consolas,monospace; }
        .terminal-line { padding:.32rem 0; border-bottom:1px solid #ffffff12; overflow-wrap:anywhere; }
        .terminal-line:last-child { border:0; }
        .terminal time { color:#9eb4aa; }
        .terminal .warn { color:#e8bd78; }
        .table-wrap { overflow:auto; border-radius:0 0 4px 4px; }
        .trace { width:100%; border-collapse:collapse; font-size:.8rem; }
        .trace th,.trace td { padding:.8rem .85rem; border-bottom:1px solid var(--line); text-align:left; white-space:nowrap; }
        .trace th { color:var(--pine); background:#f0ede4; font-size:.68rem; letter-spacing:.07em; text-transform:uppercase; }
        .trace tbody tr:hover { background:#faf7ee; }
        .trace tbody tr:last-child td { border-bottom:0; }
        .site-footer { min-height:90px; padding:1.5rem max(1.25rem,calc((100vw - 1180px)/2)); display:flex; align-items:center; justify-content:space-between; gap:1rem; color:#b9c9c1; background:var(--pine); font-size:.8rem; }
        .site-footer strong { display:block; color:#fff; font-family:Georgia,serif; font-size:1rem; letter-spacing:.04em; }
        .site-footer__label { margin-top:.2rem; font-size:.68rem; letter-spacing:.14em; text-transform:uppercase; }
        .login-page { width:100%; padding:0; display:grid; grid-template-columns:minmax(300px,.92fr) minmax(440px,1.08fr); }
        .login-welcome { position:relative; min-height:calc(100vh - 169px); padding:clamp(2rem,5vw,5rem); display:flex; flex-direction:column; justify-content:center; overflow:hidden; color:#fff; background:var(--pine-dark); }
        .login-welcome::before { content:""; position:absolute; width:min(38vw,520px); aspect-ratio:1; right:-18%; bottom:-10%; border:1px solid #ffffff1f; border-radius:50%; box-shadow:0 0 0 70px #ffffff08,0 0 0 140px #ffffff05; }
        .login-welcome::after { content:""; position:absolute; right:clamp(1rem,5vw,5rem); bottom:clamp(4rem,12vh,9rem); width:min(70%,390px); height:160px; opacity:.28; background:linear-gradient(145deg,transparent 49.4%,var(--gold) 50% 51%,transparent 51.6%) 0 45px/55% 115px no-repeat,linear-gradient(215deg,transparent 49.4%,var(--gold) 50% 51%,transparent 51.6%) 100% 15px/70% 145px no-repeat; }
        .login-welcome__copy { position:relative; z-index:1; max-width:560px; }
        .login-welcome .eyebrow { color:var(--gold); }
        .login-welcome h1 { max-width:520px; margin:0; font-family:Georgia,"Times New Roman",serif; font-size:clamp(2.6rem,5.2vw,5.2rem); font-weight:400; line-height:.98; }
        .login-welcome p:last-child { max-width:470px; margin:1.35rem 0 0; color:#c9d6d0; line-height:1.7; }
        .login-access { padding:clamp(3rem,8vw,8rem); display:grid; place-items:center; }
        .login-card { width:min(100%,430px); }
        .login-card h2 { margin:0; font-family:Georgia,"Times New Roman",serif; font-size:clamp(2.2rem,4vw,3.4rem); font-weight:400; line-height:1.05; }
        .login-intro { margin:.9rem 0 2.2rem; color:var(--muted); line-height:1.6; }
        .login-card .field + .field { margin-top:1.15rem; }
        .login-card .button-primary { width:100%; min-height:50px; margin-top:1.6rem; }
        .login-help { margin:1.15rem 0 0; display:flex; align-items:center; gap:.5rem; color:var(--muted); font-size:.78rem; }
        .login-help::before { content:""; width:7px; height:7px; flex:0 0 auto; border-radius:50%; background:var(--success); }
        @media (max-width:900px) { .form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.profile-layout{grid-template-columns:1fr}.profile-form{padding:1.5rem 0 0;border-left:0;border-top:1px solid var(--line)} }
        @media (max-width:800px) { .login-page{grid-template-columns:1fr}.login-welcome{min-height:300px;padding:3rem 1.5rem}.login-welcome h1{font-size:clamp(2.45rem,11vw,4rem)}.login-welcome p:last-child{display:none}.login-access{padding:3.5rem 1.5rem} }
        @media (max-width:600px) { .site-header{min-height:68px}.site-brand__area{display:none}.page{padding-top:2.5rem}.section-heading{align-items:start;flex-direction:column}.form-grid{grid-template-columns:1fr}.profile-card,.panel__body{padding:1.15rem}.panel__header{padding:1.2rem 1.15rem}.profile-form .button-primary{width:100%}.site-footer{flex-direction:column;align-items:flex-start}.trace th,.trace td{padding:.7rem} }
        @media (prefers-reduced-motion:reduce) { * { scroll-behavior:auto!important; transition:none!important; } }
    </style>
</head>
<body>
<header class="site-header">
    <a class="site-brand" href="{{ url('/settings') }}">
        <span class="site-brand__mark" aria-hidden="true">▲</span>
        <span><span class="site-brand__name">Refugio Agostino Rocca</span><span class="site-brand__area">Panel de facturación</span></span>
    </a>
    @yield('header-action')
</header>
@yield('content')
<footer class="site-footer">
    <span><strong>Refugio Agostino Rocca</strong><span class="site-footer__label">Panel de facturación</span></span>
    <span>Administración segura de servicios ARCA</span>
</footer>
@yield('scripts')
</body>
</html>
