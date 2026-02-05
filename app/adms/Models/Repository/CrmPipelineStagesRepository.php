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
     * OTIMIZADO: Cache implementado (TTL: 5 minutos)
     */
    public function getActiveStages(): array
    {
        // Cache para queries frequentes (TTL: 5 minutos)
        $cacheService = new \App\adms\Models\Services\QueryCacheService(null, 300);
        $cacheKey = 'crm_pipeline_stages_active';
        
        // Tentar obter do cache
        $cached = $cacheService->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Se não estiver em cache, buscar do banco
        $sql = 'SELECT * FROM crm_pipeline_stages 
                WHERE is_active = 1 
                ORDER BY display_order ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Armazenar no cache
        $cacheService->put($cacheKey, $result);
        
        return $result;
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

