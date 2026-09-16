<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Filtros GET compartilhados pelo cockpit SAP e pelas telas irmãs.
 */
final class CrmSalesFilterQuery
{
    /**
     * @return array<string, mixed>
     */
    public static function fromGet(): array
    {
        return [
            'periodo' => (string) ($_GET['periodo'] ?? '12'),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'vendedor' => $_GET['vendedor'] ?? null,
            'grupo_cliente' => $_GET['grupo_cliente'] ?? null,
            'regiao' => $_GET['regiao'] ?? null,
            'grupo_item' => $_GET['grupo_item'] ?? null,
            'ano_mes' => $_GET['ano_mes'] ?? null,
            'card_code' => $_GET['card_code'] ?? null,
            'item_code' => $_GET['item_code'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function build(array $query): string
    {
        unset($query['page'], $query['origem'], $query['url'], $query['controller'], $query['natureza']);
        $parts = [];
        foreach ($query as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === null || $item === '') {
                        continue;
                    }
                    $parts[] = rawurlencode((string) $key) . '%5B%5D=' . rawurlencode((string) $item);
                }
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }
        return implode('&', $parts);
    }

    /**
     * @param list<string> $selected
     */
    public static function isSelected(array $selected, string $value): bool
    {
        return in_array($value, $selected, true);
    }
}
