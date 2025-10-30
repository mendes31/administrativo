<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use PDO;

/**
 * Serviço de Permissões do CRM
 * 
 * Gerencia permissões baseadas em:
 * - Departamento Comercial
 * - Hierarquia (Gerente vs Colaborador)
 * - Access Level (Super Admin sempre tem acesso total)
 * 
 * @package App\adms\Models\Services
 * @author Sistema Administrativo
 */
class CrmPermissionService extends DbConnection
{
    /**
     * ID do departamento comercial (configurável)
     * Pode ser alterado conforme necessidade
     */
    private const COMMERCIAL_DEPARTMENT_NAME = 'Comercial';
    
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
     * Access Levels que têm permissão de gerente
     * 1 = Super Admin (sempre tem acesso total)
     * 2 = Gerente/Admin (pode ver subordinados)
     */
    private const MANAGER_ACCESS_LEVELS = [1, 2];
    
    /**
     * Cargos que são considerados gerentes
     * Adicione aqui os nomes dos cargos que são gerentes
     */
    private const MANAGER_POSITIONS = ['Gerente', 'Gerente Geral', 'Manager', 'Coordenador'];
    
    /**
     * Verificar se o usuário logado é do departamento comercial
     */
    public static function isFromCommercialDepartment(): bool
    {
        if (!isset($_SESSION['user_department_id'])) {
            return false;
        }
        
        $departmentId = self::getCommercialDepartmentId();
        
        if (!$departmentId) {
            // Se departamento comercial não existe, retorna true para não bloquear
            return true;
        }
        
        return $_SESSION['user_department_id'] == $departmentId;
    }
    
