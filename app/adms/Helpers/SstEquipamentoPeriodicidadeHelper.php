<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use DateTimeImmutable;

/**
 * Periodicidade de vistoria de equipamentos de segurança (em meses).
 */
final class SstEquipamentoPeriodicidadeHelper
{
    public const DIA_MIN = 1;
    public const DIA_MAX = 28;

    /** @return array<int, string> meses => rótulo */
    public static function options(): array
    {
        return [
            1 => 'Mensal',
            2 => 'Bimestral',
            3 => 'Trimestral',
            6 => 'Semestral',
            12 => 'Anual',
        ];
    }

    /** @return array<int, string> */
    public static function dayOptions(): array
    {
        $opts = [];
        for ($d = self::DIA_MIN; $d <= self::DIA_MAX; $d++) {
            $opts[$d] = 'Dia ' . $d;
        }

        return $opts;
    }

    public static function clampDay(int $day): int
    {
        return max(self::DIA_MIN, min(self::DIA_MAX, $day));
    }

    /**
     * Monta YYYY-MM-DD usando o dia configurado (ajusta para último dia do mês se necessário).
     */
    public static function buildDataPrevista(string $competencia, int $dia): string
    {
        $dia = self::clampDay($dia);
        $base = DateTimeImmutable::createFromFormat('Y-m-d', $competencia . '-01');
        if (!$base) {
            return $competencia . '-01';
        }
        $lastDay = (int) $base->format('t');
        $day = min($dia, $lastDay);

        return $base->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $equipamento
     */
    public static function resolveDiaPrevisto(array $equipamento, array $settings): int
    {
        if (isset($equipamento['dia_previsto_vistoria']) && $equipamento['dia_previsto_vistoria'] !== null && $equipamento['dia_previsto_vistoria'] !== '') {
            return self::clampDay((int) $equipamento['dia_previsto_vistoria']);
        }

        return self::clampDay((int) ($settings['dia_previsto_padrao'] ?? 1));
    }

    public static function label(int $meses): string
    {
        return self::options()[$meses] ?? ($meses . ' meses');
    }

    public static function isValid(int $meses): bool
    {
        return array_key_exists($meses, self::options());
    }
}
