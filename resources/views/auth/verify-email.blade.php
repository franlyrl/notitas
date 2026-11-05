@extends('layouts.app')

@section('title', 'Verificar correo - Mi Journal')

@section('content')
<section class="login-panel">
    <div class="page-header">
        <h2>Confirma tu correo</h2>
        <span class="meta">Un paso más</span>
    </div>

    <p class="auth-description">
        Enviamos un enlace de verificación a <strong>{{ auth()->user()->email }}</strong>. Confírmalo para entrar al diario.
    </p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary">Reenviar correo</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="verification-logout">
        @csrf
        <button type="submit" class="btn btn-secondary">Cerrar sesión</button>
    </form>
</section>
@endsection