<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Identificação de itens PA em fase de projeto (simulação de custeio sem cadastro SAP completo).
 */
final class InvCostProjectHelper
{
    public const CATEGORY_NAME = 'PA - PROJETO';

    public static function isProjectCategoryName(?string $name): bool
    {
        if ($name === null || trim($name) === '') {
            return false;
        }

        return mb_strtoupper(trim($name), 'UTF-8') === mb_strtoupper(self::CATEGORY_NAME, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function isProjectItem(array $item): bool
    {
        return self::isProjectCategoryName(isset($item['category_name']) ? (string)$item['category_name'] : null);
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function allowsManualBomLines(array $item): bool
    {
        return self::isProjectItem($item);
    }
}
