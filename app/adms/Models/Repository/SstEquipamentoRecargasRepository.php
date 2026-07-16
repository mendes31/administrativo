<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\SstEquipamentoRecargaHelper;
use App\adms\Models\Services\DbConnection;
use PDO;
use Throwable;

class SstEquipamentoRecargasRepository extends DbConnection
{
    /** @return list<array<string, mixed>> */
    public function listByEquipamento(int $equipamentoId, int $limit = 50): array
    {
        $sql = "SELECT r.*, u.name AS created_by_nome
                FROM adms_sst_equipamento_recargas r
                LEFT JOIN adms_users u ON u.id = r.created_by
                WHERE r.adms_sst_equipamento_id = :id
                ORDER BY r.data_recarga DESC, r.id DESC
                LIMIT :limit";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $equipamentoId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByPeriodo(
        ?int $equipamentoId,
        string $dataInicio,
        string $dataFim,
        ?string $empresaContratante = null,
        ?int $tipoId = null,
    ): array {
        $where = ['WHERE r.data_recarga BETWEEN :de AND :ate'];
        $params = [
            ':de' => $dataInicio,
            ':ate' => $dataFim,
        ];
        if ($equipamentoId !== null && $equipamentoId > 0) {
            $where[] = 'r.adms_sst_equipamento_id = :eq';
            $params[':eq'] = $equipamentoId;
        }
        if ($empresaContratante !== null && $empresaContratante !== '') {
            $where[] = 'e.empresa_contratante = :empresa';
            $params[':empresa'] = $empresaContratante;
        }
        if ($tipoId !== null && $tipoId > 0) {
            $where[] = 'e.adms_sst_equipamento_tipo_id = :tipo';
            $params[':tipo'] = $tipoId;
        }

        $sql = 'SELECT r.*, e.codigo AS equipamento_codigo, e.localizacao, e.empresa_contratante,
                       t.nome AS tipo_nome, u.name AS created_by_nome
                FROM adms_sst_equipamento_recargas r
                INNER JOIN adms_sst_equipamentos e ON e.id = r.adms_sst_equipamento_id
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                LEFT JOIN adms_users u ON u.id = r.created_by
                ' . implode(' AND ', $where) . '
                ORDER BY e.codigo ASC, r.data_recarga ASC, r.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Registra evento, atualiza datas no equipamento e devolve o id do histórico.
     *
     * @param array{
     *   adms_sst_equipamento_id: int,
     *   tipo_evento?: string,
     *   data_recarga: string,
     *   data_proxima_recarga?: string|null,
     *   empresa?: string|null,
     *   numero_documento?: string|null,
     *   observacao?: string|null,
     *   validade_meses?: int|null
     * } $data
     */
    public function register(array $data): int|false
    {
        $equipamentoId = (int) ($data['adms_sst_equipamento_id'] ?? 0);
        $dataRecarga = trim((string) ($data['data_recarga'] ?? ''));
        if ($equipamentoId <= 0 || $dataRecarga === '') {
            return false;
        }

        $tiposEvento = SstEquipamentoRecargaHelper::tiposEvento();
        $tipoEvento = (string) ($data['tipo_evento'] ?? 'Recarga');
        if (!in_array($tipoEvento, $tiposEvento, true)) {
            $tipoEvento = 'Recarga';
        }

        $proxima = trim((string) ($data['data_proxima_recarga'] ?? ''));
        if ($proxima === '') {
            $meses = (int) ($data['validade_meses'] ?? 12);
            $proxima = SstEquipamentoRecargaHelper::calcularProxima($dataRecarga, $meses > 0 ? $meses : 12);
        }

        $uid = (int) ($_SESSION['user_id'] ?? 0);
        $pdo = $this->getConnection();
        $owns = !$pdo->inTransaction();
        if ($owns) {
            $pdo->beginTransaction();
        }

        try {
            $sql = 'INSERT INTO adms_sst_equipamento_recargas
                    (adms_sst_equipamento_id, tipo_evento, data_recarga, data_proxima_recarga,
                     empresa, numero_documento, observacao, created_by, created_at, updated_at)
                    VALUES
                    (:eq_id, :tipo, :data_recarga, :data_proxima, :empresa, :doc, :obs, :uid, NOW(), NOW())';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':eq_id', $equipamentoId, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $tipoEvento);
            $stmt->bindValue(':data_recarga', $dataRecarga);
            $stmt->bindValue(':data_proxima', $proxima !== '' ? $proxima : null);
            $stmt->bindValue(':empresa', $this->nullIfEmpty($data['empresa'] ?? null));
            $stmt->bindValue(':doc', $this->nullIfEmpty($data['numero_documento'] ?? null));
            $stmt->bindValue(':obs', $this->nullIfEmpty($data['observacao'] ?? null));
            $stmt->bindValue(':uid', $uid > 0 ? $uid : null, $uid > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            if (!$stmt->execute()) {
                if ($owns && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                return false;
            }

            $newId = (int) $pdo->lastInsertId();

            $upd = $pdo->prepare(
                'UPDATE adms_sst_equipamentos SET
                    data_recarga = :data_recarga,
                    data_proxima_recarga = :data_proxima,
                    updated_by = :uid,
                    updated_at = NOW()
                 WHERE id = :id'
            );
            $upd->bindValue(':data_recarga', $dataRecarga);
            $upd->bindValue(':data_proxima', $proxima !== '' ? $proxima : null);
            $upd->bindValue(':uid', $uid > 0 ? $uid : null, $uid > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $upd->bindValue(':id', $equipamentoId, PDO::PARAM_INT);
            $upd->execute();

            if ($owns) {
                $pdo->commit();
            }

            return $newId;
        } catch (Throwable $e) {
            if ($owns && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return false;
        }
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        $v = trim((string) ($value ?? ''));

        return $v !== '' ? $v : null;
    }
}
