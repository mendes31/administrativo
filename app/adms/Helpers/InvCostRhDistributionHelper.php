<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Contas DRE de gastos com pessoal (Pasta 8 / bloco folha) e critério sugerido por área (Pasta 9).
 */
final class InvCostRhDistributionHelper
{
    /** @var list<string> */
    public const PERSONNEL_ACCOUNT_CODES = [
        '817', '818', '819', '820', '821', '822', '823', '824', '825', '826',
        '827', '828', '829', '830', '831', '832', '833', '834', '13',
    ];

    /** @var array<string, int> área normalizada → critério padrão */
    private const DEFAULT_CRITERION_BY_AREA = [
        'PRODUCAO' => 2,
        'PRODUCAO I' => 2,
        'CONTROLE DE QUALIDADE' => 4,
        'GARANTIA DA QUALIDADE' => 4,
        'DESENVOLVIMENTO ANALITICO' => 4,
        'PESQUISA E DESENVOLVIMENTO' => 6,
        'ADMINISTRATIVO' => 1,
        'COMERCIAL' => 1,
        'MANUTENCAO' => 3,
        'LOGISTICA' => 1,
        'EXPEDICAO' => 1,
        'PUBLICIDADE' => 1,
        'TECNOLOGIA DA INFORMACAO' => 1,
        'TECNOLOGIA DA INFORMAÇÃO' => 1,
        'TI' => 1,
        'RECURSOS HUMANOS' => 1,
        'FINANCEIRO' => 1,
        'DIRETORIA' => 1,
        'REGULATORIO' => 4,
        'REGULATÓRIO' => 4,
        'ASSUNTOS REGULATORIOS' => 4,
        'COMPRAS' => 1,
        'SUPRIMENTOS' => 1,
        'JURIDICO' => 1,
        'JURÍDICO' => 1,
        'ALMOXARIFADO' => 3,
        'HIGIENIZACAO' => 3,
        'ALIMENTACAO' => 1,
        'PROJETOS' => 6,
        'MEIO AMBIENTE' => 1,
        'ENGENHARIA' => 3,
    ];

    public static function normalizeAreaName(mixed $value): string
    {
        $raw = mb_strtoupper(trim((string)($value ?? '')), 'UTF-8');
        $raw = str_replace(['  ', "\t"], ' ', $raw);

        return $raw;
    }

    public static function isPersonnelAccountCode(mixed $code): bool
    {
        $normalized = mb_strtoupper(trim((string)($code ?? '')), 'UTF-8');
        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, self::PERSONNEL_ACCOUNT_CODES, true)) {
            return true;
        }

        return str_starts_with($normalized, 'NC-') && str_contains($normalized, 'PESSOAL');
    }

    public static function isPersonnelPoolRow(array $pool): bool
    {
        if (self::isPersonnelAccountCode($pool['account_code'] ?? '')) {
            return true;
        }

        $desc = mb_strtoupper((string)($pool['description'] ?? ''), 'UTF-8');

        return str_contains($desc, 'GASTOS COM PESSOAL')
            || str_contains($desc, 'REMUNERACAO')
            || str_contains($desc, 'REMUNERAÇÃO')
            || str_contains($desc, 'ENCARGOS SOCIAIS')
            || str_contains($desc, 'SALARIO')
            || str_contains($desc, 'SALÁRIO');
    }

    public static function defaultCriterionForArea(mixed $areaName): int
    {
        $key = self::normalizeAreaName($areaName);
        if ($key !== '' && isset(self::DEFAULT_CRITERION_BY_AREA[$key])) {
            return self::DEFAULT_CRITERION_BY_AREA[$key];
        }

        if (str_contains($key, 'PRODUC')) {
            return 2;
        }
        if (str_contains($key, 'QUALIDADE') || str_contains($key, 'CQ')) {
            return 4;
        }
        if (str_contains($key, 'PESQUISA') || str_contains($key, 'DESENVOLV')) {
            return 6;
        }
        if (str_contains($key, 'MANUTEN') || str_contains($key, 'ENGENHAR')) {
            return 3;
        }

        return 1;
    }

    public static function parseCriterion(mixed $value, mixed $areaName = null): int
    {
        if ($value === null || $value === '') {
            return self::defaultCriterionForArea($areaName);
        }

        $raw = trim((string)$value);
        if (preg_match('/^(\d+)/', $raw, $m)) {
            $n = (int)$m[1];
            if ($n >= 1 && $n <= 8) {
                return $n;
            }
        }

        return self::defaultCriterionForArea($areaName);
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    public static function withComputedShares(array $lines): array
    {
        $total = 0.0;
        foreach ($lines as $line) {
            $total += max(0.0, (float)($line['amount'] ?? 0));
        }

        $out = [];
        foreach ($lines as $line) {
            $amount = max(0.0, (float)($line['amount'] ?? 0));
            $share = $total > 0 ? round(($amount / $total) * 100, 6) : 0.0;
            $out[] = array_merge($line, [
                'amount' => round($amount, 4),
                'share_pct' => $share,
            ]);
        }

        return $out;
    }
}
