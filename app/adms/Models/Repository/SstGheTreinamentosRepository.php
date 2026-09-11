<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\SstPendenciasService;
use PDO;

class SstGheTreinamentosRepository extends DbConnection
{
    /** @return list<array<string, mixed>> */
    public function getAllByGhe(int $gheId): array
    {
        if ($gheId <= 0) {
            return [];
        }
        $sql = "SELECT gt.*, tr.nome AS treinamento_nome, tr.codigo AS treinamento_codigo
                FROM adms_sst_ghe_treinamentos gt
                INNER JOIN adms_sst_treinamentos tr ON tr.id = gt.adms_sst_treinamento_id
                WHERE gt.adms_sst_ghe_id = :gid
                ORDER BY tr.nome";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':gid', $gheId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Uma vez: vínculos do checkbox antigo (obrigatório desmarcado por padrão) passam a obrigatórios.
     * Depois disso, obrigatorio = 0 permanece como opcional.
     */
    public function promoverVinculosSemFlagParaObrigatorio(): void
    {
        $marker = dirname(__DIR__, 3) . '/storage/cache/sst/ghe_obrigatorio_promovido.txt';
        if (is_file($marker)) {
            return;
        }
        $dir = dirname($marker);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $this->getConnection()->exec(
            'UPDATE adms_sst_ghe_treinamentos SET obrigatorio = 1 WHERE obrigatorio = 0'
        );
        SstPendenciasService::invalidateDashboardCache();
        @file_put_contents($marker, date('c'));
    }

    /** @param array<int, array{obrigatorio: bool, validade_meses?: int|null}> $treinamentoMap */
    public function syncTreinamentosForGhe(int $gheId, array $treinamentoMap): void
    {
        if ($gheId <= 0) {
            return;
        }

        $treinamentoMap = array_filter(
            $treinamentoMap,
            static fn (array $cfg, int $id): bool => $id > 0,
            ARRAY_FILTER_USE_BOTH
        );

        $currentRows = $this->getAllByGhe($gheId);
        $currentByTreinamento = [];
        foreach ($currentRows as $row) {
            $currentByTreinamento[(int) ($row['adms_sst_treinamento_id'] ?? 0)] = $row;
        }

        foreach ($treinamentoMap as $treinamentoId => $cfg) {
            $treinamentoId = (int) $treinamentoId;
            $obrigatorio = !empty($cfg['obrigatorio']);
            $validadeMeses = $cfg['validade_meses'] ?? null;
            if (isset($currentByTreinamento[$treinamentoId])) {
                $rowId = (int) ($currentByTreinamento[$treinamentoId]['id'] ?? 0);
                if ($rowId > 0) {
                    $this->update($rowId, [
                        'obrigatorio' => $obrigatorio,
                        'validade_meses' => $validadeMeses,
                    ]);
                }
                unset($currentByTreinamento[$treinamentoId]);
                continue;
            }
            $this->create([
                'adms_sst_ghe_id' => $gheId,
                'adms_sst_treinamento_id' => $treinamentoId,
                'obrigatorio' => $obrigatorio,
                'validade_meses' => $validadeMeses,
            ]);
        }

        foreach ($currentByTreinamento as $row) {
            $this->delete((int) ($row['id'] ?? 0));
        }

        SstPendenciasService::invalidateDashboardCache();
    }

    public function create(array $data): int|false
    {
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $sql = 'INSERT INTO adms_sst_ghe_treinamentos (
                    adms_sst_ghe_id, adms_sst_treinamento_id, validade_meses, obrigatorio, observacoes,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :adms_sst_ghe_id, :adms_sst_treinamento_id, :validade_meses, :obrigatorio, :observacoes,
                    :created_by, :updated_by, NOW(), NOW()
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindFields($stmt, $data);
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            LogAlteracaoService::registrarAlteracao('adms_sst_ghe_treinamentos', $newId, $uid, 'INSERT', [], $data);
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $sql = 'UPDATE adms_sst_ghe_treinamentos SET
                    validade_meses = :validade_meses, obrigatorio = :obrigatorio,
                    updated_by = :updated_by, updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':validade_meses', $data['validade_meses'] ?? null, $data['validade_meses'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':obrigatorio', !empty($data['obrigatorio']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_sst_ghe_treinamentos WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok) {
            LogAlteracaoService::registrarAlteracao('adms_sst_ghe_treinamentos', $id, $uid, 'DELETE', ['id' => $id], []);
        }

        return $ok;
    }

    /** @param \PDOStatement $stmt */
    private function bindFields($stmt, array $data): void
    {
        $stmt->bindValue(':adms_sst_ghe_id', (int) ($data['adms_sst_ghe_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':adms_sst_treinamento_id', (int) ($data['adms_sst_treinamento_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':validade_meses', $data['validade_meses'] ?? null, ($data['validade_meses'] ?? null) === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':obrigatorio', !empty($data['obrigatorio']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':observacoes', $data['observacoes'] ?? null);
    }
}
