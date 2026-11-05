<?php

namespace Tests\Feature;

use App\Models\GoogleDriveConnection;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleDriveStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_start_drive_oauth_with_the_minimum_file_scope(): void
    {
        config([
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
            'services.google_drive.redirect_uri' => 'https://journal.example.test/integrations/google-drive/callback',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('google-drive.connect'));

        $response->assertRedirect();
        $authorizationUrl = parse_url($response->headers->get('Location'));
        parse_str($authorizationUrl['query'] ?? '', $parameters);

        $this->assertSame('accounts.google.com', $authorizationUrl['host'] ?? null);
        $this->assertSame('https://www.googleapis.com/auth/drive.file', $parameters['scope'] ?? null);
        $this->assertSame('offline', $parameters['access_type'] ?? null);
        $response->assertSessionHas('_google_drive_oauth_state', $parameters['state'] ?? null);
    }

    public function test_drive_oauth_callback_rejects_an_invalid_state(): void
    {
        config([
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
            'services.google_drive.redirect_uri' => 'https://journal.example.test/integrations/google-drive/callback',
        ]);
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->withSession(['_google_drive_oauth_state' => 'expected-state'])
            ->get(route('google-drive.callback', [
                'code' => 'authorization-code',
                'state' => 'attacker-state',
            ]))
            ->assertRedirect(route('settings.google-drive'));

        Http::assertNothingSent();
        $this->assertDatabaseCount('google_drive_connections', 0);
    }

    public function test_drive_oauth_callback_encrypts_the_refresh_token(): void
    {
        config([
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
            'services.google_drive.redirect_uri' => 'https://journal.example.test/integrations/google-drive/callback',
        ]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
                'scope' => 'https://www.googleapis.com/auth/drive.file',
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->withSession(['_google_drive_oauth_state' => 'valid-state'])
            ->get(route('google-drive.callback', [
                'code' => 'authorization-code',
                'state' => 'valid-state',
            ]))
            ->assertRedirect(route('settings.google-drive'));

        $connection = GoogleDriveConnection::query()->sole();

        $this->assertSame('refresh-token', $connection->refresh_token);
        $this->assertNotSame('refresh-token', $connection->getRawOriginal('refresh_token'));
    }

    public function test_drive_oauth_callback_rejects_a_scope_that_only_contains_drive_file_as_a_substring(): void
    {
        config([
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
            'services.google_drive.redirect_uri' => 'https://journal.example.test/integrations/google-drive/callback',
        ]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
                'scope' => 'https://www.googleapis.com/auth/drive.file.evil',
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->withSession(['_google_drive_oauth_state' => 'valid-state'])
            ->get(route('google-drive.callback', [
                'code' => 'authorization-code',
                'state' => 'valid-state',
            ]))
            ->assertRedirect(route('settings.google-drive'));

        $this->assertDatabaseCount('google_drive_connections', 0);
    }

    public function test_audio_uploads_to_drive_and_is_streamed_through_the_private_route(): void
    {
        config([
            'filesystems.audio_driver' => 'google_drive',
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
        ]);

        GoogleDriveConnection::query()->create([
            'id' => 1,
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'www.googleapis.com/drive/v3/files/drive-file-1*' => Http::response(
                'private audio bytes',
                200,
                ['Content-Type' => 'audio/mpeg'],
            ),
            'www.googleapis.com/drive/v3/files*' => Http::sequence()
                ->push([
                    'files' => [
                        ['id' => 'journal-folder'],
                    ],
                ])
                ->push(['id' => 'drive-file-1']),
            'www.googleapis.com/upload/drive/v3/files*' => Http::response([], 200, [
                'Location' => 'https://www.googleapis.com/upload/session/audio-1',
            ]),
            'www.googleapis.com/upload/session/audio-1' => Http::response([
                'id' => 'drive-file-1',
            ]),
        ]);

        $owner = User::factory()->create();
        $this->actingAs($owner)
            ->post(route('notes.store'), [
                'title' => 'Drive audio',
                'content' => 'Private note content',
                'note_type' => 'audio',
                'audio_file' => UploadedFile::fake()->create('voice.mp3', 1, 'audio/mpeg'),
            ])
            ->assertRedirect(route('notes.index'));

        $note = Note::query()->sole();

        $this->assertSame('gdrive:drive-file-1', $note->audio_file);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/upload/session/audio-1')
            && $request->method() === 'PUT'
        );

        $this->actingAs($owner)
            ->get(route('notes.audio', $note))
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertStreamedContent('private audio bytes');
    }
}
