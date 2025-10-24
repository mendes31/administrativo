<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAssignmentsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para exibir avaliações do usuário logado
 * 
 * @package App\adms\Controllers\evaluations
 */
class MyEvaluations
{
    private array $data = [];

    public function index(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        try {
            $assignmentsRepo = new EvaluationAssignmentsRepository();
            
            // Buscar todas as atribuições do usuário
            $this->data['avaliacoes'] = $assignmentsRepo->getAssignmentsByUser($userId);
            
            // Separar por status
            $this->data['pendentes'] = array_filter($this->data['avaliacoes'], function($a) {
                return in_array($a['status'], ['pendente', 'em_andamento', 'reprovado']);
            });
            
            $this->data['concluidas'] = array_filter($this->data['avaliacoes'], function($a) {
                return in_array($a['status'], ['aprovado', 'concluido']);
            });
            
            $this->data['canceladas'] = array_filter($this->data['avaliacoes'], function($a) {
                return $a['status'] === 'cancelado';
            });

            // Contar pendentes para badge
            $this->data['total_pendentes'] = count($this->data['pendentes']);

        } catch (\Throwable $e) {
            $this->data['avaliacoes'] = [];
            $this->data['pendentes'] = [];
            $this->data['concluidas'] = [];
            $this->data['canceladas'] = [];
            $this->data['total_pendentes'] = 0;
            $this->data['error'] = 'Erro ao buscar avaliações: ' . $e->getMessage();
        }

        // Layout e permissões
        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements([
            'title_head' => 'Minhas Avaliações',
            'menu' => 'my-evaluations',
            'buttonPermission' => [],
        ]));

        $loadView = new LoadViewService('adms/Views/evaluations/myEvaluations', $this->data);
        $loadView->loadView();
    }
} 