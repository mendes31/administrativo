<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Leitura de execuções da retenção LGPD do Canal de Denúncias.
 */
class WhistleblowingRetentionRunsRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listRuns(int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT *
                FROM adms_whistleblowing_retention_runs
                ORDER BY started_at DESC
                LIMIT :limit OFFSET :offset';

        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function countRuns(): int
    {
        try {
            return (int) $this->getConnection()->query('SELECT COUNT(*) FROM adms_whistleblowing_retention_runs')->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLastRun(): ?array
    {
        $sql = 'SELECT *
                FROM adms_whistleblowing_retention_runs
                ORDER BY started_at DESC
                LIMIT 1';

        try {
            $stmt = $this->getConnection()->query($sql);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}

