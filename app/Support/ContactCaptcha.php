<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

class ContactCaptcha
{
    private const SESSION_KEY = 'contact_captcha';

    /** @return array{question: string, left: int, right: int} */
    public function generate(): array
    {
        $left = random_int(2, 12);
        $right = random_int(2, 12);

        Session::put(self::SESSION_KEY, [
            'answer' => $left + $right,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        return [
            'question' => __('pages.contact.captcha_question', [
                'left' => $left,
                'right' => $right,
            ]),
            'left' => $left,
            'right' => $right,
        ];
    }

    public function validate(?string $answer): bool
    {
        $challenge = Session::get(self::SESSION_KEY);

        if (! is_array($challenge)) {
            return false;
        }

        if (($challenge['expires_at'] ?? 0) < now()->timestamp) {
            Session::forget(self::SESSION_KEY);

            return false;
        }

        $isValid = (int) $answer === (int) ($challenge['answer'] ?? -1);

        Session::forget(self::SESSION_KEY);

        return $isValid;
    }
}