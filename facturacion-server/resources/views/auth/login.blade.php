@extends('layouts.settings')

@section('title', 'Acceso')

@section('styles')
    .login-page { display:grid; place-items:center; }
    .login-panel { width:min(100%,420px); }
    .login-panel .panel__header { padding:1.25rem 1.5rem; }
    .login-panel .panel__body { padding:1.5rem; }
    .login-panel .field + .field { margin-top:1rem; }
    .login-panel .button-primary { width:100%; margin-top:1.4rem; }
    .login-help { margin:1rem 0 0; color:var(--muted); font-size:.82rem; text-align:center; }
@endsection

@section('content')
<main class="page login-page">
    <section class="panel login-panel" aria-labelledby="login-title">
        <div class="panel__header">
            <h1 class="page-title" id="login-title">Iniciar sesión</h1>
            <p>Ingresá para administrar las credenciales de ARCA.</p>
        </div>
        <div class="panel__body">
            @if($errors->any())<div class="alert alert--danger" role="alert">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ url('/settings/login') }}">
                @csrf
                <label class="field"><span>Usuario</span><input name="username" autocomplete="username" required autofocus></label>
                <label class="field"><span>Contraseña</span><input type="password" name="password" autocomplete="current-password" required></label>
                <button class="button-primary" type="submit">Ingresar</button>
            </form>
            <p class="login-help">Acceso exclusivo para personal autorizado.</p>
        </div>
    </section>
</main>
@endsection
