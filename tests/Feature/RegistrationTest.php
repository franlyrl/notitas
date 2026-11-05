<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['owner.email' => 'owner@example.com']);
    }

    public function test_owner_can_register_and_receives_a_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Journal Owner',
            'email' => 'owner@example.com',
            'password' => 'private-password-2026',
            'password_confirmation' => 'private-password-2026',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->sole();
        $this->assertSame('owner@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_another_email_can_register_but_must_verify_before_entering(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Other Person',
            'email' => 'other@example.com',
            'password' => 'private-password-2026',
            'password_confirmation' => 'private-password-2026',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->sole();
        $this->assertSame('other@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Journal Owner',
            'email' => 'owner@example.com',
            'password' => 'private-password-2026',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeded_unverified_owner_can_complete_registration(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create([
            'email' => 'owner@example.com',
        ]);

        $this->post(route('register.store'), [
            'name' => 'Journal Owner',
            'email' => 'owner@example.com',
            'password' => 'new-private-password-2026',
            'password_confirmation' => 'new-private-password-2026',
        ])->assertRedirect(route('verification.notice'));

        $this->assertSame(1, User::query()->count());
        $this->assertAuthenticatedAs($user->fresh());
        Notification::assertSentTo($user->fresh(), VerifyEmail::class);
    }

    public function test_unverified_owner_cannot_open_the_journal(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'owner@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('notes.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_signed_verification_link_allows_owner_into_the_journal(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'owner@example.com',
        ]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('notes.index'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
