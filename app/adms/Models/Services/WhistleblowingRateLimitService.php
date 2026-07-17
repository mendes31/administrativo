<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingConfigRepository;

/**
 * Rate limit por IP para o canal público (acompanhamento, registro e respostas).
 */
final class WhistleblowingRateLimitService
{
    private const CACHE_DIR = 'storage/cache/system';

    public function __construct(
        private readonly WhistleblowingConfigRepository $configRepo = new WhistleblowingConfigRepository()
    ) {
    }

    public function isBlocked(string $scope): bool
    {
        $data = $this->read($scope);
        if ($data === null) {
            return false;
        }

        $windowSeconds = $this->getWindowMinutes() * 60;
        $attempts = (int) ($data['attempts'] ?? 0);
        $firstAt = (int) ($data['first_at'] ?? 0);
        $blockedUntil = (int) ($data['blocked_until'] ?? 0);

        if ($blockedUntil > time()) {
            return true;
        }

        if ($firstAt > 0 && (time() - $firstAt) > $windowSeconds) {
            $this->clear($scope);

            return false;
        }

        return $attempts >= $this->getMaxAttempts();
    }

    public function secondsUntilUnblock(string $scope): int
    {
        $data = $this->read($scope);
        if ($data === null) {
            return 0;
        }
        $blockedUntil = (int) ($data['blocked_until'] ?? 0);
        if ($blockedUntil <= time()) {
            return 0;
        }

        return $blockedUntil - time();
    }

    public function recordFailedAttempt(string $scope): void
    {
        $this->recordAttempt($scope);
    }

    /** Conta qualquer tentativa (falha ou envio válido) dentro da janela. */
    public function recordAttempt(string $scope): void
    {
        $now = time();
        $data = $this->read($scope) ?? ['attempts' => 0, 'first_at' => $now, 'blocked_until' => 0];
        $windowSeconds = $this->getWindowMinutes() * 60;

        if (($data['first_at'] ?? 0) > 0 && ($now - (int) $data['first_at']) > $windowSeconds) {
            $data = ['attempts' => 0, 'first_at' => $now, 'blocked_until' => 0];
        }

        $data['attempts'] = (int) ($data['attempts'] ?? 0) + 1;
        if ($data['attempts'] >= $this->getMaxAttempts()) {
            $data['blocked_until'] = $now + $windowSeconds;
        }

        $this->write($scope, $data);
    }

    public function clear(string $scope): void
    {
        $path = $this->filePath($scope);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function getMaxAttempts(): int
    {
        $v = (int) $this->configRepo->getRateLimitMaxAttempts();

        return max(3, min(20, $v > 0 ? $v : 5));
    }

    private function getWindowMinutes(): int
    {
        $v = (int) $this->configRepo->getRateLimitWindowMinutes();

        return max(5, min(120, $v > 0 ? $v : 15));
    }

    private function filePath(string $scope): string
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3);
        $dir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::CACHE_DIR);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $hash = $this->clientHashForScope($scope);

        return $dir . DIRECTORY_SEPARATOR . 'whistleblowing_rl_' . $hash . '.json';
    }

    private function clientHashForScope(string $scope): string
    {
        $ip = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return hash('sha256', $ip . '|' . $scope);
    }

    /**
     * @return array{attempts?: int, first_at?: int, blocked_until?: int}|null
     */
    private function read(string $scope): ?array
    {
        $path = $this->filePath($scope);
        if (!is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param array{attempts?: int, first_at?: int, blocked_until?: int} $data
     */
    private function write(string $scope, array $data): void
    {
        $path = $this->filePath($scope);
        @file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR));
    }
}
