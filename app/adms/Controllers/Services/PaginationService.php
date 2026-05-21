<?php

namespace App\adms\Controllers\Services;

/**
 * Classe PaginationService
 * 
 * Esta classe fornece um serviço para gerar dados de paginação, incluindo o número total de registros,
 * o número total de páginas, a página atual, e a URL do controller. É útil para gerenciar a navegação 
 * entre diferentes páginas de resultados em uma aplicação web.
 * 
 * @package App\adms\Controllers\Services
 * @author Rafael Mendes
 */
class PaginationService
{
    /**
     * Gerar os dados de paginação
     * 
     * Este método calcula o número total de páginas com base no número total de registros e na quantidade
     * de registros por página. Ele também retorna a página atual e a URL do controller para facilitar a 
     * navegação entre as páginas.
     * 
     * @param int $totalRecords Total de registros
     * @param int $limitResult Registros por página
     * @param int $currentPage Página atual
     * @param string $urlController URL do controller
     * @param array $filters Filtros adicionais
     * @return array Dados completos de paginação, incluindo HTML
     */
    public static function generatePagination(int $totalRecords, int $limitResult, int $currentPage, string $urlController, array $filters = []): array
    {
        $lastPage = (int) ceil($totalRecords / $limitResult);
        $currentPage = max(1, min($currentPage, $lastPage));
        $firstItem = $totalRecords > 0 ? (($currentPage - 1) * $limitResult) + 1 : 0;
        $lastItem = min($currentPage * $limitResult, $totalRecords);
        $queryString = '';
        if (!empty($filters)) {
            $queryString = '&' . http_build_query($filters);
        }
        $html = '';
        if ($lastPage > 1) {
            $html .= '<ul class="pagination justify-content-end">';
            // Primeiro e Anterior
            if ($currentPage == 1) {
                $html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Primeiro</a></li>';
                $html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Anterior</a></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $_ENV['URL_ADM'] . $urlController . '?page=1' . $queryString . '">Primeiro</a></li>';
                $html .= '<li class="page-item"><a class="page-link" href="' . $_ENV['URL_ADM'] . $urlController . '?page=' . max(1, $currentPage - 1) . $queryString . '">Anterior</a></li>';
            }
            // Números das páginas (máximo 5 visíveis)
            $start = max(1, $currentPage - 2);
            $end = min($lastPage, $currentPage + 2);
            if ($start > 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            for ($i = $start; $i <= $end; $i++) {
                $active = ($i == $currentPage) ? ' active' : '';
                $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $_ENV['URL_ADM'] . $urlController . '?page=' . $i . $queryString . '">' . $i . '</a></li>';
            }
            if ($end < $lastPage) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            // Próximo e Último
            if ($currentPage == $lastPage) {
                $html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Próximo</a></li>';
                $html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Último</a></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $_ENV['URL_ADM'] . $urlController . '?page=' . min($lastPage, $currentPage + 1) . $queryString . '">Próximo</a></li>';
                $html .= '<li class="page-item"><a class="page-link" href="' . $_ENV['URL_ADM'] . $urlController . '?page=' . $lastPage . $queryString . '">Último</a></li>';
            }
            $html .= '</ul>';
        }
        return [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'total' => $totalRecords,
            'per_page' => $limitResult,
            'first_item' => $firstItem,
            'last_item' => $lastItem,
            'html' => $html,
            'url_controller' => $urlController // Sempre incluir o parâmetro principal de rota
        ];
    }

    /**
     * Paginação compacta para Timeline (mobile-first, sem cortar botões).
     */
    public static function generateTimelinePagination(
        int $totalRecords,
        int $limitResult,
        int $currentPage,
        string $urlController,
        array $filters = []
    ): array {
        $data = self::generatePagination($totalRecords, $limitResult, $currentPage, $urlController, $filters);
        if ($data['last_page'] <= 1) {
            $data['html'] = '';
            return $data;
        }
        $data['html'] = self::buildTimelinePaginationHtml($data, $urlController, $filters);
        return $data;
    }

    /**
     * @param array{current_page:int,last_page:int,total:int,first_item:int,last_item:int} $pagination
     */
    private static function buildTimelinePaginationHtml(array $pagination, string $urlController, array $filters): string
    {
        $currentPage = (int) $pagination['current_page'];
        $lastPage = (int) $pagination['last_page'];
        $total = (int) $pagination['total'];
        $firstItem = (int) $pagination['first_item'];
        $lastItem = (int) $pagination['last_item'];

        $queryString = !empty($filters) ? '&' . http_build_query($filters) : '';
        $baseUrl = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/' . ltrim($urlController, '/');

        $pageUrl = static function (int $page) use ($baseUrl, $queryString): string {
            return htmlspecialchars($baseUrl . '?page=' . $page . $queryString, ENT_QUOTES, 'UTF-8');
        };

        $pageNumbers = self::timelinePageNumbers($currentPage, $lastPage);

        $html = '<nav class="timeline-pagination" aria-label="Paginação da timeline">';
        $html .= '<div class="timeline-pagination-meta">';
        $html .= '<span class="timeline-pagination-meta-page">Página <strong>' . $currentPage . '</strong> de <strong>' . $lastPage . '</strong></span>';
        if ($total > 0) {
            $html .= '<span class="timeline-pagination-meta-range">' . $firstItem . '–' . $lastItem . ' de ' . $total . '</span>';
        }
        $html .= '</div>';

        $html .= '<div class="timeline-pagination-controls">';

        if ($currentPage <= 1) {
            $html .= '<span class="timeline-pag-btn timeline-pag-btn--nav is-disabled" aria-disabled="true">'
                . '<i class="fas fa-chevron-left" aria-hidden="true"></i>'
                . '<span class="timeline-pag-btn-label">Anterior</span></span>';
        } else {
            $html .= '<a href="' . $pageUrl($currentPage - 1) . '" class="timeline-pag-btn timeline-pag-btn--nav" rel="prev">'
                . '<i class="fas fa-chevron-left" aria-hidden="true"></i>'
                . '<span class="timeline-pag-btn-label">Anterior</span></a>';
        }

        $html .= '<div class="timeline-pagination-pages" role="group" aria-label="Números de página">';
        foreach ($pageNumbers as $pageNum) {
            if ($pageNum === '...') {
                $html .= '<span class="timeline-pag-ellipsis" aria-hidden="true">…</span>';
                continue;
            }
            $isActive = ((int) $pageNum === $currentPage);
            $activeClass = $isActive ? ' is-active' : '';
            $ariaCurrent = $isActive ? ' aria-current="page"' : '';
            $html .= '<a href="' . $pageUrl((int) $pageNum) . '" class="timeline-pag-page' . $activeClass . '"' . $ariaCurrent . '>'
                . (int) $pageNum . '</a>';
        }
        $html .= '</div>';

        $html .= '<span class="timeline-pagination-mobile-indicator" aria-hidden="true">'
            . $currentPage . ' / ' . $lastPage . '</span>';

        if ($currentPage >= $lastPage) {
            $html .= '<span class="timeline-pag-btn timeline-pag-btn--nav is-disabled" aria-disabled="true">'
                . '<span class="timeline-pag-btn-label">Próximo</span>'
                . '<i class="fas fa-chevron-right" aria-hidden="true"></i></span>';
        } else {
            $html .= '<a href="' . $pageUrl($currentPage + 1) . '" class="timeline-pag-btn timeline-pag-btn--nav" rel="next">'
                . '<span class="timeline-pag-btn-label">Próximo</span>'
                . '<i class="fas fa-chevron-right" aria-hidden="true"></i></a>';
        }

        $html .= '</div></nav>';

        return $html;
    }

    /**
     * @return array<int|string>
     */
    private static function timelinePageNumbers(int $current, int $last): array
    {
        if ($last <= 5) {
            return range(1, $last);
        }

        $set = [1, $last, $current];
        if ($current > 1) {
            $set[] = $current - 1;
        }
        if ($current < $last) {
            $set[] = $current + 1;
        }
        if ($current > 2) {
            $set[] = $current - 2;
        }
        if ($current < $last - 1) {
            $set[] = $current + 2;
        }

        $set = array_values(array_unique(array_filter($set, static function ($n) use ($last) {
            return is_int($n) && $n >= 1 && $n <= $last;
        })));
        sort($set, SORT_NUMERIC);

        $result = [];
        $prev = 0;
        foreach ($set as $n) {
            if ($prev > 0 && $n - $prev > 1) {
                $result[] = '...';
            }
            $result[] = $n;
            $prev = $n;
        }

        return $result;
    }
}
