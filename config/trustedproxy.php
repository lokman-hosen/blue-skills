<?php

/*
| Proxies in front of the app, e.g. Cloudflare and the host's load balancer (D-108). Without
| them every visitor seems to come from the proxy's IP, which breaks per-IP rate limits, and
| HTTPS isn't detected (HSTS, secure cookies). `*` trusts the connecting proxy; use it only
| when the app can't be reached except through the proxy. Comma-separated IPs or CIDRs
| otherwise. Empty = no proxy (local).
*/

$proxies = (string) env('TRUSTED_PROXIES', '');

return [
    'proxies' => match (trim($proxies)) {
        '' => null,
        '*', '**' => trim($proxies),
        default => array_values(array_filter(array_map('trim', explode(',', $proxies)))),
    },
];
