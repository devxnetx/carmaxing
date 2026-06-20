<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BulgarianPhoneLocal implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! PhoneNumber::isValidLocalPart(is_string($value) ? $value : null)) {
            $fail(__('messages.phone_invalid'));
        }
    }
}