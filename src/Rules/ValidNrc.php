<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit\Rules;

use Illuminate\Contracts\Validation\Rule;
use Thuraaung\NrcKit\Nrc;

final class ValidNrc implements Rule
{
    public function passes($attribute, $value): bool
    {
        return Nrc::from($value)->isValid();
    }

    public function message(): string
    {
        return 'The :attribute must be a valid Myanmar NRC format and state/township pair.';
    }
}
