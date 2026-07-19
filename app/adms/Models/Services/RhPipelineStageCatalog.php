<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhPipelineStageRepository;

/**
 * Catálogo de etapas do pipeline ATS (Expand Fase 1).
 * Códigos fixos; rótulo/ordem/classe vêm do banco com fallback PHP.
 */
final class RhPipelineStageCatalog
{
    /** @var list<array{code: string, label: string, display_order: int, column_class: string}>|null */
    private static ?array $cache = null;

    /**
     * @return list<array{code: string, label: string, display_order: int, column_class: string}>
     */
    public static function defaults(): array
    {
        return [
            [
                'code' => 'candidatado',
                'label' => 'Candidatado',
                'display_order' => 10,
                'column_class' => 'bg-light',
            ],
            [
                'code' => 'em_entrevista',
                'label' => 'Em Entrevista',
                'display_order' => 20,
                'column_class' => 'bg-warning-subtle',
            ],
            [
                'code' => 'aprovado',
                'label' => 'Aprovado',
                'display_order' => 30,
                'column_class' => 'bg-success-subtle',
            ],
            [
                'code' => 'reprovado',
                'label' => 'Reprovado',
                'display_order' => 40,
                'column_class' => 'bg-danger-subtle',
            ],
            [
                'code' => 'desistiu',
                'label' => 'Desistiu',
                'display_order' => 50,
                'column_class' => 'bg-secondary-subtle',
            ],
        ];
    }

    /**
     * @return list<array{code: string, label: string, display_order: int, column_class: string}>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            $fromDb = (new RhPipelineStageRepository())->listActiveOrdered();
            if (self::isCompleteCatalog($fromDb)) {
                self::$cache = $fromDb;

                return self::$cache;
            }
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Fallback para etapas hardcoded do pipeline ATS.', [
                'error' => $e->getMessage(),
            ]);
        }

        self::$cache = self::defaults();

        return self::$cache;
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_values(array_map(
            static fn (array $stage): string => $stage['code'],
            self::all()
        ));
    }

    public static function isValidCode(string $code): bool
    {
        $code = $code === 'em_analise' ? 'em_entrevista' : trim($code);

        return in_array($code, self::codes(), true);
    }

    public static function label(string $code): string
    {
        $code = $code === 'em_analise' ? 'em_entrevista' : $code;
        foreach (self::all() as $stage) {
            if ($stage['code'] === $code) {
                return $stage['label'];
            }
        }

        return ucfirst(str_replace('_', ' ', $code));
    }

    /**
     * @return array<string, string> code => label
     */
    public static function labelsMap(): array
    {
        $map = [];
        foreach (self::all() as $stage) {
            $map[$stage['code']] = $stage['label'];
        }

        return $map;
    }

    /** @internal testes */
    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * @param list<array{code: string, label: string, display_order: int, column_class: string}> $stages
     */
    private static function isCompleteCatalog(array $stages): bool
    {
        if ($stages === []) {
            return false;
        }

        $expected = array_column(self::defaults(), 'code');
        $found = array_column($stages, 'code');
        sort($expected);
        $foundSorted = $found;
        sort($foundSorted);

        return $expected === $foundSorted;
    }
}
