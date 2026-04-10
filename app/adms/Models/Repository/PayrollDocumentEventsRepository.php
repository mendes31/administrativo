<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Eventos append-only para trilha de ciência/OTP/assinatura (documentos RH).
 */
class PayrollDocumentEventsRepository extends DbConnection
{
    public function insert(
        ?int $documentId,
        ?int $userId,
        string $eventType,
        ?array $meta,
        ?string $ip,
        ?string $userAgent
    ): void {
        if (!$this->hasTable()) {
            return;
        }
        $sql = 'INSERT INTO adms_payroll_document_events
            (employee_payroll_document_id, user_id, event_type, meta_json, ip, user_agent, created_at)
            VALUES (:doc_id, :uid, :etype, :meta, :ip, :ua, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        if ($documentId !== null && $documentId > 0) {
            $stmt->bindValue(':doc_id', $documentId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':doc_id', null, PDO::PARAM_NULL);
        }
        if ($userId !== null && $userId > 0) {
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':uid', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':etype', $eventType, PDO::PARAM_STR);
        $stmt->bindValue(':meta', $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null, PDO::PARAM_STR);
        $stmt->bindValue(':ip', $ip !== null ? mb_substr($ip, 0, 45) : null, $ip === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $ua = $userAgent !== null ? mb_substr($userAgent, 0, 512) : null;
        $stmt->bindValue(':ua', $ua, $ua === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Primeira ocorrência de cada tipo de evento por documento (para relatórios por lote).
     *
     * @param list<int> $documentIds
     * @return array<int, array<string, string>> doc_id => [ event_type => created_at mysql ]
     */
    public function aggregateFirstEventTimesByDocument(array $documentIds): array
    {
        if (!$this->hasTable() || $documentIds === []) {
            return [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $documentIds), fn (int $i) => $i > 0)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', $ids);
        $sql = "SELECT employee_payroll_document_id AS doc_id, event_type, MIN(created_at) AS ts
                FROM adms_payroll_document_events
                WHERE employee_payroll_document_id IN ({$in})
                GROUP BY employee_payroll_document_id, event_type";
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $did = (int)($r['doc_id'] ?? 0);
            $et = (string)($r['event_type'] ?? '');
            if ($did > 0 && $et !== '') {
                $out[$did][$et] = (string)($r['ts'] ?? '');
            }
        }

        return $out;
    }

    /**
     * Todos os eventos dos documentos indicados, ordenados cronologicamente (trilha de auditoria).
     *
     * @param list<int> $documentIds
     * @return list<array<string, mixed>>
     */
    public function listEventsForDocumentIdsDetailed(array $documentIds): array
    {
        if (!$this->hasTable() || $documentIds === []) {
            return [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $documentIds), fn (int $i) => $i > 0)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', $ids);
        $sql = "SELECT * FROM adms_payroll_document_events
                WHERE employee_payroll_document_id IN ({$in})
                ORDER BY created_at ASC, id ASC";
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function hasTable(): bool
    {
        try {
            $this->getConnection()->query('SELECT 1 FROM adms_payroll_document_events LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
