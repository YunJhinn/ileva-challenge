<?php

declare(strict_types=1);

namespace App\Http;

/**
 * A minimal, framework-free wrapper around the incoming HTTP request.
 *
 * The API intentionally has no third-party dependencies at runtime (see the
 * README for why) so it has no external HTTP layer to lean on either -
 * this class + Response + Router are the whole "framework".
 */
final class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, mixed>  $body
     */
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
    ) {
    }

    /**
     * Named constructor used by tests to build a Request without going
     * through real superglobals / php://input.
     *
     * @param array<string, string> $query
     * @param array<string, mixed>  $body
     */
    public static function create(string $method, string $path = '/', array $query = [], array $body = []): self
    {
        return new self(strtoupper($method), $path, $query, $body);
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $path = (string) parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/');
        $path = $path === '' ? '/' : $path;

        $query = [];
        $queryString = parse_url($uri, PHP_URL_QUERY);
        if (is_string($queryString)) {
            parse_str($queryString, $query);
        }

        return new self($method, $path, $query, self::parseBody($method));
    }

    /** @return array<string, mixed> */
    private static function parseBody(string $method): array
    {
        if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return [];
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $raw = file_get_contents('php://input') ?: '';

        if ($raw !== '' && str_contains($contentType, 'application/json')) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        // Fallback: classic form-encoded bodies (still handy for quick manual testing with curl -d).
        return $_POST ?: [];
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function body(): array
    {
        return $this->body;
    }
}
