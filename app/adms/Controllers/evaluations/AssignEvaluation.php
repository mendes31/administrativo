<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\EvaluationAssignmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\DbConnection;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Services\EvaluationNotificationService;
use App\adms\Helpers\EvaluationLogService;
use App\adms\Helpers\CSRFHelper;

/**
 * Controller para atribuir avaliação para usuários
 * 
 * @package App\adms\Controllers\evaluations
 */
class AssignEvaluation
{
    private array $data = [];

    public function index($modelId = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarAtribuicao();
            return;
        }

        // Converter para int se for string
        if (is_string($modelId)) {
            $modelId = !empty($modelId) ? (int)$modelId : null;
        }

        // Se não há modelId, mostrar lista de modelos para escolher
        if (!$modelId) {
            // Carregar todos os modelos para seleção
            $modelsRepo = new EvaluationModelsRepository();
            $this->data['models'] = $modelsRepo->getAllModels();
            
            $pageElements = [
                'title_head' => 'Atribuir Avaliação',
                'menu' => 'list-evaluation-models',
                'buttonPermission' => ['AssignEvaluation'],
            ];

            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

            $loadView = new LoadViewService('adms/Views/evaluations/assignments/selectModel', $this->data);
            $loadView->loadView();
            return;
        }

        // Carregar modelo específico
        $modelsRepo = new EvaluationModelsRepository();
        $this->data['model'] = $modelsRepo->getModel($modelId);

        if (!$this->data['model']) {
            $_SESSION['msg'] = 'Modelo não encontrado!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
            exit;
        }

        // Carregar usuários ativos
        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersForSelect();

        // Carregar atribuições já existentes
        $assignmentsRepo = new EvaluationAssignmentsRepository();
        $this->data['atribuicoes_existentes'] = $assignmentsRepo->getAssignmentsByModel($modelId);

        $pageElements = [
            'title_head' => 'Atribuir Avaliação',
            'menu' => 'list-evaluation-models',
            'buttonPermission' => ['AssignEvaluation'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/evaluations/assignments/assign', $this->data);
        $loadView->loadView();
    }

    private function processarAtribuicao(): void
    {
        try {
            // Validar CSRF
            if (!CSRFHelper::validateCSRFToken('form_assign_evaluation', $_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de segurança inválido!');
            }

            $modelId = (int)($_POST['model_id'] ?? 0);
            $userIds = $_POST['user_ids'] ?? [];
            $dataLimite = !empty($_POST['data_limite']) ? $_POST['data_limite'] : null;

            if (!$modelId) {
                throw new \Exception('Modelo não informado!');
            }

            if (empty($userIds)) {
                throw new \Exception('Selecione pelo menos um usuário!');
            }

            // LOG: Início
            \App\adms\Helpers\GenerateLog::generateLog("info", "Iniciando atribuição em lote", [
                'model_id' => $modelId,
                'total_usuarios' => count($userIds),
                'data_limite' => $dataLimite,
                'criado_por' => $_SESSION['user_id'] ?? null
            ]);

            $assignmentsRepo = new EvaluationAssignmentsRepository();
            $conn = $assignmentsRepo->getConnection();
            $conn->beginTransaction();

            $assignmentsRepo = new EvaluationAssignmentsRepository();
            $usersRepo = new UsersRepository();
            $modelsRepo = new EvaluationModelsRepository();

            $model = $modelsRepo->getModel($modelId);

            $atribuidos = 0;
            $jaExistentes = 0;
            $falhas = 0;
            $erros = [];

            foreach ($userIds as $userId) {
                try {
                    $userId = (int)$userId;

                    // Verificar se já existe
                    $existente = $assignmentsRepo->getByUserAndModel($userId, $modelId);

                    if ($existente) {
                        $jaExistentes++;
                        continue;
                    }

                    // Inserir atribuição
                    $assignmentId = $assignmentsRepo->insert([
                        'evaluation_model_id' => $modelId,
                        'adms_user_id' => $userId,
                        'created_by' => $_SESSION['user_id'] ?? 1,
                        'data_atribuicao' => date('Y-m-d H:i:s'),
                        'data_limite' => $dataLimite,
                        'status' => 'pendente',
                        'tentativas' => 0,
                        'nota_maxima' => null
                    ]);

                    if ($assignmentId) {
                        // LOG
                        EvaluationLogService::logAssignmentCreated(
                            $assignmentId,
                            [
                                'evaluation_model_id' => $modelId,
                                'adms_user_id' => $userId,
                                'data_limite' => $dataLimite
                            ],
                            $_SESSION['user_id'] ?? 1
                        );

                        // Enviar notificação
                        $user = $usersRepo->getUser($userId);
                        if ($user) {
                            $notifSucesso = EvaluationNotificationService::notificarNovaAtribuicao(
                                $user,
                                $model,
                                $dataLimite
                            );

                            EvaluationLogService::logNotificationSent(
                                $userId,
                                $modelId,
                                'nova_atribuicao',
                                $notifSucesso,
                                $notifSucesso ? null : 'Falha no envio de e-mail'
                            );
                        }

                        $atribuidos++;
                    }

                } catch (\Exception $e) {
                    $falhas++;
                    $erros[] = "Usuário ID {$userId}: " . $e->getMessage();
                    
                    EvaluationLogService::logError('atribuir_para_usuario', $e, [
                        'user_id' => $userId,
                        'model_id' => $modelId
                    ]);
                }
            }

            $conn->commit();

            // LOG: Resumo
            \App\adms\Helpers\GenerateLog::generateLog("info", "Atribuição em lote CONCLUÍDA", [
                'model_id' => $modelId,
                'total_solicitados' => count($userIds),
                'total_atribuidos' => $atribuidos,
                'total_ja_existentes' => $jaExistentes,
                'total_falhas' => $falhas,
                'criado_por' => $_SESSION['user_id'] ?? null
            ]);

            // Mensagem de sucesso
            $mensagem = "Atribuição concluída!";
            if ($atribuidos > 0) {
                $mensagem .= " {$atribuidos} usuário(s) atribuído(s).";
            }
            if ($jaExistentes > 0) {
                $mensagem .= " {$jaExistentes} já tinha(m) atribuição.";
            }
            if ($falhas > 0) {
                $mensagem .= " {$falhas} falha(s).";
            }

            $_SESSION['msg'] = $mensagem;
            $_SESSION['msg_type'] = $falhas > 0 ? 'warning' : 'success';

            if (!empty($erros)) {
                $_SESSION['msg_detalhes'] = $erros;
            }

        } catch (\Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }

            EvaluationLogService::logError('atribuicao_em_lote', $e, [
                'model_id' => $modelId ?? 0,
                'total_usuarios' => count($userIds ?? [])
            ]);

            $_SESSION['msg'] = 'Erro: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        $returnUrl = isset($modelId) && $modelId 
            ? $_ENV['URL_ADM'] . 'view-evaluation-model/' . $modelId
            : $_ENV['URL_ADM'] . 'list-evaluation-models';

        header('Location: ' . $returnUrl);
        exit;
    }
}

