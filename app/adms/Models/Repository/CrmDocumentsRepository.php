<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável pelos documentos do CRM
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CrmDocumentsRepository extends DbConnection
{
    /**
     * Buscar documentos de um parceiro
     */
    public function getDocumentsByPartner(int $partnerId): array
    {
        $sql = 'SELECT 
                    d.*,
                    u.name as uploaded_by_name
                FROM crm_documents d
                LEFT JOIN adms_users u ON d.uploaded_by = u.id
                WHERE d.partner_id = :partner_id
                ORDER BY d.uploaded_at DESC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar documentos de uma oportunidade
     */
    public function getDocumentsByOpportunity(int $opportunityId): array
    {
        $sql = 'SELECT 
                    d.*,
                    u.name as uploaded_by_name
                FROM crm_documents d
                LEFT JOIN adms_users u ON d.uploaded_by = u.id
                WHERE d.opportunity_id = :opportunity_id
                ORDER BY d.uploaded_at DESC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':opportunity_id', $opportunityId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fazer upload de documento
     */
    public function uploadDocument(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO crm_documents (
                        partner_id, opportunity_id, file_name, file_path, file_size, file_type, description, uploaded_by, uploaded_at
                    ) VALUES (
                        :partner_id, :opportunity_id, :file_name, :file_path, :file_size, :file_type, :description, :uploaded_by, NOW()
                    )';

            $stmt = $this->getConnection()->prepare($sql);
            
            // Tratar valores NULL corretamente
            $partnerId = !empty($data['partner_id']) ? (int)$data['partner_id'] : null;
            $opportunityId = !empty($data['opportunity_id']) ? (int)$data['opportunity_id'] : null;
            $fileSize = !empty($data['file_size']) ? (int)$data['file_size'] : null;
            
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
            
            $stmt->bindValue(':file_name', $data['file_name'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':file_path', $data['file_path'] ?? '', PDO::PARAM_STR);
            
            if ($fileSize !== null) {
                $stmt->bindValue(':file_size', $fileSize, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':file_size', null, PDO::PARAM_NULL);
            }
            
            $stmt->bindValue(':file_type', $data['file_type'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':uploaded_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

            $stmt->execute();

            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $newRow = $this->getDocumentById($newId);
                if (is_array($newRow)) {
                    $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'crm_documents',
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
            GenerateLog::generateLog("error", "Erro ao registrar documento no banco de dados", [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Buscar documento por ID
     */
    public function getDocumentById(int $id): array|bool
    {
        $sql = 'SELECT 
                    d.*,
                    u.name as uploaded_by_name
                FROM crm_documents d
                LEFT JOIN adms_users u ON d.uploaded_by = u.id
                WHERE d.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Deletar documento
     */
    public function deleteDocument(int $id): bool
    {
        try {
            $oldData = $this->getDocumentById($id);
            $sql = 'DELETE FROM crm_documents WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $stmt->execute();
            $deleted = $stmt->rowCount() > 0;
            if ($deleted && is_array($oldData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'crm_documents',
                    $id,
                    $usuarioId,
                    'DELETE',
                    $oldData,
                    []
                );
            }

            return $deleted;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar documento", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}

