from django.db import models
from django.utils import timezone


class Note(models.Model):
    """Modelo para notas del journal"""
    
    TYPE_CHOICES = [
        ('text', 'Texto'),
        ('audio', 'Audio'),
        ('poem', 'Poema'),
    ]
    
    MOOD_CHOICES = [
        ('happy', 'Feliz 😊'),
        ('sad', 'Triste 😢'),
        ('angry', 'Enojado 😠'),
        ('calm', 'Tranquilo 😌'),
        ('anxious', 'Ansioso 😰'),
        ('creative', 'Creativo ✨'),
        ('motivated', 'Motivado 💪'),
        ('thoughtful', 'Pensativo 🤔'),
        ('tired', 'Cansado 😴'),
        ('neutral', 'Neutral 😐'),
    ]
    
    STYLE_CHOICES = [
        ('aurora', 'Aurora'),
        ('midnight', 'Midnight'),
        ('sunset', 'Sunset'),
        ('paper', 'Paper'),
    ]
    
    FONT_CHOICES = [
        ('serif', 'Serif'),
        ('mono', 'Monospace'),
        ('handwritten', 'Handwritten'),
        ('poetic', 'Poetic'),
    ]
    
    TRANSCRIPTION_STATUS_CHOICES = [
        ('pending', 'Pendiente'),
        ('processing', 'Procesando'),
        ('completed', 'Completado'),
        ('failed', 'Fallido'),
    ]
    
    POETIC_FORMAT_CHOICES = [
        ('none', 'Sin formato'),
        ('lines', 'Líneas'),
        ('stanza', 'Estrofas'),
        ('haiku', 'Haiku'),
        ('free_verse', 'Verso Libre'),
    ]
    
    # Campos básicos
    title = models.CharField(max_length=200, blank=True)
    content = models.TextField()
    note_type = models.CharField(max_length=10, choices=TYPE_CHOICES, default='text')
    mood = models.CharField(max_length=20, choices=MOOD_CHOICES, default='neutral')
    
    # Campos de audio y transcripción
    audio_file = models.FileField(upload_to='audio/', null=True, blank=True)
    transcription = models.TextField(blank=True, null=True)
    transcription_status = models.CharField(
        max_length=20, 
        choices=TRANSCRIPTION_STATUS_CHOICES,
        default='pending'
    )
    
    # Campos de estilo visual
    style = models.CharField(max_length=20, choices=STYLE_CHOICES, default='paper')
    font_style = models.CharField(max_length=20, choices=FONT_CHOICES, default='serif')
    is_poetic = models.BooleanField(default=False)
    poetic_format = models.CharField(
        max_length=20, 
        choices=POETIC_FORMAT_CHOICES,
        default='none'
    )
    
    # Campos adicionales
    notes = models.TextField(blank=True, help_text="Notas o contexto adicional")
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        ordering = ['-created_at']
        verbose_name = 'Nota del Journal'
        verbose_name_plural = 'Notas del Journal'
    
    def __str__(self):
        return f"{self.title or 'Sin título'} - {self.created_at.strftime('%d/%m/%Y')}"
