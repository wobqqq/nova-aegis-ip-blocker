<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Wobqqq\AegisIpBlocker\BlockList;
use Wobqqq\AegisIpBlocker\IpBlockerSettings;

/**
 * Refuses a list that covers the address of the administrator saving it, by address or by subnet.
 */
final readonly class DoesNotBlockAdministrator implements ValidationRule
{
    public function __construct(private ?string $administratorIp)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->administratorIp === null) {
            return;
        }

        $blockList = BlockList::fromEntries(IpBlockerSettings::entries($value));

        if ($blockList->contains($this->administratorIp)) {
            $fail('aegis-ip-blocker::ip-blocker.validation.administrator')->translate(['ip' => $this->administratorIp]);
        }
    }
}
