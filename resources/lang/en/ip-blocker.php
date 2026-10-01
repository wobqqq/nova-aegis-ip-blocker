<?php

declare(strict_types=1);

return [
    'label' => 'IP Blocker',
    'description' => 'Refuses every request from the listed addresses and subnets, on the site and in Nova, with a 403 page.',

    'fields' => [
        'enabled' => 'Enable IP Blocker',
        'view' => 'Page shown to a blocked visitor',
        'ips' => 'Blocked addresses and subnets',
        'ip' => 'Address or subnet',
        'note' => 'Note',
        'note_placeholder' => 'Why it is blocked',
    ],

    'help' => [
        'enabled' => 'Requests from the listed addresses answer 403 while this is on. Turn it off from the console with php artisan aegis:ip-blocker:disable.',
        'view' => 'A Blade view name, such as errors.403 or aegis-ip-blocker::blocked. The default page is used when the view does not exist.',
        'ips' => 'IPv4 or IPv6 addresses (203.0.113.7, 2001:db8::1) and subnets in CIDR notation (198.51.100.0/24, 2001:db8::/32). A list that covers your own address is refused.',
    ],

    'status' => [
        'on' => '{0} On, but no address is listed.|{1} On: blocking 1 address or subnet.|[2,*] On: blocking :count addresses and subnets.',
        'off' => 'Off: no address is blocked.',
    ],

    'checks' => [
        'client_ip' => [
            'label' => 'Visitor IP address',
            'pass' => 'The visitor\'s address comes from the connection or a trusted proxy.',
            'untrusted' => 'Requests arrive with :headers from a proxy that is not trusted, so every visitor has the proxy\'s address. Configure the trusted proxies before blocking addresses.',
        ],
    ],

    'validation' => [
        'ip' => 'The :attribute must be an IPv4 or IPv6 address, or a subnet in CIDR notation.',
        'administrator' => 'The list blocks your own address (:ip). Remove the entry that covers it.',
    ],

    'blocked' => [
        'title' => 'Access denied',
        'message' => 'Requests from your network are not allowed on this site.',
    ],
];
