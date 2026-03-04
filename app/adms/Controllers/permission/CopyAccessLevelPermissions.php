<?php

namespace App\adms\Controllers\permission;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AccessLevelsPagesRepository;
use App\adms\Models\Repository\AccessLevelsRepository;

/**
 * Copiar permissões de um nível de acesso para outro.
 *
 * Este endpoint é acionado a partir da tela de listagem de níveis de acesso
 * e permite clonar todas as permissões (0 e 1) de um grupo para outro.
 */
class CopyAccessLevelPermissions
{
    public function index(): void
    {
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?? [];

        // Validar CSRF
        if (
            empty($form['csrf_token']) ||
            !CSRFHelper::validateCSRFToken('form_copy_access_level_permissions', $form['csrf_token'])
        ) {
            $_SESSION['error'] = 'Token CSRF inválido ou expirado ao copiar permissões.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-access-levels');
            return;
        }

        $sourceId = isset($form['source_access_level_id']) ? (int)$form['source_access_level_id'] : 0;
        $targetId = isset($form['target_access_level_id']) ? (int)$form['target_access_level_id'] : 0;

        // Validações básicas
        if ($sourceId <= 0 || $targetId <= 0) {
            $_SESSION['error'] = 'É necessário selecionar nível de acesso origem e destino válidos.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-access-levels');
            return;
        }

        if ($sourceId === $targetId) {
            $_SESSION['error'] = 'Origem e destino não podem ser o mesmo nível de acesso.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-access-levels');
            return;
        }

        // Opcional: validar se níveis existem
        $accessLevelsRepo = new AccessLevelsRepository();
        $sourceExists = $accessLevelsRepo->getAccessLevel($sourceId);
        $targetExists = $accessLevelsRepo->getAccessLevel($targetId);

        if (!$sourceExists || !$targetExists) {
            $_SESSION['error'] = 'Nível de acesso origem ou destino não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-access-levels');
            return;
        }

        $repo = new AccessLevelsPagesRepository();
        $result = $repo->copyAccessLevelPermissions($sourceId, $targetId);

        if ($result) {
            $_SESSION['success'] = 'Permissões copiadas com sucesso para o nível de acesso destino.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-access-levels-permissions/' . $targetId);
            return;
        }

        $errorMessage = AccessLevelsPagesRepository::getLastErrorMessage() ?? 'Erro ao copiar permissões entre níveis de acesso.';
        $_SESSION['error'] = $errorMessage;

        GenerateLog::generateLog('error', 'Falha ao copiar permissões entre níveis de acesso.', [
            'source_level_id' => $sourceId,
            'target_level_id' => $targetId,
            'detail'          => $errorMessage,
        ]);

        header('Location: ' . $_ENV['URL_ADM'] . 'list-access-levels');
    }
}

