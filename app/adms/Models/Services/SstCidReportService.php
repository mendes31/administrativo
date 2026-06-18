<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use PDO;

class SstCidReportService extends DbConnection
{
    /**
     * @return array{
     *   top_cids: array<int, array<string, mixed>>,
     *   por_capitulo: array<int, array<string, mixed>>,
     *   por_setor: array<int, array<string, mixed>>,
     *   dias_por_cid: array<int, array<string, mixed>>
     * }
     */
    public function getRelatorio(array $filters = []): array
    {
        return [
            'top_cids' => $this->getTopCids($filters, 15),
            'por_capitulo' => $this->getPorCapitulo($filters),
            'por_setor' => $this->getPorSetor($filters, 15),
            'dias_por_cid' => $this->getDiasPorCid($filters, 15),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopCids(array $filters = [], int $limit = 15): array
    {
        [$where, $params] = $this->buildAfastamentoFilters($filters);
        $sql = "SELECT c.id, c.codigo, c.descricao, c.capitulo_nome, COUNT(*) AS total_afastamentos
                FROM adms_sst_afastamentos a
                INNER JOIN adms_sst_cids c ON c.id = a.adms_sst_cid_id
                LEFT JOIN adms_users u ON u.id = a.adms_user_id
                WHERE {$where}
                GROUP BY c.id, c.codigo, c.descricao, c.capitulo_nome
                ORDER BY total_afastamentos DESC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDiasPorCid(array $filters = [], int $limit = 15): array
    {
        [$where, $params] = $this->buildAfastamentoFilters($filters);
        $sql = "SELECT c.id, c.codigo, c.descricao,
                       SUM(COALESCE(a.dias_afastamento, DATEDIFF(COALESCE(a.data_fim, CURDATE()), a.data_inicio) + 1)) AS total_dias
                FROM adms_sst_afastamentos a
                INNER JOIN adms_sst_cids c ON c.id = a.adms_sst_cid_id
                LEFT JOIN adms_users u ON u.id = a.adms_user_id
                WHERE {$where}
                GROUP BY c.id, c.codigo, c.descricao
                ORDER BY total_dias DESC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPorCapitulo(array $filters = []): array
    {
        [$where, $params] = $this->buildAfastamentoFilters($filters);
        $sql = "SELECT c.capitulo_num, c.capitulo_nome,
                       COUNT(*) AS total_afastamentos,
                       SUM(COALESCE(a.dias_afastamento, DATEDIFF(COALESCE(a.data_fim, CURDATE()), a.data_inicio) + 1)) AS total_dias
                FROM adms_sst_afastamentos a
                INNER JOIN adms_sst_cids c ON c.id = a.adms_sst_cid_id
                LEFT JOIN adms_users u ON u.id = a.adms_user_id
                WHERE {$where}
                GROUP BY c.capitulo_num, c.capitulo_nome
                ORDER BY total_afastamentos DESC";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPorSetor(array $filters = [], int $limit = 15): array
    {
        [$where, $params] = $this->buildAfastamentoFilters($filters);
        $sql = "SELECT d.id AS departamento_id, d.name AS departamento_nome,
                       COUNT(*) AS total_afastamentos,
                       SUM(COALESCE(a.dias_afastamento, DATEDIFF(COALESCE(a.data_fim, CURDATE()), a.data_inicio) + 1)) AS total_dias
                FROM adms_sst_afastamentos a
                INNER JOIN adms_sst_cids c ON c.id = a.adms_sst_cid_id
                INNER JOIN adms_users u ON u.id = a.adms_user_id
                LEFT JOIN adms_departments d ON d.id = u.user_department_id
                WHERE {$where}
                GROUP BY d.id, d.name
                ORDER BY total_afastamentos DESC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildAfastamentoFilters(array $filters): array
    {
        $where = ['a.adms_sst_cid_id IS NOT NULL'];
        $params = [];

        if (!empty($filters['data_inicio_de'])) {
            $where[] = 'a.data_inicio >= :data_inicio_de';
            $params[':data_inicio_de'] = $filters['data_inicio_de'];
        }
        if (!empty($filters['data_inicio_ate'])) {
            $where[] = 'a.data_inicio <= :data_inicio_ate';
            $params[':data_inicio_ate'] = $filters['data_inicio_ate'];
        }
        if (!empty($filters['adms_user_id'])) {
            $where[] = 'a.adms_user_id = :uid';
            $params[':uid'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['natureza'])) {
            $where[] = 'a.natureza = :natureza';
            $params[':natureza'] = $filters['natureza'];
        }
        if (!empty($filters['capitulo_num'])) {
            $where[] = 'c.capitulo_num = :capitulo_num';
            $params[':capitulo_num'] = (int) $filters['capitulo_num'];
        }

        return [implode(' AND ', $where), $params];
    }
}
