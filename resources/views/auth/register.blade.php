@extends('layouts.app')

@section('title', 'Crear cuenta - Mi Journal')

@section('content')
<section class="login-panel">
    <div class="page-header">
        <h2>Crear cuenta</h2>
        <span class="meta">Correo verificado</span>
    </div>

    <p class="auth-description">
        Solo se permite la cuenta propietaria configurada para este diario. Confirma tu correo para acceder a tus notas.
    </p>

    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        <div class="form-group">
            <label for="name">Nombre</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
        </div>

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        </div>

        <div class="form-group">
            <label for="password">Contraseña (mínimo 12 caracteres)</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">Crear cuenta y enviar verificación</button>
        </div>
    </form>

    <p class="auth-switch">¿Ya tienes una cuenta? <a href="{{ route('login') }}">Iniciar sesión</a></p>
</section>
@endsection