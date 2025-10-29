<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
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
            $stmt->bindValue(':partner_id', $data['partner_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':opportunity_id', $data['opportunity_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':file_name', $data['file_name']);
            $stmt->bindValue(':file_path', $data['file_path']);
            $stmt->bindValue(':file_size', $data['file_size'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':file_type', $data['file_type'] ?? null);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':uploaded_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

            $stmt->execute();

            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Documento não cadastrado.", [
                'error' => $e->getMessage()
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
            $sql = 'DELETE FROM crm_documents WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar documento", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}

