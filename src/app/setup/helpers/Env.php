<?php

/**
 * Ortam değişkeni okuma. Değerler repoda tutulmaz.
 */
class Env
{
    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '' && $_ENV[$key] !== null) {
            return (string) $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER) && $_SERVER[$key] !== '' && $_SERVER[$key] !== null && !is_array($_SERVER[$key])) {
            return (string) $_SERVER[$key];
        }
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    }

    /**
     * @param array<int, string> $keys
     */
    public static function first(array $keys, ?string $default = null): ?string
    {
        foreach ($keys as $key) {
            $value = self::get($key, null);
            if ($value !== null) {
                return $value;
            }
        }
        return $default;
    }

    /**
     * @param string|array<int, string> $keys
     */
    public static function require($keys): string
    {
        $keys = is_array($keys) ? $keys : [$keys];
        $value = self::first($keys, null);
        if ($value === null) {
            error_log('Missing required environment variable: ' . implode(' or ', $keys));
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json');
            }
            echo json_encode([
                'status' => false,
                'message' => 'Server configuration error',
            ]);
            exit;
        }
        return $value;
    }

    public static function jwtSecret(): string
    {
        return self::require('JWT_SECRET');
    }
}
