<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstEquipamentoAcoesCorretivasRepository extends DbConnection
{
    /** @return list<array<string, mixed>> */
    public function getByNaoConformidadeId(int $ncId): array
    {
        $sql = "SELECT a.*, u.name AS responsavel_nome
                FROM adms_sst_equipamento_acoes_corretivas a
                LEFT JOIN adms_users u ON u.id = a.responsavel_adms_user_id
                WHERE a.adms_sst_equipamento_nao_conformidade_id = :ncid
                ORDER BY FIELD(a.status, 'Pendente', 'Em andamento', 'Concluído', 'Cancelado'), a.id ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ncid', $ncId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT a.*, u.name AS responsavel_nome, nc.codigo AS nc_codigo, nc.status AS nc_status,
                       nc.adms_sst_equipamento_id, nc.descricao AS nc_descricao
                FROM adms_sst_equipamento_acoes_corretivas a
                INNER JOIN adms_sst_equipamento_nao_conformidades nc
                    ON nc.id = a.adms_sst_equipamento_nao_conformidade_id
                LEFT JOIN adms_users u ON u.id = a.responsavel_adms_user_id
                WHERE a.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int|false
    {
        if (($data['status'] ?? '') === 'Concluído' && empty($data['data_conclusao'])) {
            $data['data_conclusao'] = date('Y-m-d');
        }
        $sql = 'INSERT INTO adms_sst_equipamento_acoes_corretivas
                (codigo, adms_sst_equipamento_nao_conformidade_id, titulo, descricao,
                 responsavel_adms_user_id, prazo, data_conclusao, status, observacoes,
                 created_by, updated_by, created_at, updated_at)
                VALUES (:codigo, :ncid, :titulo, :descricao, :resp, :prazo, :data_conclusao, :status, :obs,
                        :created_by, :updated_by, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $tempCodigo = 'TMP-' . uniqid('', true);
        $stmt->bindValue(':codigo', $tempCodigo);
        $stmt->bindValue(':ncid', (int) $data['adms_sst_equipamento_nao_conformidade_id'], PDO::PARAM_INT);
        $stmt->bindValue(':titulo', (string) $data['titulo']);
        $this->bindNullable($stmt, ':descricao', $data['descricao'] ?? null);
        $resp = !empty($data['responsavel_adms_user_id']) ? (int) $data['responsavel_adms_user_id'] : null;
        if ($resp === null) {
            $stmt->bindValue(':resp', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':resp', $resp, PDO::PARAM_INT);
        }
        $this->bindNullable($stmt, ':prazo', $data['prazo'] ?? null);
        $this->bindNullable($stmt, ':data_conclusao', $data['data_conclusao'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Pendente'));
        $this->bindNullable($stmt, ':obs', $data['observacoes'] ?? null);
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId <= 0) {
            return false;
        }
        $codigo = 'AC-' . str_pad((string) $newId, 6, '0', STR_PAD_LEFT);
        $upd = $this->getConnection()->prepare(
            'UPDATE adms_sst_equipamento_acoes_corretivas SET codigo = :c WHERE id = :id'
        );
        $upd->bindValue(':c', $codigo);
        $upd->bindValue(':id', $newId, PDO::PARAM_INT);
        $upd->execute();

        $newData = $this->getById($newId);
        if ($newData) {
            LogAlteracaoService::registrarAlteracao('adms_sst_equipamento_acoes_corretivas', $newId, $uid, 'INSERT', [], $newData);
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $old = $this->getById($id);
        if (!$old) {
            return false;
        }
        if (($data['status'] ?? '') === 'Concluído' && empty($data['data_conclusao'])) {
            $data['data_conclusao'] = date('Y-m-d');
        }
        $sql = 'UPDATE adms_sst_equipamento_acoes_corretivas SET
                    titulo = :titulo,
                    descricao = :descricao,
                    responsavel_adms_user_id = :resp,
                    prazo = :prazo,
                    data_conclusao = :data_conclusao,
                    status = :status,
                    observacoes = :obs,
                    updated_by = :uid,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':titulo', (string) $data['titulo']);
        $this->bindNullable($stmt, ':descricao', $data['descricao'] ?? null);
        $resp = !empty($data['responsavel_adms_user_id']) ? (int) $data['responsavel_adms_user_id'] : null;
        if ($resp === null) {
            $stmt->bindValue(':resp', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':resp', $resp, PDO::PARAM_INT);
        }
        $this->bindNullable($stmt, ':prazo', $data['prazo'] ?? null);
        $this->bindNullable($stmt, ':data_conclusao', $data['data_conclusao'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Pendente'));
        $this->bindNullable($stmt, ':obs', $data['observacoes'] ?? null);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $new = $this->getById($id);
        if ($new) {
            LogAlteracaoService::registrarAlteracao('adms_sst_equipamento_acoes_corretivas', $id, $uid, 'UPDATE', $old, $new);
        }

        return true;
    }

    private function bindNullable(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue($param, (string) $value);
        }
    }
}
