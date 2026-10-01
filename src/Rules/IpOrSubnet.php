<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Override;
use Wobqqq\AegisIpBlocker\Support\IpAddress;

final readonly class IpOrSubnet implements ValidationRule
{
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || IpAddress::entry($value) === null) {
            $fail('aegis-ip-blocker::ip-blocker.validation.ip')->translate();
        }
    }
}
