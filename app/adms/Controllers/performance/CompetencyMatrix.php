<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CompetenciesRepository;
use App\adms\Models\Repository\CompetencyMatrixRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para Matriz de Competências
 */
class CompetencyMatrix
{
    private array|string|null $data = null;

    public function index(): void
    {
        $competenciesRepo = new CompetenciesRepository();
        $positionsRepo = new PositionsRepository();
        $matrixRepo = new CompetencyMatrixRepository();
        
        // Estatísticas
        $this->data['stats'] = $matrixRepo->getMatrixStats();
        
        // Se for POST, processar ação (adicionar, atualizar ou remover)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
            // Recarregar após processar
            header('Location: ' . $_ENV['URL_ADM'] . 'competency-matrix');
            exit;
        }
        
        // Buscar todos os cargos com suas competências
        $this->loadAllPositionsWithCompetencies($competenciesRepo, $positionsRepo, $matrixRepo);
        
        $pageElements = [
            'title_head' => 'Matriz de Competências',
            'menu' => 'competency-matrix',
            'buttonPermission' => [
                'ListCompetencies',
                'CreateCompetency',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/competency_matrix', $this->data);
        $loadView->loadView();
    }

    private function loadAllPositionsWithCompetencies(CompetenciesRepository $competenciesRepo, PositionsRepository $positionsRepo, CompetencyMatrixRepository $matrixRepo): void
    {
        // Buscar todos os cargos
        $allPositions = $positionsRepo->getAllPositionsSelect();
        
        // Buscar todas as competências
        $allCompetencies = $competenciesRepo->getAll();
        
        // Para cada cargo, buscar suas competências e as disponíveis
        $positionsWithCompetencies = [];
        foreach ($allPositions as $position) {
            $positionId = $position['id'];
            
            // Buscar competências já vinculadas
            $linkedCompetencies = $matrixRepo->getCompetenciesByPosition($positionId);
            
            // IDs das competências já vinculadas
            $linkedCompetencyIds = array_column($linkedCompetencies, 'id');
            
            // Filtrar competências disponíveis (não vinculadas)
            $availableCompetencies = array_filter($allCompetencies, function($comp) use ($linkedCompetencyIds) {
                return !in_array($comp['id'], $linkedCompetencyIds);
            });
            
            $positionsWithCompetencies[] = [
                'id' => $positionId,
                'name' => $position['name'],
                'competencies' => $linkedCompetencies,
                'available_competencies' => array_values($availableCompetencies)
            ];
        }
        
        $this->data['positions_with_competencies'] = $positionsWithCompetencies;
    }

    private function handlePost(): void
    {
        $action = $_POST['action'] ?? '';
        $positionId = (int)($_POST['position_id'] ?? 0);
        
        if (empty($positionId)) {
            $_SESSION['error'] = 'Cargo não especificado.';
            return;
        }

        $matrixRepo = new CompetencyMatrixRepository();

        switch ($action) {
            case 'add':
                $this->addCompetency($matrixRepo, $positionId);
                break;
                
            case 'update':
                $this->updateCompetencies($matrixRepo, $positionId);
                break;
                
            case 'remove':
                $this->removeCompetency($matrixRepo, $positionId);
                break;
                
            default:
                $_SESSION['error'] = 'Ação inválida.';
        }
    }

    private function addCompetency(CompetencyMatrixRepository $matrixRepo, int $positionId): void
    {
        // Validar CSRF - usar identificador único por cargo
        $formIdentifier = 'form_add_competency_to_position_' . $positionId;
        if (!CSRFHelper::validateCSRFToken($formIdentifier, $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $competencyId = (int)($_POST['competency_id'] ?? 0);
        $requiredLevel = (int)($_POST['required_level'] ?? 0);
        $isMandatory = isset($_POST['is_mandatory']) && $_POST['is_mandatory'] === '1';

        if (empty($competencyId) || empty($requiredLevel)) {
            $_SESSION['error'] = 'Competência e nível requerido são obrigatórios.';
            return;
        }

        if ($requiredLevel < 1 || $requiredLevel > 5) {
            $_SESSION['error'] = 'Nível requerido deve estar entre 1 e 5.';
            return;
        }

        // Verificar se já existe
        $existing = $matrixRepo->getRequiredLevel($positionId, $competencyId);
        if ($existing) {
            $_SESSION['error'] = 'Esta competência já está vinculada a este cargo.';
            return;
        }

        if ($matrixRepo->upsert([
            'position_id' => $positionId,
            'competency_id' => $competencyId,
            'required_level' => $requiredLevel,
            'is_mandatory' => $isMandatory
        ])) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Competência adicionada ao cargo com sucesso!</div>';
            GenerateLog::generateLog("info", "Competência adicionada ao cargo na matriz.", [
                'position_id' => $positionId,
                'competency_id' => $competencyId,
                'required_level' => $requiredLevel
            ]);
        } else {
            $_SESSION['error'] = 'Erro ao adicionar competência ao cargo.';
        }
    }

    private function updateCompetencies(CompetencyMatrixRepository $matrixRepo, int $positionId): void
    {
        // Validar CSRF - usar identificador único por cargo
        $formIdentifier = 'form_update_competencies_' . $positionId;
        if (!CSRFHelper::validateCSRFToken($formIdentifier, $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $updated = 0;

        if (isset($_POST['competencies']) && is_array($_POST['competencies'])) {
            foreach ($_POST['competencies'] as $competencyId => $data) {
                $competencyId = (int)$competencyId;
                $requiredLevel = (int)($data['required_level'] ?? 0);
                $isMandatory = isset($data['is_mandatory']) && $data['is_mandatory'] === '1';

                if ($requiredLevel < 1 || $requiredLevel > 5) {
                    continue;
                }

                if ($matrixRepo->upsert([
                    'position_id' => $positionId,
                    'competency_id' => $competencyId,
                    'required_level' => $requiredLevel,
                    'is_mandatory' => $isMandatory
                ])) {
                    $updated++;
                }
            }
        }

        if ($updated > 0) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">' . $updated . ' competência(s) atualizada(s) com sucesso!</div>';
            GenerateLog::generateLog("info", "Competências do cargo atualizadas na matriz.", [
                'position_id' => $positionId,
                'updated' => $updated
            ]);
        }
    }

    private function removeCompetency(CompetencyMatrixRepository $matrixRepo, int $positionId): void
    {
        // Validar CSRF - tentar múltiplos identificadores possíveis
        $csrfToken = $_POST['csrf_token'] ?? '';
        $formIdentifierUpdate = 'form_update_competencies_' . $positionId;
        $formIdentifierAdd = 'form_add_competency_to_position_' . $positionId;
        
        if (!CSRFHelper::validateCSRFToken($formIdentifierUpdate, $csrfToken) && 
            !CSRFHelper::validateCSRFToken($formIdentifierAdd, $csrfToken)) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $competencyId = (int)($_POST['competency_id'] ?? 0);

        if (empty($competencyId)) {
            $_SESSION['error'] = 'Competência não especificada.';
            return;
        }

        if ($matrixRepo->delete($positionId, $competencyId)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Competência removida do cargo com sucesso!</div>';
            GenerateLog::generateLog("info", "Competência removida do cargo na matriz.", [
                'position_id' => $positionId,
                'competency_id' => $competencyId
            ]);
        } else {
            $_SESSION['error'] = 'Erro ao remover competência do cargo.';
        }
    }
}

