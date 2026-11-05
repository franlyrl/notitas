@extends('layouts.app')

@section('title', 'Iniciar sesión - Mi Journal')

@section('content')
<section class="login-panel">
    <div class="page-header">
        <h2>Iniciar sesión</h2>
        <span class="meta">Acceso privado</span>
    </div>

    <a href="{{ route('google.login') }}" class="btn btn-secondary google-login">
        <span class="google-mark" aria-hidden="true">G</span>
        Continuar con Google
    </a>

    <div class="login-divider"><span>o con contraseña</span></div>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>

        <label class="remember-option">
            <input type="checkbox" name="remember" value="1">
            Mantener la sesión iniciada
        </label>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">Entrar</button>
        </div>
    </form>
</section>
@endsection