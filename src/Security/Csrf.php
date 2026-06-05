<?php

declare(strict_types=1);

namespace Pdv\Security;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function token(): string
    {
        if (! isset($_SESSION[self::SESSION_KEY]) || ! is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public function validate(?string $token): bool
    {
        $known = $_SESSION[self::SESSION_KEY] ?? '';

        return is_string($known) && is_string($token) && hash_equals($known, $token);
    }
}
