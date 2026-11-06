<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreativeNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_and_view_a_private_photo_gallery(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('notes.store'), [
                'title' => 'Mi galería',
                'content' => '',
                'note_type' => 'gallery',
                'gallery_images' => [
                    UploadedFile::fake()->image('first.jpg'),
                    UploadedFile::fake()->image('second.jpg'),
                ],
            ])
            ->assertRedirect(route('notes.index'));

        $note = $owner->notes()->sole();
        $this->assertSame('gallery', $note->note_type);
        $this->assertCount(2, $note->gallery_images);
        Storage::disk('local')->assertExists($note->gallery_images);

        $this->get(route('notes.gallery-image', [$note, 0]))
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'image/jpeg');
    }

    public function test_owner_can_save_and_view_a_private_drawing(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('notes.store'), [
                'title' => 'Boceto',
                'content' => '',
                'note_type' => 'drawing',
                'drawing_file' => UploadedFile::fake()->image('drawing.png'),
            ])
            ->assertRedirect(route('notes.index'));

        $note = $owner->notes()->sole();
        $this->assertSame('drawing', $note->note_type);
        Storage::disk('local')->assertExists($note->drawing_file);

        $this->get(route('notes.drawing', $note))
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'image/png');
    }

    public function test_owner_can_edit_a_poem_without_changing_its_type(): void
    {
        $owner = User::factory()->create();
        $poem = $owner->notes()->create([
            'title' => 'Poema',
            'content' => 'Primera versión',
            'note_type' => 'poem',
        ]);

        $this->actingAs($owner)
            ->put(route('notes.update', $poem), [
                'title' => 'Poema nuevo',
                'content' => "Verso primero\nVerso segundo",
                'note_type' => 'poem',
            ])
            ->assertRedirect(route('notes.show', $poem));

        $this->assertSame('poem', $poem->fresh()->note_type);
        $this->assertSame("Verso primero\nVerso segundo", $poem->fresh()->content);
    }
}