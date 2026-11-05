<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\GoogleDriveConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GoogleDriveController extends Controller
{
    private const DRIVE_FILE_SCOPE = 'https://www.googleapis.com/auth/drive.file';

    public function show(): View
    {
        return view('settings.google-drive', [
            'connected' => GoogleDriveConnection::query()->exists(),
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $clientId = config('services.google_drive.client_id');
        $redirectUri = config('services.google_drive.redirect_uri');

        if (! is_string($clientId) || $clientId === '' || ! is_string($redirectUri) || $redirectUri === '') {
            return redirect()->route('settings.google-drive')
                ->withErrors(['google_drive' => 'Configura GOOGLE_DRIVE_CLIENT_ID y GOOGLE_DRIVE_REDIRECT_URI en el archivo .env.']);
        }

        $state = Str::random(48);
        $request->session()->put('_google_drive_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::DRIVE_FILE_SCOPE,
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = $request->session()->pull('_google_drive_oauth_state');
        $receivedState = $request->query('state');

        if (! is_string($expectedState) || ! is_string($receivedState) || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('settings.google-drive')
                ->withErrors(['google_drive' => 'La respuesta de Google no pudo validarse. Intenta conectar la cuenta otra vez.']);
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('settings.google-drive')
                ->withErrors(['google_drive' => 'Google Drive no fue autorizado.']);
        }

        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google_drive.client_id'),
                'client_secret' => config('services.google_drive.client_secret'),
                'code' => $request->string('code')->toString(),
                'redirect_uri' => config('services.google_drive.redirect_uri'),
                'grant_type' => 'authorization_code',
            ]);

        if (! $response->successful()) {
            return redirect()->route('settings.google-drive')
                ->withErrors(['google_drive' => 'Google no pudo completar la conexión. Verifica las credenciales OAuth y la URI de retorno.']);
        }

        $token = $response->json();
        $grantedScopes = preg_split('/\s+/', trim((string) ($token['scope'] ?? ''))) ?: [];

        if (! in_array(self::DRIVE_FILE_SCOPE, $grantedScopes, true)) {
            return redirect()->route('settings.google-drive')
                ->withErrors(['google_drive' => 'Google no otorgó permiso para guardar archivos de la aplicación.']);
        }

        $connection = GoogleDriveConnection::query()->first();
        $refreshToken = $token['refresh_token'] ?? $connection?->refresh_token;

        if (! is_string($refreshToken) || $refreshToken === '' || ! is_string($token['access_token'] ?? null)) {
            return redirect()->route('settings.google-drive')
                ->withErrors(['google_drive' => 'Google no devolvió credenciales persistentes. Revoca el acceso de la app en Google y vuelve a conectarla.']);
        }

        $connection ??= new GoogleDriveConnection;
        $connection->forceFill([
            'access_token' => $token['access_token'],
            'refresh_token' => $refreshToken,
            'expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
        ])->save();

        return redirect()->route('settings.google-drive')->with('status', 'Google Drive quedó conectado.');
    }

    public function disconnect(): RedirectResponse
    {
        $connection = GoogleDriveConnection::query()->first();

        if ($connection) {
            Http::asForm()
                ->connectTimeout(3)
                ->timeout(5)
                ->post('https://oauth2.googleapis.com/revoke', [
                    'token' => $connection->refresh_token,
                ]);

            $connection->delete();
        }

        return redirect()->route('settings.google-drive')->with('status', 'Google Drive fue desconectado. Los archivos ya guardados siguen en tu cuenta.');
    }
}
