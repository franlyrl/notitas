@extends('layouts.app')

@section('title', 'Eliminar Nota - Journal')

@section('content')
<div style="max-width: 600px; margin: 0 auto; text-align: center;">
    <div style="background: #f8d7da; padding: 30px; border-radius: 10px; border: 2px solid #f5c6cb;">
        <h2 style="color: #721c24; margin-bottom: 20px;">⚠️ Eliminar Nota</h2>

        <p style="color: #721c24; font-size: 1.1rem; margin-bottom: 20px;">
            ¿Estás seguro de que quieres eliminar esta nota?
        </p>

        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: left;">
            <h3 style="color: #8b4513; margin-bottom: 10px;">{{ $note->title ?: 'Sin título' }}</h3>
            <p style="color: #666; margin-bottom: 10px;">{{ \Illuminate\Support\Str::words($note->content, 30) }}</p>
            <p style="color: #999; font-size: 0.9rem;">
                📅 {{ $note->created_at->format('d/m/Y H:i') }}
            </p>
        </div>

        <p style="color: #721c24; margin-bottom: 30px; font-weight: bold;">
            Esta acción no se puede deshacer
        </p>

        <div class="button-group" style="justify-content: center;">
            <form method="POST" action="{{ route('notes.destroy', $note) }}" style="margin: 0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">🗑️ Sí, Eliminar</button>
            </form>
            <a href="{{ route('notes.show', $note) }}" class="btn btn-secondary">← Cancelar</a>
        </div>
    </div>
</div>
@endsection
