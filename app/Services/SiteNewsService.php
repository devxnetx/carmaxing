<?php

namespace App\Services;

use App\Mail\SiteNewsMail;
use App\Models\SiteNewsPost;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SiteNewsService
{
    public const TARGET_SUBSCRIBERS = 'subscribers';

    public const TARGET_NON_SUBSCRIBERS = 'non_subscribers';

    public const TARGET_ALL = 'all';

    /** @return list<string> */
    public static function recipientTargets(): array
    {
        return [
            self::TARGET_SUBSCRIBERS,
            self::TARGET_NON_SUBSCRIBERS,
            self::TARGET_ALL,
        ];
    }

    public function send(SiteNewsPost $post, string $target = self::TARGET_SUBSCRIBERS): int
    {
        $sent = 0;

        $this->recipientQuery($target)
            ->chunkById(50, function ($users) use ($post, &$sent) {
                foreach ($users as $user) {
                    Mail::to($user->email)->send(new SiteNewsMail($post, $user));
                    $sent++;
                }
            });

        $post->update([
            'sent_at' => now(),
            'recipient_count' => $sent,
        ]);

        return $sent;
    }

    /** @return \Illuminate\Database\Eloquent\Builder<User> */
    private function recipientQuery(string $target)
    {
        $query = User::query()->whereNotNull('email');

        return match ($target) {
            self::TARGET_NON_SUBSCRIBERS => $query->where('subscribe_news', false),
            self::TARGET_ALL => $query,
            default => $query->where('subscribe_news', true),
        };
    }
}