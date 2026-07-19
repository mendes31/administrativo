<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Outbox genérica de eventos de domínio (Expand Fase 0.5).
 * O worker de entrevistas consome os eventos vinculados às comunicações SMTP.
 */
class DomainEventOutboxRepository extends DbConnection
{
    public const STATUS_PENDING = 'pending';

    /**
     * Insere evento pendente. Idempotente por idempotency_key.
     *
     * @param array{
     *   event_name: string,
     *   event_version?: int,
     *   aggregate_type: string,
     *   aggregate_id: int,
     *   idempotency_key: string,
     *   correlation_id?: string|null,
     *   payload: array<string, mixed>,
     *   privacy_classification?: string,
     *   occurred_at?: string|null
     * } $data
     * @return int ID do evento (novo ou já existente)
     */
    public function enqueue(array $data): int
    {
        $key = trim((string) ($data['idempotency_key'] ?? ''));
        if ($key === '') {
            throw new Exception('idempotency_key é obrigatória.');
        }

        $existing = $this->findIdByIdempotencyKey($key);
        if ($existing !== null) {
            return $existing;
        }

        $payload = json_encode($data['payload'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new Exception('Falha ao serializar payload do evento.');
        }

        try {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO adms_domain_event_outbox
                    (event_name, event_version, aggregate_type, aggregate_id, idempotency_key,
                     correlation_id, payload_json, privacy_classification, status, attempt_count,
                     available_at, occurred_at, created_at)
                 VALUES
                    (:event_name, :event_version, :aggregate_type, :aggregate_id, :idempotency_key,
                     :correlation_id, :payload_json, :privacy, :status, 0,
                     NOW(), :occurred_at, NOW())'
            );
            $stmt->bindValue(':event_name', (string) $data['event_name'], PDO::PARAM_STR);
            $stmt->bindValue(':event_version', (int) ($data['event_version'] ?? 1), PDO::PARAM_INT);
            $stmt->bindValue(':aggregate_type', (string) $data['aggregate_type'], PDO::PARAM_STR);
            $stmt->bindValue(':aggregate_id', (int) $data['aggregate_id'], PDO::PARAM_INT);
            $stmt->bindValue(':idempotency_key', $key, PDO::PARAM_STR);
            $corr = isset($data['correlation_id']) ? trim((string) $data['correlation_id']) : '';
            $stmt->bindValue(
                ':correlation_id',
                $corr !== '' ? $corr : null,
                $corr !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':payload_json', $payload, PDO::PARAM_STR);
            $stmt->bindValue(
                ':privacy',
                (string) ($data['privacy_classification'] ?? 'pessoal'),
                PDO::PARAM_STR
            );
            $stmt->bindValue(':status', self::STATUS_PENDING, PDO::PARAM_STR);
            $occurred = (string) ($data['occurred_at'] ?? date('Y-m-d H:i:s'));
            $stmt->bindValue(':occurred_at', $occurred, PDO::PARAM_STR);
            $stmt->execute();

            return (int) $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            // Corrida: outro processo pode ter inserido a mesma chave.
            $existing = $this->findIdByIdempotencyKey($key);
            if ($existing !== null) {
                return $existing;
            }
            GenerateLog::generateLog('error', 'Erro ao enfileirar evento na outbox.', [
                'idempotency_key' => $key,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function findIdByIdempotencyKey(string $key): ?int
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM adms_domain_event_outbox WHERE idempotency_key = :key LIMIT 1'
        );
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }
}
