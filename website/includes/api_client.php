<?php

/**
 * API base URL can be overridden per environment, for example:
 * KOHICEBU_API_BASE_URL=https://example.com/KohiCebu/api
 */
function kohiApiBaseUrl(): string
{
    $configured = getenv('KOHICEBU_API_BASE_URL');
    $base = $configured !== false && trim($configured) !== ''
        ? $configured
        : 'http://127.0.0.1/KohiCebu/api';

    return rtrim($base, '/');
}

function kohiApiUrl(string $endpoint): string
{
    return kohiApiBaseUrl() . '/' . ltrim($endpoint, '/');
}

