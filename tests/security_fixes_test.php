<?php

require_once __DIR__ . '/../src/app/setup/helpers/PasswordPolicy.php';
require_once __DIR__ . '/../src/app/setup/helpers/ShareLinkPolicy.php';
require_once __DIR__ . '/../src/app/setup/helpers/Env.php';
require_once __DIR__ . '/../src/app/setup/helpers/HttpSecurity.php';

$failures = 0;

function check(bool $condition, string $message): void
{
    global $failures;
    if ($condition) {
        echo "ok  $message\n";
        return;
    }
    $failures++;
    echo "FAIL $message\n";
}

check(PasswordPolicy::violation('') === 'Password is required.', 'empty password');
check(PasswordPolicy::violation('1234567') === 'Password must be at least 8 characters.', 'short password');
check(PasswordPolicy::violation('123456') !== null, 'six digits rejected');
check(PasswordPolicy::violation('12345678') === 'Password cannot be only digits.', 'digits only');
check(PasswordPolicy::violation('password') === null, 'eight letter password allowed');
check(PasswordPolicy::violation('abc12345') === null, 'mixed password allowed');
check(PasswordPolicy::MIN_LENGTH === 8, 'minimum length is 8');

$now = strtotime('2026-09-27 12:00:00');
check(
    ShareLinkPolicy::acceptanceBlockReason('pending', '2026-09-27 11:00:00', null, $now) !== null,
    'expired pending invite is blocked'
);
check(
    ShareLinkPolicy::acceptanceBlockReason('pending', '2026-09-28 12:00:00', null, $now) === null,
    'fresh pending invite is allowed'
);
check(
    ShareLinkPolicy::acceptanceBlockReason('revoked', '2026-09-28 12:00:00', null, $now) !== null,
    'revoked invite is blocked'
);
check(
    ShareLinkPolicy::acceptanceBlockReason('declined', '2026-09-28 12:00:00', null, $now) !== null,
    'declined invite is blocked'
);
check(
    ShareLinkPolicy::acceptanceBlockReason('accepted', '2026-09-27 11:00:00', $now - 10, $now) === null,
    'accepted membership stays usable after link expiry'
);
check(
    ShareLinkPolicy::acceptanceBlockReason('pending', '2026-09-28 12:00:00', $now - 5, $now) !== null,
    'pending invite with past jwt exp is blocked'
);
check(ShareLinkPolicy::tokenCheckAllowed('revoked', '2026-09-28 12:00:00', null, $now) === false, 'revoked token check fails');
check(ShareLinkPolicy::tokenCheckAllowed('pending', '2026-09-28 12:00:00', null, $now) === true, 'pending token check passes');
check(ShareLinkPolicy::tokenCheckAllowed('pending', '2026-09-27 11:00:00', null, $now) === false, 'expired token check fails');

putenv('CORS_ALLOWED_ORIGINS');
unset($_ENV['CORS_ALLOWED_ORIGINS'], $_SERVER['CORS_ALLOWED_ORIGINS']);
check(HttpSecurity::resolveAllowOrigin('https://app.schopi.com') === 'https://app.schopi.com', 'app origin allowed');
check(HttpSecurity::resolveAllowOrigin('https://www.schopi.com') === 'https://www.schopi.com', 'www origin allowed');
check(HttpSecurity::resolveAllowOrigin('https://evil.example') === null, 'unknown origin rejected');
check(HttpSecurity::resolveAllowOrigin(null) === null, 'missing origin rejected');
check(HttpSecurity::resolveAllowOrigin('*') === null, 'star origin rejected');
check(!in_array('*', HttpSecurity::allowedOrigins(), true), 'allowlist has no star');

$_ENV['CORS_ALLOWED_ORIGINS'] = 'capacitor://localhost, * ,ionic://localhost';
check(HttpSecurity::resolveAllowOrigin('capacitor://localhost') === 'capacitor://localhost', 'env origin added');
check(HttpSecurity::resolveAllowOrigin('ionic://localhost') === 'ionic://localhost', 'second env origin added');
check(HttpSecurity::resolveAllowOrigin('https://app.schopi.com') === 'https://app.schopi.com', 'default origin kept when env adds more');
check(!in_array('*', HttpSecurity::allowedOrigins(), true), 'star in env is ignored');
unset($_ENV['CORS_ALLOWED_ORIGINS']);

$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https,http';
check(HttpSecurity::forwardedProto() === 'https', 'first forwarded proto is used');
check(HttpSecurity::isHttps() === true, 'https forwarded proto counts as https');
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
unset($_SERVER['HTTPS']);
check(HttpSecurity::isHttps() === false, 'http forwarded proto is not https');
check(HttpSecurity::forwardedProto() === 'http', 'http proto detected for redirect');

if ($failures > 0) {
    fwrite(STDERR, "$failures assertion(s) failed\n");
    exit(1);
}

echo "all assertions passed\n";
exit(0);
