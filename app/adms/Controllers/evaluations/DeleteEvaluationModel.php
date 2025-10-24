<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\EvaluationAssignmentsRepository;
use App\adms\Helpers\EvaluationLogService;
use App\adms\Helpers\CSRFHelper;

/**
 * Controller para deletar modelo de avaliação
 * 
 * @package App\adms\Controllers\evaluations
 */
class DeleteEvaluationModel
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
            exit;
        }

        try {
            // Validar CSRF
            if (!CSRFHelper::validateCSRFToken('form_delete_evaluation_model', $_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de segurança inválido!');
            }

            $modelId = (int)($_POST['id'] ?? 0);

            if (!$modelId) {
                throw new \Exception('ID do modelo não informado!');
            }

            // Buscar modelo
            $modelsRepo = new EvaluationModelsRepository();
            $model = $modelsRepo->getModel($modelId);

            if (!$model) {
                throw new \Exception('Modelo não encontrado!');
            }

            // Verificar se existem atribuições
            $assignmentsRepo = new EvaluationAssignmentsRepository();
            $assignments = $assignmentsRepo->getAssignmentsByModel($modelId);

            if (!empty($assignments)) {
                // Contar atribuições ativas
                $ativas = array_filter($assignments, function($a) {
                    return !in_array($a['status'], ['cancelado']);
                });

                if (!empty($ativas)) {
                    throw new \Exception(
                        'Não é possível deletar este modelo pois existem ' . 
                        count($ativas) . ' atribuição(ões) ativa(s). ' .
                        'Cancele as atribuições primeiro ou desative o modelo.'
                    );
                }
            }

            // Deletar (cascade vai deletar questões automaticamente)
            $resultado = $modelsRepo->deleteModel($modelId);

            if (!$resultado) {
                throw new \Exception('Erro ao deletar modelo!');
            }

            // LOG
            EvaluationLogService::logModelDeleted(
                $modelId,
                $model,
                $_SESSION['user_id'] ?? 1
            );

            $_SESSION['msg'] = 'Modelo deletado com sucesso!';
            $_SESSION['msg_type'] = 'success';

        } catch (\Exception $e) {
            EvaluationLogService::logError('deletar_modelo', $e, ['model_id' => $modelId ?? 0]);
            
            $_SESSION['msg'] = 'Erro: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
        exit;
    }
}
