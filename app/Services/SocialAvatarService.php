<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SocialAvatarService
{
    public function syncForUser(User $user, ?string $remoteUrl): ?string
    {
        if (! filled($remoteUrl)) {
            return $user->avatar;
        }

        if (! Str::startsWith($remoteUrl, ['http://', 'https://'])) {
            return $user->avatar;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'CARMAXING/1.0'])
                ->get($remoteUrl);

            if (! $response->successful()) {
                return $user->avatar;
            }

            $extension = match ($response->header('Content-Type')) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => 'jpg',
            };

            $path = "avatars/{$user->id}.{$extension}";

            Storage::disk('public')->put($path, $response->body());

            $this->deleteStaleAvatars($user->id, $path);

            return $path;
        } catch (\Throwable) {
            return $user->avatar;
        }
    }

    private function deleteStaleAvatars(int $userId, string $keepPath): void
    {
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $extension) {
            $candidate = "avatars/{$userId}.{$extension}";

            if ($candidate !== $keepPath && Storage::disk('public')->exists($candidate)) {
                Storage::disk('public')->delete($candidate);
            }
        }
    }
}