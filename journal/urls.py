from django.urls import path
from . import views

urlpatterns = [
    # Vistas principales
    path('', views.note_list, name='note_list'),
    path('nota/<int:pk>/', views.note_detail, name='note_detail'),
    path('crear/', views.note_create, name='note_create'),
    path('nota/<int:pk>/editar/', views.note_update, name='note_update'),
    path('nota/<int:pk>/eliminar/', views.note_delete, name='note_delete'),
    
    # APIs
    path('api/nota/<int:pk>/transcribir/', views.transcribe_audio, name='api_transcribe'),
    path('api/nota/<int:pk>/actualizar-transcripcion/', views.update_transcription, name='api_update_transcription'),
    path('api/convertir-poesia/', views.convert_to_poetic, name='convert_to_poetic'),
    path('api/nota/<int:pk>/exportar/', views.export_note, name='api_export_note'),
]