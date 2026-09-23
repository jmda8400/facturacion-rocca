@extends('layouts.settings')

@section('title', 'Puntos de venta')

@section('styles')
        .register { margin-top:1.75rem; }
        .register-head { display:flex; align-items:start; justify-content:space-between; gap:1rem; }
        .register h2 { margin:0; font-size:1.08rem; }
        .register p { margin:.4rem 0 0; color:var(--muted); font-size:.9rem; }
        .register-grid { margin-top:1.3rem; display:grid; grid-template-columns:1.25fr 1fr 1fr auto; align-items:end; gap:1rem; }
        .submit { white-space:nowrap; }
        .section-head { margin:3.2rem 0 1.15rem; display:flex; align-items:end; justify-content:space-between; gap:1rem; }
        .section-head h2 { margin:0; font-size:1.2rem; font-weight:600; }
        .section-head span { color:var(--muted); font-size:.85rem; }
        .points { display:grid; grid-template-columns:repeat(auto-fit,minmax(290px,1fr)); gap:1rem; }
        .point { position:relative; min-height:230px; padding:1.35rem; overflow:hidden; background:#fff; border:1px solid var(--line); border-radius:5px; box-shadow:0 .125rem .25rem rgba(0,0,0,.075); }
        .point::before { content:""; position:absolute; inset:0 auto 0 0; width:4px; background:var(--brand); }
        .point-top { display:flex; justify-content:space-between; gap:1rem; }
        .point h3 { margin:.3rem 0 .2rem; font-size:1.25rem; font-weight:600; }
        .eyebrow { color:var(--brand-dark); font-size:.73rem; font-weight:700; text-transform:uppercase; }
        .identifier { color:var(--muted); font-size:.78rem; }
        .status { height:fit-content; display:inline-flex; align-items:center; gap:.4rem; padding:.38rem .65rem; border-radius:999px; color:#2f6c4e; background:#e4f0e7; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .status::before { content:""; width:7px; height:7px; border-radius:50%; background:#4d986b; }
        .status.off { color:#7c615f; background:#eee7e4; }
        .status.off::before { background:#a5746f; }
        .renewal { margin:1.7rem 0 1.5rem; padding-top:1.25rem; border-top:1px solid var(--line); }
        .renewal-label { color:var(--muted); font-size:.74rem; font-weight:750; letter-spacing:.1em; text-transform:uppercase; }
        .countdown { margin-top:.38rem; color:#212529; font-size:1.8rem; font-variant-numeric:tabular-nums; }
        .renewal small { display:block; margin-top:.35rem; color:var(--muted); }
        .delete { padding:.48rem 0; color:var(--danger); background:transparent; border:0; font-weight:600; }
        .delete:hover { text-decoration:underline; }
        .empty { grid-column:1/-1; padding:3.5rem 1.5rem; text-align:center; color:var(--muted); background:#fffaf1; border:1px dashed #cfc7b6; }
        .empty strong { display:block; margin-bottom:.4rem; color:var(--ink); font-size:1.15rem; }
        .operations { margin-top:1rem; padding:1rem 1.25rem; display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; color:#495057; background:#e9ecef; border:1px solid #dee2e6; border-radius:4px; }
        .operations strong { color:#212529; }
        .operations p { margin:0; }
        .operations .technical { color:#b9c9c1; font-size:.78rem; }
        @media (max-width:900px) { .register-grid{grid-template-columns:1fr 1fr}.submit{width:100%} }
        @media (max-width:600px) { .brand small{display:none}.topbar{height:68px}.shell{padding-top:2.5rem}.section-head,.register-head{align-items:start;flex-direction:column}.register-grid{grid-template-columns:1fr}.operations{flex-direction:column}.point{min-height:225px} }
@endsection

@section('header-action')
    <form method="post" action="{{ route('settings.logout') }}">@csrf<button class="header-action">Cerrar sesión</button></form>
@endsection

@section('content')
<main class="page">
    <h1 class="page-title">Puntos de Venta</h1>
    <p class="page-intro">Estado de las credenciales y de los tickets de acceso utilizados para emitir comprobantes.</p>
    @if(session('status'))<div class="alert alert--success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())
        <div class="alert alert--danger" role="alert"><strong>No se pudo registrar el punto de venta.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="panel register" aria-labelledby="register-title">
        <div class="panel__header register-head"><div><h2 id="register-title">Registrar punto de venta</h2><p>Ingresá un nombre y cargá las credenciales provistas por ARCA.</p></div></div>
        <form method="post" enctype="multipart/form-data" action="{{ route('settings.profiles.store') }}">
            @csrf
            <div class="panel__body register-grid">
                <label class="field"><span>Nombre</span><input name="name" value="{{ old('name') }}" maxlength="100" autocomplete="off" required></label>
                <label class="field"><span>Certificado (.crt)</span><input type="file" name="certificate" accept=".crt" required></label>
                <label class="field"><span>Clave privada (.key)</span><input type="file" name="private_key" accept=".key" required></label>
                <button class="button-primary submit" type="submit">Registrar punto</button>
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
@endsection

@section('scripts')
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
@endsection
