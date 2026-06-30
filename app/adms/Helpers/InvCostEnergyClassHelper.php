<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Services\InvEnergyClassFactorService;

/**
 * Classe HVAC (critério 8) — alinhado SAP UDF ClasseHvac + planilha Tiaraju.
 *
 * SAP: NA (padrão), CM, PROB → gravados no item na sync.
 * Vazio/desconhecido no SAP → NA (multiplicador 0).
 * Multiplicadores: inv_energy_class_factors.
 */
final class InvCostEnergyClassHelper
{
    public const CLASS_NA = 'NA';
    public const CLASS_CM = 'CM';
    public const CLASS_PROB = 'PROB';
    /** Legado planilha (OUTRO); SAP não envia este código. */
    public const CLASS_OTHER = 'OTHER';

    /** @var list<string> */
    public const ALLOWED = [self::CLASS_NA, self::CLASS_CM, self::CLASS_PROB, self::CLASS_OTHER];

    /** @var list<string> Códigos sincronizados do SAP. */
    public const SAP_CODES = [self::CLASS_NA, self::CLASS_CM, self::CLASS_PROB];

    public static function label(string $code): string
    {
        return match (self::resolveForCosting($code)) {
            self::CLASS_NA => 'Não aplicável',
            self::CLASS_CM => 'Cápsula mole',
            self::CLASS_PROB => 'Probiótico',
            self::CLASS_OTHER => 'Outros',
            default => $code,
        };
    }

    /**
     * Código válido já gravado (cadastro manual) ou null se inválido/vazio.
     */
    public static function normalize(mixed $value): ?string
    {
        $class = mb_strtoupper(trim((string)($value ?? '')), 'UTF-8');
        if ($class === '') {
            return null;
        }

        if (in_array($class, ['N/A', 'NAO APLICAVEL', 'NÃO APLICÁVEL', 'NAO APLICÁVEL'], true)) {
            return self::CLASS_NA;
        }

        return match ($class) {
            self::CLASS_NA => self::CLASS_NA,
            self::CLASS_CM, 'CAPSULA', 'CAPSULA MOLE', 'CÁPSULA MOLE' => self::CLASS_CM,
            self::CLASS_PROB, 'PROBIOTICO', 'PROBIÓTICO' => self::CLASS_PROB,
            self::CLASS_OTHER, 'OUTRO' => self::CLASS_OTHER,
            default => in_array($class, self::ALLOWED, true) ? $class : null,
        };
    }

    /**
     * Valor vindo do SAP na sync: NA / CM / PROB; vazio ou desconhecido → NA.
     */
    public static function fromSap(mixed $value): string
    {
        return self::normalize($value) ?? self::CLASS_NA;
    }

    /**
     * Resolução para rateio CFIX (crit. 8): vazio no cadastro → NA (fator 0).
     */
    public static function resolveForCosting(mixed $value): string
    {
        return self::normalize($value) ?? self::CLASS_NA;
    }

    public static function multiplier(string $class): float
    {
        return InvEnergyClassFactorService::getMultiplier($class);
    }

    /**
     * Driver base do critério 8 (peso secagem × participação no rateio).
     */
    public static function hvacDriverWeight(mixed $energyClass): float
    {
        $class = self::resolveForCosting($energyClass);

        return self::multiplier($class);
    }
}
