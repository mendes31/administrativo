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
        
        // Normalizar caminhos das imagens
        foreach ($allUsers as &$user) {
            if (!empty($user['image'])) {
                $baseUploads = 'public/adms/uploads/';
                $hasSubdir = strpos($user['image'], '/') !== false || strpos($user['image'], '\\') !== false;
                $relativePath = $hasSubdir ? $user['image'] : ('users/' . $user['id'] . '/' . $user['image']);
                if (file_exists($baseUploads . $relativePath)) {
                    $user['image'] = $relativePath;
                } else {
                    $user['image'] = null;
                }
            } else {
                $user['image'] = null;
            }
        }
        unset($user);
        
        // OTIMIZADO: Separar usuários usando contagem de subordinados (já calculada no SQL)
        $usersWithSupervisor = [];
        $orphans = []; // Sem supervisor e sem subordinados
        
        foreach ($allUsers as $user) {
            $hasSubordinates = (int)($user['direct_subordinates_count'] ?? 0) > 0;
            
            if ($user['immediate_supervisor_id'] === null || $user['immediate_supervisor_id'] === '') {
                if (!$hasSubordinates) {
                    $orphans[] = $user;
                } else {
                    $usersWithSupervisor[] = $user;
                }
            } else {
                $usersWithSupervisor[] = $user;
            }
        }
        
        // Organizar em estrutura hierárquica (apenas os com supervisor ou que são topo)
        $this->data['hierarchy'] = $this->buildHierarchy($usersWithSupervisor);
        
        // Adicionar galho de "Órfãos" se houver
        if (!empty($orphans)) {
            $orphansBranch = [];
            foreach ($orphans as $orphan) {
                $orphansBranch[] = [
                    'user' => $orphan,
                    'children' => [],
                    'children_count' => 0,
                    'total_subordinates' => 0
                ];
            }
            $this->data['hierarchy'][] = [
                'user' => [
                    'id' => 0,
                    'name' => 'Sem Hierarquia Definida',
                    'position_name' => 'Usuários sem supervisor',
                    'department_name' => 'Vários Departamentos',
                    'image' => null,
                    'immediate_supervisor_id' => null
                ],
                'children' => $orphansBranch,
                'children_count' => count($orphansBranch),
                'total_subordinates' => 0
            ];
        }
        
        // Estatísticas (OTIMIZADO: usar método do repository)
        $stats = $usersRepo->getHierarchyStats();
        $stats['total_levels'] = $this->countLevels($allUsers); // Ainda precisa ser calculado em PHP
        $this->data['stats'] = $stats;
        
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
            'menu' => 'organization-chart',
            'buttonPermission' => ['OrganizationChart'],
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
     * OTIMIZADO: Usa contagem já calculada no SQL
     */
    private function countManagers(array $users): int
    {
        $managers = 0;
        
        foreach ($users as $user) {
            if ((int)($user['direct_subordinates_count'] ?? 0) > 0) {
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
     * OTIMIZADO: Usa contagem já calculada no SQL
     */
    private function getLargestTeam(array $users): array
    {
        $largest = ['manager' => null, 'count' => 0];
        
        foreach ($users as $user) {
            $count = (int)($user['direct_subordinates_count'] ?? 0);
            
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