    /**
     * Verificar se o usuário logado é gerente
     * 
     * Critérios (ordem de prioridade):
     * 1. Access Level = 1 (Super Admin)
     * 2. Tem subordinados (outros usuários apontam para ele como supervisor)
     * 3. Access Level = 2 (Gerente)
     * 4. Cargo/Position está na lista de cargos gerenciais
     */
    public static function isManager(): bool
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$userId) {
            return false;
        }
        
        // Super Admin sempre é considerado gerente
        if (isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1) {
            return true;
        }
        
        // Verificar se tem subordinados (método mais confiável)
        if (self::hasSubordinates($userId)) {
            return true;
        }
        
        // Verificar por Access Level
        if (isset($_SESSION['user_access_level_id']) && in_array($_SESSION['user_access_level_id'], self::MANAGER_ACCESS_LEVELS)) {
            return true;
        }
        
        // Verificar por nome do cargo
        if (isset($_SESSION['pos_name'])) {
            foreach (self::MANAGER_POSITIONS as $managerPosition) {
                if (stripos($_SESSION['pos_name'], $managerPosition) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Verificar se o usuário tem subordinados
     * 
     * @param int $userId ID do usuário
     * @return bool
     */
    private static function hasSubordinates(int $userId): bool
    {
        $conn = self::getStaticConnection();
        
        $sql = 'SELECT COUNT(*) as count 
                FROM adms_users 
                WHERE immediate_supervisor_id = :supervisor_id 
                AND status = 1';
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':supervisor_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Obter ID do departamento comercial
     */
    private static function getCommercialDepartmentId(): int|null
    {
        $conn = self::getStaticConnection();
        
        $sql = 'SELECT id FROM adms_departments WHERE name = :name LIMIT 1';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':name', self::COMMERCIAL_DEPARTMENT_NAME, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (int)$result['id'] : null;
    }
    
    /**
     * Obter subordinados diretos de um usuário
     * 
     * @param int $userId ID do supervisor
     * @return array Lista de IDs dos subordinados diretos
     */
    private static function getDirectSubordinates(int $userId): array
    {
        $conn = self::getStaticConnection();
        
        $sql = 'SELECT id 
                FROM adms_users 
                WHERE immediate_supervisor_id = :supervisor_id 
                AND status = 1';
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':supervisor_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
    }
    
    /**
     * Obter todos os subordinados (diretos e indiretos) de forma recursiva
     * 
     * @param int $userId ID do supervisor
     * @param array $visited IDs já visitados (para evitar loops)
     * @return array Lista de IDs de todos os subordinados
     */
    public static function getAllSubordinates(int $userId, array $visited = []): array
    {
        // Evitar loops infinitos
        if (in_array($userId, $visited)) {
            return [];
        }
        
        $visited[] = $userId;
        $subordinates = [];
        
        // Obter subordinados diretos
        $directSubordinates = self::getDirectSubordinates($userId);
        
        foreach ($directSubordinates as $subordinateId) {
            $subordinates[] = $subordinateId;
            
            // Recursivamente obter subordinados dos subordinados
            $indirectSubordinates = self::getAllSubordinates($subordinateId, $visited);
            $subordinates = array_merge($subordinates, $indirectSubordinates);
        }
        
        return array_unique($subordinates);
    }
    
    /**
     * Obter lista de IDs de usuários que o usuário logado pode visualizar/selecionar
     * 
     * Regras (NOVA HIERARQUIA):
     * - Super Admin (access_level = 1): Todos os usuários do departamento comercial
     * - Gerente/Supervisor: Seus subordinados diretos + indiretos + ele mesmo
     * - Colaborador: Apenas ele mesmo
     * 
     * @return array Lista de IDs de usuários
     */
    public static function getAllowedUserIds(): array
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$userId) {
            return [];
        }
        
        // Super Admin vê todos do departamento comercial
        if (isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1) {
            return self::getCommercialDepartmentUserIds();
        }
        
        // Se é gerente/supervisor, retorna ele mesmo + todos seus subordinados
        if (self::isManager()) {
            $allowedIds = [$userId]; // Ele mesmo
            $subordinates = self::getAllSubordinates($userId);
            $allowedIds = array_merge($allowedIds, $subordinates);
            
            return array_unique($allowedIds);
        }
        
        // Se não é gerente, retorna apenas ele mesmo
        return [$userId];
    }
    
    /**
     * Obter todos os usuários do departamento comercial
     * 
     * @return array Lista de IDs de usuários
     */
    public static function getCommercialDepartmentUserIds(): array
    {
        $departmentId = self::getCommercialDepartmentId();
        
        if (!$departmentId) {
            // Se departamento não existe, retorna todos os usuários ativos
            $conn = self::getStaticConnection();
            
            $sql = 'SELECT id FROM adms_users WHERE status = 1 ORDER BY name ASC';
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
        }
        
        $conn = self::getStaticConnection();
        
        $sql = 'SELECT id 
                FROM adms_users 
                WHERE user_department_id = :department_id 
                AND status = 1 
                ORDER BY name ASC';
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':department_id', $departmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
    }
    
    /**
     * Obter usuários permitidos para dropdown (respeitando hierarquia)
     * 
     * @param bool $includeCurrentUserOnly Se true, retorna apenas o usuário logado (para não-gerentes)
     * @return array Lista de usuários com id e name
     */
    public static function getCommercialDepartmentUsers(bool $includeCurrentUserOnly = false): array
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$userId) {
            return [];
        }
        
        $conn = self::getStaticConnection();
        
        // Obter IDs permitidos baseado na hierarquia
        $allowedIds = self::getAllowedUserIds();
        
        if (empty($allowedIds)) {
            return [];
        }
        
        // Criar placeholders para a query IN
        $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
        
        $sql = 'SELECT id, name 
                FROM adms_users 
                WHERE id IN (' . $placeholders . ') 
                AND status = 1 
                ORDER BY name ASC';
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($allowedIds);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Filtrar usuários com base nas permissões do CRM
     * 
     * @return array ['user_ids' => array, 'is_manager' => bool, 'current_user_id' => int]
     */
    public static function getCrmUserFilters(): array
    {
        $isManager = self::isManager();
        $currentUserId = $_SESSION['user_id'] ?? 0;
        
        return [
            'user_ids' => self::getAllowedUserIds(),
            'is_manager' => $isManager,
            'current_user_id' => $currentUserId,
            'commercial_users' => self::getCommercialDepartmentUsers(!$isManager)
        ];
    }
    
    /**
     * Validar se o usuário tem permissão para visualizar dados de outro usuário
     * 
     * Baseado na hierarquia de subordinação
     * 
     * @param int $targetUserId ID do usuário que se quer visualizar
     * @return bool
     */
    public static function canViewUser(int $targetUserId): bool
    {
        $currentUserId = $_SESSION['user_id'] ?? 0;
        
        // Pode visualizar a si mesmo
        if ($targetUserId == $currentUserId) {
            return true;
        }
        
        // Verificar se o targetUserId está na lista de IDs permitidos
        $allowedIds = self::getAllowedUserIds();
        
        return in_array($targetUserId, $allowedIds);
    }
    
    /**
     * Obter o supervisor imediato do usuário logado
     * 
     * @return int|null ID do supervisor ou null se não houver
     */
    public static function getImmediateSupervisor(): int|null
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$userId) {
            return null;
        }
        
        $conn = self::getStaticConnection();
        
        $sql = 'SELECT immediate_supervisor_id 
                FROM adms_users 
                WHERE id = :user_id';
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['immediate_supervisor_id'] ?? null;
    }
}

