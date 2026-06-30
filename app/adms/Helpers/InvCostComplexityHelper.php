<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Services\InvComplexityLevelFactorService;

/**
 * Complexidade (critérios 4 e 6) — alinhado SAP UDF Complexidade + planilha Tiaraju.
 *
 * SAP (1 caractere): N, B, M, A → convertidos para NA, BAIXA, MEDIA, ALTA no item.
 * Vazio/desconhecido no SAP → NA (multiplicador 0).
 * Multiplicadores: inv_complexity_level_factors.
 */
final class InvCostComplexityHelper
{
    public const LEVEL_NA = 'NA';
    public const LEVEL_BAIXA = 'BAIXA';
    public const LEVEL_MEDIA = 'MEDIA';
    public const LEVEL_ALTA = 'ALTA';

    /** @var list<string> Códigos gravados no sistema (cadastro / sync). */
    public const ALLOWED = [self::LEVEL_NA, self::LEVEL_BAIXA, self::LEVEL_MEDIA, self::LEVEL_ALTA];

    /** @var list<string> Valores válidos da UDF SAP (comprimento 1). */
    public const SAP_UDF_CODES = ['N', 'B', 'M', 'A'];

    public static function label(string $code): string
    {
        return match (self::resolveForCosting($code)) {
            self::LEVEL_NA => 'Não aplicável',
            self::LEVEL_BAIXA => 'Baixa',
            self::LEVEL_MEDIA => 'Média',
            self::LEVEL_ALTA => 'Alta',
            default => $code,
        };
    }

    /**
     * Código válido já gravado (cadastro manual) ou null se inválido/vazio.
     */
    public static function normalize(mixed $value): ?string
    {
        $level = mb_strtoupper(trim((string)($value ?? '')), 'UTF-8');
        if ($level === '') {
            return null;
        }

        if (in_array($level, ['N/A', 'NAO APLICAVEL', 'NÃO APLICÁVEL', 'NAO APLICÁVEL'], true)) {
            return self::LEVEL_NA;
        }

        return match ($level) {
            self::LEVEL_NA, 'N' => self::LEVEL_NA,
            self::LEVEL_BAIXA, 'B', 'BAIXO', 'LOW' => self::LEVEL_BAIXA,
            self::LEVEL_MEDIA, 'M', 'MÉDIA', 'MEDIO', 'MÉDIO', 'MEDIUM' => self::LEVEL_MEDIA,
            self::LEVEL_ALTA, 'A', 'ALTO', 'HIGH' => self::LEVEL_ALTA,
            default => match (mb_strtolower($level, 'UTF-8')) {
                'baixa', 'low' => self::LEVEL_BAIXA,
                'media', 'média', 'medium' => self::LEVEL_MEDIA,
                'alta', 'high' => self::LEVEL_ALTA,
                default => in_array($level, self::ALLOWED, true) ? $level : null,
            },
        };
    }

    /**
     * Valor vindo do SAP na sync (N/B/M/A ou vazio) → código interno; desconhecido → NA.
     */
    public static function fromSap(mixed $value): string
    {
        return self::normalize($value) ?? self::LEVEL_NA;
    }

    /**
     * Resolução para rateio CFIX (crit. 4 e 6): vazio no cadastro → NA (fator 0).
     */
    public static function resolveForCosting(mixed $value): string
    {
        return self::normalize($value) ?? self::LEVEL_NA;
    }

    public static function multiplier(string $code): float
    {
        return InvComplexityLevelFactorService::getMultiplier($code);
    }

    /**
     * Driver base dos critérios 4 e 6 (fator de complexidade).
     */
    public static function complexityDriverWeight(mixed $complexityLevel): float
    {
        $level = self::resolveForCosting($complexityLevel);

        return self::multiplier($level);
    }
}
