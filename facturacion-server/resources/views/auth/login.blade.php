@extends('layouts.settings')

@section('title', 'Acceso')

@section('content')
<main class="login-wrap">
    <section class="login-card" aria-labelledby="login-title">
        <p class="eyebrow">Facturación</p>
        <h1 id="login-title">Facturación Rocca</h1>
        <p class="login-intro">Ingresá tus credenciales para continuar.</p>
        @if($errors->any())<div class="alert alert--danger" role="alert">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ url('/settings/login') }}">
            @csrf
            <label class="field"><span>Usuario</span><input name="username" autocomplete="username" required autofocus></label>
            <label class="field"><span>Contraseña</span><input type="password" name="password" autocomplete="current-password" required></label>
            <button class="button-primary" type="submit">Ingresar al panel</button>
        </form>
        <p class="login-help">Acceso protegido a la configuración de ARCA</p>
    </section>
</main>
@endsection
