<?php

return [
    /*
     * Set trusted proxy IP addresses.
     *
     * Both IPv4 and IPv6 addresses are supported, along with CIDR notation.
     *
     * The "*" character is syntactic sugar within TrustedProxy to trust any
     * proxy that connects directly to your server, a requirement when you
     * cannot know the address of your proxy (e.g., if using Cloudflare).
     * 
     * Since we're using Docker Nginx, we trust the Docker network
     */
    'proxies' => [
        '172.16.0.0/12', // Docker's default network range
        '127.0.0.1',     // Localhost
        'host.docker.internal', // Docker host
    ],

    /*
     * Which headers to use to detect proxy forwarded data
     *
     * Options include:
     *
     * - Illuminate\Http\Request::HEADER_X_FORWARDED_ALL (use all x-forwarded-* headers to establish trust)
     * - Illuminate\Http\Request::HEADER_FORWARDED (use the FORWARDED header to establish trust)
     * - Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB (If you are using AWS Elastic Load Balancer)
     *
     * @link https://symfony.com/doc/current/deployment/proxies.html
     */
    'headers' => \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR |
        \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST |
        \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT |
        \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO |
        \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PREFIX,
];