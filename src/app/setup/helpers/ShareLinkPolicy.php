<?php

/**
 * Paylaşım bağlantısı: süre ve iptal kararı. Veritabanı çağırmaz.
 * Kabul edilmiş üyelik, süre dolunca liste erişiminden düşmez;
 * süre ve iptal, bekleyen bağlantı ile token kontrolüne uygulanır.
 */
class ShareLinkPolicy
{
    public static function isTerminalStatus(?string $status): bool
    {
        return in_array(strtolower((string) $status), ['revoked', 'declined'], true);
    }

    public static function isPast(?string $mysqlDate, int $now): bool
    {
        if ($mysqlDate === null || trim($mysqlDate) === '') {
            return false;
        }
        $timestamp = strtotime($mysqlDate);
        if ($timestamp === false) {
            return false;
        }
        return $timestamp <= $now;
    }

    public static function acceptanceBlockReason(?string $status, ?string $expiredDate, ?int $jwtExp, ?int $now = null): ?string
    {
        $now = $now ?? time();
        if (self::isTerminalStatus($status)) {
            return 'Paylaşım bağlantısı iptal edilmiş.';
        }
        if (strtolower((string) $status) === 'accepted') {
            return null;
        }
        if (self::isPast($expiredDate, $now) || ($jwtExp !== null && $jwtExp <= $now)) {
            return 'Paylaşım bağlantısının süresi dolmuş.';
        }
        return null;
    }

    public static function tokenCheckAllowed(?string $status, ?string $expiredDate, ?int $jwtExp, ?int $now = null): bool
    {
        $now = $now ?? time();
        if (self::isTerminalStatus($status)) {
            return false;
        }
        if (self::isPast($expiredDate, $now)) {
            return false;
        }
        if ($jwtExp !== null && $jwtExp <= $now) {
            return false;
        }
        return true;
    }
}
