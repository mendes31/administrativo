<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingCategoriesRepository;

/**
 * Classificações exibidas no canal público e nos comitês.
 */
final class WhistleblowingCategoryService
{
    /** @var list<string> */
  private const FALLBACK = [
        'Assédio Moral',
        'Assédio Sexual',
        'Fraude',
        'Corrupção',
        'Favorecimento',
        'Furto',
        'Discriminação',
        'Segurança',
        'Qualidade',
        'Meio Ambiente',
        'Conflito de Interesse',
        'Outros',
    ];

    /**
     * @return list<string>
     */
    public static function getActiveNames(): array
    {
        $fromDb = (new WhistleblowingCategoriesRepository())->getActiveNames();

        return $fromDb !== [] ? $fromDb : self::FALLBACK;
    }

    /**
     * Todas as classificações (ativas e inativas) — útil em filtros internos.
     *
     * @return list<string>
     */
    public static function getAllNames(): array
    {
        $rows = (new WhistleblowingCategoriesRepository())->getAll();
        $names = array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['name'] ?? '')),
            $rows
        )));

        return $names !== [] ? $names : self::FALLBACK;
    }

    public static function normalize(string $category): string
    {
        $names = self::getActiveNames();
        $trimmed = trim($category);
        if ($trimmed === '') {
            return self::defaultCategory($names);
        }
        if (in_array($trimmed, $names, true)) {
            return $trimmed;
        }
        foreach ($names as $name) {
            if (strcasecmp($name, $trimmed) === 0) {
                return $name;
            }
        }

        return self::defaultCategory($names);
    }

    /**
     * @param list<string> $names
     */
    private static function defaultCategory(array $names): string
    {
        if (in_array('Outros', $names, true)) {
            return 'Outros';
        }

        return $names[0] ?? 'Outros';
    }
}
