<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstEpiFichasRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT f.*, u.name AS colaborador_nome, uc.name AS entregue_por_nome,
                       (SELECT COUNT(*) FROM adms_sst_epi_ficha_itens i WHERE i.adms_sst_epi_ficha_id = f.id) AS total_itens
                FROM adms_sst_epi_fichas f
                LEFT JOIN adms_users u ON u.id = f.adms_user_id
                LEFT JOIN adms_users uc ON uc.id = f.created_by
                {$whereClause}
                ORDER BY f.data_entrega DESC, f.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTotal(array $filters = []): int
    {
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_epi_fichas f
                LEFT JOIN adms_users u ON u.id = f.adms_user_id
                {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT f.*, u.name AS colaborador_nome, u.cpf AS colaborador_cpf,
                       uc.name AS entregue_por_nome,
                       p.name AS cargo_nome, d.name AS departamento_nome
                FROM adms_sst_epi_fichas f
                LEFT JOIN adms_users u ON u.id = f.adms_user_id
                LEFT JOIN adms_users uc ON uc.id = f.created_by
                LEFT JOIN adms_positions p ON p.id = u.user_position_id
                LEFT JOIN adms_departments d ON d.id = u.user_department_id
                WHERE f.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getByIdForUser(int $id, int $userId): ?array
    {
        $row = $this->getById($id);
        if (!$row || (int) ($row['adms_user_id'] ?? 0) !== $userId) {
            return null;
        }

        return $row;
    }

    /** @return list<array<string, mixed>> */
    public function getItens(int $fichaId): array
    {
        $sql = "SELECT i.*, ep.nome AS epi_nome
                FROM adms_sst_epi_ficha_itens i
                INNER JOIN adms_sst_epis ep ON ep.id = i.adms_sst_epi_id
                WHERE i.adms_sst_epi_ficha_id = :fid
                ORDER BY i.id ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':fid', $fichaId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function getByUserId(int $userId, int $limit = 50): array
    {
        $sql = "SELECT f.*, uc.name AS entregue_por_nome,
                       (SELECT COUNT(*) FROM adms_sst_epi_ficha_itens i WHERE i.adms_sst_epi_ficha_id = f.id) AS total_itens
                FROM adms_sst_epi_fichas f
                LEFT JOIN adms_users uc ON uc.id = f.created_by
                WHERE f.adms_user_id = :uid
                ORDER BY f.data_entrega DESC, f.id DESC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lista consolidada de EPIs entregues ao colaborador (fichas assinadas + registros legados).
     *
     * @return list<array<string, mixed>>
     */
    public function getEpisEntreguesPorColaborador(int $userId, int $limit = 200): array
    {
        $sql = "SELECT * FROM (
                    SELECT
                        fi.id AS linha_id,
                        'ficha' AS origem,
                        f.id AS ficha_id,
                        f.data_entrega AS data_entrega,
                        ep.nome AS epi_nome,
                        fi.quantidade,
                        fi.ca_utilizado AS ca,
                        fi.tamanho AS tamanho,
                        fi.data_prevista_troca,
                        f.status_assinatura,
                        f.signed_at
                    FROM adms_sst_epi_ficha_itens fi
                    INNER JOIN adms_sst_epi_fichas f ON f.id = fi.adms_sst_epi_ficha_id
                    INNER JOIN adms_sst_epis ep ON ep.id = fi.adms_sst_epi_id
                    WHERE f.adms_user_id = :uid1 AND f.status_assinatura = 'Assinado'
                    UNION ALL
                    SELECT
                        e.id AS linha_id,
                        'legado' AS origem,
                        e.adms_sst_epi_ficha_id AS ficha_id,
                        e.data_movimento AS data_entrega,
                        ep.nome AS epi_nome,
                        e.quantidade,
                        NULL AS ca,
                        NULL AS tamanho,
                        e.data_prevista_troca,
                        CASE WHEN e.termo_assinado = 1 THEN 'Assinado' ELSE 'Pendente' END AS status_assinatura,
                        NULL AS signed_at
                    FROM adms_sst_epi_entregas e
                    INNER JOIN adms_sst_epis ep ON ep.id = e.adms_sst_epi_id
                    WHERE e.adms_user_id = :uid2
                      AND e.tipo_movimento = 'Entrega'
                      AND (e.adms_sst_epi_ficha_id IS NULL OR e.adms_sst_epi_ficha_id = 0)
                ) AS t
                ORDER BY data_entrega DESC, linha_id DESC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param list<array<string, mixed>> $itens
     */
    public function createWithItens(array $header, array $itens): int|false
    {
        $conn = $this->getConnection();
        $conn->beginTransaction();
        try {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            $sql = 'INSERT INTO adms_sst_epi_fichas
                (adms_user_id, data_entrega, status_assinatura, observacoes, created_by, updated_by, created_at, updated_at)
                VALUES (:adms_user_id, :data_entrega, :status_assinatura, :observacoes, :created_by, :updated_by, NOW(), NOW())';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':adms_user_id', (int) $header['adms_user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':data_entrega', (string) $header['data_entrega'], PDO::PARAM_STR);
            $stmt->bindValue(':status_assinatura', $header['status_assinatura'] ?? 'Pendente', PDO::PARAM_STR);
            $obs = trim((string) ($header['observacoes'] ?? ''));
            $stmt->bindValue(':observacoes', $obs !== '' ? $obs : null, $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
            $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                $conn->rollBack();

                return false;
            }
            $fichaId = (int) $conn->lastInsertId();
            $this->insertItens($fichaId, $itens);
            $conn->commit();
            $snap = $this->getById($fichaId);
            if ($snap) {
                LogAlteracaoService::registrarAlteracao('adms_sst_epi_fichas', $fichaId, $uid, 'INSERT', [], $snap);
            }

            return $fichaId;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $e;
        }
    }

    /** @param list<array<string, mixed>> $itens */
    private function insertItens(int $fichaId, array $itens): void
    {
        $sql = 'INSERT INTO adms_sst_epi_ficha_itens
            (adms_sst_epi_ficha_id, adms_sst_epi_id, quantidade, ca_utilizado'
            . ($this->hasItemColumn('tamanho') ? ', tamanho' : '')
            . ', data_prevista_troca, observacoes, created_at)
            VALUES (:ficha_id, :epi_id, :quantidade, :ca_utilizado'
            . ($this->hasItemColumn('tamanho') ? ', :tamanho' : '')
            . ', :data_prevista_troca, :observacoes, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($itens as $item) {
            $epiId = (int) ($item['adms_sst_epi_id'] ?? 0);
            if ($epiId <= 0) {
                continue;
            }
            $qty = max(1, (int) ($item['quantidade'] ?? 1));
            $ca = trim((string) ($item['ca_utilizado'] ?? ''));
            $prev = trim((string) ($item['data_prevista_troca'] ?? ''));
            $obs = trim((string) ($item['observacoes'] ?? ''));
            $stmt->bindValue(':ficha_id', $fichaId, PDO::PARAM_INT);
            $stmt->bindValue(':epi_id', $epiId, PDO::PARAM_INT);
            $stmt->bindValue(':quantidade', $qty, PDO::PARAM_INT);
            $stmt->bindValue(':ca_utilizado', $ca !== '' ? $ca : null, $ca !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            if ($this->hasItemColumn('tamanho')) {
                $tam = \App\adms\Helpers\SstEpiTamanhoHelper::normalize((string) ($item['tamanho'] ?? ''));
                $stmt->bindValue(':tamanho', $tam !== '' ? $tam : null, $tam !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            }
            $stmt->bindValue(':data_prevista_troca', $prev !== '' ? $prev : null, $prev !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':observacoes', $obs !== '' ? $obs : null, $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
        }
    }

    public function updatePdfMeta(int $fichaId, string $storagePath, string $hashSha256): bool
    {
        $sql = 'UPDATE adms_sst_epi_fichas SET pdf_storage_path = :path, pdf_hash_sha256 = :hash, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':path', $storagePath, PDO::PARAM_STR);
        $stmt->bindValue(':hash', strtolower($hashSha256), PDO::PARAM_STR);
        $stmt->bindValue(':id', $fichaId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function recordSignature(
        int $fichaId,
        int $userId,
        string $ip,
        ?string $userAgent,
        string $documentHash
    ): bool {
        $before = $this->getById($fichaId);
        if (!$before || ($before['status_assinatura'] ?? '') !== 'Pendente') {
            return false;
        }
        if ((int) ($before['adms_user_id'] ?? 0) !== $userId) {
            return false;
        }

        $ua = $userAgent !== null ? mb_substr($userAgent, 0, 512) : null;
        $sql = 'UPDATE adms_sst_epi_fichas SET
            status_assinatura = \'Assinado\',
            signed_at = NOW(),
            signed_ip = :ip,
            signed_user_agent = :ua,
            signed_auth_method = \'session\',
            signed_document_hash_sha256 = :dh,
            updated_by = :uid,
            updated_at = NOW()
            WHERE id = :id AND status_assinatura = \'Pendente\'';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ip', mb_substr($ip, 0, 45), PDO::PARAM_STR);
        $stmt->bindValue(':ua', $ua, $ua === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':dh', strtolower($documentHash), PDO::PARAM_STR);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $fichaId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() <= 0) {
            return false;
        }

        $this->syncEntregasFromFicha($fichaId, $userId);
        $after = $this->getById($fichaId);
        if ($after) {
            LogAlteracaoService::registrarAlteracao('adms_sst_epi_fichas', $fichaId, $userId, 'UPDATE', $before, $after);
        }

        return true;
    }

    private function syncEntregasFromFicha(int $fichaId, int $actorUserId): void
    {
        if (!$this->hasColumnOnEntregas('adms_sst_epi_ficha_id')) {
            return;
        }
        $ficha = $this->getById($fichaId);
        if (!$ficha) {
            return;
        }
        $itens = $this->getItens($fichaId);
        $entregaRepo = new SstEpiEntregasRepository();
        $estoqueSvc = new \App\adms\Models\Services\SstEpiEstoqueService();
        foreach ($itens as $item) {
            $entregaRepo->createFromFichaItem($ficha, $item, $fichaId, $actorUserId);
            $estoqueSvc->registrarSaidaPorFicha(
                $fichaId,
                (int) ($item['adms_sst_epi_id'] ?? 0),
                (int) ($item['quantidade'] ?? 1),
                (string) ($ficha['data_entrega'] ?? date('Y-m-d')),
                isset($item['ca_utilizado']) ? (string) $item['ca_utilizado'] : null,
                isset($item['tamanho']) ? (string) $item['tamanho'] : null,
                isset($item['id']) ? (int) $item['id'] : null
            );
        }
    }

    private function hasItemColumn(string $column): bool
    {
        static $cache = [];
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }
        try {
            $stmt = $this->getConnection()->query(
                'SHOW COLUMNS FROM adms_sst_epi_ficha_itens LIKE ' . $this->getConnection()->quote($column)
            );
            $cache[$column] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }

    private function hasColumnOnEntregas(string $column): bool
    {
        try {
            $stmt = $this->getConnection()->query('SHOW COLUMNS FROM adms_sst_epi_entregas LIKE ' . $this->getConnection()->quote($column));
            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException) {
            return false;
        }
    }

    public function absoluteStoragePath(string $relativeFromProjectRoot): string
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);

        return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativeFromProjectRoot, '/\\'));
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE :search OR f.id LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['adms_user_id'])) {
            $where[] = 'f.adms_user_id = :adms_user_id';
            $params[':adms_user_id'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['status_assinatura'])) {
            $where[] = 'f.status_assinatura = :status_assinatura';
            $params[':status_assinatura'] = $filters['status_assinatura'];
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }
}
