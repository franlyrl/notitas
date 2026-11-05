<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200)->nullable();
            $table->text('content');
            $table->string('note_type', 10)->default('text');
            $table->string('mood', 20)->default('neutral');
            $table->string('audio_file')->nullable();
            $table->text('transcription')->nullable();
            $table->string('transcription_status', 20)->default('pending');
            $table->string('style', 20)->default('paper');
            $table->string('font_style', 20)->default('serif');
            $table->boolean('is_poetic')->default(false);
            $table->string('poetic_format', 20)->default('none');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
