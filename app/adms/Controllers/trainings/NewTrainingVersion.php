<?php

namespace App\adms\Controllers\trainings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TrainingsRepository;

class NewTrainingVersion
{
    public function index(): void
    {
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (
            empty($form['csrf_token']) ||
            !CSRFHelper::validateCSRFToken('form_new_training_version', (string)$form['csrf_token'])
        ) {
            $_SESSION['error'] = 'Token CSRF inválido para criar nova versão.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        }

        $sourceTrainingId = (int)($form['source_training_id'] ?? 0);
        if ($sourceTrainingId < 1) {
            $_SESSION['error'] = 'Treinamento de origem inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        }

        $repo = new TrainingsRepository();
        $sourceTraining = $repo->getTraining($sourceTrainingId);
        if (!$sourceTraining) {
            $_SESSION['error'] = 'Treinamento de origem não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        }
        if (isset($sourceTraining['is_current_version']) && (int)$sourceTraining['is_current_version'] !== 1) {
            $_SESSION['error'] = 'Nova versão só pode ser criada a partir da versão atual.';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-training/' . $sourceTrainingId);
            exit;
        }

        $payload = [
            'nome' => $form['nome'] ?? null,
            'versao' => $form['versao'] ?? null,
            'prazo_treinamento' => $form['prazo_treinamento'] ?? null,
            'tipo' => $form['tipo'] ?? null,
            'instrutor' => $form['instrutor'] ?? null,
            'carga_horaria' => $form['carga_horaria'] ?? null,
            'instructor_user_id' => $form['instructor_user_id'] ?? null,
            'instructor_email' => $form['instructor_email'] ?? null,
            'instructor_name' => $form['instructor_name'] ?? null,
            'reciclagem' => $form['reciclagem'] ?? null,
            'reciclagem_periodo' => $form['reciclagem_periodo'] ?? null,
            'area_responsavel_id' => $form['area_responsavel_id'] ?? null,
            'area_elaborador_id' => $form['area_elaborador_id'] ?? null,
            'tipo_obrigatoriedade' => $form['tipo_obrigatoriedade'] ?? null,
            'change_summary' => $form['change_summary'] ?? null,
            'require_retraining' => !empty($form['require_retraining']) ? 1 : 0,
        ];

        $newId = $repo->createNewVersion($sourceTrainingId, $payload, (int)($_SESSION['user_id'] ?? 0));

        if (!$newId) {
            if (empty($_SESSION['error'])) {
                $_SESSION['error'] = 'Não foi possível criar a nova versão do treinamento.';
            }
            header('Location: ' . $_ENV['URL_ADM'] . 'view-training/' . $sourceTrainingId);
            exit;
        }

        $_SESSION['success'] = 'Nova versão criada com sucesso.';
        header('Location: ' . $_ENV['URL_ADM'] . 'view-training/' . $newId);
        exit;
    }
}

