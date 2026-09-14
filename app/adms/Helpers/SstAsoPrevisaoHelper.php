<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Data prevista do próximo ASO periódico (validade ou realização + periodicidade). */
final class SstAsoPrevisaoHelper
{
    /** @var array<int, string> */
    public const MESES_PT = [
        1 => 'janeiro',
        2 => 'fevereiro',
        3 => 'março',
        4 => 'abril',
        5 => 'maio',
        6 => 'junho',
        7 => 'julho',
        8 => 'agosto',
        9 => 'setembro',
        10 => 'outubro',
        11 => 'novembro',
        12 => 'dezembro',
    ];

    public static function previsaoEm(?string $dataValidade, ?string $dataRealizacao, int $periodicidadeMeses = 12): ?\DateTimeImmutable
    {
        $dataValidade = substr(trim((string) $dataValidade), 0, 10);
        if ($dataValidade !== '') {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dataValidade);

            return $dt ?: null;
        }
        $dataRealizacao = substr(trim((string) $dataRealizacao), 0, 10);
        if ($dataRealizacao === '') {
            return null;
        }
        $real = \DateTimeImmutable::createFromFormat('Y-m-d', $dataRealizacao);
        if ($real === false) {
            return null;
        }
        $meses = $periodicidadeMeses > 0 ? $periodicidadeMeses : 12;

        return $real->modify('+' . $meses . ' months');
    }

    public static function normalizarMes(?string $ym, ?\DateTimeImmutable $hoje = null): string
    {
        $hoje ??= new \DateTimeImmutable('today');
        $atual = $hoje->format('Y-m');
        $ym = trim((string) $ym);
        if (!preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
            return $atual;
        }
        $month = (int) $m[2];
        if ($month < 1 || $month > 12) {
            return $atual;
        }

        return sprintf('%04d-%02d', (int) $m[1], $month);
    }

    public static function labelMes(string $ym): string
    {
        $ym = self::normalizarMes($ym);
        $month = (int) substr($ym, 5, 2);
        $year = substr($ym, 0, 4);

        return (self::MESES_PT[$month] ?? $ym) . ' de ' . $year;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function mesesOpcoes(int $futuros = 18, ?\DateTimeImmutable $hoje = null): array
    {
        $hoje ??= new \DateTimeImmutable('today');
        $cursor = $hoje->modify('first day of this month');
        $out = [];
        for ($i = 0; $i <= $futuros; $i++) {
            $value = $cursor->format('Y-m');
            $out[] = ['value' => $value, 'label' => self::labelMes($value)];
            $cursor = $cursor->modify('+1 month');
        }

        return $out;
    }

    public static function situacao(\DateTimeImmutable $previsto, ?\DateTimeImmutable $hoje = null): string
    {
        $hoje ??= new \DateTimeImmutable('today');
        if ($previsto < $hoje) {
            return 'vencido';
        }
        if ($previsto <= $hoje->modify('+30 days')) {
            return 'a_vencer';
        }

        return 'previsto';
    }

    public static function situacaoLabel(string $situacao): string
    {
        return match ($situacao) {
            'vencido' => 'Vencido',
            'a_vencer' => 'A vencer (30 dias)',
            default => 'Previsto',
        };
    }
}
