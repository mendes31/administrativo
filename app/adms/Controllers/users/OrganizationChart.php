<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\HierarchyManagementService;
use App\adms\Views\Services\LoadViewService;

/**
 * Organograma da Empresa
 * 
 * Exibe a estrutura hierárquica completa da empresa
 * baseada no campo immediate_supervisor_id
 * 
 * @package App\adms\Controllers\users
 * @author Sistema Administrativo
 */
class OrganizationChart
{
    private array $data = [];

    public function index(): void
    {
        $usersRepo = new UsersRepository();
        
        // Buscar todos os usuários ativos
        $allUsers = $usersRepo->getAllUsersForChart();
        
        // Organizar em estrutura hierárquica
        $this->data['hierarchy'] = $this->buildHierarchy($allUsers);
        
        // Estatísticas
        $this->data['stats'] = [
            'total_users' => count($allUsers),
            'total_managers' => $this->countManagers($allUsers),
            'total_levels' => $this->countLevels($allUsers),
            'largest_team' => $this->getLargestTeam($allUsers)
        ];
        
        // Filtro por departamento (opcional)
        $filters = [
            'department_id' => $_GET['department_id'] ?? ''
        ];
        
        $this->data['filters'] = $filters;
        
        // Lista de departamentos para filtro
        $this->data['departments'] = $this->getDepartments();
        
        // Layout
        $pageElements = [
            'title_head' => 'Organograma da Empresa',
            'menu' => 'list-users',
            'buttonPermission' => ['ListUsers'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/users/organization-chart", $this->data);
        $loadView->loadView();
    }
    
    /**
     * Construir estrutura hierárquica recursiva
     */
    private function buildHierarchy(array $users, $parentId = null): array
    {
        $branch = [];
        
        foreach ($users as $user) {
            if ($user['immediate_supervisor_id'] == $parentId) {
                $children = $this->buildHierarchy($users, $user['id']);
                
                $branch[] = [
                    'user' => $user,
                    'children' => $children,
                    'children_count' => count($children),
                    'total_subordinates' => $this->countTotalSubordinates($children)
                ];
            }
        }
        
        return $branch;
    }
    
    /**
     * Contar total de subordinados recursivamente
     */
    private function countTotalSubordinates(array $children): int
    {
        $count = count($children);
        
        foreach ($children as $child) {
            $count += $this->countTotalSubordinates($child['children']);
        }
        
        return $count;
    }
    
    /**
     * Contar quantos gerentes (usuários com subordinados)
     */
    private function countManagers(array $users): int
    {
        $managers = 0;
        
        foreach ($users as $user) {
            $hasSubordinates = false;
            foreach ($users as $potentialSubordinate) {
                if ($potentialSubordinate['immediate_supervisor_id'] == $user['id']) {
                    $hasSubordinates = true;
                    break;
                }
            }
            
            if ($hasSubordinates) {
                $managers++;
            }
        }
        
        return $managers;
    }
    
    /**
     * Contar quantos níveis hierárquicos
     */
    private function countLevels(array $users): int
    {
        $maxLevel = 1;
        
        foreach ($users as $user) {
            $level = $this->getUserLevel($users, $user['id']);
            if ($level > $maxLevel) {
                $maxLevel = $level;
            }
        }
        
        return $maxLevel;
    }
    
    /**
     * Obter nível hierárquico de um usuário
     */
    private function getUserLevel(array $users, int $userId, int $currentLevel = 1): int
    {
        $user = array_filter($users, fn($u) => $u['id'] == $userId);
        $user = reset($user);
        
        if (!$user || !$user['immediate_supervisor_id']) {
            return $currentLevel;
        }
        
        return $this->getUserLevel($users, $user['immediate_supervisor_id'], $currentLevel + 1);
    }
    
    /**
     * Obter maior equipe (gerente com mais subordinados diretos)
     */
    private function getLargestTeam(array $users): array
    {
        $largest = ['manager' => null, 'count' => 0];
        
        foreach ($users as $user) {
            $subordinates = array_filter($users, fn($u) => $u['immediate_supervisor_id'] == $user['id']);
            $count = count($subordinates);
            
            if ($count > $largest['count']) {
                $largest = [
                    'manager' => $user['name'],
                    'count' => $count
                ];
            }
        }
        
        return $largest;
    }
    
    /**
     * Obter lista de departamentos
     */
    private function getDepartments(): array
    {
        $usersRepo = new UsersRepository();
        $conn = $usersRepo->getConnection();
        
        $sql = 'SELECT id, name FROM adms_departments ORDER BY name ASC';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

