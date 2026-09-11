<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\SstPendenciasService;
use PDO;

class SstGheColaboradoresRepository extends DbConnection
{
    /** @return list<array<string, mixed>> */
    public function getAtivosByGheId(int $gheId): array
    {
        if ($gheId <= 0) {
            return [];
        }
        $sql = "SELECT gc.*, u.name AS colaborador_nome
                FROM adms_sst_ghe_colaboradores gc
                INNER JOIN adms_users u ON u.id = gc.adms_user_id
                WHERE gc.adms_sst_ghe_id = :gid AND gc.data_fim IS NULL
                ORDER BY u.name";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':gid', $gheId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param list<int> $userIds */
    public function syncColaboradoresForGhe(int $gheId, array $userIds, ?string $dataInicio = null): void
    {
        if ($gheId <= 0) {
            return;
        }
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn(int $id): bool => $id > 0)));
        $inicio = $dataInicio !== null && $dataInicio !== '' ? $dataInicio : date('Y-m-d');
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $conn = $this->getConnection();

        $atuais = $this->getAtivosByGheId($gheId);
        $atuaisIds = array_map(static fn(array $r): int => (int) ($r['adms_user_id'] ?? 0), $atuais);

        foreach ($atuais as $row) {
            $userId = (int) ($row['adms_user_id'] ?? 0);
            if ($userId > 0 && !in_array($userId, $userIds, true)) {
                $this->encerrarVinculo((int) $row['id'], $uid);
            }
        }

        foreach ($userIds as $userId) {
            if (in_array($userId, $atuaisIds, true)) {
                continue;
            }
            $this->encerrarGheAtivoDoUsuario($userId, $uid);
            $sql = 'INSERT INTO adms_sst_ghe_colaboradores (
                        adms_sst_ghe_id, adms_user_id, data_inicio, data_fim, observacoes,
                        created_by, updated_by, created_at, updated_at
                    ) VALUES (
                        :gid, :uid, :inicio, NULL, NULL,
                        :created_by, :updated_by, NOW(), NOW()
                    )';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':gid', $gheId, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':inicio', $inicio);
            $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
            $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
            $stmt->execute();
            $newId = (int) $conn->lastInsertId();
            if ($newId > 0) {
                LogAlteracaoService::registrarAlteracao('adms_sst_ghe_colaboradores', $newId, $uid, 'INSERT', [], [
                    'adms_sst_ghe_id' => $gheId,
                    'adms_user_id' => $userId,
                    'data_inicio' => $inicio,
                ]);
            }
        }

        SstPendenciasService::invalidateDashboardCache();
    }

    private function encerrarVinculo(int $id, int $uid): void
    {
        $sql = 'UPDATE adms_sst_ghe_colaboradores SET data_fim = CURDATE(), updated_by = :ub, updated_at = NOW() WHERE id = :id AND data_fim IS NULL';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':ub', $uid, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function encerrarGheAtivoDoUsuario(int $userId, int $uid): void
    {
        $sql = 'UPDATE adms_sst_ghe_colaboradores SET data_fim = CURDATE(), updated_by = :ub, updated_at = NOW()
                WHERE adms_user_id = :uid AND data_fim IS NULL';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':ub', $uid, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @param array{adms_sst_ghe_id: int, adms_user_id: int, data_inicio?: string|null, observacoes?: string|null} $data
     */
    public function create(array $data): int|false
    {
        $gheId = (int) ($data['adms_sst_ghe_id'] ?? 0);
        $userId = (int) ($data['adms_user_id'] ?? 0);
        if ($gheId <= 0 || $userId <= 0) {
            return false;
        }
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $inicio = trim((string) ($data['data_inicio'] ?? ''));
        if ($inicio === '') {
            $inicio = date('Y-m-d');
        }
        $this->encerrarGheAtivoDoUsuario($userId, $uid);
        $sql = 'INSERT INTO adms_sst_ghe_colaboradores (
                    adms_sst_ghe_id, adms_user_id, data_inicio, data_fim, observacoes,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :gid, :uid, :inicio, NULL, :obs,
                    :created_by, :updated_by, NOW(), NOW()
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':gid', $gheId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':inicio', $inicio);
        $stmt->bindValue(':obs', $data['observacoes'] ?? null);
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            LogAlteracaoService::registrarAlteracao('adms_sst_ghe_colaboradores', $newId, $uid, 'INSERT', [], [
                'adms_sst_ghe_id' => $gheId,
                'adms_user_id' => $userId,
                'data_inicio' => $inicio,
            ]);
            SstPendenciasService::invalidateDashboardCache();
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $sql = 'UPDATE adms_sst_ghe_colaboradores SET
                    data_inicio = :inicio, observacoes = :obs,
                    updated_by = :ub, updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':inicio', $data['data_inicio'] ?? date('Y-m-d'));
        $stmt->bindValue(':obs', $data['observacoes'] ?? null);
        $stmt->bindValue(':ub', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok) {
            SstPendenciasService::invalidateDashboardCache();
        }

        return $ok;
    }
}
