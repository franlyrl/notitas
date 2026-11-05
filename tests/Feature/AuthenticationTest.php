<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\OwnerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_the_login_page(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_guest_cannot_create_a_note(): void
    {
        $this->post(route('notes.store'), [])
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_use_the_api(): void
    {
        $this->post(route('notes.convert-poetic'), ['text' => 'Private'])
            ->assertUnauthorized();
    }

    public function test_guest_cannot_fetch_note_audio(): void
    {
        $owner = User::factory()->create();
        $note = $owner->notes()->create([
            'title' => 'Private note',
            'content' => 'Private content',
            'audio_file' => 'audio/private.mp3',
        ]);

        $this->get(route('notes.audio', $note))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_stream_note_audio(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audio/private.mp3', 'audio contents');
        $owner = User::factory()->create();
        $note = $owner->notes()->create([
            'title' => 'Private note',
            'content' => 'Private content',
            'audio_file' => 'audio/private.mp3',
        ]);

        $this->actingAs($owner)
            ->get(route('notes.audio', $note))
            ->assertOk()
            ->assertHeaderContains('Content-Disposition', 'inline');
    }

    public function test_owner_can_log_in(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('notes.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        User::factory()->create();

        $this->post(route('login.store'), [
            'email' => 'owner@example.com',
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_owner_can_log_out(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_registration_page_offers_email_registration(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Crear cuenta')
            ->assertSee('Correo electrónico')
            ->assertSee('Contraseña (mínimo 12 caracteres)')
            ->assertSee('Confirmar contraseña');
    }

    public function test_repeated_login_failures_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $response = $this->post(route('login.store'), [
                'email' => 'owner@example.com',
                'password' => 'incorrect-password',
            ]);
        }

        $response->assertTooManyRequests();
    }

    public function test_owner_seeder_creates_one_account_and_keeps_its_password(): void
    {
        config([
            'owner.name' => 'Journal Owner',
            'owner.email' => 'owner@example.com',
            'owner.password' => 'long-test-password-2026',
        ]);

        $this->seed(OwnerSeeder::class);
        $owner = User::query()->sole();

        $this->assertSame('Journal Owner', $owner->name);
        $this->assertTrue(Hash::check('long-test-password-2026', $owner->password));

        $this->seed(OwnerSeeder::class);

        $this->assertSame(1, User::query()->count());
    }

    public function test_owner_seeder_does_not_create_a_default_account_without_credentials(): void
    {
        config([
            'owner.email' => null,
            'owner.password' => null,
        ]);

        $this->seed(OwnerSeeder::class);

        $this->assertSame(0, User::query()->count());
    }
}
