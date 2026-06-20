<?php

namespace App\Support;

use Illuminate\Http\Request;

class CookieConsent
{
    public function __construct(private ?array $data) {}

    public static function fromRequest(?Request $request = null): self
    {
        $request ??= request();
        $raw = $request->cookie(config('cookies.consent_cookie', 'carmaxing_consent'));

        if (! is_string($raw) || $raw === '') {
            return new self(null);
        }

        $data = json_decode($raw, true);

        if (! is_array($data) || ($data['v'] ?? null) !== config('cookies.consent_version', 1)) {
            return new self(null);
        }

        return new self($data);
    }

    public function hasChoice(): bool
    {
        return $this->data !== null && isset($this->data['ts']);
    }

    public function allows(string $category): bool
    {
        if ($category === 'necessary') {
            return true;
        }

        if (! $this->hasChoice()) {
            return false;
        }

        return (bool) ($this->data[$category] ?? false);
    }

    public function allowsFunctional(): bool
    {
        return $this->allows('functional');
    }

    public function guestTheme(?string $fallback = 'light'): string
    {
        if (! $this->allowsFunctional()) {
            return $fallback;
        }

        $theme = request()->cookie('theme', $fallback);

        return in_array($theme, ['light', 'dark'], true) ? $theme : $fallback;
    }

    public function toFrontend(): array
    {
        return [
            'hasChoice' => $this->hasChoice(),
            'necessary' => true,
            'functional' => $this->allows('functional'),
            'analytics' => $this->allows('analytics'),
            'marketing' => $this->allows('marketing'),
            'version' => config('cookies.consent_version', 1),
            'categories' => config('cookies.categories', []),
        ];
    }

    public static function payload(bool $functional, bool $analytics, bool $marketing): array
    {
        return [
            'v' => config('cookies.consent_version', 1),
            'necessary' => true,
            'functional' => $functional,
            'analytics' => $analytics,
            'marketing' => $marketing,
            'ts' => time(),
        ];
    }
}