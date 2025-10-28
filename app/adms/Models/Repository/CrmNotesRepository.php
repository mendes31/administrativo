<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável pelas notas do CRM
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CrmNotesRepository extends DbConnection
{
    /**
     * Buscar notas de um parceiro
     */
    public function getNotesByPartner(int $partnerId): array
    {
        $sql = 'SELECT 
                    n.*,
                    u.name as created_by_name
                FROM crm_notes n
                LEFT JOIN adms_users u ON n.created_by = u.id
                WHERE n.partner_id = :partner_id
                ORDER BY n.is_pinned DESC, n.created_at DESC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar notas de uma oportunidade
     */
    public function getNotesByOpportunity(int $opportunityId): array
    {
        $sql = 'SELECT 
                    n.*,
                    u.name as created_by_name
                FROM crm_notes n
                LEFT JOIN adms_users u ON n.created_by = u.id
                WHERE n.opportunity_id = :opportunity_id
                ORDER BY n.is_pinned DESC, n.created_at DESC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':opportunity_id', $opportunityId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Criar nota
     */
    public function createNote(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO crm_notes (
                        partner_id, opportunity_id, content, is_pinned, created_by, created_at
                    ) VALUES (
                        :partner_id, :opportunity_id, :content, :is_pinned, :created_by, NOW()
                    )';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':partner_id', $data['partner_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':opportunity_id', $data['opportunity_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':content', $data['content']);
            $stmt->bindValue(':is_pinned', $data['is_pinned'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':created_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

            $stmt->execute();

            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Nota não cadastrada.", [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}

