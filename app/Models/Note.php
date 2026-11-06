<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'content',
    'note_type',
    'mood',
    'audio_file',
    'gallery_images',
    'drawing_file',
    'transcription',
    'transcription_status',
    'style',
    'font_style',
    'is_poetic',
    'poetic_format',
    'notes',
])]
class Note extends Model
{
    public const TYPES = [
        'text' => 'Texto',
        'audio' => 'Audio',
        'poem' => 'Poema',
        'gallery' => 'Galería de fotos',
        'drawing' => 'Dibujo',
    ];

    public const MOODS = [
        'happy' => 'Feliz 😊',
        'sad' => 'Triste 😢',
        'angry' => 'Enojado 😠',
        'calm' => 'Tranquilo 😌',
        'anxious' => 'Ansioso 😰',
        'creative' => 'Creativo ✨',
        'motivated' => 'Motivado 💪',
        'thoughtful' => 'Pensativo 🤔',
        'tired' => 'Cansado 😴',
        'neutral' => 'Neutral 😐',
    ];

    public const STYLES = [
        'aurora' => 'Aurora',
        'midnight' => 'Midnight',
        'sunset' => 'Sunset',
        'paper' => 'Paper',
    ];

    public const FONTS = [
        'serif' => 'Serif',
        'mono' => 'Monospace',
        'handwritten' => 'Handwritten',
        'poetic' => 'Poetic',
    ];

    protected function casts(): array
    {
        return [
            'is_poetic' => 'boolean',
            'gallery_images' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMoodDisplayAttribute(): string
    {
        return self::MOODS[$this->mood] ?? $this->mood;
    }

    public function getNoteTypeDisplayAttribute(): string
    {
        return self::TYPES[$this->note_type] ?? $this->note_type;
    }

    public function getStyleDisplayAttribute(): string
    {
        return self::STYLES[$this->style] ?? $this->style;
    }

    public function getAudioUrlAttribute(): ?string
    {
        if (! $this->audio_file) {
            return null;
        }

        return route('notes.audio', $this);
    }
}
