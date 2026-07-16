<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Normalização e formatação do código automático de equipamentos SST (PREFIXO + 5 dígitos).
 */
final class SstEquipamentoCodigoHelper
{
    public const PREFIXO_LEN = 3;

    public const SEQUENCIA_LEN = 5;

    public static function normalizePrefixo(?string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', (string) $value) ?? '';

        return strtoupper(substr($clean, 0, self::PREFIXO_LEN));
    }

    public static function isValidPrefixo(?string $value): bool
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', (string) $value) ?? '';

        return strlen($clean) === self::PREFIXO_LEN;
    }

    public static function format(string $prefixo, int $numero): string
    {
        $prefixo = self::normalizePrefixo($prefixo);

        return $prefixo . str_pad((string) max(0, $numero), self::SEQUENCIA_LEN, '0', STR_PAD_LEFT);
    }

    /**
     * Extrai o número sequencial de um código no padrão PREFIXO#####.
     */
    public static function extractNumero(string $codigo, string $prefixo): ?int
    {
        $prefixo = self::normalizePrefixo($prefixo);
        $codigo = strtoupper(trim($codigo));
        if ($prefixo === '' || !preg_match('/^' . preg_quote($prefixo, '/') . '(\d{' . self::SEQUENCIA_LEN . '})$/', $codigo, $m)) {
            return null;
        }

        return (int) $m[1];
    }
}
