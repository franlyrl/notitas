<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class NoteController extends Controller
{
    public function index(): View
    {
        $notes = Note::query()->latest()->get();

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
        unset($data['audio_file']);
        $data['audio_file'] = $this->storeAudio($request);
        $data['is_poetic'] = ($data['note_type'] ?? 'text') === 'poem';

        Note::create($data);

        return redirect()->route('notes.index');
    }

    public function show(Note $note): View
    {
        return view('notes.show', compact('note'));
    }

    public function edit(Note $note): View
    {
        return view('notes.form', [
            'note' => $note,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Note $note): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['audio_file']);
        $data['is_poetic'] = ($data['note_type'] ?? $note->note_type) === 'poem';

        if ($path = $this->storeAudio($request)) {
            if ($note->audio_file) {
                Storage::disk('public')->delete($note->audio_file);
            }
            $data['audio_file'] = $path;
        }

        $note->update($data);

        return redirect()->route('notes.show', $note);
    }

    public function confirmDelete(Note $note): View
    {
        return view('notes.delete', compact('note'));
    }

    public function destroy(Note $note): RedirectResponse
    {
        if ($note->audio_file) {
            Storage::disk('public')->delete($note->audio_file);
        }

        $note->delete();

        return redirect()->route('notes.index');
    }

    public function transcribe(Note $note): JsonResponse
    {
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

    public function export(Note $note): JsonResponse
    {
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
            'content' => ['required', 'string'],
            'note_type' => ['nullable', 'in:text,audio,poem'],
            'mood' => ['nullable', 'in:'.implode(',', array_keys(Note::MOODS))],
            'style' => ['nullable', 'in:'.implode(',', array_keys(Note::STYLES))],
            'font_style' => ['nullable', 'in:'.implode(',', array_keys(Note::FONTS))],
            'notes' => ['nullable', 'string'],
            'audio_file' => ['nullable', 'file', 'max:20480'],
        ]);
    }

    private function storeAudio(Request $request): ?string
    {
        if (! $request->hasFile('audio_file')) {
            return null;
        }

        return $request->file('audio_file')->store('audio', 'public');
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
