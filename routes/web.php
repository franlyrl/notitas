<?php

use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [NoteController::class, 'index'])->name('notes.index');
Route::get('/crear', [NoteController::class, 'create'])->name('notes.create');
Route::post('/crear', [NoteController::class, 'store'])->name('notes.store');
Route::get('/nota/{note}', [NoteController::class, 'show'])->name('notes.show');
Route::get('/nota/{note}/editar', [NoteController::class, 'edit'])->name('notes.edit');
Route::put('/nota/{note}', [NoteController::class, 'update'])->name('notes.update');
Route::get('/nota/{note}/eliminar', [NoteController::class, 'confirmDelete'])->name('notes.confirm-delete');
Route::delete('/nota/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

Route::post('/api/nota/{note}/transcribir', [NoteController::class, 'transcribe'])->name('notes.transcribe');
Route::post('/api/nota/{note}/actualizar-transcripcion', [NoteController::class, 'updateTranscription'])->name('notes.update-transcription');
Route::post('/api/convertir-poesia', [NoteController::class, 'convertToPoetic'])->name('notes.convert-poetic');
Route::post('/api/nota/{note}/exportar', [NoteController::class, 'export'])->name('notes.export');
