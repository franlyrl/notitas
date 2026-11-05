<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotePrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_user_only_sees_their_own_notes(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $owner->notes()->create([
            'title' => 'Owner private note',
            'content' => 'Only the owner can read this.',
        ]);
        $otherUser->notes()->create([
            'title' => 'Other private note',
            'content' => 'This belongs to another account.',
        ]);

        $this->actingAs($otherUser)
            ->get(route('notes.index'))
            ->assertOk()
            ->assertSeeText('Other private note')
            ->assertDontSeeText('Owner private note');
    }

    public function test_user_cannot_open_another_users_note(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $note = $owner->notes()->create([
            'title' => 'Owner private note',
            'content' => 'Only the owner can read this.',
        ]);

        $this->actingAs($otherUser)
            ->get(route('notes.show', $note))
            ->assertNotFound();
    }

    public function test_user_cannot_update_another_users_note(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $note = $owner->notes()->create([
            'title' => 'Owner private note',
            'content' => 'Original content.',
        ]);

        $this->actingAs($otherUser)
            ->put(route('notes.update', $note), [
                'title' => 'Changed by another user',
                'content' => 'Changed content.',
            ])
            ->assertNotFound();

        $this->assertSame('Owner private note', $note->fresh()->title);
    }

    public function test_user_cannot_delete_another_users_note(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $note = $owner->notes()->create([
            'title' => 'Owner private note',
            'content' => 'Only the owner can delete this.',
        ]);

        $this->actingAs($otherUser)
            ->delete(route('notes.destroy', $note))
            ->assertNotFound();

        $this->assertModelExists($note);
    }

    public function test_user_cannot_fetch_another_users_audio(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audio/private.mp3', 'private audio');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $note = $owner->notes()->create([
            'title' => 'Audio note',
            'content' => 'Private audio content.',
            'audio_file' => 'audio/private.mp3',
        ]);

        $this->actingAs($otherUser)
            ->get(route('notes.audio', $note))
            ->assertNotFound();
    }
}
