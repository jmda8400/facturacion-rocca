@extends('layouts.settings')

@section('title', 'Administración')

@section('header-action')
<form method="post" action="{{ route('settings.logout') }}">@csrf<button class="header-action">Cerrar sesión</button></form>
@endsection

@section('content')
<main class="page">
    <p class="eyebrow">Panel de facturación</p>
    <h1 class="page-title">Facturación central</h1>
    <p class="page-intro">Administrá los puntos de venta, las credenciales de ARCA y la trazabilidad de los comprobantes desde un único lugar.</p>

    @if(session('status'))<div class="alert alert--success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert--danger" role="alert"><strong>No se pudieron guardar los datos.</strong><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <section class="panel section" aria-labelledby="register-title">
        <div class="panel__header">
            <h2 id="register-title">Registrar punto de venta</h2>
            <p>Ingresá los datos fiscales y cargá las credenciales provistas por ARCA.</p>
        </div>
        <form method="post" enctype="multipart/form-data" action="{{ route('settings.profiles.store') }}">
            @csrf
            <div class="panel__body form-grid">
                @foreach(['name'=>'Nombre','cuit'=>'CUIT (11 dígitos)','sales_point'=>'Punto de venta','business_name'=>'Razón social','address'=>'Domicilio','vat_condition'=>'Condición IVA','gross_income'=>'Ingresos brutos','activity_started_at'=>'Inicio de actividades'] as $key=>$label)
                    <label class="field"><span>{{ $label }}</span><input name="{{ $key }}" value="{{ old($key) }}" @if(in_array($key,['name','cuit'])) required @endif></label>
                @endforeach
                <label class="field"><span>Certificado (.crt)</span><input type="file" name="certificate" accept=".crt" required></label>
                <label class="field"><span>Private key (.key)</span><input type="file" name="private_key" accept=".key" required></label>
                <button class="button-primary" type="submit">Registrar punto</button>
            </div>
        </form>
    </section>

    <section class="section" aria-labelledby="points-title">
        <div class="section-heading">
            <h2 id="points-title">Puntos de venta</h2>
            <span>{{ $profiles->count() }} {{ $profiles->count() === 1 ? 'punto registrado' : 'puntos registrados' }}</span>
        </div>
        <div class="profile-cards">
            @forelse($profiles as $profile)
                <article class="panel profile-card">
                    <div class="profile-layout">
                        <div class="profile-summary">
                            <p class="eyebrow">Punto de venta</p>
                            <h3>{{ $profile->name }}</h3>
                            <p class="meta">{{ $profile->slug }} · CUIT {{ $profile->cuit }} · PV {{ $profile->sales_point }}</p>
                            <div class="renewal">
                                <div class="renewal__label">Próxima renovación del TA</div>
                                <span class="countdown" data-next-renewal="{{ $profile->next_renewal_at?->toIso8601String() }}">--:--:--</span>
                                <small>{{ $profile->last_renewal_at ? 'Última renovación '.$profile->last_renewal_at->diffForHumans() : 'Pendiente de primera renovación' }}</small>
                            </div>
                        </div>
                        <form class="profile-form" method="post" enctype="multipart/form-data" action="{{ route('settings.profiles.update',$profile) }}">
                            @csrf @method('PUT')
                            <div class="form-grid">
                                @foreach(['name','cuit','sales_point','business_name','address','vat_condition','gross_income','activity_started_at'] as $key)
                                    <label class="field"><span>{{ str_replace('_',' ',ucfirst($key)) }}</span><input name="{{ $key }}" value="{{ $key==='activity_started_at' ? $profile->$key?->format('Y-m-d') : $profile->$key }}" @if(in_array($key,['name','cuit'])) required @endif></label>
                                @endforeach
                                <label class="field"><span>Nuevo .crt (opcional)</span><input type="file" name="certificate" accept=".crt"></label>
                                <label class="field"><span>Nueva .key (opcional)</span><input type="file" name="private_key" accept=".key"></label>
                                <button class="button-primary" type="submit">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </article>
            @empty
                <div class="empty-state"><strong>No hay puntos de venta registrados</strong>Los puntos configurados aparecerán aquí junto con el estado de su próximo TA.</div>
            @endforelse
        </div>
    </section>

    <section class="panel section info-panel" aria-labelledby="timer-title">
        <div class="panel__header"><h2 id="timer-title">Cómo funciona el cronometrado</h2></div>
        <div class="panel__body"><p>El scheduler de Laravel se mantiene activo dentro del contenedor y dispara <code>arca:renew-tickets</code> cada seis horas. Cada tarjeta calcula localmente la cuenta regresiva desde la última renovación; además, ARCA renueva bajo demanda si al ticket le quedan menos de {{ config('billing.arca.renew_before_minutes') }} minutos. Las activaciones y renovaciones quedan registradas abajo.</p></div>
    </section>

    <section class="section" aria-labelledby="history-title">
        <div class="section-heading"><h2 id="history-title">Historial del sistema</h2><span>Actividad reciente</span></div>
        <div class="terminal" role="log">
            @forelse($history as $event)<div class="terminal-line"><time>[{{ $event->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}]</time> <span class="{{ $event->level }}">{{ $event->event }}</span> — {{ $event->message }} @if($event->context)<span>{{ json_encode($event->context,JSON_UNESCAPED_UNICODE) }}</span>@endif</div>@empty Sin eventos todavía.@endforelse
        </div>
    </section>

    <section class="panel section" aria-labelledby="trace-title">
        <div class="panel__header"><h2 id="trace-title">Trazabilidad de facturas</h2><p>Seguimiento de solicitudes, comprobantes, CAE y entregas por correo.</p></div>
        <div class="table-wrap">
            <table class="trace">
                <thead><tr><th>Recibida</th><th>Cliente</th><th>Referencia</th><th>Idempotencia</th><th>Fingerprint</th><th>Estado</th><th>Perfil / PV</th><th>Comprobante</th><th>CAE / Vto.</th><th>Email</th><th>Error</th></tr></thead>
                <tbody>@foreach($invoices as $i)<tr><td>{{ $i->created_at }}</td><td>{{ $i->billingClient?->slug??'legacy' }}</td><td>{{ $i->external_reference }}</td><td>{{ $i->idempotency_key }}</td><td title="{{ $i->request_fingerprint }}">{{ Str::limit($i->request_fingerprint,12) }}</td><td>{{ $i->status }}</td><td>{{ $i->profile?->slug }} / {{ $i->profile?->sales_point }}</td><td>{{ $i->voucher_number??'-' }}</td><td>{{ $i->cae??'-' }} / {{ $i->cae_expires_at?->format('Y-m-d')??'-' }}</td><td>{{ $i->emailed_at??'-' }}</td><td>{{ Str::limit($i->error,80) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    </section>
</main>
@endsection

@section('scripts')
<script>
    const clocks = document.querySelectorAll('[data-next-renewal]');
    function tick() {
        clocks.forEach((clock) => {
            const target = Date.parse(clock.dataset.nextRenewal);
            const remaining = Math.max(0, target - Date.now());
            if (!target) { clock.textContent = 'Pendiente'; return; }
            if (!remaining) { clock.textContent = 'Renovando…'; return; }
            clock.textContent = [Math.floor(remaining / 3600000), Math.floor(remaining % 3600000 / 60000), Math.floor(remaining % 60000 / 1000)].map((value) => String(value).padStart(2, '0')).join(':');
        });
    }
    tick();
    setInterval(tick, 1000);
</script>
@endsection
