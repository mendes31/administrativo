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
}
