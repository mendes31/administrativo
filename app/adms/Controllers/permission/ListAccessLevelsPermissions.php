<?php

namespace App\adms\Controllers\permission;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationAccessLevelPermissionService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\AccessLevelsPagesRepository;
use App\adms\Models\Repository\AccessLevelsRepository;
use App\adms\Models\Repository\MenuPermissionUserRepository;
use App\adms\Models\Repository\PagesRepository;
use App\adms\Views\Services\LoadViewService;

class ListAccessLevelsPermissions
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** 
     * @var int $id ID do nível de acesso 
     */
    private int $id;

    public function index(string|int $id): void
    {
        // Fluxo especial: cópia de permissões entre níveis (acionado pela listagem)
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['mode'])
            && $_POST['mode'] === 'copy_permissions'
        ) {
            $this->handleCopyPermissions();
            return;
        }

        $this->id = $id;

        // Receber os dados do formulário (usar $_POST para preservar array aninhado 'permissions')
        $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
        // Remover chave 0 para evitar Duplicate entry '0' na tabela adms_access_levels_pages
        unset($permissions[0]);
        $this->data['form'] = [
            'csrf_token'            => $_POST['csrf_token'] ?? '',
            'adms_access_level_id'  => isset($_POST['adms_access_level_id']) ? (int) $_POST['adms_access_level_id'] : 0,
            'permissions'           => $permissions,
        ];

        // Verificar se é uma requisição AJAX
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (
            isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_update_access_level_permissions', $this->data['form']['csrf_token'])
        ) {
            // Editar o nível de acesso
            $this->editAccessLevelPermissions($isAjax);

        } else {
            // Se for AJAX, retornar erro em JSON (incluir novo token para permitir nova tentativa)
            if ($isAjax) {
                $this->returnJsonResponse(false, 'Token CSRF inválido ou não encontrado', [
                    'csrf_token' => CSRFHelper::generateCSRFToken('form_update_access_level_permissions'),
                ]);
                return;
            }
            // Carregar a visualização para edição do nível de acesso
            $this->viewAccessLevelPermissions();
        }
        
    }

    /**
     * Cópia de permissões entre níveis de acesso a partir da tela de listagem.
     */
    private function handleCopyPermissions(): void
    {
        $form = $_POST;

        // Validar CSRF específico da cópia
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
            MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
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

    private function viewAccessLevelPermissions(): void
    {     

        // Recuperar o registro do nível de acesso
        $viewAccessLevel = new AccessLevelsRepository();
        $this->data['accessLevel'] = $viewAccessLevel->getAccessLevel($this->id);

        // Verificar se o nível de acesso foi encontrado
        if (!$this->data['accessLevel']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Nível de acesso não encontrado", ['id' => (int) $this->id]);
            $_SESSION['error'] = "Nível de acesso não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-access-levels");
            return;
        }

        // Recuperar as páginas associadas ao nível de acesso
        $listPages = new PagesRepository();
        $this->data['pages'] = $listPages->getAllPagesFull();
        
        // Recuperar as permissões do nível de acesso
        $listAccessLevelsPages = new AccessLevelsPagesRepository();
        $this->data['accessLevelsPages'] = $listAccessLevelsPages->getPagesAccessLevelsArray($this->id, true);

        $this->data['super_admin_level_permissions_locked'] =
            (int) $this->id === UserAccessHelper::SUPER_ADMIN_LEVEL_ID;
        
        // Gerar token CSRF para o formulário
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('form_update_access_level_permissions');
        
        // Definir o título da página, ativar o item de menu, apresentar ou ocultar botão
        $pageElements = [
            'title_head' => 'Editar Permissão do Nível de Acesso',
            'menu' => 'list-access-levels',
            'buttonPermission' => ['ListAccessLevels'],
        ];
        $pageLayoutService = new PageLayoutService(); 
        // Combinar os valores do atributos 'data' com o array dos elementos da página
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // // Definir o título da página
        // $this->data['title_head'] = "Editar Permissão do Nível de Acesso";

        // // Ativar o item de menu
        // $this->data['menu'] = "list-access-levels";

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/permission/list", $this->data);
        $loadView->loadView();
    }

    private function editAccessLevelPermissions(bool $isAjax): void 
    {
        // Log de debug detalhado
        error_log('=== EDITACCESSLEVELPERMISSIONS INICIADO ===');
        error_log('Timestamp: ' . date('Y-m-d H:i:s'));
        error_log('É AJAX? ' . ($isAjax ? 'SIM' : 'NÃO'));
        error_log('Dados do formulário: ' . json_encode($this->data['form']));
        
        // Log específico para desktop vs mobile
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'não definido';
        error_log('User-Agent: ' . $userAgent);
        
        if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false || strpos($userAgent, 'iPhone') !== false) {
            error_log('🔍 REQUISIÇÃO IDENTIFICADA COMO MOBILE');
        } else {
            error_log('🔍 REQUISIÇÃO IDENTIFICADA COMO DESKTOP');
        }
        
        // Log detalhado das permissões
        if (isset($this->data['form']['permissions']) && is_array($this->data['form']['permissions'])) {
            error_log('Total de permissões recebidas: ' . count($this->data['form']['permissions']));
            error_log('Permissões autorizadas (1): ' . count(array_filter($this->data['form']['permissions'], function($v) { return $v == '1'; })));
            error_log('Permissões revogadas (0): ' . count(array_filter($this->data['form']['permissions'], function($v) { return $v == '0'; })));
            
            // Log das primeiras 5 permissões para debug
            $firstPermissions = array_slice($this->data['form']['permissions'], 0, 5, true);
            error_log('Primeiras 5 permissões: ' . json_encode($firstPermissions));
        } else {
            error_log('❌ ERRO: Campo permissions não encontrado ou não é array');
            error_log('Tipo de permissions: ' . gettype($this->data['form']['permissions'] ?? 'não definido'));
        }

        if ((int) ($this->data['form']['adms_access_level_id'] ?? 0) === UserAccessHelper::SUPER_ADMIN_LEVEL_ID) {
            $msg = 'As permissões do nível Super Administrador não podem ser alteradas: o nível tem acesso total ao sistema.';
            if ($isAjax) {
                $this->returnJsonResponse(false, $msg, [
                    'csrf_token' => CSRFHelper::generateCSRFToken('form_update_access_level_permissions'),
                ]);
                return;
            }
            $_SESSION['error'] = $msg;
            header('Location: ' . $_ENV['URL_ADM'] . 'list-access-levels-permissions/' . UserAccessHelper::SUPER_ADMIN_LEVEL_ID);
            return;
        }
        
        // Validar os dados do formulário 
        $validationAccessLevelPermissions = new ValidationAccessLevelPermissionService();
        $this->data['errors'] = $validationAccessLevelPermissions->validate($this->data['form']);

        // Se houver erros de validação
        if(!empty($this->data['errors'])){
            if ($isAjax) {
                $this->returnJsonResponse(false, 'Erro de validação: ' . implode(', ', $this->data['errors']));
                return;
            }
            $this->viewAccessLevelPermissions();
            return;
        }

        // Atualizar as permissões do nível de acesso
        $accessLevelPagesUpdate = new AccessLevelsPagesRepository();
        $result = $accessLevelPagesUpdate->updateAccessLevelPages($this->data['form']);

        // Verifica o resultado da atualização
        if ($result) {
            MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
            if ($isAjax) {
                error_log('Permissões salvas com sucesso via AJAX');
                $this->returnJsonResponse(true, 'Permissões do nível de acesso editadas com sucesso!', [
                    'csrf_token' => CSRFHelper::generateCSRFToken('form_update_access_level_permissions'),
                ]);
                return;
            }
            $_SESSION['success'] = "Permissões do nível de acesso editadas com sucesso!";
            header("Location: {$_ENV['URL_ADM']}list-access-levels-permissions/{$this->data['form']['adms_access_level_id']}");
        } else {
            $errorDetail = AccessLevelsPagesRepository::getLastErrorMessage();
            $message = $errorDetail
                ? 'Permissões do nível de acesso não editadas: ' . $errorDetail
                : 'Permissões do nível de acesso não editadas!';
            if ($isAjax) {
                error_log('Falha ao salvar permissões via AJAX: ' . ($errorDetail ?? ''));
                $this->returnJsonResponse(false, $message, [
                    'csrf_token' => CSRFHelper::generateCSRFToken('form_update_access_level_permissions'),
                ]);
                return;
            }
            $this->data['errors'][] = $message;
            $this->viewAccessLevelPermissions();
        }
    }

    private function returnJsonResponse(bool $success, string $message, array $extra = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
        exit;
    }

}
