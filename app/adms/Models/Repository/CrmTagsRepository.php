<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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

    /**
     * Buscar tag por ID
     */
    public function getTagById(int $id): array|bool
    {
        $sql = 'SELECT * FROM crm_tags WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualizar tag
     */
    public function updateTag(array $data): bool
    {
        try {
            $oldData = $this->getTagById($data['id']);

            $sql = 'UPDATE crm_tags SET
                        name = :name,
                        color = :color,
                        description = :description
                    WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':color', $data['color']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            $result = $stmt->execute();

            if ($result) {
                LogAlteracaoService::registrarAlteracao(
                    'crm_tags',
                    $data['id'],
                    $_SESSION['user_id'] ?? 1,
                    'UPDATE',
                    $oldData,
                    $data
                );
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Tag não atualizada.", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Deletar tag
     */
    public function deleteTag(int $id): bool
    {
        try {
            $tag = $this->getTagById($id);

            $sql = 'DELETE FROM crm_tags WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $result = $stmt->execute();

            if ($result) {
                LogAlteracaoService::registrarAlteracao(
                    'crm_tags',
                    $id,
                    $_SESSION['user_id'] ?? 1,
                    'DELETE',
                    $tag,
                    []
                );
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Tag não deletada.", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remover tag de um parceiro
     */
    public function removeTagFromPartner(int $partnerId, int $tagId): bool
    {
        try {
            $sql = 'DELETE FROM crm_partner_tags WHERE partner_id = :partner_id AND tag_id = :tag_id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
            $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao remover tag do parceiro.", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

