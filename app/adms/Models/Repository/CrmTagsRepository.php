<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável pelas tags do CRM
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CrmTagsRepository extends DbConnection
{
    /**
     * Buscar todas as tags
     */
    public function getAllTags(): array
    {
        $sql = 'SELECT * FROM crm_tags ORDER BY name ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Criar tag
     */
    public function createTag(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO crm_tags (name, color, description, created_at) 
                    VALUES (:name, :color, :description, NOW())';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':color', $data['color'] ?? '#6c757d');
            $stmt->bindValue(':description', $data['description'] ?? null);

            $stmt->execute();

            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Tag não cadastrada.", [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Vincular tag a parceiro
     */
    public function attachTagToPartner(int $partnerId, int $tagId): bool
    {
        try {
            $sql = 'INSERT IGNORE INTO crm_partner_tags (partner_id, tag_id) VALUES (:partner_id, :tag_id)';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
            $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Tag não vinculada.", [
                'partner_id' => $partnerId,
                'tag_id' => $tagId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Buscar tags de um parceiro
     */
    public function getPartnerTags(int $partnerId): array
    {
        $sql = 'SELECT t.*
                FROM crm_tags t
                INNER JOIN crm_partner_tags pt ON t.id = pt.tag_id
                WHERE pt.partner_id = :partner_id
                ORDER BY t.name ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

