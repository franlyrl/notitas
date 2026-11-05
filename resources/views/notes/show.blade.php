@extends('layouts.app')

@section('title', ($note->title ?: 'Sin título').' - Journal')

@section('content')
<div id="note-app" style="max-width: 800px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #fffef5 0%, #f9f5e9 100%); padding: 30px; border-radius: 10px; border-left: 5px solid #d4a574;">
        <h2 style="color: #8b4513; margin-bottom: 10px;">{{ $note->title ?: 'Sin título' }}</h2>

        <div style="margin: 15px 0;">
            <span class="mood">{{ $note->mood_display }}</span>
            <span style="display: inline-block; background: #e0d9cf; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; margin-left: 5px;">
                {{ $note->note_type_display }}
            </span>
        </div>

        <p style="color: #999; font-size: 0.9rem;">
            📅 Creado: {{ $note->created_at->format('d/m/Y H:i') }}
            @if ($note->updated_at->ne($note->created_at))
                <br>✏️ Actualizado: {{ $note->updated_at->format('d/m/Y H:i') }}
            @endif
        </p>

        <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">

        <div style="background: white; padding: 20px; border-radius: 5px; line-height: 1.8; color: #333; white-space: pre-wrap;">{{ $note->content }}</div>

        @if ($note->audio_file)
            <div style="margin-top: 30px; padding: 20px; background: #f0ebe0; border-radius: 8px;">
                <h3 style="color: #8b4513; margin-bottom: 15px;">🎵 Audio</h3>
                <audio controls style="width: 100%; margin-bottom: 15px;">
                    <source src="{{ $note->audio_url }}">
                    Tu navegador no soporta el elemento de audio.
                </audio>

                <button type="button" class="btn btn-primary" :disabled="transcribing" v-on:click="transcribeAudio">
                    @{{ transcribing ? '🎙️ Transcribiendo...' : '🎙️ Transcribir Audio' }}
                </button>

                @if ($note->transcription)
                    <div style="background: white; padding: 15px; border-radius: 5px; border-left: 3px solid #d4a574; margin-top: 15px;">
                        <h4 style="color: #654321; margin-bottom: 10px;">📝 Transcripción</h4>
                        <p style="white-space: pre-wrap;">{{ $note->transcription }}</p>
                        <button type="button" class="btn btn-primary" style="margin-top: 10px;" v-on:click="editTranscription">
                            ✏️ Editar Transcripción
                        </button>
                    </div>
                @endif
            </div>
        @endif

        @if ($note->is_poetic || $note->note_type === 'poem')
            <div style="margin-top: 30px; padding: 20px; background: #f0e8f0; border-radius: 8px;">
                <h3 style="color: #8b4513; margin-bottom: 15px;">📖 Herramientas Poéticas</h3>
                <button type="button" class="btn btn-primary" v-on:click="convertToPoetic('lines')">➡️ Formato Líneas</button>
                <button type="button" class="btn btn-primary" v-on:click="convertToPoetic('stanza')">📄 Formato Estrofas</button>
                <button type="button" class="btn btn-primary" v-on:click="convertToPoetic('haiku')">🌸 Formato Haiku</button>
                <button type="button" class="btn btn-primary" v-on:click="convertToPoetic('free_verse')">🖋️ Verso Libre</button>

                <div v-if="poeticText" style="background: white; padding: 15px; border-radius: 5px; border-left: 3px solid #d4a574; margin-top: 15px;">
                    <h4 style="color: #654321; margin-bottom: 10px;">✨ Resultado</h4>
                    <p style="white-space: pre-wrap;">@{{ poeticText }}</p>
                </div>
            </div>
        @endif

        <div class="button-group" style="margin-top: 30px;">
            <a href="{{ route('notes.edit', $note) }}" class="btn btn-primary">✏️ Editar</a>
            <button type="button" class="btn btn-primary" v-on:click="exportNote">📥 Exportar</button>
            <a href="{{ route('notes.confirm-delete', $note) }}" class="btn btn-danger">🗑️ Eliminar</a>
            <a href="{{ route('notes.index') }}" class="btn btn-secondary">← Volver</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script>
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                transcribing: false,
                poeticText: '',
                csrf: document.querySelector('meta[name="csrf-token"]').content,
                noteId: {{ $note->id }},
                noteContent: @json($note->content),
            };
        },
        methods: {
            headers(json = false) {
                const headers = {
                    'X-CSRF-TOKEN': this.csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                };
                if (json) {
                    headers['Content-Type'] = 'application/json';
                }
                return headers;
            },
            async transcribeAudio() {
                this.transcribing = true;
                try {
                    const response = await fetch(`/api/nota/${this.noteId}/transcribir`, {
                        method: 'POST',
                        headers: this.headers(),
                    });
                    const data = await response.json();
                    if (data.success) {
                        alert('✅ Audio transcrito exitosamente');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.error);
                    }
                } catch (error) {
                    alert('❌ Error: ' + error);
                } finally {
                    this.transcribing = false;
                }
            },
            async editTranscription() {
                const next = prompt('Editar transcripción:', @json($note->transcription));
                if (next === null) {
                    return;
                }
                try {
                    const response = await fetch(`/api/nota/${this.noteId}/actualizar-transcripcion`, {
                        method: 'POST',
                        headers: this.headers(true),
                        body: JSON.stringify({ transcription: next }),
                    });
                    const data = await response.json();
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.error);
                    }
                } catch (error) {
                    alert('❌ Error: ' + error);
                }
            },
            async convertToPoetic(format) {
                try {
                    const response = await fetch('/api/convertir-poesia', {
                        method: 'POST',
                        headers: this.headers(true),
                        body: JSON.stringify({ text: this.noteContent, format }),
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.poeticText = data.poetic_text;
                    } else {
                        alert('❌ Error: ' + data.error);
                    }
                } catch (error) {
                    alert('❌ Error: ' + error);
                }
            },
            async exportNote() {
                try {
                    const response = await fetch(`/api/nota/${this.noteId}/exportar`, {
                        method: 'POST',
                        headers: this.headers(),
                    });
                    const data = await response.json();
                    if (data.success) {
                        const blob = new Blob([data.content], { type: 'text/plain' });
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = data.filename;
                        link.click();
                        URL.revokeObjectURL(url);
                    } else {
                        alert('❌ Error: ' + data.error);
                    }
                } catch (error) {
                    alert('❌ Error: ' + error);
                }
            },
        },
    }).mount('#note-app');
</script>
@endpush
