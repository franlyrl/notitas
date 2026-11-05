<?php

namespace App\Services;

use App\Models\GoogleDriveConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleDriveStorage
{
    private const API_URL = 'https://www.googleapis.com/drive/v3';

    private const UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3';

    public function store(UploadedFile $file): string
    {
        $connection = $this->connection();
        $accessToken = $this->accessToken($connection);
        $folderId = $this->folderId($connection, $accessToken);
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $filename = Str::uuid().'.'.($file->guessExtension() ?: 'bin');

        $uploadSession = Http::withToken($accessToken)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->withHeaders([
                'X-Upload-Content-Type' => $mimeType,
                'X-Upload-Content-Length' => (string) $file->getSize(),
            ])
            ->post(self::UPLOAD_URL.'/files?uploadType=resumable&fields=id', [
                'name' => $filename,
                'mimeType' => $mimeType,
                'parents' => [$folderId],
            ])
            ->throw();

        $uploadUrl = $uploadSession->header('Location');

        if (! is_string($uploadUrl) || ! $this->isGoogleUploadUrl($uploadUrl)) {
            throw new RuntimeException('Google Drive returned an invalid upload URL.');
        }

        $uploadedFile = Http::withToken($accessToken)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(120)
            ->withBody($file->get(), $mimeType)
            ->put($uploadUrl)
            ->throw()
            ->json('id');

        if (! is_string($uploadedFile) || $uploadedFile === '') {
            throw new RuntimeException('Google Drive did not return a file ID.');
        }

        return 'gdrive:'.$uploadedFile;
    }

    public function stream(string $fileId): StreamedResponse
    {
        $response = $this->authorizedRequest($this->connection())
            ->get(self::API_URL.'/files/'.rawurlencode($fileId), ['alt' => 'media'])
            ->throw();
        $contents = $response->body();
        $headers = [
            'Content-Type' => $response->header('Content-Type', 'application/octet-stream'),
            'Content-Length' => (string) strlen($contents),
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return response()->stream(static function () use ($contents): void {
            echo $contents;
        }, 200, $headers);
    }

    public function delete(string $fileId): void
    {
        $response = $this->authorizedRequest($this->connection())
            ->delete(self::API_URL.'/files/'.rawurlencode($fileId));

        if (! $response->successful() && $response->status() !== 404) {
            $response->throw();
        }
    }

    private function connection(): GoogleDriveConnection
    {
        $connection = GoogleDriveConnection::query()->first();

        if (! $connection) {
            abort(503, 'Conecta Google Drive antes de guardar audios en la nube.');
        }

        return $connection;
    }

    private function accessToken(GoogleDriveConnection $connection): string
    {
        if ($connection->expires_at?->isFuture()) {
            return $connection->access_token;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google_drive.client_id'),
                'client_secret' => config('services.google_drive.client_secret'),
                'refresh_token' => $connection->refresh_token,
                'grant_type' => 'refresh_token',
            ])
            ->throw();

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Google did not return an access token.');
        }

        $connection->forceFill([
            'access_token' => $token,
            'refresh_token' => $response->json('refresh_token', $connection->refresh_token),
            'expires_at' => now()->addSeconds((int) $response->json('expires_in', 3600)),
        ])->save();

        return $connection->access_token;
    }

    private function folderId(GoogleDriveConnection $connection, string $accessToken): string
    {
        if (is_string($connection->drive_folder_id) && $connection->drive_folder_id !== '') {
            return $connection->drive_folder_id;
        }

        $query = "name = 'Mi Journal' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
        $existingFolderId = Http::withToken($accessToken)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->get(self::API_URL.'/files', [
                'q' => $query,
                'fields' => 'files(id)',
                'pageSize' => 1,
            ])
            ->throw()
            ->json('files.0.id');

        $folderId = $existingFolderId;

        if (! is_string($folderId) || $folderId === '') {
            $folderId = Http::withToken($accessToken)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->post(self::API_URL.'/files', [
                    'name' => 'Mi Journal',
                    'mimeType' => 'application/vnd.google-apps.folder',
                ])
                ->throw()
                ->json('id');
        }

        if (! is_string($folderId) || $folderId === '') {
            throw new RuntimeException('Google Drive did not return a folder ID.');
        }

        $connection->forceFill(['drive_folder_id' => $folderId])->save();

        return $folderId;
    }

    private function authorizedRequest(GoogleDriveConnection $connection): PendingRequest
    {
        return Http::withToken($this->accessToken($connection))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(30);
    }

    private function isGoogleUploadUrl(string $url): bool
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? null) === 'https'
            && ($parts['host'] ?? null) === 'www.googleapis.com';
    }
}
