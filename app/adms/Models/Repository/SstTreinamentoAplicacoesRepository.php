<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstTreinamentoAplicacoesRepository extends DbConnection
{
    /** @return list<array<string, mixed>> */
    public function getByVinculoId(int $vinculoId, int $limit = 50): array
    {
        $sql = 'SELECT a.*, u.name AS aplicado_por_nome
                FROM adms_sst_treinamento_aplicacoes a
                LEFT JOIN adms_users u ON u.id = a.aplicado_por
                WHERE a.adms_sst_treinamento_vinculo_id = :vid
                ORDER BY a.data_realizacao DESC, a.id DESC
                LIMIT :lim';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':vid', $vinculoId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT a.*, u.name AS aplicado_por_nome
                FROM adms_sst_treinamento_aplicacoes a
                LEFT JOIN adms_users u ON u.id = a.aplicado_por
                WHERE a.id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getByIdDetalhado(int $id): ?array
    {
        $sql = 'SELECT a.*, u.name AS aplicado_por_nome,
                       tr.nome AS treinamento_nome, tr.codigo AS treinamento_codigo,
                       tr.nr_referencia, tr.tipo AS treinamento_tipo, tr.modalidade AS treinamento_modalidade,
                       tr.carga_horaria_minutos,
                       v.data_validade AS data_validade_vinculo
                FROM adms_sst_treinamento_aplicacoes a
                LEFT JOIN adms_users u ON u.id = a.aplicado_por
                INNER JOIN adms_sst_treinamentos tr ON tr.id = a.adms_sst_treinamento_id
                LEFT JOIN adms_sst_treinamento_vinculos v ON v.id = a.adms_sst_treinamento_vinculo_id
                WHERE a.id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): int|false
    {
        $sql = 'INSERT INTO adms_sst_treinamento_aplicacoes (
                    adms_sst_treinamento_vinculo_id, adms_user_id, adms_sst_treinamento_id,
                    data_realizacao, data_agendada, nota, instrutor_nome, instrutor_registro,
                    modalidade_aplicada, certificado, observacoes, status, aplicado_por,
                    created_at, updated_at
                ) VALUES (
                    :adms_sst_treinamento_vinculo_id, :adms_user_id, :adms_sst_treinamento_id,
                    :data_realizacao, :data_agendada, :nota, :instrutor_nome, :instrutor_registro,
                    :modalidade_aplicada, :certificado, :observacoes, :status, :aplicado_por,
                    NOW(), NOW()
                )';
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        if (empty($data['aplicado_por'])) {
            $data['aplicado_por'] = $uid;
        }
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindFields($stmt, $data);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_treinamento_aplicacoes', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function updateCertificado(int $id, string $relPath): bool
    {
        $oldData = $this->getById($id);
        if (!$oldData) {
            return false;
        }
        $sql = 'UPDATE adms_sst_treinamento_aplicacoes SET certificado = :certificado, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':certificado', $relPath, PDO::PARAM_STR);
        $ok = $stmt->execute();
        if ($ok) {
            $newData = $this->getById($id);
            if ($newData) {
                $uid = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao('adms_sst_treinamento_aplicacoes', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    private function bindFields(\PDOStatement $stmt, array $data): void
    {
        $this->bindField($stmt, ':adms_sst_treinamento_vinculo_id', $data['adms_sst_treinamento_vinculo_id'] ?? null);
        $this->bindField($stmt, ':adms_user_id', $data['adms_user_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_treinamento_id', $data['adms_sst_treinamento_id'] ?? null);
        $this->bindField($stmt, ':data_realizacao', $data['data_realizacao'] ?? null);
        $this->bindField($stmt, ':data_agendada', $data['data_agendada'] ?? null);
        $this->bindField($stmt, ':nota', $data['nota'] ?? null);
        $this->bindField($stmt, ':instrutor_nome', $data['instrutor_nome'] ?? null);
        $this->bindField($stmt, ':instrutor_registro', $data['instrutor_registro'] ?? null);
        $this->bindField($stmt, ':modalidade_aplicada', $data['modalidade_aplicada'] ?? null);
        $this->bindField($stmt, ':certificado', $data['certificado'] ?? null);
        $this->bindField($stmt, ':observacoes', $data['observacoes'] ?? null);
        $this->bindField($stmt, ':status', $data['status'] ?? 'concluido');
        $this->bindField($stmt, ':aplicado_por', $data['aplicado_por'] ?? null);
    }

    private function bindField(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);

            return;
        }
        if (is_int($value)) {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);

            return;
        }
        $stmt->bindValue($param, (string) $value, PDO::PARAM_STR);
    }
}
