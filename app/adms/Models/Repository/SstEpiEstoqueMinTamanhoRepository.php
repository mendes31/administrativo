<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\SstEpiTamanhoHelper;
use App\adms\Models\Services\DbConnection;
use PDO;

class SstEpiEstoqueMinTamanhoRepository extends DbConnection
{
    public function hasTable(): bool
    {
        try {
            $this->getConnection()->query('SELECT 1 FROM adms_sst_epi_estoque_min_tamanho LIMIT 1');

            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * @return array<string, int>
     */
    public function getMapByEpiId(int $epiId): array
    {
        if (!$this->hasTable() || $epiId <= 0) {
            return [];
        }
        $sql = 'SELECT tamanho, estoque_minimo FROM adms_sst_epi_estoque_min_tamanho
                WHERE adms_sst_epi_id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $epiId, PDO::PARAM_INT);
        $stmt->execute();
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $tam = SstEpiTamanhoHelper::normalize((string) ($row['tamanho'] ?? ''));
            $min = (int) ($row['estoque_minimo'] ?? 0);
            if ($tam !== '' && $min > 0) {
                $out[$tam] = $min;
            }
        }

        return $out;
    }

    /**
     * @param list<int> $ids
     * @return array<int, array<string, int>>
     */
    public function getMapsByEpiIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0
        )));
        if (!$this->hasTable() || $ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT adms_sst_epi_id, tamanho, estoque_minimo
                FROM adms_sst_epi_estoque_min_tamanho
                WHERE adms_sst_epi_id IN ({$placeholders})";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $epiId = (int) ($row['adms_sst_epi_id'] ?? 0);
            $tam = SstEpiTamanhoHelper::normalize((string) ($row['tamanho'] ?? ''));
            $min = (int) ($row['estoque_minimo'] ?? 0);
            if ($epiId > 0 && $tam !== '' && $min > 0) {
                $out[$epiId][$tam] = $min;
            }
        }

        return $out;
    }

    /**
     * Substitui os mínimos específicos do EPI (vazio = só o padrão da grade).
     *
     * @param array<string, int> $map
     */
    public function replaceForEpi(int $epiId, array $map): void
    {
        if (!$this->hasTable() || $epiId <= 0) {
            return;
        }
        $pdo = $this->getConnection();
        $del = $pdo->prepare('DELETE FROM adms_sst_epi_estoque_min_tamanho WHERE adms_sst_epi_id = :id');
        $del->bindValue(':id', $epiId, PDO::PARAM_INT);
        $del->execute();

        $ins = $pdo->prepare(
            'INSERT INTO adms_sst_epi_estoque_min_tamanho
                (adms_sst_epi_id, tamanho, estoque_minimo, created_at, updated_at)
             VALUES (:id, :tam, :min, NOW(), NOW())'
        );
        foreach ($map as $tam => $min) {
            $t = SstEpiTamanhoHelper::normalize((string) $tam);
            $n = (int) $min;
            if ($t === '' || $n <= 0) {
                continue;
            }
            $ins->bindValue(':id', $epiId, PDO::PARAM_INT);
            $ins->bindValue(':tam', $t);
            $ins->bindValue(':min', $n, PDO::PARAM_INT);
            $ins->execute();
        }
    }
}
