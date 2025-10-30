<?php

namespace App\adms\Models\Services;

use PDO;

/**
 * Serviço de Gerenciamento de Hierarquia
 * 
 * Facilita operações complexas de reorganização hierárquica,
 * especialmente quando supervisores são removidos ou substituídos.
 * 
 * @package App\adms\Models\Services
 * @author Sistema Administrativo
 */
class HierarchyManagementService extends DbConnection
{
    /**
     * Obter conexão PDO estática
     * Helper para métodos estáticos que precisam acessar o banco
     */
    private static function getStaticConnection(): \PDO
    {
        $instance = new class extends DbConnection {
            public function getPublicConnection(): \PDO {
                return $this->getConnection();
            }
        };
        return $instance->getPublicConnection();
    }
    
    /**
     * Transferir todos os subordinados de um supervisor para outro
     * 
     * Útil quando um gerente é demitido ou promovido.
     * 
     * @param int $fromSupervisorId ID do supervisor atual
     * @param int $toSupervisorId ID do novo supervisor
     * @param bool $includeIndirect Se true, transfere também subordinados indiretos
     * @return array ['success' => bool, 'transferred_count' => int, 'message' => string]
     */
    public static function transferSubordinates(
        int $fromSupervisorId, 
        int $toSupervisorId, 
        bool $includeIndirect = false
    ): array {
        // Validações
        if ($fromSupervisorId === $toSupervisorId) {
            return [
                'success' => false,
                'transferred_count' => 0,
                'message' => 'O supervisor de origem e destino não podem ser iguais.'
            ];
        }
        
        // Verificar se o destino existe e está ativo
        if (!self::isValidSupervisor($toSupervisorId)) {
            return [
                'success' => false,
                'transferred_count' => 0,
                'message' => 'O supervisor de destino não existe ou está inativo.'
            ];
        }
        
        // Evitar loop: verificar se toSupervisor não é subordinado de fromSupervisor
        if (self::isSubordinateOf($toSupervisorId, $fromSupervisorId)) {
            return [
                'success' => false,
                'transferred_count' => 0,
                'message' => 'Não é possível transferir para um subordinado. Isso criaria um loop na hierarquia.'
            ];
        }
        
        $conn = self::getStaticConnection();
        
        try {
            $conn->beginTransaction();
            
            if ($includeIndirect) {
                // Transferir todos (diretos + indiretos) - reorganiza toda a sub-árvore
                $subordinateIds = CrmPermissionService::getAllSubordinates($fromSupervisorId);
                
                if (empty($subordinateIds)) {
                    $conn->rollBack();
                    return [
                        'success' => false,
                        'transferred_count' => 0,
                        'message' => 'Nenhum subordinado encontrado para transferir.'
                    ];
                }
                
                $placeholders = implode(',', array_fill(0, count($subordinateIds), '?'));
                $sql = "UPDATE adms_users 
                        SET immediate_supervisor_id = ? 
                        WHERE id IN ($placeholders)";
                
                $params = array_merge([$toSupervisorId], $subordinateIds);
                
            } else {
                // Transferir apenas subordinados diretos
                $sql = "UPDATE adms_users 
                        SET immediate_supervisor_id = ? 
                        WHERE immediate_supervisor_id = ?";
                
                $params = [$toSupervisorId, $fromSupervisorId];
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $transferredCount = $stmt->rowCount();
            
            $conn->commit();
            
            return [
                'success' => true,
                'transferred_count' => $transferredCount,
                'message' => "Sucesso! {$transferredCount} usuário(s) transferido(s)."
            ];
            
        } catch (\Exception $e) {
            $conn->rollBack();
            
            return [
                'success' => false,
                'transferred_count' => 0,
                'message' => 'Erro ao transferir subordinados: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Promover subordinados para o supervisor do supervisor
     * (Sobem um nível na hierarquia)
     * 
     * Útil quando um gerente intermediário é removido.
     * 
     * @param int $removedSupervisorId ID do supervisor que será removido
     * @return array ['success' => bool, 'promoted_count' => int, 'message' => string]
     */
    public static function promoteSubordinates(int $removedSupervisorId): array
    {
        $conn = self::getStaticConnection();
        
        // Obter o supervisor do supervisor (avô na hierarquia)
        $sql = "SELECT immediate_supervisor_id 
                FROM adms_users 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$removedSupervisorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $grandSupervisorId = $result['immediate_supervisor_id'] ?? null;
        
        try {
            $conn->beginTransaction();
            
            // Transferir subordinados diretos para o avô (ou NULL se não houver)
            $sql = "UPDATE adms_users 
                    SET immediate_supervisor_id = ? 
                    WHERE immediate_supervisor_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$grandSupervisorId, $removedSupervisorId]);
            
            $promotedCount = $stmt->rowCount();
            
            $conn->commit();
            
            $message = $grandSupervisorId 
                ? "Sucesso! {$promotedCount} usuário(s) promovido(s) na hierarquia."
                : "Sucesso! {$promotedCount} usuário(s) ficaram sem supervisor.";
            
            return [
                'success' => true,
                'promoted_count' => $promotedCount,
                'new_supervisor_id' => $grandSupervisorId,
                'message' => $message
            ];
            
        } catch (\Exception $e) {
            $conn->rollBack();
            
            return [
                'success' => false,
                'promoted_count' => 0,
                'message' => 'Erro ao promover subordinados: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar se um usuário tem subordinados
     * 
     * @param int $userId ID do usuário
     * @return array ['has_subordinates' => bool, 'count' => int, 'subordinates' => array]
     */
    public static function checkSubordinates(int $userId): array
    {
        $conn = self::getStaticConnection();
        
        $sql = "SELECT id, name 
                FROM adms_users 
                WHERE immediate_supervisor_id = ? 
                AND status = 1 
                ORDER BY name ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
        
        $subordinates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'has_subordinates' => count($subordinates) > 0,
            'count' => count($subordinates),
            'subordinates' => $subordinates
        ];
    }
    
    /**
     * Validar se um usuário pode ser supervisor
     * 
     * @param int $userId ID do usuário
     * @return bool
     */
    private static function isValidSupervisor(int $userId): bool
    {
        $conn = self::getStaticConnection();
        
        $sql = "SELECT status FROM adms_users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['status'] == 1;
    }
    
    /**
     * Verificar se um usuário é subordinado de outro (direto ou indireto)
     * 
     * @param int $userId ID do usuário a verificar
     * @param int $potentialSupervisorId ID do potencial supervisor
     * @return bool
     */
    private static function isSubordinateOf(int $userId, int $potentialSupervisorId): bool
    {
        $allSubordinates = CrmPermissionService::getAllSubordinates($potentialSupervisorId);
        
        return in_array($userId, $allSubordinates);
    }
    
    /**
     * Remover subordinados (deixar sem supervisor)
     * 
     * @param int $supervisorId ID do supervisor
     * @return array ['success' => bool, 'orphaned_count' => int, 'message' => string]
     */
    public static function orphanSubordinates(int $supervisorId): array
    {
        $conn = self::getStaticConnection();
        
        try {
            $conn->beginTransaction();
            
            $sql = "UPDATE adms_users 
                    SET immediate_supervisor_id = NULL 
                    WHERE immediate_supervisor_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$supervisorId]);
            
            $orphanedCount = $stmt->rowCount();
            
            $conn->commit();
            
            return [
                'success' => true,
                'orphaned_count' => $orphanedCount,
                'message' => "Sucesso! {$orphanedCount} usuário(s) ficaram sem supervisor."
            ];
            
        } catch (\Exception $e) {
            $conn->rollBack();
            
            return [
                'success' => false,
                'orphaned_count' => 0,
                'message' => 'Erro ao remover subordinados: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter informações completas da hierarquia de um usuário
     * 
     * @param int $userId ID do usuário
     * @return array Informações hierárquicas completas
     */
    public static function getHierarchyInfo(int $userId): array
    {
        $conn = self::getStaticConnection();
        
        // Informações do usuário
        $sql = "SELECT id, name, immediate_supervisor_id, user_department_id, user_position_id, status
                FROM adms_users 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'user' => null,
                'supervisor' => null,
                'subordinates_direct' => [],
                'subordinates_all' => [],
                'subordinates_count' => 0
            ];
        }
        
        // Supervisor imediato
        $supervisor = null;
        if ($user['immediate_supervisor_id']) {
            $sql = "SELECT id, name FROM adms_users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user['immediate_supervisor_id']]);
            $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Subordinados diretos
        $sql = "SELECT id, name 
                FROM adms_users 
                WHERE immediate_supervisor_id = ? 
                AND status = 1 
                ORDER BY name ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
        $subordinatesDirect = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Todos os subordinados (diretos + indiretos)
        $subordinatesAll = CrmPermissionService::getAllSubordinates($userId);
        
        return [
            'user' => $user,
            'supervisor' => $supervisor,
            'subordinates_direct' => $subordinatesDirect,
            'subordinates_all' => $subordinatesAll,
            'subordinates_count' => count($subordinatesDirect),
            'total_team_size' => count($subordinatesAll)
        ];
    }
}

