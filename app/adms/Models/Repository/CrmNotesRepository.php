<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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
            
            // Tratar valores NULL corretamente
            $partnerId = !empty($data['partner_id']) ? (int)$data['partner_id'] : null;
            $opportunityId = !empty($data['opportunity_id']) ? (int)$data['opportunity_id'] : null;
            
            if ($partnerId !== null) {
                $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':partner_id', null, PDO::PARAM_NULL);
            }
            
            if ($opportunityId !== null) {
                $stmt->bindValue(':opportunity_id', $opportunityId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':opportunity_id', null, PDO::PARAM_NULL);
            }
            
            $stmt->bindValue(':content', $data['content'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':is_pinned', $data['is_pinned'] ?? ($data['is_important'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':created_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

            $stmt->execute();

            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $newRow = $this->getNoteById($newId);
                if (is_array($newRow)) {
                    $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'crm_notes',
                        $newId,
                        $usuarioId,
                        'INSERT',
                        [],
                        $newRow
                    );
                }
            }

            return $newId;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao criar nota", [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Buscar nota por ID
     */
    public function getNoteById(int $id): array|bool
    {
        $sql = 'SELECT 
                    n.*,
                    u.name as created_by_name
                FROM crm_notes n
                LEFT JOIN adms_users u ON n.created_by = u.id
                WHERE n.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Deletar nota
     */
    public function deleteNote(int $id): bool
    {
        try {
            $oldData = $this->getNoteById($id);
            $sql = 'DELETE FROM crm_notes WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $stmt->execute();
            $deleted = $stmt->rowCount() > 0;
            if ($deleted && is_array($oldData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'crm_notes',
                    $id,
                    $usuarioId,
                    'DELETE',
                    $oldData,
                    []
                );
            }

            return $deleted;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar nota", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}

