<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Normalização e rótulos para campos demográficos do cadastro de utilizador.
 */
final class UserFormHelper
{
    public static function normalizeSexo(mixed $value): ?string
    {
        $v = strtoupper(trim((string) $value));

        return in_array($v, ['M', 'F', 'O'], true) ? $v : null;
    }

    public static function normalizeFilhos(mixed $value): ?string
    {
        $v = strtoupper(trim((string) $value));

        return in_array($v, ['S', 'N'], true) ? $v : null;
    }

    public static function sexoLabel(?string $code): string
    {
        return match ($code) {
            'M' => 'Masculino',
            'F' => 'Feminino',
            'O' => 'Outros',
            default => 'Não informado',
        };
    }

    public static function filhosLabel(?string $code): string
    {
        return match ($code) {
            'S' => 'Sim',
            'N' => 'Não',
            default => 'Não informado',
        };
    }
}
