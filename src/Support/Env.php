<?php

declare(strict_types=1);

namespace Pdv\Support;

use Dotenv\Dotenv;

final class Env
{
    public static function load(string $rootPath): void
    {
        $envFile = $rootPath . '/.env';

        if (is_file($envFile)) {
            Dotenv::createImmutable($rootPath)->safeLoad();
        }

        date_default_timezone_set(self::get('APP_TIMEZONE', 'America/Sao_Paulo'));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function path(string $rootPath, string $key, string $default): string
    {
        $path = self::get($key, $default) ?? $default;

        if (preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($rootPath, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
