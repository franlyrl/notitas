from django.shortcuts import render, redirect, get_object_or_404
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from django.views.decorators.http import require_http_methods
import json
import re
from .models import Note


def note_list(request):
    """Listar todas las notas del journal"""
    notes = Note.objects.all()
    context = {
        'notes': notes,
        'total_notes': notes.count(),
    }
    return render(request, 'journal/note_list.html', context)


def note_detail(request, pk):
    """Ver detalle de una nota"""
    note = get_object_or_404(Note, pk=pk)
    context = {'note': note}
    return render(request, 'journal/note_detail.html', context)


def note_create(request):
    """Crear una nueva nota"""
    if request.method == 'POST':
        title = request.POST.get('title', '')
        content = request.POST.get('content', '')
        note_type = request.POST.get('note_type', 'text')
        mood = request.POST.get('mood', 'neutral')
        audio_file = request.FILES.get('audio_file', None)
        style = request.POST.get('style', 'paper')
        font_style = request.POST.get('font_style', 'serif')
        notes = request.POST.get('notes', '')
        
        Note.objects.create(
            title=title,
            content=content,
            note_type=note_type,
            mood=mood,
            audio_file=audio_file,
            style=style,
            font_style=font_style,
            notes=notes
        )
        return redirect('note_list')
    
    return render(request, 'journal/note_form.html', {
        'mode': 'create'
    })


def note_update(request, pk):
    """Editar una nota"""
    note = get_object_or_404(Note, pk=pk)
    
    if request.method == 'POST':
        note.title = request.POST.get('title', note.title)
        note.content = request.POST.get('content', note.content)
        note.note_type = request.POST.get('note_type', note.note_type)
        note.mood = request.POST.get('mood', note.mood)
        note.style = request.POST.get('style', note.style)
        note.font_style = request.POST.get('font_style', note.font_style)
        note.notes = request.POST.get('notes', note.notes)
        
        if request.FILES.get('audio_file'):
            note.audio_file = request.FILES['audio_file']
        
        note.save()
        return redirect('note_detail', pk=pk)
    
    context = {
        'note': note,
        'mode': 'edit'
    }
    return render(request, 'journal/note_form.html', context)


def note_delete(request, pk):
    """Eliminar una nota"""
    note = get_object_or_404(Note, pk=pk)
    
    if request.method == 'POST':
        note.delete()
        return redirect('note_list')
    
    context = {'note': note}
    return render(request, 'journal/note_confirm_delete.html', context)


@csrf_exempt
@require_http_methods(["POST"])
def transcribe_audio(request, pk):
    """API: Transcribir audio de una nota"""
    try:
        note = Note.objects.get(id=pk)
        
        if not note.audio_file:
            return JsonResponse({
                'success': False, 
                'error': 'No hay archivo de audio en esta nota'
            }, status=400)
        
        # Cambiar estado a procesando
        note.transcription_status = 'processing'
        note.save()
        
        # SIMULACIÓN de transcripción (en producción usar Whisper API)
        simulated_transcription = (
            f"Transcripción simulada del audio '{note.title}' "
            f"grabado con emoción {note.get_mood_display()}. "
            f"Contexto: {note.notes if note.notes else 'Sin contexto adicional.'}"
        )
        
        note.transcription = simulated_transcription
        note.transcription_status = 'completed'
        note.save()
        
        return JsonResponse({
            'success': True,
            'transcription': note.transcription,
            'status': note.transcription_status
        })
        
    except Note.DoesNotExist:
        return JsonResponse({
            'success': False, 
            'error': 'Nota no encontrada'
        }, status=404)
    except Exception as e:
        return JsonResponse({
            'success': False, 
            'error': str(e)
        }, status=500)


