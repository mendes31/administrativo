<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\AdmsNotificationSettingsRepository;

/**
 * Facade para leitura das configurações de notificações (cache por requisição no repositório).
 */
class NotificationSettingsService
{
    private static ?AdmsNotificationSettingsRepository $repo = null;

    public static function isEnabled(string $key): bool
    {
        return self::repo()->isEnabled($key);
    }

    public static function isAnyEnabled(string ...$keys): bool
    {
        foreach ($keys as $key) {
            if (self::isEnabled($key)) {
                return true;
            }
        }
        return false;
    }

    public static function clearCache(): void
    {
        AdmsNotificationSettingsRepository::clearCache();
    }

    private static function repo(): AdmsNotificationSettingsRepository
    {
        if (self::$repo === null) {
            self::$repo = new AdmsNotificationSettingsRepository();
        }
        return self::$repo;
    }
}
