<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'TikTrack'),
    'url' => rtrim(env('APP_URL', 'http://localhost:8080'), '/'),
    'saas_base_domains' => array_filter(array_map('trim', explode(',', env('SAAS_BASE_DOMAINS', 'localhost,127.0.0.1')))),
    'domain_verify_vps_ip' => env('DOMAIN_VERIFY_VPS_IP', ''),
    'domain_verify_local_mode' => env('DOMAIN_VERIFY_LOCAL_MODE', 'false') === 'true',
    'next_public_domain_verify_local_mode' => env('NEXT_PUBLIC_DOMAIN_VERIFY_LOCAL_MODE', 'false') === 'true',
    'next_public_vps_ip' => env('NEXT_PUBLIC_VPS_IP', ''),
    'custom_domain_scheme' => env('NEXT_PUBLIC_CUSTOM_DOMAIN_SCHEME', ''),
];