@csrf_exempt
@require_http_methods(["POST"])
def update_transcription(request, pk):
    """API: Actualizar transcripción manualmente"""
    try:
        note = Note.objects.get(id=pk)
        data = json.loads(request.body)
        
        note.transcription = data.get('transcription', '')
        note.transcription_status = 'completed'
        note.save()
        
        return JsonResponse({'success': True})
        
    except Note.DoesNotExist:
        return JsonResponse({
            'success': False, 
            'error': 'Nota no encontrada'
        }, status=404)
    except Exception as e:
        return JsonResponse({
            'success': False, 
            'error': str(e)
        }, status=500)


@csrf_exempt
@require_http_methods(["POST"])
def convert_to_poetic(request):
    """API: Convertir texto a formato poético"""
    try:
        data = json.loads(request.body)
        text = data.get('text', '').strip()
        format_type = data.get('format', 'lines')
        
        if not text:
            return JsonResponse({
                'success': False, 
                'error': 'Texto vacío'
            }, status=400)
        
        # Aplicar formato poético
        if format_type == 'lines':
            # Dividir por oraciones y convertir a líneas
            sentences = re.split(r'[.!?]+', text)
            poetic_text = '\n'.join([s.strip() for s in sentences if s.strip()])
            
        elif format_type == 'stanza':
            # Dividir en estrofas de 4 líneas
            sentences = re.split(r'[.!?]+', text)
            sentences = [s.strip() for s in sentences if s.strip()]
            stanzas = []
            for i in range(0, len(sentences), 4):
                stanza = sentences[i:i+4]
                stanzas.append('\n'.join(stanza))
            poetic_text = '\n\n'.join(stanzas)
            
        elif format_type == 'haiku':
            # Intentar crear un haiku simple
            words = text.split()
            if len(words) >= 12:
                line1 = ' '.join(words[:3])
                line2 = ' '.join(words[3:7])
                line3 = ' '.join(words[7:10])
                poetic_text = f"{line1}\n{line2}\n{line3}"
            else:
                poetic_text = text.replace('. ', '\n').replace('! ', '\n').replace('? ', '\n')
                
        elif format_type == 'free_verse':
            # Verso libre
            phrases = re.split(r'[,;]+', text)
            poetic_text = '\n'.join([p.strip() for p in phrases if p.strip()])
            
        else:
            poetic_text = text
        
        return JsonResponse({
            'success': True,
            'poetic_text': poetic_text,
            'format': format_type
        })
        
    except json.JSONDecodeError:
        return JsonResponse({
            'success': False, 
            'error': 'JSON inválido'
        }, status=400)
    except Exception as e:
        return JsonResponse({
            'success': False, 
            'error': str(e)
        }, status=500)


@csrf_exempt
@require_http_methods(["POST"])
def export_note(request, pk):
    """API: Exportar nota a archivo de texto"""
    try:
        note = Note.objects.get(id=pk)
        
        # Construir contenido
        content = f"""NOTA DEL JOURNAL
{'='*50}

Título: {note.title or 'Sin título'}
Fecha: {note.created_at.strftime('%d/%m/%Y %H:%M')}
Emoción: {note.get_mood_display()}
Tipo: {note.get_note_type_display()}
Estilo: {note.get_style_display()}

{'='*50}
CONTENIDO:
{'='*50}

{note.content}
"""
        
        if note.notes:
            content += f"\n{'='*50}\nNOTAS ADICIONALES:\n{'='*50}\n\n{note.notes}"
        
        if note.audio_file:
            content += f"\n\n{'='*50}\nARCHIVO DE AUDIO:\n{'='*50}\n{note.audio_file.name}"
            
            if note.transcription:
                content += f"\n\n{'='*50}\nTRANSCRIPCIÓN:\n{'='*50}\n\n{note.transcription}"
        
        return JsonResponse({
            'success': True,
            'content': content,
            'filename': f"nota_{note.id}.txt"
        })
        
    except Note.DoesNotExist:
        return JsonResponse({
            'success': False, 
            'error': 'Nota no encontrada'
        }, status=404)
    except Exception as e:
        return JsonResponse({
            'success': False, 
            'error': str(e)
        }, status=500)
