<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class RhPipelineStageRepository extends DbConnection
{
    /**
     * @return list<array{code: string, label: string, display_order: int, column_class: string}>
     */
    public function listActiveOrdered(): array
    {
        $sql = 'SELECT code, label, display_order, column_class
                FROM rh_pipeline_stages
                WHERE is_active = 1
                ORDER BY display_order ASC, id ASC';

        $stmt = $this->getConnection()->query($sql);
        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $result = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $result[] = [
                'code' => $code,
                'label' => trim((string) ($row['label'] ?? $code)),
                'display_order' => (int) ($row['display_order'] ?? 0),
                'column_class' => trim((string) ($row['column_class'] ?? 'bg-light')) ?: 'bg-light',
            ];
        }

        return $result;
    }
}
