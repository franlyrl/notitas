<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Integrations\GoogleDriveController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('/auth/google', [GoogleLoginController::class, 'redirect'])->name('google.login');
    Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback'])
        ->name('google.login.callback');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/ajustes/google-drive', [GoogleDriveController::class, 'show'])->name('settings.google-drive');
    Route::get('/integrations/google-drive/connect', [GoogleDriveController::class, 'connect'])->name('google-drive.connect');
    Route::get('/integrations/google-drive/callback', [GoogleDriveController::class, 'callback'])->name('google-drive.callback');
    Route::post('/integrations/google-drive/disconnect', [GoogleDriveController::class, 'disconnect'])->name('google-drive.disconnect');

    Route::get('/', [NoteController::class, 'index'])->name('notes.index');
    Route::get('/crear', [NoteController::class, 'create'])->name('notes.create');
    Route::post('/crear', [NoteController::class, 'store'])->name('notes.store');
    Route::get('/nota/{note}', [NoteController::class, 'show'])->name('notes.show');
    Route::get('/nota/{note}/audio', [NoteController::class, 'audio'])->name('notes.audio');
    Route::get('/nota/{note}/editar', [NoteController::class, 'edit'])->name('notes.edit');
    Route::put('/nota/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::get('/nota/{note}/eliminar', [NoteController::class, 'confirmDelete'])->name('notes.confirm-delete');
    Route::delete('/nota/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    Route::post('/api/nota/{note}/transcribir', [NoteController::class, 'transcribe'])->name('notes.transcribe');
    Route::post('/api/nota/{note}/actualizar-transcripcion', [NoteController::class, 'updateTranscription'])->name('notes.update-transcription');
    Route::post('/api/convertir-poesia', [NoteController::class, 'convertToPoetic'])->name('notes.convert-poetic');
    Route::post('/api/nota/{note}/exportar', [NoteController::class, 'export'])->name('notes.export');
});
