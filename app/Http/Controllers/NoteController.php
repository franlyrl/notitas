<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Services\GoogleDriveStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NoteController extends Controller
{
    public function index(Request $request): View
    {
        $notes = $request->user()
            ? $request->user()->notes()->latest()->get()
            : Note::query()->latest()->get();

        return view('notes.index', [
            'notes' => $notes,
            'totalNotes' => $notes->count(),
        ]);
    }

    public function create(): View
    {
        return view('notes.form', [
            'note' => new Note,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['audio_file'], $data['gallery_images'], $data['drawing_file']);
        $data['audio_file'] = $this->storeAudio($request);
        $data['gallery_images'] = $this->storeGalleryImages($request);
        $data['drawing_file'] = $this->storeDrawing($request);
        $data['content'] ??= '';
        $data['is_poetic'] = ($data['note_type'] ?? 'text') === 'poem';

        if ($request->user()) {
            $request->user()->notes()->create($data);
        } else {
            Note::query()->create($data);
        }

        return redirect()->route('notes.index');
    }

    public function show(Request $request, Note $note): View
    {
        $note = $this->ownedNote($request, $note);

        return view('notes.show', compact('note'));
    }

    public function audio(Request $request, Note $note): StreamedResponse
    {
        $note = $this->ownedNote($request, $note);
        abort_unless($note->audio_file, 404);

        if (str_starts_with($note->audio_file, 'gdrive:')) {
            return app(GoogleDriveStorage::class)->stream(substr($note->audio_file, 7));
        }

        abort_unless(Storage::disk('public')->exists($note->audio_file), 404);

        return Storage::disk('public')->response($note->audio_file);
    }

    public function galleryImage(Request $request, Note $note, int $index): StreamedResponse
    {
        $note = $this->ownedNote($request, $note);
        $imagePath = $note->gallery_images[$index] ?? null;

        abort_unless(is_string($imagePath) && Storage::disk('local')->exists($imagePath), 404);

        return Storage::disk('local')->response($imagePath, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function drawing(Request $request, Note $note): StreamedResponse
    {
        $note = $this->ownedNote($request, $note);
        abort_unless($note->drawing_file && Storage::disk('local')->exists($note->drawing_file), 404);

        return Storage::disk('local')->response($note->drawing_file, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function edit(Request $request, Note $note): View
    {
        $note = $this->ownedNote($request, $note);

        return view('notes.form', [
            'note' => $note,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Note $note): RedirectResponse
    {
        $note = $this->ownedNote($request, $note);
        $data = $this->validated($request);
        unset($data['audio_file'], $data['gallery_images'], $data['drawing_file']);
        $data['content'] ??= '';
        $data['is_poetic'] = ($data['note_type'] ?? $note->note_type) === 'poem';

        if ($path = $this->storeAudio($request)) {
            if ($note->audio_file) {
                $this->deleteAudio($note->audio_file);
            }
            $data['audio_file'] = $path;
        }

        $newGalleryImages = $this->storeGalleryImages($request);

        if ($newGalleryImages !== []) {
            $data['gallery_images'] = array_merge($note->gallery_images ?? [], $newGalleryImages);
        }

        if ($drawingPath = $this->storeDrawing($request)) {
            if ($note->drawing_file) {
                Storage::disk('local')->delete($note->drawing_file);
            }

            $data['drawing_file'] = $drawingPath;
        }

        $note->update($data);

        return redirect()->route('notes.show', $note);
    }

    public function confirmDelete(Request $request, Note $note): View
    {
        $note = $this->ownedNote($request, $note);

        return view('notes.delete', compact('note'));
    }

    public function destroy(Request $request, Note $note): RedirectResponse
    {
        $note = $this->ownedNote($request, $note);

        if ($note->audio_file) {
            $this->deleteAudio($note->audio_file);
        }

        Storage::disk('local')->delete($note->gallery_images ?? []);

        if ($note->drawing_file) {
            Storage::disk('local')->delete($note->drawing_file);
        }

        $note->delete();

        return redirect()->route('notes.index');
    }

    public function transcribe(Request $request, Note $note): JsonResponse
    {
        $note = $this->ownedNote($request, $note);

        if (! $note->audio_file) {
            return response()->json([
                'success' => false,
                'error' => 'No hay archivo de audio en esta nota',
            ], 400);
        }

        $note->update(['transcription_status' => 'processing']);

        $transcription = sprintf(
            "Transcripción simulada del audio '%s' grabado con emoción %s. Contexto: %s",
            $note->title ?: 'Sin título',
            $note->mood_display,
            $note->notes ?: 'Sin contexto adicional.'
        );

        $note->update([
            'transcription' => $transcription,
            'transcription_status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'transcription' => $note->transcription,
            'status' => $note->transcription_status,
        ]);
    }

    public function updateTranscription(Request $request, Note $note): JsonResponse
    {
        $note = $this->ownedNote($request, $note);

        $note->update([
            'transcription' => $request->input('transcription', ''),
            'transcription_status' => 'completed',
        ]);

        return response()->json(['success' => true]);
    }

    public function convertToPoetic(Request $request): JsonResponse
    {
        $text = trim((string) $request->input('text', ''));
        $format = $request->input('format', 'lines');

        if ($text === '') {
            return response()->json([
                'success' => false,
                'error' => 'Texto vacío',
            ], 400);
        }

        $poeticText = match ($format) {
            'lines' => $this->toLines($text),
            'stanza' => $this->toStanzas($text),
            'haiku' => $this->toHaiku($text),
            'free_verse' => $this->toFreeVerse($text),
            default => $text,
        };

        return response()->json([
            'success' => true,
            'poetic_text' => $poeticText,
            'format' => $format,
        ]);
    }

    public function export(Request $request, Note $note): JsonResponse
    {
        $note = $this->ownedNote($request, $note);

        $line = str_repeat('=', 50);
        $title = $note->title ?: 'Sin título';

        $content = <<<TXT
NOTA DEL JOURNAL
{$line}

Título: {$title}
Fecha: {$note->created_at->format('d/m/Y H:i')}
Emoción: {$note->mood_display}
Tipo: {$note->note_type_display}
Estilo: {$note->style_display}

{$line}
CONTENIDO:
{$line}

{$note->content}

TXT;

        if ($note->notes) {
            $content .= "\n{$line}\nNOTAS ADICIONALES:\n{$line}\n\n{$note->notes}";
        }

        if ($note->audio_file) {
            $content .= "\n\n{$line}\nARCHIVO DE AUDIO:\n{$line}\n{$note->audio_file}";

            if ($note->transcription) {
                $content .= "\n\n{$line}\nTRANSCRIPCIÓN:\n{$line}\n\n{$note->transcription}";
            }
        }

        return response()->json([
            'success' => true,
            'content' => $content,
            'filename' => "nota_{$note->id}.txt",
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'content' => ['nullable', 'string', 'required_unless:note_type,gallery,drawing'],
            'note_type' => ['nullable', 'in:text,audio,poem,gallery,drawing'],
            'mood' => ['nullable', 'in:'.implode(',', array_keys(Note::MOODS))],
            'style' => ['nullable', 'in:'.implode(',', array_keys(Note::STYLES))],
            'font_style' => ['nullable', 'in:'.implode(',', array_keys(Note::FONTS))],
            'notes' => ['nullable', 'string'],
            'audio_file' => ['nullable', 'file', 'max:20480'],
            'gallery_images' => ['sometimes', 'array', 'max:20'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'drawing_file' => ['nullable', 'image', 'mimes:png', 'max:5120'],
        ]);
    }

    /** @return array<int, string> */
    private function storeGalleryImages(Request $request): array
    {
        return collect($request->file('gallery_images', []))
            ->map(fn (UploadedFile $image): string => $image->store('galleries', 'local'))
            ->all();
    }

    private function storeDrawing(Request $request): ?string
    {
        return $request->file('drawing_file')?->store('drawings', 'local');
    }

    private function ownedNote(Request $request, Note $note): Note
    {
        if (! config('auth.enabled')) {
            return $note;
        }

        return $request->user()->notes()->findOrFail($note->getKey());
    }

    private function storeAudio(Request $request): ?string
    {
        if (! $request->hasFile('audio_file')) {
            return null;
        }

        if (config('filesystems.audio_driver') === 'google_drive') {
            return app(GoogleDriveStorage::class)->store($request->file('audio_file'));
        }

        return $request->file('audio_file')->store('audio', 'public');
    }

    private function deleteAudio(string $audioFile): void
    {
        if (str_starts_with($audioFile, 'gdrive:')) {
            app(GoogleDriveStorage::class)->delete(substr($audioFile, 7));

            return;
        }

        Storage::disk('public')->delete($audioFile);
    }

    private function toLines(string $text): string
    {
        $sentences = preg_split('/[.!?]+/', $text) ?: [];

        return collect($sentences)
            ->map(fn (string $sentence) => trim($sentence))
            ->filter()
            ->implode("\n");
    }

    private function toStanzas(string $text): string
    {
        $sentences = collect(preg_split('/[.!?]+/', $text) ?: [])
            ->map(fn (string $sentence) => trim($sentence))
            ->filter()
            ->values();

        return $sentences
            ->chunk(4)
            ->map(fn ($stanza) => $stanza->implode("\n"))
            ->implode("\n\n");
    }

    private function toHaiku(string $text): string
    {
        $words = preg_split('/\s+/', $text) ?: [];

        if (count($words) >= 12) {
            return implode(' ', array_slice($words, 0, 3))."\n"
                .implode(' ', array_slice($words, 3, 4))."\n"
                .implode(' ', array_slice($words, 7, 3));
        }

        return str_replace(['. ', '! ', '? '], "\n", $text);
    }

    private function toFreeVerse(string $text): string
    {
        $phrases = preg_split('/[,;]+/', $text) ?: [];

        return collect($phrases)
            ->map(fn (string $phrase) => trim($phrase))
            ->filter()
            ->implode("\n");
    }
}
