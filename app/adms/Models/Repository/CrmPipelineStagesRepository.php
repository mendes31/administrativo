<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável pelas etapas do pipeline CRM
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CrmPipelineStagesRepository extends DbConnection
{
    /**
     * Buscar todas as etapas ativas
     */
    public function getActiveStages(): array
    {
        $sql = 'SELECT * FROM crm_pipeline_stages 
                WHERE is_active = 1 
                ORDER BY display_order ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar etapa específica
     */
    public function getStage(int $id): array|bool
    {
        $sql = 'SELECT * FROM crm_pipeline_stages WHERE id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar etapas do tipo active (não finalizadas)
     */
    public function getActivePipelineStages(): array
    {
        $sql = 'SELECT * FROM crm_pipeline_stages 
                WHERE is_active = 1 AND stage_type = "active"
                ORDER BY display_order ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

