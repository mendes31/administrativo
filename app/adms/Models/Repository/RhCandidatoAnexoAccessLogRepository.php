<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Controllers\Services\RequestHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Auditoria de download de anexos/currículos (append-only).
 */
class RhCandidatoAnexoAccessLogRepository extends DbConnection
{
    /**
     * Registra download autorizado. Falhas não devem interromper a entrega.
     *
     * @param array{
     *   rh_candidato_id: int,
     *   actor_user_id: int,
     *   source: string,
     *   rh_candidato_anexo_id?: int|null,
     *   action?: string,
     *   delivery_mode?: string,
     *   anexo_tipo?: string|null,
     *   path?: string|null,
     *   dedup_seconds?: int
     * } $data
     */
    public function logDownload(array $data): void
    {
        try {
            if (!$this->tableExists()) {
                return;
            }

            $candidatoId = (int) ($data['rh_candidato_id'] ?? 0);
            $actorId = (int) ($data['actor_user_id'] ?? 0);
            $source = substr((string) ($data['source'] ?? 'unknown'), 0, 32);
            $action = substr((string) ($data['action'] ?? 'download'), 0, 32);
            $anexoId = isset($data['rh_candidato_anexo_id']) ? (int) $data['rh_candidato_anexo_id'] : 0;
            $anexoId = $anexoId > 0 ? $anexoId : null;

            if ($candidatoId <= 0 || $actorId <= 0 || $source === '') {
                return;
            }

            $dedup = (int) ($data['dedup_seconds'] ?? 0);
            if ($dedup > 0 && $this->hasRecentEntry($candidatoId, $actorId, $action, $anexoId, $source, $dedup)) {
                return;
            }

            $mode = strtolower(trim((string) ($data['delivery_mode'] ?? 'inline'))) === 'attachment'
                ? 'attachment'
                : 'inline';
            $tipo = isset($data['anexo_tipo']) && $data['anexo_tipo'] !== null && $data['anexo_tipo'] !== ''
                ? substr((string) $data['anexo_tipo'], 0, 50)
                : null;
            $path = isset($data['path']) ? (string) $data['path'] : '';
            $pathHash = $path !== ''
                ? hash('sha256', str_replace('\\', '/', ltrim(trim($path), '/')))
                : null;

            $ip = substr(RequestHelper::getClientIp(), 0, 45);
            $uaRaw = (string) (RequestHelper::getUserAgent() ?? '');
            $ua = $uaRaw !== '' ? substr($uaRaw, 0, 512) : null;

            $sql = 'INSERT INTO rh_candidato_anexo_access_logs
                        (rh_candidato_anexo_id, rh_candidato_id, actor_user_id, action, delivery_mode,
                         source, anexo_tipo, path_hash, ip_address, user_agent, created_at)
                    VALUES
                        (:anexo_id, :candidato_id, :actor_id, :action, :delivery_mode,
                         :source, :anexo_tipo, :path_hash, :ip, :ua, NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            if ($anexoId !== null) {
                $stmt->bindValue(':anexo_id', $anexoId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':anexo_id', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
            $stmt->bindValue(':actor_id', $actorId, PDO::PARAM_INT);
            $stmt->bindValue(':action', $action, PDO::PARAM_STR);
            $stmt->bindValue(':delivery_mode', $mode, PDO::PARAM_STR);
            $stmt->bindValue(':source', $source, PDO::PARAM_STR);
            $stmt->bindValue(':anexo_tipo', $tipo, $tipo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':path_hash', $pathHash, $pathHash === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':ip', $ip !== '' ? $ip : null, $ip !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':ua', $ua, $ua === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'RhCandidatoAnexoAccessLogRepository::logDownload — ' . $e->getMessage(), [
                'candidato_id' => $data['rh_candidato_id'] ?? null,
                'anexo_id' => $data['rh_candidato_anexo_id'] ?? null,
                'source' => $data['source'] ?? null,
            ]);
        }
    }

    private function hasRecentEntry(
        int $candidatoId,
        int $actorId,
        string $action,
        ?int $anexoId,
        string $source,
        int $seconds
    ): bool {
        $sql = 'SELECT 1 FROM rh_candidato_anexo_access_logs
                WHERE rh_candidato_id = :candidato_id
                  AND actor_user_id = :actor_id
                  AND action = :action
                  AND source = :source
                  AND created_at >= :since';
        if ($anexoId !== null) {
            $sql .= ' AND rh_candidato_anexo_id = :anexo_id';
        } else {
            $sql .= ' AND rh_candidato_anexo_id IS NULL';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $params = [
            ':candidato_id' => $candidatoId,
            ':actor_id' => $actorId,
            ':action' => $action,
            ':source' => $source,
            ':since' => date('Y-m-d H:i:s', time() - $seconds),
        ];
        if ($anexoId !== null) {
            $params[':anexo_id'] = $anexoId;
        }
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    private function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'rh_candidato_anexo_access_logs'");
            $exists = $stmt !== false && $stmt->fetch(PDO::FETCH_NUM) !== false;
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }
}
