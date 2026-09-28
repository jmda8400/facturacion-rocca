@extends('layouts.settings')

@section('title', 'Acceso')

@section('content')
<main class="login-page">
    <section class="login-welcome" aria-label="Facturación Refugio Agostino Rocca">
        <div class="login-welcome__copy">
            <p class="eyebrow">Administración</p>
            <h1>Gestión simple, segura y en un solo lugar.</h1>
            <p>Administrá los puntos de venta y las credenciales de facturación electrónica del refugio.</p>
        </div>
    </section>
    <section class="login-access">
        <div class="login-card">
            <p class="eyebrow">Panel de facturación</p>
            <h2>Bienvenido</h2>
            <p class="login-intro">Ingresá tus credenciales para continuar.</p>
            @if($errors->any())<div class="alert alert--danger" role="alert">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ url('/settings/login') }}">
                @csrf
                <label class="field"><span>Usuario</span><input name="username" autocomplete="username" required autofocus></label>
                <label class="field"><span>Contraseña</span><input type="password" name="password" autocomplete="current-password" required></label>
                <button class="button-primary" type="submit">Ingresar al panel</button>
            </form>
            <p class="login-help">Acceso protegido a la configuración de ARCA</p>
        </div>
    </section>
</main>
@endsection
