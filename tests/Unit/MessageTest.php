<?php

declare(strict_types=1);

use Wobqqq\AegisIpBlocker\Support\Message;

it('returns the translated line', function (): void {
    expect(Message::get('aegis-ip-blocker::ip-blocker.label'))->toBe(__('aegis-ip-blocker::ip-blocker.label'))
        ->and(Message::get('aegis-ip-blocker::ip-blocker.label'))->not->toBe('aegis-ip-blocker::ip-blocker.label');
});

it('answers the key itself for a key that names a group of lines', function (): void {
    expect(Message::get('aegis-ip-blocker::ip-blocker.fields'))->toBe('aegis-ip-blocker::ip-blocker.fields');
});
