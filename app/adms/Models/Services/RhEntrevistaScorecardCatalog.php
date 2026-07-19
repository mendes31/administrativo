<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Critérios padrão do scorecard de entrevista (Fase 2 Expand).
 * Códigos estáveis; rótulo/peso podem ser sobrescritos no snapshot do item.
 */
final class RhEntrevistaScorecardCatalog
{
    /**
     * @return list<array{codigo: string, label: string, peso: int}>
     */
    public static function defaultCriteria(): array
    {
        return [
            ['codigo' => 'COMUNICACAO', 'label' => 'Comunicação', 'peso' => 20],
            ['codigo' => 'EXPERIENCIA_TECNICA', 'label' => 'Experiência técnica', 'peso' => 30],
            ['codigo' => 'CULTURA_FIT', 'label' => 'Alinhamento cultural', 'peso' => 20],
            ['codigo' => 'MOTIVACAO', 'label' => 'Motivação / interesse', 'peso' => 15],
            ['codigo' => 'RESOLUCAO_PROBLEMAS', 'label' => 'Resolução de problemas', 'peso' => 15],
        ];
    }

    /**
     * @param list<array{peso?: int|string|null, nota?: int|string|null}> $itens
     */
    public static function calcularNotaPonderada(array $itens): ?float
    {
        $pesoTotal = 0;
        $acumulado = 0.0;
        $temNota = false;

        foreach ($itens as $item) {
            $peso = max(0, (int) ($item['peso'] ?? 0));
            if ($peso <= 0) {
                continue;
            }
            if ($item['nota'] === null || $item['nota'] === '') {
                continue;
            }
            $nota = (int) $item['nota'];
            if ($nota < 0 || $nota > 10) {
                continue;
            }
            $temNota = true;
            $pesoTotal += $peso;
            $acumulado += $nota * $peso;
        }

        if (!$temNota || $pesoTotal <= 0) {
            return null;
        }

        return round($acumulado / $pesoTotal, 2);
    }
}
