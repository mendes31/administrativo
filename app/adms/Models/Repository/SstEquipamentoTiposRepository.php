<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class SstEquipamentoTiposRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*,
                       (SELECT COUNT(*) FROM adms_sst_equipamento_checklist_itens c
                        WHERE c.adms_sst_equipamento_tipo_id = t.id AND c.ativo = 1) AS total_checklist,
                       (SELECT COUNT(*) FROM adms_sst_equipamentos e
                        WHERE e.adms_sst_equipamento_tipo_id = t.id) AS total_equipamentos
                FROM adms_sst_equipamento_tipos t
                {$where}
                ORDER BY t.nome ASC
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
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_equipamento_tipos t {$where}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public function getAllActiveForSelect(): array
    {
        $sql = "SELECT id, nome, codigo, prefixo, controla_recarga, validade_recarga_meses
                FROM adms_sst_equipamento_tipos WHERE status = 'Ativo' ORDER BY nome";
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_sst_equipamento_tipos WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int|false
    {
        $sql = 'INSERT INTO adms_sst_equipamento_tipos
                (nome, codigo, prefixo, controla_recarga, validade_recarga_meses, descricao, status, created_by, updated_by, created_at, updated_at)
                VALUES (:nome, :codigo, :prefixo, :controla_recarga, :validade_meses, :descricao, :status, :uid, :uid, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':nome', $data['nome'] ?? '');
        $stmt->bindValue(':codigo', strtoupper(trim((string) ($data['codigo'] ?? ''))));
        $stmt->bindValue(':prefixo', \App\adms\Helpers\SstEquipamentoCodigoHelper::normalizePrefixo((string) ($data['prefixo'] ?? '')));
        $stmt->bindValue(':controla_recarga', !empty($data['controla_recarga']) ? 1 : 0, PDO::PARAM_INT);
        $validade = isset($data['validade_recarga_meses']) && $data['validade_recarga_meses'] !== ''
            ? (int) $data['validade_recarga_meses']
            : 12;
        $stmt->bindValue(':validade_meses', max(1, $validade), PDO::PARAM_INT);
        $stmt->bindValue(':descricao', $data['descricao'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo');
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }

        return (int) $this->getConnection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_sst_equipamento_tipos SET
                nome = :nome, codigo = :codigo, prefixo = :prefixo,
                controla_recarga = :controla_recarga, validade_recarga_meses = :validade_meses,
                descricao = :descricao, status = :status,
                updated_by = :uid, updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':nome', $data['nome'] ?? '');
        $stmt->bindValue(':codigo', strtoupper(trim((string) ($data['codigo'] ?? ''))));
        $stmt->bindValue(':prefixo', \App\adms\Helpers\SstEquipamentoCodigoHelper::normalizePrefixo((string) ($data['prefixo'] ?? '')));
        $stmt->bindValue(':controla_recarga', !empty($data['controla_recarga']) ? 1 : 0, PDO::PARAM_INT);
        $validade = isset($data['validade_recarga_meses']) && $data['validade_recarga_meses'] !== ''
            ? (int) $data['validade_recarga_meses']
            : 12;
        $stmt->bindValue(':validade_meses', max(1, $validade), PDO::PARAM_INT);
        $stmt->bindValue(':descricao', $data['descricao'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo');
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function prefixoExists(string $prefixo, ?int $excludeId = null): bool
    {
        $prefixo = \App\adms\Helpers\SstEquipamentoCodigoHelper::normalizePrefixo($prefixo);
        $sql = 'SELECT id FROM adms_sst_equipamento_tipos WHERE prefixo = :prefixo';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':prefixo', $prefixo);
        if ($excludeId !== null) {
            $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM adms_sst_equipamento_tipos WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function countEquipamentos(int $tipoId): int
    {
        $sql = 'SELECT COUNT(*) AS c FROM adms_sst_equipamentos WHERE adms_sst_equipamento_tipo_id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $tipoId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public function getChecklistItens(int $tipoId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM adms_sst_equipamento_checklist_itens
                WHERE adms_sst_equipamento_tipo_id = :id';
        if ($onlyActive) {
            $sql .= ' AND ativo = 1';
        }
        $sql .= ' ORDER BY ordem ASC, id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $tipoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function addChecklistItem(int $tipoId, array $data): int|false
    {
        $ordem = (int) ($data['ordem'] ?? 0);
        if ($ordem <= 0) {
            $ordem = $this->nextChecklistOrdem($tipoId);
        }
        $sql = 'INSERT INTO adms_sst_equipamento_checklist_itens
                (adms_sst_equipamento_tipo_id, descricao, ordem, obrigatorio, ativo, created_at, updated_at)
                VALUES (:tipo_id, :descricao, :ordem, :obrigatorio, 1, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':tipo_id', $tipoId, PDO::PARAM_INT);
        $stmt->bindValue(':descricao', trim((string) ($data['descricao'] ?? '')));
        $stmt->bindValue(':ordem', $ordem, PDO::PARAM_INT);
        $stmt->bindValue(':obrigatorio', !empty($data['obrigatorio']) ? 1 : 0, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }

        return (int) $this->getConnection()->lastInsertId();
    }

    public function updateChecklistItem(int $itemId, array $data): bool
    {
        $sql = 'UPDATE adms_sst_equipamento_checklist_itens SET
                descricao = :descricao, ordem = :ordem, obrigatorio = :obrigatorio, ativo = :ativo, updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(':descricao', trim((string) ($data['descricao'] ?? '')));
        $stmt->bindValue(':ordem', (int) ($data['ordem'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':obrigatorio', !empty($data['obrigatorio']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':ativo', !empty($data['ativo']) ? 1 : 0, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function deleteChecklistItem(int $itemId): bool
    {
        $sql = 'DELETE FROM adms_sst_equipamento_checklist_itens WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function nextChecklistOrdem(int $tipoId): int
    {
        $sql = 'SELECT COALESCE(MAX(ordem), 0) + 1 AS n FROM adms_sst_equipamento_checklist_itens WHERE adms_sst_equipamento_tipo_id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $tipoId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['n'] ?? 1);
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $filters): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(t.nome LIKE :search OR t.codigo LIKE :search OR t.prefixo LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }

        return [' WHERE ' . implode(' AND ', $where), $params];
    }
}
