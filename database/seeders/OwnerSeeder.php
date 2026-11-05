<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        $ownerName = config('owner.name');
        $ownerEmail = config('owner.email');
        $ownerPassword = config('owner.password');

        if ($ownerEmail === null && $ownerPassword === null) {
            return;
        }

        if (! is_string($ownerName) || trim($ownerName) === ''
            || ! is_string($ownerEmail) || filter_var($ownerEmail, FILTER_VALIDATE_EMAIL) === false
            || ! is_string($ownerPassword) || mb_strlen($ownerPassword) < 16) {
            throw new RuntimeException('Set OWNER_NAME, OWNER_EMAIL, and an OWNER_PASSWORD of at least 16 characters before seeding.');
        }

        User::query()->create([
            'name' => $ownerName,
            'email' => $ownerEmail,
            'password' => $ownerPassword,
        ]);
    }
}
