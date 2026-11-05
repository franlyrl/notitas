@extends('layouts.app')

@section('title', 'Google Drive - Mi Journal')

@section('content')
<section class="login-panel">
    <div class="page-header">
        <h2>Google Drive</h2>
        <span class="meta">Almacenamiento privado</span>
    </div>

    <p style="color: var(--muted); line-height: 1.6; margin-bottom: 20px;">
        Los nuevos audios se guardarán en una carpeta privada de tu Drive. La app solo solicita permiso para los archivos que crea.
    </p>

    @if ($connected)
        <p style="margin-bottom: 20px; color: #1e6b3a;">Google Drive está conectado.</p>
        <form method="POST" action="{{ route('google-drive.disconnect') }}">
            @csrf
            <button type="submit" class="btn btn-secondary">Desconectar Google Drive</button>
        </form>
    @else
        <p style="margin-bottom: 20px; color: var(--muted);">Google Drive todavía no está conectado.</p>
        <a href="{{ route('google-drive.connect') }}" class="btn btn-primary">Conectar Google Drive</a>
    @endif
</section>
@endsection