<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $ownerEmail = Str::lower(trim((string) config('owner.email', '')));
        $ownerId = $ownerEmail === ''
            ? null
            : DB::table('users')->where('email', $ownerEmail)->value('id');

        if ($ownerId === null) {
            $existingUserIds = DB::table('users')->orderBy('id')->limit(2)->pluck('id');
            $ownerId = $existingUserIds->count() === 1 ? $existingUserIds->first() : null;
        }

        if ($ownerId !== null) {
            DB::table('notes')->whereNull('user_id')->update(['user_id' => $ownerId]);
        }
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
