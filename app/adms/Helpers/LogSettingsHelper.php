<?php

namespace App\adms\Helpers;

use App\adms\Models\Repository\AdmsLogSettingsRepository;

final class LogSettingsHelper
{
    private static ?bool $sessionDebugEnabled = null;

    public static function isSessionDebugEnabled(): bool
    {
        if (self::$sessionDebugEnabled !== null) {
            return self::$sessionDebugEnabled;
        }

        try {
            $repo = new AdmsLogSettingsRepository();
            self::$sessionDebugEnabled = $repo->isSessionDebugEnabled();
        } catch (\Throwable) {
            self::$sessionDebugEnabled = false;
        }

        return self::$sessionDebugEnabled;
    }
}

