from django.contrib import admin
from .models import Note


@admin.register(Note)
class NoteAdmin(admin.ModelAdmin):
    list_display = ('title', 'note_type', 'mood', 'created_at')
    list_filter = ('note_type', 'mood', 'created_at')
    search_fields = ('title', 'content')
    readonly_fields = ('created_at', 'updated_at')
    fieldsets = (
        ('Información', {
            'fields': ('title', 'content', 'note_type', 'mood')
        }),
        ('Multimedia', {
            'fields': ('audio_file', 'transcription'),
            'classes': ('collapse',)
        }),
        ('Fechas', {
            'fields': ('created_at', 'updated_at'),
            'classes': ('collapse',)
        }),
    )
