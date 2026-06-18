<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class SstAsoExamesRepository extends DbConnection
{
    /** @return list<array<string, mixed>> */
    public function getByAsoId(int $asoId): array
    {
        $sql = "SELECT ae.*, ex.nome AS exame_nome
                FROM adms_sst_aso_exames ae
                INNER JOIN adms_sst_exames ex ON ex.id = ae.adms_sst_exame_id
                WHERE ae.adms_sst_aso_id = :aso_id
                ORDER BY ex.nome";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':aso_id', $asoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Última realização do exame complementar (itens do ASO ou legado no cabeçalho).
     *
     * @return array<string, mixed>|null
     */
    public function getUltimaRealizacao(int $userId, int $exameId, ?string $categoriaAso = null): ?array
    {
        $categoriaFilter = '';
        $params = [
            ':uid' => $userId,
            ':exame_id' => $exameId,
        ];
        if ($categoriaAso !== null && $categoriaAso !== '') {
            $categoriaFilter = ' AND a.tipo = :categoria_aso';
            $params[':categoria_aso'] = $categoriaAso;
        }

        $sql = "SELECT ae.adms_sst_exame_id, ae.data_realizacao, ae.resultado,
                       a.id AS aso_id, a.tipo AS categoria_aso, a.data_realizacao AS aso_data_realizacao, a.data_validade
                FROM adms_sst_aso_exames ae
                INNER JOIN adms_sst_asos a ON a.id = ae.adms_sst_aso_id
                WHERE a.adms_user_id = :uid
                  AND ae.adms_sst_exame_id = :exame_id
                  {$categoriaFilter}
                ORDER BY COALESCE(ae.data_realizacao, a.data_realizacao) DESC, a.id DESC
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }

        $sqlLegacy = "SELECT a.adms_sst_exame_id, a.data_realizacao, a.resultado,
                             a.id AS aso_id, a.tipo AS categoria_aso, a.data_realizacao AS aso_data_realizacao, a.data_validade
                      FROM adms_sst_asos a
                      WHERE a.adms_user_id = :uid
                        AND a.adms_sst_exame_id = :exame_id
                        {$categoriaFilter}
                      ORDER BY a.data_realizacao DESC, a.id DESC
                      LIMIT 1";
        $stmtLegacy = $this->getConnection()->prepare($sqlLegacy);
        foreach ($params as $k => $v) {
            $stmtLegacy->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmtLegacy->execute();
        $legacy = $stmtLegacy->fetch(PDO::FETCH_ASSOC);

        return $legacy ?: null;
    }

    /**
     * @param list<array{adms_sst_exame_id?: int, data_realizacao?: string|null, resultado?: string|null, observacoes?: string|null}> $rows
     */
    public function syncForAso(int $asoId, array $rows): void
    {
        $conn = $this->getConnection();
        $del = $conn->prepare('DELETE FROM adms_sst_aso_exames WHERE adms_sst_aso_id = :aso_id');
        $del->bindValue(':aso_id', $asoId, PDO::PARAM_INT);
        $del->execute();

        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $ins = $conn->prepare(
            'INSERT INTO adms_sst_aso_exames
                (adms_sst_aso_id, adms_sst_exame_id, data_realizacao, resultado, observacoes, created_by, updated_by, created_at, updated_at)
             VALUES (:aso_id, :exame_id, :data_realizacao, :resultado, :observacoes, :uid, :uid, NOW(), NOW())'
        );

        foreach ($rows as $row) {
            $exameId = (int) ($row['adms_sst_exame_id'] ?? 0);
            if ($exameId <= 0) {
                continue;
            }
            $ins->bindValue(':aso_id', $asoId, PDO::PARAM_INT);
            $ins->bindValue(':exame_id', $exameId, PDO::PARAM_INT);
            $dataReal = $row['data_realizacao'] ?? null;
            if ($dataReal === null || $dataReal === '') {
                $ins->bindValue(':data_realizacao', null, PDO::PARAM_NULL);
            } else {
                $ins->bindValue(':data_realizacao', (string) $dataReal, PDO::PARAM_STR);
            }
            $resultado = $row['resultado'] ?? null;
            if ($resultado === null || $resultado === '') {
                $ins->bindValue(':resultado', null, PDO::PARAM_NULL);
            } else {
                $ins->bindValue(':resultado', (string) $resultado, PDO::PARAM_STR);
            }
            $obs = $row['observacoes'] ?? null;
            if ($obs === null || $obs === '') {
                $ins->bindValue(':observacoes', null, PDO::PARAM_NULL);
            } else {
                $ins->bindValue(':observacoes', (string) $obs, PDO::PARAM_STR);
            }
            $ins->bindValue(':uid', $uid, PDO::PARAM_INT);
            $ins->execute();
        }
    }
}
