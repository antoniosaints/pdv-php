<?php

declare(strict_types=1);

namespace Pdv\Http;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $post,
        private readonly array $server,
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $_GET,
            $_POST,
            $_SERVER,
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function input(string $key, ?string $default = null): ?string
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? $default;

        if ($value === null) {
            return null;
        }

        return trim((string) $value);
    }

    public function post(string $key, ?string $default = null): ?string
    {
        $value = $this->post[$key] ?? $default;

        if ($value === null) {
            return null;
        }

        return trim((string) $value);
    }

    public function query(string $key, ?string $default = null): ?string
    {
        $value = $this->query[$key] ?? $default;

        if ($value === null) {
            return null;
        }

        return trim((string) $value);
    }

    public function clientIp(): ?string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? null;

        return $ip === null ? null : (string) $ip;
    }
}
