<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * CAPTCHA simples (soma) + honeypot + rate limit em ficheiro, sem dependência de hCaptcha.
 */
final class LgpdPublicCaptchaService
{
    private const SESSION_KEY = 'lgpd_public_captcha';
    private const RATE_DIR = 'storage/cache/system';
    private const MAX_ATTEMPTS = 8;
    private const WINDOW_SECONDS = 900;

    public function generateQuestion(): string
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $_SESSION[self::SESSION_KEY] = (string) ($a + $b);

        return "Quanto é {$a} + {$b}?";
    }

    public function verify(string $answer): bool
    {
        $expected = (string) ($_SESSION[self::SESSION_KEY] ?? '');
        unset($_SESSION[self::SESSION_KEY]);
        if ($expected === '') {
            return false;
        }

        return hash_equals($expected, trim($answer));
    }

    public function honeypotFilled(): bool
    {
        return trim((string) ($_POST['website'] ?? '')) !== '';
    }

    public function isBlocked(string $scope): bool
    {
        $data = $this->readRate($scope);
        if ($data === null) {
            return false;
        }
        if ((int) ($data['blocked_until'] ?? 0) > time()) {
            return true;
        }
        if ((time() - (int) ($data['first_at'] ?? 0)) > self::WINDOW_SECONDS) {
            $this->clearRate($scope);

            return false;
        }

        return (int) ($data['attempts'] ?? 0) >= self::MAX_ATTEMPTS;
    }

    public function secondsUntilUnblock(string $scope): int
    {
        $data = $this->readRate($scope);
        $until = (int) ($data['blocked_until'] ?? 0);

        return max(0, $until - time());
    }

    public function recordAttempt(string $scope): void
    {
        $now = time();
        $data = $this->readRate($scope) ?? ['attempts' => 0, 'first_at' => $now, 'blocked_until' => 0];
        if ((time() - (int) ($data['first_at'] ?? 0)) > self::WINDOW_SECONDS) {
            $data = ['attempts' => 0, 'first_at' => $now, 'blocked_until' => 0];
        }
        $data['attempts'] = (int) ($data['attempts'] ?? 0) + 1;
        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            $data['blocked_until'] = $now + self::WINDOW_SECONDS;
        }
        $this->writeRate($scope, $data);
    }

    private function rateFile(string $scope): string
    {
        $ip = preg_replace('/[^0-9a-fA-F:.]/', '', (string) ($_SERVER['REMOTE_ADDR'] ?? '0')) ?: '0';
        $root = dirname(__DIR__, 4);
        $dir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::RATE_DIR);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir . DIRECTORY_SEPARATOR . 'lgpd_rl_' . md5($scope . '|' . $ip) . '.json';
    }

    private function readRate(string $scope): ?array
    {
        $file = $this->rateFile($scope);
        if (!is_file($file)) {
            return null;
        }
        $json = json_decode((string) file_get_contents($file), true);

        return is_array($json) ? $json : null;
    }

    private function writeRate(string $scope, array $data): void
    {
        @file_put_contents($this->rateFile($scope), json_encode($data));
    }

    private function clearRate(string $scope): void
    {
        $file = $this->rateFile($scope);
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
