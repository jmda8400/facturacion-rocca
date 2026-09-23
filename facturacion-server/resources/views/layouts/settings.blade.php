<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#212529">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Refugio Agostino Rocca</title>
    <style>
        :root { --brand:#f28c28; --brand-dark:#d87312; --header:#212529; --ink:#343a40; --muted:#6c757d; --canvas:#f5f6f7; --surface:#fff; --line:#dee2e6; --success:#198754; --danger:#dc3545; }
        * { box-sizing:border-box; }
        html { min-height:100%; }
        body { min-height:100vh; margin:0; display:flex; flex-direction:column; color:var(--ink); background:var(--canvas); font-family:Arial,"Helvetica Neue",sans-serif; }
        button,input { font:inherit; }
        button { cursor:pointer; }
        .site-header { min-height:64px; padding:0 max(1rem,calc((100vw - 1180px)/2)); display:flex; align-items:center; justify-content:space-between; gap:1rem; color:#fff; background:var(--header); box-shadow:0 2px 5px rgba(0,0,0,.2); }
        .site-brand { display:flex; align-items:center; gap:.7rem; color:#fff; text-decoration:none; }
        .site-brand__mark { width:38px; height:38px; display:grid; place-items:center; flex:0 0 auto; color:#fff; background:var(--brand); border-radius:50%; font-size:1.05rem; }
        .site-brand__name { display:block; font-size:1rem; font-weight:700; }
        .site-brand__area { display:block; margin-top:.1rem; color:#ced4da; font-size:.72rem; }
        .header-action { padding:.5rem .85rem; color:#f8f9fa; background:transparent; border:1px solid #6c757d; border-radius:4px; }
        .header-action:hover { color:#fff; border-color:#fff; }
        .page { width:min(1180px,calc(100% - 2rem)); margin:0 auto; padding:2.25rem 0 3rem; flex:1; }
        .page-title { margin:0; color:#212529; font-size:1.75rem; font-weight:500; }
        .page-intro { margin:.5rem 0 0; color:var(--muted); line-height:1.55; }
        .panel { background:var(--surface); border:1px solid var(--line); border-radius:5px; box-shadow:0 .125rem .25rem rgba(0,0,0,.075); }
        .panel__header { padding:1rem 1.25rem; background:#f8f9fa; border-bottom:1px solid var(--line); }
        .panel__header h2 { margin:0; font-size:1.08rem; font-weight:600; }
        .panel__header p { margin:.35rem 0 0; color:var(--muted); font-size:.88rem; }
        .panel__body { padding:1.25rem; }
        .field span { display:block; margin-bottom:.4rem; color:#495057; font-size:.86rem; font-weight:600; }
        .field input { width:100%; min-height:40px; padding:.55rem .7rem; color:var(--ink); background:#fff; border:1px solid #ced4da; border-radius:4px; }
        .field input:focus { outline:0; border-color:#f6b26b; box-shadow:0 0 0 .2rem rgba(242,140,40,.2); }
        .button-primary { min-height:40px; padding:.55rem 1rem; color:#fff; background:var(--brand); border:1px solid var(--brand); border-radius:4px; font-weight:600; }
        .button-primary:hover { background:var(--brand-dark); border-color:var(--brand-dark); }
        .alert { margin:1.25rem 0 0; padding:.85rem 1rem; border:1px solid transparent; border-radius:4px; }
        .alert--success { color:#0f5132; background:#d1e7dd; border-color:#badbcc; }
        .alert--danger { color:#842029; background:#f8d7da; border-color:#f5c2c7; }
        .alert ul { margin:.35rem 0 0; padding-left:1.2rem; }
        .site-footer { padding:1.25rem max(1rem,calc((100vw - 1180px)/2)); display:flex; justify-content:space-between; gap:1rem; color:#adb5bd; background:var(--header); font-size:.8rem; }
        .site-footer strong { color:#f8f9fa; font-weight:600; }
        @media (max-width:600px) { .site-header{min-height:58px}.site-brand__area{display:none}.page{padding-top:1.5rem}.site-footer{flex-direction:column;text-align:center}.header-action{padding:.42rem .65rem} }
        @media (prefers-reduced-motion:reduce) { * { scroll-behavior:auto!important; transition:none!important; } }
        @yield('styles')
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
    <span><strong>Refugio Agostino Rocca</strong></span>
    <span>Administración de servicios</span>
</footer>
@yield('scripts')
</body>
</html>
