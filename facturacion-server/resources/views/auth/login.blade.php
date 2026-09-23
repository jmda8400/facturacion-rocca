<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17362e">
    <title>Acceso · Facturación Rocca</title>
    <style>
        :root { --ink:#18332d; --pine:#244b40; --pine-dark:#17362e; --cream:#f5f1e7; --paper:#fffdf8; --gold:#c89c55; --muted:#738079; --line:#dedbd0; --danger:#9b433b; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; color:var(--ink); background:var(--cream); font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        button, input { font:inherit; }
        .page { min-height:100vh; display:grid; grid-template-columns:minmax(300px,.92fr) minmax(440px,1.08fr); }
        .welcome { position:relative; min-height:100vh; padding:clamp(2rem,5vw,5rem); display:flex; flex-direction:column; justify-content:space-between; overflow:hidden; color:#fff; background:var(--pine-dark); }
        .welcome::before { content:""; position:absolute; width:min(38vw,520px); aspect-ratio:1; right:-18%; bottom:-10%; border:1px solid #ffffff1f; border-radius:50%; box-shadow:0 0 0 70px #ffffff08,0 0 0 140px #ffffff05; }
        .welcome::after { content:""; position:absolute; right:clamp(1rem,5vw,5rem); bottom:clamp(4rem,12vh,9rem); width:min(70%,390px); height:160px; opacity:.28; background:linear-gradient(145deg,transparent 49.4%,var(--gold) 50% 51%,transparent 51.6%) 0 45px/55% 115px no-repeat,linear-gradient(215deg,transparent 49.4%,var(--gold) 50% 51%,transparent 51.6%) 100% 15px/70% 145px no-repeat; }
        .brand { position:relative; z-index:1; display:flex; align-items:center; gap:.85rem; letter-spacing:.04em; text-transform:uppercase; }
        .brand-mark { width:46px; height:46px; display:grid; place-items:center; border:1px solid #ffffff55; border-radius:50%; color:var(--gold); font-size:1.15rem; }
        .brand strong { display:block; font-family:Georgia,serif; font-size:1rem; letter-spacing:.08em; }
        .brand small { display:block; margin-top:.12rem; color:#dbe5df; font-size:.62rem; letter-spacing:.18em; }
        .welcome-copy { position:relative; z-index:1; max-width:560px; margin-bottom:clamp(3rem,10vh,8rem); }
        .eyebrow { margin:0 0 .85rem; color:var(--gold); font-size:.72rem; font-weight:800; letter-spacing:.2em; text-transform:uppercase; }
        .welcome h1 { max-width:520px; margin:0; font-family:Georgia,"Times New Roman",serif; font-size:clamp(2.6rem,5.2vw,5.2rem); font-weight:400; line-height:.98; }
        .welcome-copy p:last-child { max-width:470px; margin:1.35rem 0 0; color:#c9d6d0; font-size:1rem; line-height:1.7; }
        .access { min-height:100vh; padding:2rem clamp(1.5rem,8vw,8rem); display:grid; place-items:center; background:var(--cream); }
        .card { width:min(100%,430px); }
        .card .eyebrow { color:#9a7133; }
        .card h2 { margin:0; font-family:Georgia,"Times New Roman",serif; font-size:clamp(2.2rem,4vw,3.4rem); font-weight:400; line-height:1.05; }
        .intro { margin:.9rem 0 2.2rem; color:var(--muted); line-height:1.6; }
        .error { margin:0 0 1.35rem; padding:.9rem 1rem; color:#7d312b; background:#f7e5e2; border-left:3px solid var(--danger); border-radius:3px; }
        .field { display:block; margin-top:1.15rem; }
        .field span { display:block; margin-bottom:.48rem; color:var(--pine); font-size:.75rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .field input { width:100%; min-height:50px; padding:.75rem .85rem; color:var(--ink); background:var(--paper); border:1px solid #bfc6bf; border-radius:3px; transition:border-color .2s,box-shadow .2s; }
        .field input:focus { outline:0; border-color:var(--gold); box-shadow:0 0 0 3px #c89c5529; }
        .submit { width:100%; min-height:50px; margin-top:1.6rem; padding:.75rem 1rem; color:#fff; background:var(--pine); border:0; border-radius:3px; cursor:pointer; font-weight:750; transition:background .2s,transform .2s; }
        .submit:hover { background:var(--pine-dark); }
        .submit:active { transform:translateY(1px); }
        .security { margin:1.15rem 0 0; display:flex; align-items:center; gap:.5rem; color:var(--muted); font-size:.78rem; }
        .security::before { content:""; width:7px; height:7px; flex:0 0 auto; border-radius:50%; background:#5b9572; }
        @media (max-width:800px) {
            .page { grid-template-columns:1fr; }
            .welcome { min-height:300px; padding:1.5rem; }
            .welcome-copy { margin:3.5rem 0 1.5rem; }
            .welcome h1 { max-width:430px; font-size:clamp(2.45rem,11vw,4rem); }
            .welcome-copy p:last-child { display:none; }
            .access { min-height:auto; padding:clamp(3rem,10vw,5rem) 1.5rem; }
        }
        @media (prefers-reduced-motion:reduce) { * { transition:none !important; } }
    </style>
</head>
<body>
<main class="page">
    <section class="welcome" aria-label="Facturación Refugio Rocca">
        <div class="brand"><span class="brand-mark" aria-hidden="true">▲</span><span><strong>Refugio Rocca</strong><small>Facturación</small></span></div>
        <div class="welcome-copy">
            <p class="eyebrow">Administración</p>
            <h1>Gestión simple, segura y en un solo lugar.</h1>
            <p>Administrá los puntos de venta y las credenciales de facturación electrónica del refugio.</p>
        </div>
    </section>
    <section class="access">
        <div class="card">
            <p class="eyebrow">Panel de facturación</p>
            <h2>Bienvenido</h2>
            <p class="intro">Ingresá tus credenciales para continuar.</p>
            @if($errors->any())<div class="error" role="alert">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ url('/settings/login') }}">
                @csrf
                <label class="field"><span>Usuario</span><input name="username" autocomplete="username" required autofocus></label>
                <label class="field"><span>Contraseña</span><input type="password" name="password" autocomplete="current-password" required></label>
                <button class="submit" type="submit">Ingresar al panel</button>
            </form>
            <p class="security">Acceso protegido a la configuración de ARCA</p>
        </div>
    </section>
</main>
</body>
</html>
