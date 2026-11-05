<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_start_google_login(): void
    {
        config([
            'services.google_drive.client_id' => 'client-id',
            'services.google_login.redirect_uri' => 'https://journal.example.test/auth/google/callback',
        ]);

        $response = $this->get(route('google.login'));

        $response->assertRedirect();
        $authorizationUrl = parse_url($response->headers->get('Location'));
        parse_str($authorizationUrl['query'] ?? '', $parameters);

        $this->assertSame('accounts.google.com', $authorizationUrl['host'] ?? null);
        $this->assertSame('openid email profile', $parameters['scope'] ?? null);
        $this->assertSame('https://journal.example.test/auth/google/callback', $parameters['redirect_uri'] ?? null);
        $response->assertSessionHas('_google_login_state', $parameters['state'] ?? null);
    }

    public function test_google_callback_rejects_an_invalid_state(): void
    {
        Http::fake();

        $this->withSession(['_google_login_state' => 'expected-state'])
            ->get(route('google.login.callback', [
                'code' => 'authorization-code',
                'state' => 'attacker-state',
            ]))
            ->assertRedirect(route('login'));

        Http::assertNothingSent();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_verified_owner_google_account_is_registered_and_authenticated(): void
    {
        config([
            'owner.email' => 'owner@example.com',
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
            'services.google_login.redirect_uri' => 'https://journal.example.test/auth/google/callback',
        ]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'expires_in' => 3600,
            ]),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-owner-id',
                'email' => 'owner@example.com',
                'email_verified' => true,
                'name' => 'Journal Owner',
            ]),
        ]);

        $this->withSession(['_google_login_state' => 'valid-state'])
            ->get(route('google.login.callback', [
                'code' => 'authorization-code',
                'state' => 'valid-state',
            ]))
            ->assertRedirect(route('notes.index'));

        $user = User::query()->sole();
        $this->assertSame('google-owner-id', $user->google_id);
        $this->assertSame('owner@example.com', $user->email);
        $this->assertSame('Journal Owner', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_account_outside_owner_allowlist_is_rejected(): void
    {
        config([
            'owner.email' => 'owner@example.com',
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
            'services.google_login.redirect_uri' => 'https://journal.example.test/auth/google/callback',
        ]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'expires_in' => 3600,
            ]),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'other-google-id',
                'email' => 'someone-else@example.com',
                'email_verified' => true,
                'name' => 'Another Person',
            ]),
        ]);

        $this->withSession(['_google_login_state' => 'valid-state'])
            ->get(route('google.login.callback', [
                'code' => 'authorization-code',
                'state' => 'valid-state',
            ]))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_account_without_verified_email_is_rejected(): void
    {
        config([
            'owner.email' => 'owner@example.com',
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
            'services.google_login.redirect_uri' => 'https://journal.example.test/auth/google/callback',
        ]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'expires_in' => 3600,
            ]),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-owner-id',
                'email' => 'owner@example.com',
                'email_verified' => false,
                'name' => 'Journal Owner',
            ]),
        ]);

        $this->withSession(['_google_login_state' => 'valid-state'])
            ->get(route('google.login.callback', [
                'code' => 'authorization-code',
                'state' => 'valid-state',
            ]))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_login_uses_the_configured_oauth_client(): void
    {
        config([
            'owner.email' => 'owner@example.com',
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
            'services.google_login.redirect_uri' => 'https://journal.example.test/auth/google/callback',
        ]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'expires_in' => 3600,
            ]),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-owner-id',
                'email' => 'owner@example.com',
                'email_verified' => true,
                'name' => 'Journal Owner',
            ]),
        ]);

        $this->withSession(['_google_login_state' => 'valid-state'])
            ->get(route('google.login.callback', [
                'code' => 'authorization-code',
                'state' => 'valid-state',
            ]));

        Http::assertSent(fn (Request $request): bool =>
            $request->url() === 'https://oauth2.googleapis.com/token'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
        );
    }
}