<?php

/**
 * Kayıt, sıfırlama ve şifre değiştirme için ortak sunucu tarafı kural.
 * Girişte uygulanmaz; mevcut kısa şifreler oturum açabilsin.
 */
class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    public static function violation(?string $password): ?string
    {
        $password = trim((string) $password);
        if ($password === '') {
            return 'Password is required.';
        }
        if (strlen($password) < self::MIN_LENGTH) {
            return 'Password must be at least ' . self::MIN_LENGTH . ' characters.';
        }
        if (preg_match('/^\d+$/', $password) === 1) {
            return 'Password cannot be only digits.';
        }
        return null;
    }
}
