<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Puntos de Venta · Refugio Rocca</title>
    <style>
        :root { --ink:#18332d; --pine:#244b40; --pine-dark:#17362e; --cream:#f5f1e7; --paper:#fffdf8; --gold:#c89c55; --muted:#738079; --line:#dedbd0; --danger:#9b433b; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; color:var(--ink); background:var(--cream); font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        button, input { font:inherit; }
        .topbar { height:76px; padding:0 clamp(1.25rem,5vw,5rem); display:flex; align-items:center; justify-content:space-between; color:#fff; background:var(--pine-dark); border-bottom:3px solid var(--gold); }
        .brand { display:flex; align-items:center; gap:.85rem; letter-spacing:.04em; text-transform:uppercase; }
        .brand-mark { width:42px; height:42px; display:grid; place-items:center; border:1px solid #ffffff55; border-radius:50%; color:var(--gold); font-size:1.15rem; }
        .brand strong { display:block; font-family:Georgia,serif; font-size:1rem; letter-spacing:.08em; }
        .brand small { display:block; margin-top:.12rem; color:#dbe5df; font-size:.62rem; letter-spacing:.18em; }
        .logout { padding:.6rem 1rem; color:#fff; background:transparent; border:1px solid #ffffff66; border-radius:999px; cursor:pointer; transition:.2s; }
        .logout:hover { color:var(--pine-dark); background:#fff; }
        .shell { width:min(1180px,calc(100% - 2rem)); margin:0 auto; padding:clamp(2rem,5vw,4.5rem) 0; }
        .eyebrow { margin:0 0 .65rem; color:#9a7133; font-size:.73rem; font-weight:800; letter-spacing:.18em; text-transform:uppercase; }
        h1 { margin:0; font-family:Georgia,"Times New Roman",serif; font-size:clamp(2.25rem,5vw,4.2rem); font-weight:400; line-height:1; }
        .lead { max-width:630px; margin:1rem 0 0; color:var(--muted); font-size:1.02rem; line-height:1.65; }
        .notice { margin:1.5rem 0 0; padding:1rem 1.15rem; color:#1f563e; background:#e1efe5; border-left:3px solid #5b9572; border-radius:4px; }
        .errors { margin:1.5rem 0 0; padding:1rem 1.15rem; color:#7d312b; background:#f7e5e2; border-left:3px solid var(--danger); border-radius:4px; }
        .errors ul { margin:.35rem 0 0; padding-left:1.2rem; }
        .register { margin-top:2rem; padding:1.5rem; background:var(--paper); border:1px solid var(--line); border-radius:4px; box-shadow:0 12px 30px rgba(32,49,42,.06); }
        .register-head { display:flex; align-items:start; justify-content:space-between; gap:1rem; }
        .register h2 { margin:0; font-family:Georgia,serif; font-size:1.55rem; font-weight:400; }
        .register p { margin:.4rem 0 0; color:var(--muted); font-size:.9rem; }
        .register-grid { margin-top:1.3rem; display:grid; grid-template-columns:1.25fr 1fr 1fr auto; align-items:end; gap:1rem; }
        .field span { display:block; margin-bottom:.45rem; color:var(--pine); font-size:.76rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
        .field input { width:100%; min-height:44px; padding:.65rem .75rem; color:var(--ink); background:#fff; border:1px solid #bfc6bf; border-radius:3px; }
        .field input:focus { outline:2px solid #c89c5570; border-color:var(--gold); }
        .submit { min-height:44px; padding:.65rem 1.15rem; color:#fff; background:var(--pine); border:0; border-radius:3px; cursor:pointer; font-weight:750; white-space:nowrap; }
        .submit:hover { background:var(--pine-dark); }
        .section-head { margin:3.2rem 0 1.15rem; display:flex; align-items:end; justify-content:space-between; gap:1rem; }
        .section-head h2 { margin:0; font-family:Georgia,serif; font-size:1.55rem; font-weight:400; }
        .section-head span { color:var(--muted); font-size:.85rem; }
        .points { display:grid; grid-template-columns:repeat(auto-fit,minmax(290px,1fr)); gap:1rem; }
        .point { position:relative; min-height:245px; padding:1.55rem; overflow:hidden; background:var(--paper); border:1px solid var(--line); border-radius:4px; box-shadow:0 12px 30px rgba(32,49,42,.06); }
        .point::before { content:""; position:absolute; inset:0 auto 0 0; width:4px; background:var(--gold); }
        .point-top { display:flex; justify-content:space-between; gap:1rem; }
        .point h3 { margin:.3rem 0 .2rem; font-family:Georgia,serif; font-size:1.45rem; font-weight:400; }
        .identifier { color:var(--muted); font-size:.78rem; }
        .status { height:fit-content; display:inline-flex; align-items:center; gap:.4rem; padding:.38rem .65rem; border-radius:999px; color:#2f6c4e; background:#e4f0e7; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .status::before { content:""; width:7px; height:7px; border-radius:50%; background:#4d986b; }
        .status.off { color:#7c615f; background:#eee7e4; }
        .status.off::before { background:#a5746f; }
        .renewal { margin:1.7rem 0 1.5rem; padding-top:1.25rem; border-top:1px solid var(--line); }
        .renewal-label { color:var(--muted); font-size:.74rem; font-weight:750; letter-spacing:.1em; text-transform:uppercase; }
        .countdown { margin-top:.38rem; color:var(--pine); font-family:Georgia,serif; font-size:2rem; font-variant-numeric:tabular-nums; }
        .renewal small { display:block; margin-top:.35rem; color:var(--muted); }
        .delete { padding:.48rem .7rem; color:var(--danger); background:transparent; border:0; cursor:pointer; font-weight:700; }
        .delete:hover { text-decoration:underline; }
        .empty { grid-column:1/-1; padding:3.5rem 1.5rem; text-align:center; color:var(--muted); background:#fffaf1; border:1px dashed #cfc7b6; }
        .empty strong { display:block; margin-bottom:.4rem; color:var(--ink); font-family:Georgia,serif; font-size:1.35rem; font-weight:400; }
        .operations { margin-top:1rem; padding:1.25rem 1.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; color:#d9e3de; background:var(--pine); border-radius:4px; }
        .operations strong { color:#fff; }
        .operations p { margin:0; }
        .operations .technical { color:#b9c9c1; font-size:.78rem; }
        @media (max-width:900px) { .register-grid{grid-template-columns:1fr 1fr}.submit{width:100%} }
        @media (max-width:600px) { .brand small{display:none}.topbar{height:68px}.shell{padding-top:2.5rem}.section-head,.register-head{align-items:start;flex-direction:column}.register-grid{grid-template-columns:1fr}.operations{flex-direction:column}.point{min-height:225px} }
    </style>
</head>
<body>
<header class="topbar">
    <div class="brand"><span class="brand-mark">▲</span><span><strong>Refugio Rocca</strong><small>Facturación</small></span></div>
    <form method="post" action="{{ route('settings.logout') }}">@csrf<button class="logout">Cerrar sesión</button></form>
</header>
<main class="shell">
    <p class="eyebrow">Panel de facturación</p>
    <h1>Puntos de Venta</h1>
    <p class="lead">Estado de las credenciales y de los tickets de acceso utilizados para emitir comprobantes.</p>
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())
        <div class="errors" role="alert"><strong>No se pudo registrar el punto de venta.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="register" aria-labelledby="register-title">
        <div class="register-head"><div><h2 id="register-title">Registrar punto de venta</h2><p>Ingresá un nombre y cargá las credenciales provistas por ARCA.</p></div></div>
        <form method="post" enctype="multipart/form-data" action="{{ route('settings.profiles.store') }}">
            @csrf
            <div class="register-grid">
                <label class="field"><span>Nombre</span><input name="name" value="{{ old('name') }}" maxlength="100" autocomplete="off" required></label>
                <label class="field"><span>Certificado (.crt)</span><input type="file" name="certificate" accept=".crt" required></label>
                <label class="field"><span>Clave privada (.key)</span><input type="file" name="private_key" accept=".key" required></label>
                <button class="submit" type="submit">Registrar punto</button>
            </div>
        </form>
    </section>

    <div class="section-head"><h2>Puntos conectados</h2><span>{{ $profiles->count() }} {{ $profiles->count() === 1 ? 'punto registrado' : 'puntos registrados' }}</span></div>
    <section class="points" aria-label="Puntos de venta">
        @forelse($profiles as $profile)
            <article class="point">
                <div class="point-top">
                    <div><span class="eyebrow">Punto de venta</span><h3>{{ $profile->name }}</h3><span class="identifier">{{ $profile->slug }} · ID #{{ str_pad((string) $profile->id, 3, '0', STR_PAD_LEFT) }}</span></div>
                    <span class="status {{ $profile->active ? '' : 'off' }}">{{ $profile->active ? 'Activo' : 'Inactivo' }}</span>
                </div>
                <div class="renewal">
                    <div class="renewal-label">Próxima renovación del TA</div>
                    <div class="countdown" data-next-renewal="{{ $profile->next_renewal_at?->toIso8601String() }}">--:--:--</div>
                    <small>{{ $profile->last_renewal_at ? 'Última renovación '.$profile->last_renewal_at->diffForHumans() : 'Se renovará en la próxima ejecución' }}</small>
                </div>
                <form method="post" action="{{ route('settings.profiles.destroy', $profile) }}" onsubmit="return confirm('¿Eliminar este punto de venta?')">@csrf @method('DELETE')<button class="delete">Eliminar punto</button></form>
            </article>
        @empty
            <div class="empty"><strong>No hay puntos de venta registrados</strong>Los puntos configurados aparecerán aquí junto con el estado de su próximo TA.</div>
        @endforelse
    </section>
    <section class="operations"><p>Renovación automática: <strong>cada 6 horas</strong></p><p class="technical">SMTP: {{ config('mail.default') }} · Cola: {{ config('queue.default') }} · ARCA: {{ config('billing.arca.environment') }}</p></section>
</main>
<script>
    const clocks = document.querySelectorAll('[data-next-renewal]');
    function updateClocks() {
        clocks.forEach((clock) => {
            const target = Date.parse(clock.dataset.nextRenewal);
            if (!target) { clock.textContent = 'Pendiente'; return; }
            const remaining = Math.max(0, target - Date.now());
            if (remaining === 0) { clock.textContent = 'Renovando…'; return; }
            const hours = Math.floor(remaining / 3600000);
            const minutes = Math.floor((remaining % 3600000) / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            clock.textContent = [hours, minutes, seconds].map((value) => String(value).padStart(2, '0')).join(':');
        });
    }
    updateClocks();
    setInterval(updateClocks, 1000);
</script>
</body>
</html>
