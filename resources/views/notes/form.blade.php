@extends('layouts.app')

@section('title', $mode === 'edit' ? 'Editar Nota - Journal' : 'Nueva Nota - Journal')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <h2>{{ $mode === 'edit' ? '✏️ Editar Nota' : '✍️ Nueva Nota' }}</h2>

    <form method="POST"
          action="{{ $mode === 'edit' ? route('notes.update', $note) : route('notes.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="title">Título (Opcional)</label>
            <input type="text" id="title" name="title" value="{{ old('title', $note->title) }}" placeholder="Dale un título a tu nota...">
        </div>

        <div class="form-group">
            <label for="content">Contenido *</label>
            <textarea id="content" name="content" required placeholder="Expresa tus sentimientos, escribe poesía o lo que sientas...">{{ old('content', $note->content) }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="note_type">Tipo de Nota</label>
                <select id="note_type" name="note_type">
                    @foreach (\App\Models\Note::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('note_type', $note->note_type ?: 'text') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="mood">¿Qué emoción describes?</label>
                <select id="mood" name="mood">
                    @foreach (\App\Models\Note::MOODS as $value => $label)
                        <option value="{{ $value }}" @selected(old('mood', $note->mood ?: 'neutral') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="style">🎨 Estilo Visual</label>
                <select id="style" name="style">
                    @foreach (\App\Models\Note::STYLES as $value => $label)
                        <option value="{{ $value }}" @selected(old('style', $note->style ?: 'paper') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="font_style">✍️ Fuente</label>
                <select id="font_style" name="font_style">
                    @foreach (\App\Models\Note::FONTS as $value => $label)
                        <option value="{{ $value }}" @selected(old('font_style', $note->font_style ?: 'serif') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="notes">📝 Notas Adicionales</label>
            <textarea id="notes" name="notes" placeholder="Contexto, inspiración, o notas personales..." style="min-height: 100px;">{{ old('notes', $note->notes) }}</textarea>
        </div>

        <div class="form-group">
            <label for="audio_file">🎵 Archivo de Audio (Opcional)</label>
            <input type="file" id="audio_file" name="audio_file" accept="audio/*">
            @if ($note->audio_file)
                <p style="margin-top: 10px; font-size: 0.9rem; color: #666;">
                    Archivo actual: {{ $note->audio_file }}
                </p>
            @endif
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">
                {{ $mode === 'edit' ? '✔️ Actualizar Nota' : '✍️ Guardar Nota' }}
            </button>
            <a href="{{ route('notes.index') }}" class="btn btn-secondary">← Cancelar</a>
        </div>
    </form>
</div>
@endsection
