<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Throwable;

final class PortariaTermoAceitesRepository extends DbConnection
{
    /** @param array<string, mixed> $data */
    public function create(array $data): int|false
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO portaria_termo_aceites
                 (visitante_id, lgpd_termo_id, metodo, porteiro_user_id, conferencia_identidade,
                  aceito_em, valido_ate, dispositivo, observacoes)
                 VALUES (:visitante_id, :termo_id, :metodo, :porteiro_id, :conferencia,
                         :aceito_em, :valido_ate, :dispositivo, :observacoes)'
            );
            $stmt->execute([
                ':visitante_id' => (int) ($data['visitante_id'] ?? 0),
                ':termo_id' => (int) ($data['lgpd_termo_id'] ?? 0),
                ':metodo' => trim((string) ($data['metodo'] ?? 'presencial')),
                ':porteiro_id' => (int) ($data['porteiro_user_id'] ?? 0) ?: null,
                ':conferencia' => !empty($data['conferencia_identidade']) ? 1 : 0,
                ':aceito_em' => (string) ($data['aceito_em'] ?? date('Y-m-d H:i:s')),
                ':valido_ate' => (string) ($data['valido_ate'] ?? date('Y-m-d H:i:s', strtotime('+1 year'))),
                ':dispositivo' => trim((string) ($data['dispositivo'] ?? '')) ?: null,
                ':observacoes' => trim((string) ($data['observacoes'] ?? '')) ?: null,
            ]);
            $id = (int) $this->getConnection()->lastInsertId();
            return $id > 0 ? $id : false;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaTermoAceitesRepository::create', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** @return list<array<string, mixed>> */
    public function listByVisitante(int $visitanteId): array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT a.*, t.titulo AS termo_titulo, t.versao AS termo_versao
                 FROM portaria_termo_aceites a
                 LEFT JOIN lgpd_termos t ON t.id = a.lgpd_termo_id
                 WHERE a.visitante_id = :visitante_id ORDER BY a.aceito_em DESC'
            );
            $stmt->bindValue(':visitante_id', $visitanteId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaTermoAceitesRepository::listByVisitante', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    public function getVigente(int $visitanteId): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT a.*, t.titulo AS termo_titulo, t.versao AS termo_versao
                 FROM portaria_termo_aceites a
                 LEFT JOIN lgpd_termos t ON t.id = a.lgpd_termo_id
                 WHERE a.visitante_id = :visitante_id AND a.revogado_em IS NULL AND a.valido_ate >= NOW()
                 ORDER BY a.aceito_em DESC LIMIT 1'
            );
            $stmt->bindValue(':visitante_id', $visitanteId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaTermoAceitesRepository::getVigente', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
