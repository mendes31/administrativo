<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdInventoryRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdDataMappingRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\LgpdFinalidadesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar ROPA automaticamente a partir do Inventário LGPD
 */
class LgpdRopaCreateFromInventory
{
    private array|string|null $data = null;

    /**
     * Criar ROPA a partir do inventário
     */
    public function index(): void
    {
        // Obter ID do inventário da URL
        $urlParts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        $inventoryId = end($urlParts);
        
        if (empty($inventoryId) || !is_numeric($inventoryId)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>ID do inventário não fornecido!</div>";
            $urlRedirect = $_ENV['URL_ADM'] . "lgpd-inventory";
            header("Location: $urlRedirect");
            exit();
        }
        
        $inventoryId = (int) $inventoryId;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->createRopaFromInventory($inventoryId);
        } else {
            $this->viewCreateFromInventory($inventoryId);
        }
    }
    
    /**
     * Exibir formulário para criar ROPA a partir do inventário
     */
    private function viewCreateFromInventory(int $inventoryId): void
    {
        $inventoryRepo = new LgpdInventoryRepository();
        $inventory = $inventoryRepo->getById($inventoryId);
        
        if (!$inventory) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Inventário não encontrado!</div>";
            $urlRedirect = $_ENV['URL_ADM'] . "lgpd-inventory";
            header("Location: $urlRedirect");
            exit();
        }
        
        // Buscar grupos de dados associados
        $dataGroups = $inventoryRepo->getDataGroupsByInventoryId($inventoryId);
        
        // Carregar dados para o formulário
        $departmentsRepo = new DepartmentsRepository();
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();
        
        // Carregar finalidades LGPD
        $finalidadesRepo = new LgpdFinalidadesRepository();
        $this->data['finalidades'] = $finalidadesRepo->getActiveFinalidades();
        
        // Dados específicos do inventário
        $this->data['inventory'] = $inventory;
        $this->data['data_groups'] = $dataGroups;
        
        // Preparar dados base para cada grupo
        $this->data['base_data'] = [
            'departamento_id' => $inventory['department_id'],
            'data_subject' => $inventory['data_subject'],
            'processing_purpose' => 'Processamento baseado no inventário',
            'retencao' => '5 anos',
            'sharing' => 'Não há'
        ];

        $pageElements = [
            'title_head' => 'Criar ROPA a partir do Inventário',
            'menu' => 'ListLgpdRopa',
            'buttonPermission' => ['ListLgpdRopa'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/ropa/create-from-inventory", $this->data);
        $loadView->loadView();
    }
    
    /**
     * Criar ROPA a partir do inventário
     */
    private function createRopaFromInventory(int $inventoryId): void
    {
        error_log("=== MÉTODO createRopaFromInventory CHAMADO ===");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        
        $formData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        if (isset($formData['SendAddRopa'])) {
            unset($formData['SendAddRopa']);
            
            // Debug: Log dos dados recebidos
            error_log("=== DEBUG ROPA CREATE ===");
            error_log("POST data: " . print_r($_POST, true));
            error_log("FormData: " . print_r($formData, true));
            
            
            
            // Verificar se há atividades definidas
            if (!isset($formData['activities']) || empty($formData['activities'])) {
                $_SESSION['msg'] = "<div class='alert alert-warning' role='alert'>Defina pelo menos uma atividade para criar a ROPA!</div>";
                $this->viewCreateFromInventory($inventoryId);
                return;
            }
            
            // Verificar se departamento foi selecionado
            if (empty($formData['departamento_id'])) {
                $_SESSION['msg'] = "<div class='alert alert-warning' role='alert'>O campo 'Departamento' é obrigatório!</div>";
                $this->viewCreateFromInventory($inventoryId);
                return;
            }
            
            $ropaRepo = new LgpdRopaRepository();
            $dataMappingRepo = new LgpdDataMappingRepository();
            $createdRopas = [];
            $errors = [];
            
            // Processar cada atividade definida
            foreach ($formData['activities'] as $activityId => $activityData) {
                error_log("Processando atividade: " . print_r($activityData, true));
                
                // Verificar se a atividade tem nome
                if (empty($activityData['name'])) {
                    $errors[] = "Nome da atividade #$activityId não foi definido";
                    continue;
                }
                
                // Verificar se há grupos selecionados para esta atividade
                if (!isset($activityData['groups']) || empty($activityData['groups'])) {
                    $errors[] = "Nenhum grupo selecionado para a atividade: " . $activityData['name'];
                    continue;
                }
                
                // Agrupar grupos por base legal dentro desta atividade
                $groupedByBaseLegal = [];
                
                foreach ($activityData['groups'] as $groupId => $groupData) {
                    if (empty($groupData['selected'])) {
                        continue; // Pular grupos não selecionados
                    }
                    
                    // Verificar se base_legal foi selecionada para este grupo
                    if (empty($groupData['base_legal'])) {
                        $errors[] = "Base legal não selecionada para o grupo: " . $groupData['name'] . " na atividade: " . $activityData['name'];
                        continue;
                    }
                    
                    // Agrupar por base legal
                    $baseLegal = $groupData['base_legal'];
                    if (!isset($groupedByBaseLegal[$baseLegal])) {
                        $groupedByBaseLegal[$baseLegal] = [];
                    }
                    $groupedByBaseLegal[$baseLegal][] = $groupData;
                }
                
                // Criar um ROPA para cada base legal dentro desta atividade
                foreach ($groupedByBaseLegal as $baseLegal => $groups) {
                    // Gerar código único para cada ROPA
                    $nextCode = $ropaRepo->getNextCode();
                    $codigo = 'ROPA-' . str_pad($nextCode, 3, '0', STR_PAD_LEFT);
                    
                    error_log("Gerando código: " . $codigo . " para atividade: " . $activityData['name'] . " - base legal: " . $baseLegal);
                    
                    // Preparar dados da ROPA para esta combinação atividade + base legal
                    $personalDataList = array_map(function($group) {
                        return $group['name'];
                    }, $groups);
                    
                    $highestRisk = 'Baixo';
                    foreach ($groups as $group) {
                        if ($group['risk_level'] === 'Alto') {
                            $highestRisk = 'Alto';
                            break;
                        } elseif ($group['risk_level'] === 'Médio' && $highestRisk !== 'Alto') {
                            $highestRisk = 'Médio';
                        }
                    }
                    
                    // Coletar observações dos grupos
                    $groupObservations = [];
                    foreach ($groups as $group) {
                        if (!empty($group['observacoes'])) {
                            $groupObservations[] = $group['name'] . ': ' . $group['observacoes'];
                        }
                    }
                    
                    $observacoes = "ROPA criado automaticamente a partir do inventário.\n";
                    $observacoes .= "Atividade: " . $activityData['name'] . "\n";
                    $observacoes .= "Grupos: " . implode(', ', $personalDataList);
                    if (!empty($groupObservations)) {
                        $observacoes .= "\n\nObservações por grupo:\n" . implode("\n", $groupObservations);
                    }
                    
                                         $ropaData = [
                         'codigo' => $codigo,
                         'atividade' => $activityData['name'] . ' - ' . $baseLegal,
                         'inventory_id' => $inventoryId,
                         'departamento_id' => $formData['departamento_id'],
                         'data_subject' => $formData['data_subject'],
                         'personal_data' => implode(', ', $personalDataList),
                         'processing_purpose' => $formData['processing_purpose'],
                         'base_legal' => $baseLegal,
                         'retencao' => $formData['retencao'],
                         'sharing' => $formData['sharing'],
                         'medidas_seguranca' => $activityData['medidas_seguranca'] ?? 'Acesso restrito, criptografia',
                         'responsavel' => $activityData['responsavel'] ?? 'Departamento responsável',
                         'observacoes' => $observacoes,
                         'riscos' => $highestRisk,
                         'status' => 'Ativo',
                         'ultima_atualizacao' => date('Y-m-d')
                     ];
                
                    error_log("Dados da ROPA: " . print_r($ropaData, true));
                    
                    // Criar ROPA
                    $ropaId = $ropaRepo->create($ropaData);
                    error_log("Resultado da criação da ROPA: " . ($ropaId ? "SUCESSO - ID: $ropaId" : "FALHA"));
                    
                    if ($ropaId) {
                        $createdRopas[] = $ropaId;
                        
                        // Criar Data Mapping automaticamente
                        $mappingId = $dataMappingRepo->createFromRopa($ropaId);
                        error_log("Resultado da criação do Data Mapping: " . ($mappingId ? "SUCESSO - ID: $mappingId" : "FALHA"));
                        
                        if (!$mappingId) {
                            $errors[] = "Data Mapping não foi criado para a atividade: " . $activityData['name'];
                        }
                    } else {
                        $errors[] = "Erro ao criar ROPA para a atividade: " . $activityData['name'];
                    }
                } // Fim do foreach base legal
            } // Fim do foreach atividades
            
            // Preparar mensagem de resultado
            if (!empty($createdRopas)) {
                $successMsg = "<div class='alert alert-success' role='alert'>";
                $successMsg .= "<strong>ROPA(s) criada(s) com sucesso!</strong><br>";
                $successMsg .= "Total de ROPAs criadas: " . count($createdRopas);
                
                if (count($createdRopas) > 1) {
                    $successMsg .= "<br><small>💡 <strong>Padrão Granular:</strong> Cada combinação de atividade + base legal foi registrada como um ROPA separado, seguindo as melhores práticas LGPD.</small>";
                }
                
                if (!empty($errors)) {
                    $successMsg .= "<br>⚠️ Avisos: " . implode(', ', $errors);
                }
                
                $successMsg .= "</div>";
                $_SESSION['msg'] = $successMsg;
                
                // Redirecionar para a primeira ROPA criada
                $urlRedirect = $_ENV['URL_ADM'] . "lgpd-ropa-view/" . $createdRopas[0];
                header("Location: $urlRedirect");
                exit();
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro: Nenhuma ROPA foi criada!</div>";
                $this->viewCreateFromInventory($inventoryId);
            }
        } else {
            $this->viewCreateFromInventory($inventoryId);
        }
    }
} 