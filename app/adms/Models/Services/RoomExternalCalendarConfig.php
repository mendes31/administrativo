<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\RoomCalendarSettingsRepository;

/**
 * Lê a configuração de calendários externos (Outlook/Google) guardada na base — tela "Integração calendário (Salas)".
 */
final class RoomExternalCalendarConfig
{
    private static ?array $cache = null;

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function row(): array
    {
        if (self::$cache === null) {
            self::$cache = (new RoomCalendarSettingsRepository())->getSingleton();
        }

        return self::$cache;
    }

    public static function isOutlookSyncEnabled(): bool
    {
        return !empty(self::row()['outlook_sync_enabled']);
    }

    public static function isGoogleSyncEnabled(): bool
    {
        return !empty(self::row()['google_sync_enabled']);
    }

    public static function outlookTenantId(): string
    {
        return trim((string) (self::row()['outlook_tenant_id'] ?? ''));
    }

    public static function outlookClientId(): string
    {
        return trim((string) (self::row()['outlook_client_id'] ?? ''));
    }

    public static function googleClientId(): string
    {
        return trim((string) (self::row()['google_client_id'] ?? ''));
    }

    public static function anyExternalSyncConfigured(): bool
    {
        return self::isOutlookSyncEnabled() || self::isGoogleSyncEnabled();
    }
}
