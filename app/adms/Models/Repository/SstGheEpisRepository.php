<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\SstPendenciasService;
use PDO;

class SstGheEpisRepository extends DbConnection
{
    /** @return list<array<string, mixed>> */
    public function getAllByGhe(int $gheId): array
    {
        if ($gheId <= 0 || !$this->hasTable('adms_sst_ghe_epis')) {
            return [];
        }
        $sql = "SELECT ge.*, ep.nome AS epi_nome
                FROM adms_sst_ghe_epis ge
                INNER JOIN adms_sst_epis ep ON ep.id = ge.adms_sst_epi_id
                WHERE ge.adms_sst_ghe_id = :gid
                ORDER BY ep.nome";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':gid', $gheId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param array<int, array{obrigatorio: bool}> $epiMap */
    public function syncEpisForGhe(int $gheId, array $epiMap): bool
    {
        if ($gheId <= 0 || !$this->hasTable('adms_sst_ghe_epis')) {
            return false;
        }

        $epiMap = array_filter(
            $epiMap,
            static fn (array $cfg, int $id): bool => $id > 0,
            ARRAY_FILTER_USE_BOTH
        );

        $currentRows = $this->getAllByGhe($gheId);
        $currentByEpi = [];
        foreach ($currentRows as $row) {
            $currentByEpi[(int) ($row['adms_sst_epi_id'] ?? 0)] = $row;
        }

        foreach ($epiMap as $epiId => $cfg) {
            $epiId = (int) $epiId;
            $obrigatorio = !empty($cfg['obrigatorio']);
            if (isset($currentByEpi[$epiId])) {
                $rowId = (int) ($currentByEpi[$epiId]['id'] ?? 0);
                if ($rowId > 0) {
                    $this->update($rowId, ['obrigatorio' => $obrigatorio]);
                }
                unset($currentByEpi[$epiId]);
                continue;
            }
            $this->create([
                'adms_sst_ghe_id' => $gheId,
                'adms_sst_epi_id' => $epiId,
                'obrigatorio' => $obrigatorio,
            ]);
        }

        foreach ($currentByEpi as $row) {
            $this->delete((int) ($row['id'] ?? 0));
        }

        SstPendenciasService::invalidateDashboardCache();

        return true;
    }

    public function create(array $data): int|false
    {
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $sql = 'INSERT INTO adms_sst_ghe_epis (
                    adms_sst_ghe_id, adms_sst_epi_id, obrigatorio, observacoes,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :adms_sst_ghe_id, :adms_sst_epi_id, :obrigatorio, :observacoes,
                    :created_by, :updated_by, NOW(), NOW()
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':adms_sst_ghe_id', (int) ($data['adms_sst_ghe_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':adms_sst_epi_id', (int) ($data['adms_sst_epi_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':obrigatorio', !empty($data['obrigatorio']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':observacoes', $data['observacoes'] ?? null);
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            LogAlteracaoService::registrarAlteracao('adms_sst_ghe_epis', $newId, $uid, 'INSERT', [], $data);
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $sql = 'UPDATE adms_sst_ghe_epis SET
                    obrigatorio = :obrigatorio, updated_by = :updated_by, updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
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
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_sst_ghe_epis WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok) {
            LogAlteracaoService::registrarAlteracao('adms_sst_ghe_epis', $id, $uid, 'DELETE', ['id' => $id], []);
        }

        return $ok;
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
}
