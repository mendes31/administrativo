<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAssignmentsRepository;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;

/**
 * Controller para listar e gerenciar atribuições de avaliações
 * 
 * @package App\adms\Controllers\evaluations
 */
class ListEvaluationAssignments
{
    private array $data = [];
    private int $limitResult = 20;

    public function index($page = 1): void
    {
        // Filtros
        $searchTerm = $_GET['search'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $modelIdFilter = $_GET['model_id'] ?? '';

        // Buscar atribuições usando query direta (temporário)
        $assignmentsRepo = new EvaluationAssignmentsRepository();
        
        // Para agora, vamos buscar todas as atribuições e filtrar depois
        // TODO: Implementar filtros no repositório
        $this->data['assignments'] = $this->getAllAssignmentsWithDetails($page);

        // Buscar modelos para filtro
        $modelsRepo = new EvaluationModelsRepository();
        $this->data['models'] = $modelsRepo->getAllModels();

        // Dados para a view
        $this->data['searchTerm'] = $searchTerm;
        $this->data['statusFilter'] = $statusFilter;
        $this->data['modelIdFilter'] = $modelIdFilter;
        $this->data['currentPage'] = $page;

        $pageElements = [
            'title_head' => 'Atribuições de Avaliações',
            'menu' => 'list-evaluation-assignments',
            'buttonPermission' => ['ListEvaluationAssignments'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/evaluations/assignments/list', $this->data);
        $loadView->loadView();
    }

    private function getAllAssignmentsWithDetails($page = 1): array
    {
        // Converter para int se for string
        if (is_string($page)) {
            $page = !empty($page) ? (int)$page : 1;
        }
        
        $assignmentsRepo = new EvaluationAssignmentsRepository();
        $conn = $assignmentsRepo->getConnection();
        
        $offset = ($page - 1) * $this->limitResult;
        
        $sql = "
            SELECT 
                ea.*,
                u.name as user_name,
                u.email as user_email,
                em.titulo as model_titulo,
                em.nota_minima_aprovacao,
                em.max_tentativas,
                at.nome as training_name,
                cancelador.name as cancelado_por_name
            FROM adms_evaluation_assignments ea
            INNER JOIN adms_users u ON u.id = ea.adms_user_id
            INNER JOIN adms_evaluation_models em ON em.id = ea.evaluation_model_id
            LEFT JOIN adms_trainings at ON at.id = em.adms_training_id
            LEFT JOIN adms_users cancelador ON cancelador.id = ea.cancelado_por
            ORDER BY 
                CASE ea.status
                    WHEN 'em_andamento' THEN 1
                    WHEN 'pendente' THEN 2
                    WHEN 'reprovado' THEN 3
                    WHEN 'aprovado' THEN 4
                    WHEN 'concluido' THEN 5
                    WHEN 'cancelado' THEN 6
                END,
                ea.data_limite ASC,
                ea.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':limit', $this->limitResult, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}