<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\inventory\InvItemsRepository;

/**
 * Contexto de navegação lista ↔ visualizar/editar item de estoque.
 */
final class InvInventoryItemListNavHelper
{
    private const SESSION_KEY = 'inv_items_list_nav';

    /** @var list<string> */
    private const FILTER_KEYS = [
        'code',
        'description',
        'active',
        'categoria_id',
        'production_line',
        'inv_pharma_form_id',
    ];

    /**
     * @param array<string, mixed> $filters
     */
    public static function storeListContext(int $page, int $perPage, array $filters): void
    {
        $_SESSION[self::SESSION_KEY] = [
            'page' => max(1, $page),
            'per_page' => self::normalizePerPage($perPage),
            'filters' => self::normalizeFilters($filters),
        ];
    }

    public static function captureFromRequest(): void
    {
        $get = $_GET;
        $hasNav = isset($get['page']) || isset($get['per_page']);

        foreach (self::FILTER_KEYS as $key) {
            if (array_key_exists($key, $get)) {
                $hasNav = true;
                break;
            }
        }

        if ($hasNav) {
            self::storeListContext(
                max(1, (int)($get['page'] ?? 1)),
                (int)($get['per_page'] ?? 10),
                $get
            );

            return;
        }

        if (isset($_SESSION[self::SESSION_KEY]) && is_array($_SESSION[self::SESSION_KEY])) {
            return;
        }

        $sessionFilters = $_SESSION['filtros_list_inventory_items'] ?? [];
        if (!is_array($sessionFilters) || $sessionFilters === []) {
            return;
        }

        self::storeListContext(
            max(1, (int)($sessionFilters['list_page'] ?? 1)),
            (int)($sessionFilters['per_page'] ?? 10),
            $sessionFilters
        );
    }

    /**
     * @return array{
     *   page: int,
     *   per_page: int,
     *   filters: array<string, mixed>,
     *   list_return_url: string,
     *   list_nav_query: string,
     *   nav_prev: ?array{id: int, code: string, description: string},
     *   nav_next: ?array{id: int, code: string, description: string},
     *   nav_prev_view_url: ?string,
     *   nav_next_view_url: ?string,
     *   nav_prev_edit_url: ?string,
     *   nav_next_edit_url: ?string
     * }
     */
    public static function resolveForItem(int $itemId): array
    {
        self::captureFromRequest();

        $context = self::getContext();
        $filters = $context['filters'];
        $query = self::buildQueryString($context);
        $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/');
        $listReturnUrl = $base . '/list-inventory-items' . ($query !== '' ? '?' . $query : '');

        $neighbors = (new InvItemsRepository())->findListNeighbors($itemId, $filters);
        $prev = $neighbors['prev'] ?? null;
        $next = $neighbors['next'] ?? null;

        return [
            'page' => $context['page'],
            'per_page' => $context['per_page'],
            'filters' => $filters,
            'list_return_url' => $listReturnUrl,
            'list_nav_query' => $query,
            'nav_prev' => $prev,
            'nav_next' => $next,
            'nav_prev_view_url' => $prev !== null
                ? self::buildItemUrl('view-inventory-item', (int)$prev['id'], $query)
                : null,
            'nav_next_view_url' => $next !== null
                ? self::buildItemUrl('view-inventory-item', (int)$next['id'], $query)
                : null,
            'nav_prev_edit_url' => $prev !== null
                ? self::buildItemUrl('update-inventory-item', (int)$prev['id'], $query)
                : null,
            'nav_next_edit_url' => $next !== null
                ? self::buildItemUrl('update-inventory-item', (int)$next['id'], $query)
                : null,
        ];
    }

    public static function appendQueryToUrl(string $url, string $query = ''): string
    {
        if ($query === '') {
            return $url;
        }

        $fragment = '';
        $hashPos = strpos($url, '#');
        if ($hashPos !== false) {
            $fragment = substr($url, $hashPos);
            $url = substr($url, 0, $hashPos);
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $query . $fragment;
    }

    /**
     * @param array<string, mixed> $pagination
     * @param array<string, mixed> $filters
     */
    public static function buildQueryFromListView(array $pagination, int $perPage, array $filters): string
    {
        return self::buildQueryString([
            'page' => max(1, (int)($pagination['current_page'] ?? 1)),
            'per_page' => $perPage,
            'filters' => $filters,
        ]);
    }

    /**
     * @param array{page?: int, per_page?: int, filters?: array<string, mixed>} $context
     */
    private static function buildQueryString(array $context): string
    {
        $params = array_merge(
            self::normalizeFilters($context['filters'] ?? []),
            [
                'page' => max(1, (int)($context['page'] ?? 1)),
                'per_page' => self::normalizePerPage((int)($context['per_page'] ?? 10)),
            ]
        );

        return http_build_query($params);
    }

    /**
     * @return array{page: int, per_page: int, filters: array<string, mixed>}
     */
    private static function getContext(): array
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($stored)) {
            return [
                'page' => 1,
                'per_page' => 10,
                'filters' => [],
            ];
        }

        return [
            'page' => max(1, (int)($stored['page'] ?? 1)),
            'per_page' => self::normalizePerPage((int)($stored['per_page'] ?? 10)),
            'filters' => self::normalizeFilters($stored['filters'] ?? []),
        ];
    }

  /**
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    private static function normalizeFilters(array $source): array
    {
        $normalized = [];
        foreach (self::FILTER_KEYS as $key) {
            if (!array_key_exists($key, $source)) {
                continue;
            }
            $value = $source[$key];
            if ($value === null || $value === '') {
                continue;
            }
            $normalized[$key] = is_scalar($value) ? (string)$value : $value;
        }

        return $normalized;
    }

    private static function normalizePerPage(int $perPage): int
    {
        return in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;
    }

    private static function buildItemUrl(string $route, int $itemId, string $query): string
    {
        $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/');
        $url = $base . '/' . trim($route, '/') . '/' . $itemId;

        return self::appendQueryToUrl($url, $query);
    }
}
