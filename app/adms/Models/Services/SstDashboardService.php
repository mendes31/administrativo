<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Services\DbConnection;
use PDO;

class SstDashboardService extends DbConnection
{
    public function getPendingExamsCount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_asos
                WHERE data_validade IS NOT NULL AND data_validade < CURDATE()";
        return (int) ($this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getExpiredEpisCount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_epi_entregas
                WHERE tipo_movimento = 'Entrega'
                  AND data_prevista_troca IS NOT NULL
                  AND data_prevista_troca < CURDATE()";
        return (int) ($this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getOpenAccidentsCount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_acidentes
                WHERE status IN ('Aberto', 'Em investigação')";
        return (int) ($this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getPendingExams(int $limit = 5): array
    {
        $sql = "SELECT a.*, u.name AS colaborador_nome
                FROM adms_sst_asos a
                INNER JOIN adms_users u ON u.id = a.adms_user_id
                WHERE a.data_validade IS NOT NULL AND a.data_validade < CURDATE()
                ORDER BY a.data_validade ASC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getExpiredEpis(int $limit = 5): array
    {
        $sql = "SELECT e.*, u.name AS colaborador_nome, ep.nome AS epi_nome
                FROM adms_sst_epi_entregas e
                INNER JOIN adms_users u ON u.id = e.adms_user_id
                INNER JOIN adms_sst_epis ep ON ep.id = e.adms_sst_epi_id
                WHERE e.tipo_movimento = 'Entrega'
                  AND e.data_prevista_troca IS NOT NULL
                  AND e.data_prevista_troca < CURDATE()
                ORDER BY e.data_prevista_troca ASC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getOpenAccidents(int $limit = 5): array
    {
        $sql = "SELECT a.*, u.name AS colaborador_nome
                FROM adms_sst_acidentes a
                INNER JOIN adms_users u ON u.id = a.adms_user_id
                WHERE a.status IN ('Aberto', 'Em investigação')
                ORDER BY a.data_ocorrencia DESC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReportPendencias(): array
    {
        return [
            'exames_vencidos' => $this->getPendingExams(100),
            'epis_vencidos' => $this->getExpiredEpis(100),
            'acidentes_abertos' => $this->getOpenAccidents(100),
            'afastamentos_ativos' => $this->listAfastamentosAtivos(100),
        ];
    }

    public function getReportExames(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['adms_user_id'])) {
            $where[] = 'a.adms_user_id = :uid';
            $params[':uid'] = (int) $filters['adms_user_id'];
        }

        $status = $filters['status_vencimento'] ?? '';
        if ($status === 'vencido') {
            $where[] = 'a.data_validade IS NOT NULL AND a.data_validade < CURDATE()';
        } elseif ($status === 'a_vencer') {
            $where[] = 'a.data_validade IS NOT NULL AND a.data_validade >= CURDATE() AND a.data_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        } elseif ($status === 'valido') {
            $where[] = '(a.data_validade IS NULL OR a.data_validade > DATE_ADD(CURDATE(), INTERVAL 30 DAY))';
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT a.*, u.name AS colaborador_nome, ex.nome AS exame_nome
                FROM adms_sst_asos a
                LEFT JOIN adms_users u ON u.id = a.adms_user_id
                LEFT JOIN adms_sst_exames ex ON ex.id = a.adms_sst_exame_id
                WHERE {$whereClause}
                ORDER BY a.data_validade ASC, a.id DESC
                LIMIT 500";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReportEpis(array $filters = []): array
    {
        $where = ["e.tipo_movimento = 'Entrega'"];
        $params = [];

        if (!empty($filters['adms_user_id'])) {
            $where[] = 'e.adms_user_id = :uid';
            $params[':uid'] = (int) $filters['adms_user_id'];
        }

        $status = $filters['status_vencimento'] ?? '';
        if ($status === 'vencido') {
            $where[] = 'e.data_prevista_troca IS NOT NULL AND e.data_prevista_troca < CURDATE()';
        } elseif ($status === 'a_vencer') {
            $where[] = 'e.data_prevista_troca IS NOT NULL AND e.data_prevista_troca >= CURDATE() AND e.data_prevista_troca <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        } elseif ($status === 'valido') {
            $where[] = '(e.data_prevista_troca IS NULL OR e.data_prevista_troca > DATE_ADD(CURDATE(), INTERVAL 30 DAY))';
        }

        if (!empty($filters['termo_assinado'])) {
            if ($filters['termo_assinado'] === 'sim') {
                $where[] = 'e.termo_assinado = 1';
            } elseif ($filters['termo_assinado'] === 'nao') {
                $where[] = '(e.termo_assinado IS NULL OR e.termo_assinado = 0)';
            }
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT e.*, u.name AS colaborador_nome, ep.nome AS epi_nome
                FROM adms_sst_epi_entregas e
                LEFT JOIN adms_users u ON u.id = e.adms_user_id
                LEFT JOIN adms_sst_epis ep ON ep.id = e.adms_sst_epi_id
                WHERE {$whereClause}
                ORDER BY e.data_prevista_troca ASC, e.id DESC
                LIMIT 500";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReportTreinamentos(array $filters = []): array
    {
        if (!$this->hasTable('adms_sst_treinamento_vinculos')) {
            return [];
        }

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['adms_user_id'])) {
            $where[] = 'v.adms_user_id = :uid';
            $params[':uid'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'v.status = :status';
            $params[':status'] = (string) $filters['status'];
        }

        $statusVenc = $filters['status_vencimento'] ?? '';
        if ($statusVenc === 'vencido') {
            $where[] = "v.status = 'vencido'";
        } elseif ($statusVenc === 'a_vencer') {
            $where[] = "v.status = 'proximo_vencimento'";
        } elseif ($statusVenc === 'valido') {
            $where[] = "v.status IN ('dentro_do_prazo', 'concluido')";
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT v.*, u.name AS colaborador_nome, tr.nome AS treinamento_nome, tr.codigo AS treinamento_codigo
                FROM adms_sst_treinamento_vinculos v
                LEFT JOIN adms_users u ON u.id = v.adms_user_id
                LEFT JOIN adms_sst_treinamentos tr ON tr.id = v.adms_sst_treinamento_id
                WHERE {$whereClause}
                ORDER BY v.data_validade ASC, v.id DESC
                LIMIT 500";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function hasTable(string $table): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1'
        );
        $stmt->bindValue(':t', $table);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function listAfastamentosAtivos(int $limit = 5): array
    {
        $sql = "SELECT a.*, u.name AS colaborador_nome, c.codigo AS cid_codigo
                FROM adms_sst_afastamentos a
                INNER JOIN adms_users u ON u.id = a.adms_user_id
                LEFT JOIN adms_sst_cids c ON c.id = a.adms_sst_cid_id
                WHERE a.status = 'Ativo'
                ORDER BY a.data_inicio DESC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAfastamentosAtivosCount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_afastamentos WHERE status = 'Ativo'";
        return (int) ($this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getLowStockEpisCount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_epis
                WHERE status = 'Ativo' AND estoque_minimo > 0 AND estoque_atual <= estoque_minimo";
        return (int) ($this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getLowStockEpis(int $limit = 5): array
    {
        $sql = "SELECT * FROM adms_sst_epis
                WHERE status = 'Ativo' AND estoque_minimo > 0 AND estoque_atual <= estoque_minimo
                ORDER BY estoque_atual ASC, nome ASC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReportAfastamentos(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['adms_user_id'])) {
            $where[] = 'a.adms_user_id = :uid';
            $params[':uid'] = (int) $filters['adms_user_id'];
        }

        $status = $filters['status'] ?? '';
        if ($status !== '') {
            $where[] = 'a.status = :status';
            $params[':status'] = $status;
        }

        $tipo = trim((string) ($filters['tipo'] ?? ''));
        if ($tipo !== '') {
            $where[] = 'a.tipo = :tipo';
            $params[':tipo'] = $tipo;
        }

        if (!empty($filters['data_inicio_de'])) {
            $where[] = 'a.data_inicio >= :data_inicio_de';
            $params[':data_inicio_de'] = $filters['data_inicio_de'];
        }
        if (!empty($filters['data_inicio_ate'])) {
            $where[] = 'a.data_inicio <= :data_inicio_ate';
            $params[':data_inicio_ate'] = $filters['data_inicio_ate'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT a.*, u.name AS colaborador_nome, c.codigo AS cid_codigo, c.descricao AS cid_descricao
                FROM adms_sst_afastamentos a
                LEFT JOIN adms_users u ON u.id = a.adms_user_id
                LEFT JOIN adms_sst_cids c ON c.id = a.adms_sst_cid_id
                WHERE {$whereClause}
                ORDER BY a.data_inicio DESC, a.id DESC
                LIMIT 500";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
