<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GoogleLoginController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('services.google_drive.client_id');
        $redirectUri = config('services.google_login.redirect_uri');

        if (! is_string($clientId) || $clientId === '' || ! is_string($redirectUri) || $redirectUri === '') {
            return redirect()->route('login')
                ->withErrors(['google' => 'Google OAuth todavía no está configurado.']);
        }

        $state = Str::random(48);
        $request->session()->put('_google_login_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = $request->session()->pull('_google_login_state');
        $receivedState = $request->query('state');

        if (! is_string($expectedState) || ! is_string($receivedState) || ! hash_equals($expectedState, $receivedState)) {
            return $this->loginError('La respuesta de Google no pudo validarse. Intenta de nuevo.');
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->loginError('No se autorizó el inicio de sesión con Google.');
        }

        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $redirectUri = config('services.google_login.redirect_uri');

        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === ''
            || ! is_string($redirectUri) || $redirectUri === '') {
            return $this->loginError('Google OAuth todavía no está configurado.');
        }

        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $request->string('code')->toString(),
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

        $accessToken = $tokenResponse->json('access_token');

        if (! $tokenResponse->successful() || ! is_string($accessToken) || $accessToken === '') {
            return $this->loginError('Google no pudo verificar la cuenta. Intenta de nuevo.');
        }

        $profileResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (! $profileResponse->successful()) {
            return $this->loginError('No se pudo obtener el perfil verificado de Google.');
        }

        $profile = $profileResponse->json();
        if (! is_array($profile)) {
            return $this->loginError('Google devolvió un perfil no válido.');
        }

        $googleId = $profile['sub'] ?? null;
        $email = Str::lower(trim((string) ($profile['email'] ?? '')));
        $ownerEmail = Str::lower(trim((string) config('owner.email', '')));
        $emailVerified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! is_string($googleId) || $googleId === '' || ! $emailVerified
            || $ownerEmail === '' || ! hash_equals($ownerEmail, $email)) {
            return $this->loginError('Esta cuenta Google no está autorizada para este diario.');
        }

        $user = User::query()->where('google_id', $googleId)->first();

        if ($user && ! hash_equals($user->email, $email)) {
            return $this->loginError('Esta cuenta Google está vinculada a otro correo.');
        }

        $user ??= User::query()->where('email', $email)->first();

        if ($user && $user->google_id !== null && ! hash_equals($user->google_id, $googleId)) {
            return $this->loginError('Esta cuenta Google no coincide con la cuenta vinculada.');
        }

        $user ??= new User;
        $user->forceFill([
            'name' => $user->exists ? $user->name : (string) ($profile['name'] ?? $email),
            'email' => $email,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'google_id' => $googleId,
            'password' => $user->exists ? $user->password : Str::random(64),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('notes.index'));
    }

    private function loginError(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['google' => $message]);
    }
}