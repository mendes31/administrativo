<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstPlanosAcaoRepository extends DbConnection
{
    public function getByAcidenteId(int $acidenteId): array
    {
        $sql = "SELECT p.*, u.name AS responsavel_nome
                FROM adms_sst_planos_acao p
                LEFT JOIN adms_users u ON u.id = p.responsavel_adms_user_id
                WHERE p.adms_sst_acidente_id = :aid
                ORDER BY
                    FIELD(p.status, 'Pendente', 'Em andamento', 'Concluído', 'Cancelado'),
                    p.prazo IS NULL, p.prazo ASC,
                    p.id ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':aid', $acidenteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT p.*, u.name AS responsavel_nome, a.tipo AS acidente_tipo
                FROM adms_sst_planos_acao p
                LEFT JOIN adms_users u ON u.id = p.responsavel_adms_user_id
                LEFT JOIN adms_sst_acidentes a ON a.id = p.adms_sst_acidente_id
                WHERE p.id = :id
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function countPendentesByAcidente(int $acidenteId): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_planos_acao
                WHERE adms_sst_acidente_id = :aid AND status IN ('Pendente', 'Em andamento')";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':aid', $acidenteId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function countVencidos(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_planos_acao
                WHERE status IN ('Pendente', 'Em andamento')
                  AND prazo IS NOT NULL AND prazo < CURDATE()";
        return (int) ($this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function create(array $data): int|false
    {
        if (($data['status'] ?? '') === 'Concluído' && empty($data['data_conclusao'])) {
            $data['data_conclusao'] = date('Y-m-d');
        }

        $sql = "INSERT INTO adms_sst_planos_acao
                (adms_sst_acidente_id, titulo, descricao, responsavel_adms_user_id, prazo, data_conclusao, status, observacoes, created_by, updated_by, created_at, updated_at)
                VALUES (:adms_sst_acidente_id, :titulo, :descricao, :responsavel_adms_user_id, :prazo, :data_conclusao, :status, :observacoes, :created_by, :updated_by, NOW(), NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':adms_sst_acidente_id', (int) $data['adms_sst_acidente_id'], PDO::PARAM_INT);
        $stmt->bindValue(':titulo', (string) $data['titulo']);
        $this->bindField($stmt, ':descricao', $data['descricao'] ?? null);
        $this->bindField($stmt, ':responsavel_adms_user_id', $data['responsavel_adms_user_id'] ?? null);
        $this->bindField($stmt, ':prazo', $data['prazo'] ?? null);
        $this->bindField($stmt, ':data_conclusao', $data['data_conclusao'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Pendente'));
        $this->bindField($stmt, ':observacoes', $data['observacoes'] ?? null);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return false;
        }

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_planos_acao', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        if (($data['status'] ?? '') === 'Concluído' && empty($data['data_conclusao'])) {
            $data['data_conclusao'] = date('Y-m-d');
        }

        $sql = "UPDATE adms_sst_planos_acao SET
                    titulo = :titulo,
                    descricao = :descricao,
                    responsavel_adms_user_id = :responsavel_adms_user_id,
                    prazo = :prazo,
                    data_conclusao = :data_conclusao,
                    status = :status,
                    observacoes = :observacoes,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':titulo', (string) $data['titulo']);
        $this->bindField($stmt, ':descricao', $data['descricao'] ?? null);
        $this->bindField($stmt, ':responsavel_adms_user_id', $data['responsavel_adms_user_id'] ?? null);
        $this->bindField($stmt, ':prazo', $data['prazo'] ?? null);
        $this->bindField($stmt, ':data_conclusao', $data['data_conclusao'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Pendente'));
        $this->bindField($stmt, ':observacoes', $data['observacoes'] ?? null);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();

        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_planos_acao', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_planos_acao WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        $uid = (int) ($_SESSION['user_id'] ?? 1);

        if ($ok && $oldData) {
            LogAlteracaoService::registrarAlteracao('adms_sst_planos_acao', $id, $uid, 'DELETE', $oldData, []);
        }

        return $ok;
    }

    private function bindField(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($param, $value);
    }
}
