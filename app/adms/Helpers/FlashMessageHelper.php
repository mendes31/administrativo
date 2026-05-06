<?php

declare(strict_types=1);

namespace App\adms\Helpers;

class FlashMessageHelper
{
    /**
     * Registra uma mensagem flash com escopo opcional de rota de destino.
     */
    public static function push(string $message, string $type = 'info', ?string $targetPath = null): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION['adms_flash_scoped'] = [
            'message' => $message,
            'type' => self::normalizeType($type),
            'target_path' => self::normalizePath($targetPath),
            'created_at' => time(),
        ];
    }

    /**
     * Consome a mensagem flash apenas quando o escopo da rota for compatível.
     *
     * @return array<string, mixed>|null
     */
    public static function consumeForCurrentRoute(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $payload = $_SESSION['adms_flash_scoped'] ?? null;
        unset($_SESSION['adms_flash_scoped']);

        if (!is_array($payload) || empty($payload['message'])) {
            return null;
        }

        $targetPath = self::normalizePath((string)($payload['target_path'] ?? ''));
        if ($targetPath !== '' && $targetPath !== self::getCurrentPath()) {
            return null;
        }

        return [
            'message' => (string)$payload['message'],
            'type' => self::normalizeType((string)($payload['type'] ?? 'info')),
        ];
    }

    private static function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        return match ($type) {
            'success', 'warning', 'danger', 'info' => $type,
            default => 'info',
        };
    }

    private static function normalizePath(?string $path): string
    {
        $raw = trim((string)$path);
        if ($raw === '') {
            return '';
        }

        $parsed = (string)(parse_url($raw, PHP_URL_PATH) ?? '');
        if ($parsed === '') {
            return '';
        }

        return trim($parsed, '/');
    }

    private static function getCurrentPath(): string
    {
        $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $parsed = (string)(parse_url($requestUri, PHP_URL_PATH) ?? '');
        return trim($parsed, '/');
    }
}

