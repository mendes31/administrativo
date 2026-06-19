<?php

declare(strict_types=1);

namespace App\adms\Helpers;

final class SstAsoStatusHelper
{
    public const AGUARDANDO_EXAMES = 'Aguardando exames';
    public const CONCLUIDO = 'Concluído';

    public static function isAguardando(?array $aso): bool
    {
        return is_array($aso) && ($aso['status'] ?? '') === self::AGUARDANDO_EXAMES;
    }

    public static function isConcluido(?array $aso): bool
    {
        if (!is_array($aso)) {
            return false;
        }
        $status = $aso['status'] ?? self::CONCLUIDO;

        return $status === self::CONCLUIDO || $status === '';
    }

    public static function badgeClass(?string $status): string
    {
        return match ($status) {
            self::AGUARDANDO_EXAMES => 'warning',
            self::CONCLUIDO => 'success',
            default => 'secondary',
        };
    }

    public static function label(?string $status): string
    {
        if ($status === null || $status === '') {
            return self::CONCLUIDO;
        }

        return $status;
    }
}
