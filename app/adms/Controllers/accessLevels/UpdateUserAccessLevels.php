<?php

namespace App\adms\Controllers\accessLevels;

use App\adms\Controllers\Services\Validation\ValidationUserAccessLevelService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\WhistleblowingPermissionService;

class UpdateUserAccessLevels
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;


    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (
            isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_update_access_level', $this->data['form']['csrf_token'])
        ) {
            $this->editUserAccessLevel();
        }
    }

    private function viewUserAccessLevel(): void
    {
        GenerateLog::generateLog("error", "Nível de acesso do usuário não editado.", ['id' => (int) $this->data['form']['adms_user_id']]);

        $_SESSION['error'] = "Nível de acesso do usuário não editado!";
        header("Location: {$_ENV['URL_ADM']}view-user/{$this->data['form']['adms_user_id']}");
        return;
    }

    private function editUserAccessLevel(): void
    {
        $validationUserAccessLevel = new ValidationUserAccessLevelService();
        $_SESSION['errors'] = $validationUserAccessLevel->validate($this->data['form']);

        if (!empty($_SESSION['errors'])) {
            $this->viewUserAccessLevel();
            return;
        }

        $targetId = (int) ($this->data['form']['adms_user_id'] ?? 0);
        $targetUser = (new UsersRepository())->getUser($targetId);
        if ($targetUser && (int) ($targetUser['super_usuario'] ?? 0) === 1) {
            $_SESSION['error'] = 'Usuários com super usuário têm acesso total. Remova o flag Super usuário no cadastro antes de alterar os níveis de acesso.';
            header("Location: {$_ENV['URL_ADM']}view-user/{$targetId}");
            return;
        }

        $userAccessLevelsUpdate = new UsersAccessLevelsRepository();

        if (!$this->assertWhistleblowingLevelsAllowed($userAccessLevelsUpdate, $targetId)) {
            return;
        }

        $result = $userAccessLevelsUpdate->updateUserAccessLevel($this->data['form']);

        if ($result) {
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_users_access_levels',
                    'action' => 'edição',
                    'record_id' => $this->data['form']['adms_user_id'],
                    'description' => 'Alteração de níveis de acesso do usuário',
                ];
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }

            $_SESSION['success'] = "Nível de acesso do usuário editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-user/{$this->data['form']['adms_user_id']}");
        } else {
            $this->data['errors'][] = "Nível de acesso do usuário não editado!";
            $this->viewUserAccessLevel();
        }
    }

    /**
     * Impede que quem não é Super Administrador / Super usuário conceda ou remova
     * os níveis do Canal de Denúncias (mesmo com UpdateUserAccessLevels).
     */
    private function assertWhistleblowingLevelsAllowed(UsersAccessLevelsRepository $repo, int $targetId): bool
    {
        if (WhistleblowingPermissionService::canManageAccessLevelsAssignment()) {
            return true;
        }

        $protectedIds = WhistleblowingPermissionService::protectedAccessLevelIds();
        if ($protectedIds === []) {
            return true;
        }

        $current = $repo->getUserAccessLevelArray($targetId);
        $currentIds = is_array($current) ? array_map('intval', $current) : [];

        $submitted = $this->data['form']['userAccessLevelsArray'] ?? [];
        if (!is_array($submitted)) {
            $submitted = [];
        }
        $submittedIds = array_map('intval', array_values($submitted));

        $currentProtected = array_values(array_intersect($currentIds, $protectedIds));
        $submittedProtected = array_values(array_intersect($submittedIds, $protectedIds));
        sort($currentProtected);
        sort($submittedProtected);

        if ($currentProtected === $submittedProtected) {
            return true;
        }

        $_SESSION['error'] = 'Somente Super Administrador ou Super usuário pode atribuir ou remover os níveis '
            . 'Canal de Denúncias — Operador e Canal de Denúncias — Administrador.';
        header("Location: {$_ENV['URL_ADM']}view-user/{$targetId}");

        return false;
    }
}
