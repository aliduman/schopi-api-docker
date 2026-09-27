<?php

require_once __DIR__ . '/Env.php';

/**
 * HTTPS, güvenlik başlıkları ve CORS allowlist.
 * Kimlik doğrulama Bearer JWT ile yapılır; PHP oturumu başlatılmaz.
 */
class HttpSecurity
{
    public const CSP = "default-src 'none'; frame-ancestors 'none'; base-uri 'none'";

    public static function bootstrapRequest(): void
    {
        self::enforceHttps();
        self::sendBaselineHeaders();
        self::configureSessionCookies();
        self::applyCors();
    }

    public static function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';
        if ($https !== '' && strtolower((string) $https) !== 'off') {
            return true;
        }
        return self::forwardedProto() === 'https';
    }

    public static function forwardedProto(): string
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (!is_string($forwarded) || $forwarded === '') {
            return '';
        }
        $first = strtolower(trim(explode(',', $forwarded)[0]));
        return $first;
    }

    public static function enforceHttps(): void
    {
        if (self::forwardedProto() !== 'http') {
            return;
        }
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!is_string($host) || !preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) {
            http_response_code(400);
            exit;
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (!is_string($uri) || $uri === '') {
            $uri = '/';
        }
        $uri = str_replace(["\r", "\n"], '', $uri);
        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }

    public static function sendBaselineHeaders(): void
    {
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: ' . self::CSP);
        header('Referrer-Policy: no-referrer');
        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    /**
     * Oturum çerezi bayrakları. session_start çağrılmaz;
     * başka bir yol oturum açarsa Secure, HttpOnly ve SameSite=Lax uygulanır.
     */
    public static function configureSessionCookies(): void
    {
        $secure = self::isHttps();
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function defaultOrigins(): array
    {
        return [
            'https://app.schopi.com',
            'https://www.schopi.com',
            'https://schopi.com',
            'http://localhost:4200',
            'http://127.0.0.1:4200',
            'http://localhost:8100',
            'http://127.0.0.1:8100',
            'http://localhost:3000',
            'http://127.0.0.1:3000',
        ];
    }

    /**
     * CORS_ALLOWED_ORIGINS virgülle ek origin ekler. Yıldız kabul edilmez.
     * Yerel iOS webview origin'i bu değişkenle eklenir. Native istemci Origin göndermez.
     *
     * @return array<int, string>
     */
    public static function allowedOrigins(): array
    {
        $origins = self::defaultOrigins();
        $extra = Env::get('CORS_ALLOWED_ORIGINS', '');
        if ($extra === null || $extra === '') {
            return $origins;
        }
        foreach (explode(',', $extra) as $origin) {
            $origin = trim($origin);
            if ($origin === '' || $origin === '*') {
                continue;
            }
            $origins[] = $origin;
        }
        return array_values(array_unique($origins));
    }

    public static function resolveAllowOrigin(?string $requestOrigin): ?string
    {
        if ($requestOrigin === null || $requestOrigin === '') {
            return null;
        }
        return in_array($requestOrigin, self::allowedOrigins(), true) ? $requestOrigin : null;
    }

    public static function applyCors(): void
    {
        header('Vary: Origin');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = self::resolveAllowOrigin(is_string($origin) ? $origin : '');
        if ($allowed !== null) {
            header('Access-Control-Allow-Origin: ' . $allowed);
            header('Access-Control-Allow-Credentials: true');
        }
        header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, Access-Control-Max-Age, Access-Control-Allow-Credentials, Access-Control-Allow-Methods, Access-Control-Allow-Origin, Access-Control-Allow-Headers');
        header('Access-Control-Max-Age: 600');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
