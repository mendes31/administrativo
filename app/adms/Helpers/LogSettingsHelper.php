<?php

namespace App\adms\Helpers;

use App\adms\Models\Repository\AdmsLogSettingsRepository;

final class LogSettingsHelper
{
    private static ?bool $sessionDebugEnabled = null;
    private static ?bool $slowProfilerEnabled = null;
    private static ?int $slowProfilerThresholdMs = null;
    private static ?int $slowProfilerRetentionDays = null;

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

    public static function isSlowProfilerEnabled(): bool
    {
        if (self::$slowProfilerEnabled !== null) {
            return self::$slowProfilerEnabled;
        }
        try {
            $repo = new AdmsLogSettingsRepository();
            self::$slowProfilerEnabled = $repo->isSlowProfilerEnabled();
        } catch (\Throwable) {
            self::$slowProfilerEnabled = false;
        }
        return self::$slowProfilerEnabled;
    }

    public static function getSlowProfilerThresholdMs(): int
    {
        if (self::$slowProfilerThresholdMs !== null) {
            return self::$slowProfilerThresholdMs;
        }
        try {
            $repo = new AdmsLogSettingsRepository();
            self::$slowProfilerThresholdMs = $repo->getSlowProfilerThresholdMs();
        } catch (\Throwable) {
            self::$slowProfilerThresholdMs = 700;
        }
        return self::$slowProfilerThresholdMs;
    }

    public static function getSlowProfilerRetentionDays(): int
    {
        if (self::$slowProfilerRetentionDays !== null) {
            return self::$slowProfilerRetentionDays;
        }
        try {
            $repo = new AdmsLogSettingsRepository();
            self::$slowProfilerRetentionDays = $repo->getSlowProfilerRetentionDays();
        } catch (\Throwable) {
            self::$slowProfilerRetentionDays = 7;
        }
        return self::$slowProfilerRetentionDays;
    }
}

