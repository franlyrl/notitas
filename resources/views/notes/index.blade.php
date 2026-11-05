@extends('layouts.app')

@section('title', 'Mis Notas - Journal')

@section('content')
<div class="page-header">
    <h2>Mis notas</h2>
    <span class="meta">{{ $totalNotes }} nota(s) guardada(s)</span>
</div>

@if ($notes->isNotEmpty())
    <div class="note-list">
        @foreach ($notes as $note)
            <div class="note-card" onclick="window.location='{{ route('notes.show', $note) }}'">
                <div>
                    <h3>{{ \Illuminate\Support\Str::words($note->title ?: 'Sin título', 5) }}</h3>
                    <p>{{ \Illuminate\Support\Str::words($note->content, 18) }}</p>
                </div>

                <div>
                    <div class="meta-row">
                        <span class="mood">{{ $note->mood_display }}</span>
                        <span class="mood-chip">{{ $note->note_type_display }}</span>
                    </div>

                    <div class="date">
                        {{ $note->created_at->format('d/m/Y - H:i') }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div style="text-align: center; padding: 48px 20px 24px; color: #7a655d;">
        <p style="font-size: 1.45rem; font-weight: 700; color: var(--primary);">No tienes notas aún</p>
        <p style="margin-top: 10px;">Crea tu primera nota para comenzar a escribir.</p>
        <a href="{{ route('notes.create') }}" class="btn btn-primary" style="margin-top: 22px;">
            Crear mi primera nota
        </a>
    </div>
@endif
@endsection
