<?php

namespace App\adms\Helpers;

/**
 * Helper para leitura segura de filtros e paginação a partir de $_GET
 *
 * Uso recomendado em todos os controllers de listagem:
 *   $params = FiltersHelper::getFilters(['nome', 'status', 'publica', ...]);
 *   $page = $params['page'];
 *   $perPage = $params['per_page'];
 *   $filters = $params['filters'];
 */
class FiltersHelper
{
    /**
     * Retorna filtros e paginação padronizados a partir de $_GET
     * @param array $filterKeys Lista de chaves de filtros aceitas (ex: ['nome','status','publica'])
     * @param int $defaultPerPage Valor padrão de registros por página
     * @return array
     */
    public static function getFilters(array $filterKeys, int $defaultPerPage = 10): array
    {
        $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) && is_numeric($_GET['per_page']) && $_GET['per_page'] > 0 ? (int)$_GET['per_page'] : $defaultPerPage;
        $filters = [];
        foreach ($filterKeys as $key) {
            $filters[$key] = isset($_GET[$key]) ? trim($_GET[$key]) : '';
        }
        return [
            'page' => $page,
            'per_page' => $perPage,
            'filters' => $filters
        ];
    }
} 