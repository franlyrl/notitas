@extends('layouts.app')

@section('title', $mode === 'edit' ? 'Editar Nota - Journal' : 'Nueva Nota - Journal')

@section('content')
<section class="note-editor">
    <div class="page-header">
        <h2>{{ $mode === 'edit' ? 'Editar nota' : 'Nueva nota' }}</h2>
        <span class="meta">{{ $mode === 'edit' ? 'Borrador guardado' : 'Crear en tu diario' }}</span>
    </div>

    <form id="note-form" method="POST"
          action="{{ $mode === 'edit' ? route('notes.update', $note) : route('notes.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="title">Título</label>
            <input type="text" id="title" name="title" value="{{ old('title', $note->title) }}" placeholder="Ponle un título">
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="note_type">Tipo</label>
                <select id="note_type" name="note_type">
                    @foreach (\App\Models\Note::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('note_type', $note->note_type ?: 'text') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="mood">Emoción</label>
                <select id="mood" name="mood">
                    @foreach (\App\Models\Note::MOODS as $value => $label)
                        <option value="{{ $value }}" @selected(old('mood', $note->mood ?: 'neutral') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="style">Estilo visual</label>
                <select id="style" name="style">
                    @foreach (\App\Models\Note::STYLES as $value => $label)
                        <option value="{{ $value }}" @selected(old('style', $note->style ?: 'paper') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="font_style">Tipografía</label>
                <select id="font_style" name="font_style">
                    @foreach (\App\Models\Note::FONTS as $value => $label)
                        <option value="{{ $value }}" @selected(old('font_style', $note->font_style ?: 'serif') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="content" id="content-label">Contenido</label>
            <textarea id="content" name="content" placeholder="Escribe tus ideas...">{{ old('content', $note->content) }}</textarea>
        </div>

        <section class="creative-panel" data-editor-panel="gallery" @if(old('note_type', $note->note_type ?: 'text') !== 'gallery') hidden @endif>
            <div class="creative-panel-heading">
                <div>
                    <h3>Galería</h3>
                    <p>Selecciona hasta 20 fotos. Se guardarán de forma privada.</p>
                </div>
                <label for="gallery_images" class="btn btn-secondary">Añadir fotos</label>
            </div>
            <input class="visually-hidden" type="file" id="gallery_images" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
            <div class="photo-grid" id="gallery-preview">
                @foreach ($note->gallery_images ?? [] as $index => $imagePath)
                    <img src="{{ route('notes.gallery-image', [$note, $index]) }}" alt="Foto de la galería">
                @endforeach
            </div>
        </section>

        <section class="creative-panel" data-editor-panel="drawing" @if(old('note_type', $note->note_type ?: 'text') !== 'drawing') hidden @endif>
            <div class="creative-panel-heading">
                <div>
                    <h3>Área de dibujo</h3>
                    <p>Dibuja con el dedo o el mouse. El trazo se guarda como una imagen privada.</p>
                </div>
            </div>
            @if ($note->drawing_file)
                <img class="saved-drawing" src="{{ route('notes.drawing', $note) }}" alt="Dibujo guardado">
            @endif
            <canvas id="drawing-canvas" class="drawing-canvas" width="1200" height="750" aria-label="Lienzo para dibujar"></canvas>
            <div class="drawing-tools">
                <label class="color-tool" for="drawing-color">Color
                    <input type="color" id="drawing-color" value="#263b37" aria-label="Color del trazo">
                </label>
                <label class="brush-tool" for="drawing-size">Grosor
                    <input type="range" id="drawing-size" min="2" max="36" value="7" aria-label="Grosor del trazo">
                </label>
                <button type="button" class="btn btn-secondary" id="clear-drawing">Limpiar</button>
            </div>
            <input class="visually-hidden" type="file" id="drawing-file" name="drawing_file" accept="image/png">
        </section>

        <div class="form-group" data-editor-panel="audio" @if(old('note_type', $note->note_type ?: 'text') !== 'audio') hidden @endif>
            <label for="audio_file">Audio</label>
            <input type="file" id="audio_file" name="audio_file" accept="audio/*">
            @if ($note->audio_file)
                <p class="current-file">Hay un audio guardado. Sube otro solo si quieres reemplazarlo.</p>
            @endif
        </div>

        <div class="form-group">
            <label for="notes">Notas adicionales</label>
            <textarea id="notes" name="notes" class="notes-field" placeholder="Contexto o inspiración">{{ old('notes', $note->notes) }}</textarea>
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">{{ $mode === 'edit' ? 'Guardar cambios' : 'Guardar nota' }}</button>
            <a href="{{ route('notes.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script>
    const noteTypeSelect = document.getElementById('note_type');
    const contentField = document.getElementById('content');
    const contentLabel = document.getElementById('content-label');
    const noteForm = document.getElementById('note-form');
    const galleryInput = document.getElementById('gallery_images');
    const galleryPreview = document.getElementById('gallery-preview');
    const drawingCanvas = document.getElementById('drawing-canvas');
    const drawingFile = document.getElementById('drawing-file');

    function updateEditorPanels() {
        document.querySelectorAll('[data-editor-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.editorPanel !== noteTypeSelect.value
                && !(panel.dataset.editorPanel === 'audio' && noteTypeSelect.value === 'audio');
        });

        contentField.classList.toggle('poem-editor', noteTypeSelect.value === 'poem');
        contentLabel.textContent = noteTypeSelect.value === 'poem' ? 'Poema' : 'Contenido';
        contentField.placeholder = noteTypeSelect.value === 'poem'
            ? 'Escribe tus versos, deja que respiren las líneas...'
            : 'Escribe tus ideas...';
    }

    noteTypeSelect.addEventListener('change', updateEditorPanels);
    updateEditorPanels();

    galleryInput.addEventListener('change', () => {
        galleryPreview.querySelectorAll('[data-preview]').forEach((preview) => preview.remove());

        Array.from(galleryInput.files).forEach((file) => {
            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = file.name;
            image.dataset.preview = 'true';
            galleryPreview.append(image);
        });
    });

    if (drawingCanvas) {
        const context = drawingCanvas.getContext('2d');
        const colorInput = document.getElementById('drawing-color');
        const sizeInput = document.getElementById('drawing-size');
        let drawing = false;
        let hasNewDrawing = false;

        context.lineCap = 'round';
        context.lineJoin = 'round';

        const pointFor = (event) => {
            const bounds = drawingCanvas.getBoundingClientRect();
            return {
                x: (event.clientX - bounds.left) * drawingCanvas.width / bounds.width,
                y: (event.clientY - bounds.top) * drawingCanvas.height / bounds.height,
            };
        };

        drawingCanvas.addEventListener('pointerdown', (event) => {
            drawing = true;
            hasNewDrawing = true;
            drawingCanvas.setPointerCapture(event.pointerId);
            const point = pointFor(event);
            context.beginPath();
            context.moveTo(point.x, point.y);
        });

        drawingCanvas.addEventListener('pointermove', (event) => {
            if (!drawing) {
                return;
            }

            const point = pointFor(event);
            context.strokeStyle = colorInput.value;
            context.lineWidth = Number(sizeInput.value);
            context.lineTo(point.x, point.y);
            context.stroke();
        });

        const stopDrawing = () => {
            drawing = false;
            context.closePath();
        };

        drawingCanvas.addEventListener('pointerup', stopDrawing);
        drawingCanvas.addEventListener('pointercancel', stopDrawing);

        document.getElementById('clear-drawing').addEventListener('click', () => {
            context.clearRect(0, 0, drawingCanvas.width, drawingCanvas.height);
            hasNewDrawing = false;
            drawingFile.value = '';
        });

        noteForm.addEventListener('submit', (event) => {
            if (!hasNewDrawing || noteTypeSelect.value !== 'drawing') {
                return;
            }

            event.preventDefault();
            drawingCanvas.toBlob((blob) => {
                if (!blob) {
                    return;
                }

                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], 'drawing.png', { type: 'image/png' }));
                drawingFile.files = transfer.files;
                HTMLFormElement.prototype.submit.call(noteForm);
            }, 'image/png');
        });
    }
</script>
@endpush
