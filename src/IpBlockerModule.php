<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker;

use Override;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\Aegis\Settings\Field;
use Wobqqq\AegisIpBlocker\Rules\DoesNotBlockAdministrator;
use Wobqqq\AegisIpBlocker\Rules\IpOrSubnet;
use Wobqqq\AegisIpBlocker\Support\Message;

final readonly class IpBlockerModule implements Module
{
    public const string KEY = 'ip-blocker';

    public function __construct(private Administrator $administrator)
    {
    }

    #[Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[Override]
    public function label(): string
    {
        return Message::get('aegis-ip-blocker::ip-blocker.label');
    }

    #[Override]
    public function description(): string
    {
        return Message::get('aegis-ip-blocker::ip-blocker.description');
    }

    #[Override]
    public function defaults(): array
    {
        return [
            'enabled' => false,
            'view' => IpBlockerSettings::DEFAULT_VIEW,
            'ips' => [],
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'view' => ['required', 'string', 'max:100', 'regex:' . IpBlockerSettings::VIEW_PATTERN],
            'ips' => ['present', 'array', 'max:' . IpBlockerSettings::MAX_ENTRIES, new DoesNotBlockAdministrator($this->administrator->ip())],
            'ips.*' => ['array:ip,note'],
            'ips.*.ip' => ['nullable', 'string', 'max:100', new IpOrSubnet()],
            'ips.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return list<Field>
     */
    #[Override]
    public function fields(): array
    {
        $label = static fn (string $name): string => Message::get('aegis-ip-blocker::ip-blocker.fields.' . $name);
        $help = static fn (string $name): string => Message::get('aegis-ip-blocker::ip-blocker.help.' . $name);

        return [
            Field::toggle('enabled', $label('enabled'), $help('enabled')),
            Field::text('view', $label('view'), $help('view'), IpBlockerSettings::DEFAULT_VIEW),
            Field::table('ips', $label('ips'), [
                Field::text('ip', $label('ip'), placeholder: '203.0.113.7 / 198.51.100.0/24 / 2001:db8::/32'),
                Field::text('note', $label('note'), placeholder: $label('note_placeholder')),
            ], $help('ips')),
        ];
    }

    #[Override]
    public function status(array $values): CheckResult
    {
        $settings = IpBlockerSettings::fromArray($values);
        $label = $this->label();

        if (!$settings->enabled) {
            return CheckResult::warn(self::KEY, $label, Message::get('aegis-ip-blocker::ip-blocker.status.off'));
        }

        return CheckResult::pass(self::KEY, $label, trans_choice('aegis-ip-blocker::ip-blocker.status.on', $settings->blockList->count()));
    }
}
