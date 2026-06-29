<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Linha produtiva Tiaraju (Pasta 1 — linhas 19/20): TERCEIRO vs TIARAJU.
 *
 * Critério 7 (EE direta / conta 29-D): só SKUs produzidos na linha TIARAJU
 * entram na base de kWh; terceirizados (TERCEIRO) não consomem energia direta da planta.
 */
final class InvCostProductionLineHelper
{
    public const LINE_TERCEIRO = 'TERCEIRO';
    public const LINE_TIARAJU = 'TIARAJU';

    /** @var list<string> */
    public const ALLOWED_LINES = [self::LINE_TERCEIRO, self::LINE_TIARAJU];

    public static function normalize(mixed $value): ?string
    {
        $line = mb_strtoupper(trim((string)($value ?? '')), 'UTF-8');
        if ($line === '') {
            return null;
        }

        return in_array($line, self::ALLOWED_LINES, true) ? $line : null;
    }

    /**
     * Converte SAP OITM.U_LinhaProduto (ex.: Própria, Terceiro) para TERCEIRO/TIARAJU.
     */
    public static function fromSapLinhaProduto(mixed $value): ?string
    {
        $raw = mb_strtolower(trim((string)($value ?? '')), 'UTF-8');
        if ($raw === '') {
            return null;
        }

        if ($raw === 'p' || str_starts_with($raw, 'própri') || str_starts_with($raw, 'propri')) {
            return self::LINE_TIARAJU;
        }
        if ($raw === 't' || str_contains($raw, 'terceir')) {
            return self::LINE_TERCEIRO;
        }

        return self::normalize($value);
    }

    /**
     * Elegível para critério 7 (kWh linha produtiva / CVAR energia direta).
     */
    public static function isEligibleForDirectEnergy(mixed $productionLine): bool
    {
        return self::normalize($productionLine) === self::LINE_TIARAJU;
    }

    /**
     * @param array<string, mixed>|null $periodItem
     */
    public static function fromPeriodItem(?array $periodItem): ?string
    {
        if (!is_array($periodItem)) {
            return null;
        }

        return self::normalize($periodItem['production_line'] ?? null);
    }

    /**
     * @param array<string, mixed>|null $periodItem
     */
    public static function isPeriodItemEligibleForDirectEnergy(?array $periodItem): bool
    {
        return self::isEligibleForDirectEnergy(self::fromPeriodItem($periodItem));
    }
}
