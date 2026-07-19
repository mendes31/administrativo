<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Intenções de comunicação de entrevista (Expand Fase 2).
 * Status inicial: recorded — sem envio SMTP neste incremento.
 */
class RhEntrevistaComunicacoesRepository extends DbConnection
{
    public const STATUS_RECORDED = 'recorded';
    public const STATUS_READY = 'ready';
    public const STATUS_BLOCKED = 'blocked';
    public const PURPOSE_AGENDAMENTO = 'agendamento';
    public const PURPOSE_REAGENDAMENTO = 'reagendamento';

    /**
     * @return list<array<string, mixed>>
     */
    public function listByEntrevista(int $entrevistaId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT c.*, o.event_name, o.status AS outbox_status
             FROM rh_entrevista_comunicacoes c
             LEFT JOIN adms_domain_event_outbox o ON o.id = c.outbox_event_id
             WHERE c.rh_entrevista_id = :entrevista_id
             ORDER BY c.created_at DESC, c.id DESC'
        );
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lote de intenções recorded para preflight (sem corpo completo no SELECT mínimo).
     *
     * @return list<array<string, mixed>>
     */
    public function listRecordedForPreflight(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $sql = 'SELECT c.id, c.rh_entrevista_id, c.outbox_event_id, c.purpose, c.template_key,
                       c.template_version, c.recipient_name, c.recipient_address, c.status,
                       c.subject_snapshot,
                       CASE WHEN c.body_html_snapshot IS NULL OR c.body_html_snapshot = \'\' THEN 0 ELSE 1 END AS has_body_html,
                       CASE WHEN c.body_text_snapshot IS NULL OR c.body_text_snapshot = \'\' THEN 0 ELSE 1 END AS has_body_text,
                       o.event_name, o.status AS outbox_status, o.idempotency_key
                FROM rh_entrevista_comunicacoes c
                LEFT JOIN adms_domain_event_outbox o ON o.id = c.outbox_event_id
                WHERE c.status = :status
                ORDER BY c.id ASC
                LIMIT ' . $limit;
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':status', self::STATUS_RECORDED, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateStatus(int $id, string $status, ?string $lastError = null): bool
    {
        if (!in_array($status, [self::STATUS_READY, self::STATUS_BLOCKED], true)) {
            throw new Exception('Status de preflight inválido.');
        }

        $stmt = $this->getConnection()->prepare(
            'UPDATE rh_entrevista_comunicacoes
             SET status = :status,
                 last_error = :last_error,
                 updated_at = NOW()
             WHERE id = :id AND status = :expected'
        );
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(
            ':last_error',
            $lastError !== null && $lastError !== '' ? $lastError : null,
            $lastError !== null && $lastError !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':expected', self::STATUS_RECORDED, PDO::PARAM_STR);

        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    /**
     * @param array{
     *   rh_entrevista_id: int,
     *   rh_entrevista_reagendamento_id?: int|null,
     *   outbox_event_id?: int|null,
     *   purpose: string,
     *   template_key: string,
     *   template_version: int,
     *   recipient_name?: string|null,
     *   recipient_address?: string|null,
     *   subject_snapshot: string,
     *   body_html_snapshot: string,
     *   body_text_snapshot?: string|null
     * } $data
     */
    public function record(array $data): int
    {
        $entrevistaId = (int) ($data['rh_entrevista_id'] ?? 0);
        if ($entrevistaId <= 0) {
            throw new Exception('Entrevista inválida para comunicação.');
        }

        $outboxId = isset($data['outbox_event_id']) ? (int) $data['outbox_event_id'] : 0;
        if ($outboxId > 0) {
            $existing = $this->findIdByOutboxEventId($outboxId);
            if ($existing !== null) {
                return $existing;
            }
        }

        try {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO rh_entrevista_comunicacoes
                    (rh_entrevista_id, rh_entrevista_reagendamento_id, outbox_event_id, channel, purpose,
                     template_key, template_version, recipient_name, recipient_address,
                     subject_snapshot, body_html_snapshot, body_text_snapshot, status, created_at)
                 VALUES
                    (:entrevista_id, :reagendamento_id, :outbox_event_id, \'email\', :purpose,
                     :template_key, :template_version, :recipient_name, :recipient_address,
                     :subject, :body_html, :body_text, :status, NOW())'
            );
            $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
            $reagId = isset($data['rh_entrevista_reagendamento_id'])
                ? (int) $data['rh_entrevista_reagendamento_id']
                : 0;
            $stmt->bindValue(
                ':reagendamento_id',
                $reagId > 0 ? $reagId : null,
                $reagId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(
                ':outbox_event_id',
                $outboxId > 0 ? $outboxId : null,
                $outboxId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':purpose', (string) $data['purpose'], PDO::PARAM_STR);
            $stmt->bindValue(':template_key', (string) $data['template_key'], PDO::PARAM_STR);
            $stmt->bindValue(':template_version', (int) $data['template_version'], PDO::PARAM_INT);
            $name = trim((string) ($data['recipient_name'] ?? ''));
            $addr = trim((string) ($data['recipient_address'] ?? ''));
            $stmt->bindValue(
                ':recipient_name',
                $name !== '' ? $name : null,
                $name !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(
                ':recipient_address',
                $addr !== '' ? $addr : null,
                $addr !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':subject', (string) $data['subject_snapshot'], PDO::PARAM_STR);
            $stmt->bindValue(':body_html', (string) $data['body_html_snapshot'], PDO::PARAM_STR);
            $bodyText = isset($data['body_text_snapshot']) ? (string) $data['body_text_snapshot'] : null;
            $stmt->bindValue(
                ':body_text',
                $bodyText !== null && $bodyText !== '' ? $bodyText : null,
                $bodyText !== null && $bodyText !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':status', self::STATUS_RECORDED, PDO::PARAM_STR);
            $stmt->execute();

            return (int) $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            if ($outboxId > 0) {
                $existing = $this->findIdByOutboxEventId($outboxId);
                if ($existing !== null) {
                    return $existing;
                }
            }
            GenerateLog::generateLog('error', 'Erro ao registrar comunicação de entrevista.', [
                'entrevista_id' => $entrevistaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function findIdByOutboxEventId(int $outboxEventId): ?int
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM rh_entrevista_comunicacoes WHERE outbox_event_id = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $outboxEventId, PDO::PARAM_INT);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }
}
