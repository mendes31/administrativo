<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\EmploymentHistoryRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar histórico de admissões/desligamentos
 */
class UpdateEmploymentHistory
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int) $id) {
            GenerateLog::generateLog("error", "Histórico de emprego não encontrado.", ['id' => (int) $id]);
            $_SESSION['error'] = "Registro de histórico não encontrado.";
            header("Location: {$_ENV['URL_ADM']}list-users");
            return;
        }

        $historyRepo = new EmploymentHistoryRepository();
        $this->data['history'] = $historyRepo->getById((int) $id);

        if (!$this->data['history']) {
            GenerateLog::generateLog("error", "Histórico de emprego não encontrado.", ['id' => (int) $id]);
            $_SESSION['error'] = "Registro de histórico não encontrado.";
            header("Location: {$_ENV['URL_ADM']}list-users");
            return;
        }

        // Buscar informações do usuário
        $usersRepo = new UsersRepository();
        $this->data['user'] = $usersRepo->getUser($this->data['history']['adms_user_id']);

        // Verificar se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update();
        } else {
            $this->showForm();
        }
    }

    private function showForm(): void
    {
        $pageElements = [
            'title_head' => 'Editar Histórico de Emprego',
            'menu' => 'list-users',
            'buttonPermission' => [
                'UpdateUser',
                'ViewUser',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/users/update_employment_history', $this->data);
        $loadView->loadView();
    }

    private function update(): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_update_employment_history', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            $this->showForm();
            return;
        }

        $data = [
            'data_admissao' => $_POST['data_admissao'] ?? '',
            'data_desligamento' => !empty($_POST['data_desligamento']) ? $_POST['data_desligamento'] : null,
            'motivo_desligamento' => !empty($_POST['motivo_desligamento']) ? $_POST['motivo_desligamento'] : null,
            'tipo_periodo' => $_POST['tipo_periodo'] ?? 'Admissão',
            'observacoes' => !empty($_POST['observacoes']) ? $_POST['observacoes'] : null,
        ];

        // Validações
        if (empty($data['data_admissao'])) {
            $_SESSION['error'] = 'Data de admissão é obrigatória!';
            $this->showForm();
            return;
        }

        // Validar que data de desligamento não seja anterior à admissão
        if (!empty($data['data_desligamento'])) {
            $admissao = new \DateTime($data['data_admissao']);
            $desligamento = new \DateTime($data['data_desligamento']);
            if ($desligamento < $admissao) {
                $_SESSION['error'] = 'Data de desligamento não pode ser anterior à data de admissão!';
                $this->showForm();
                return;
            }
        }

        $historyRepo = new EmploymentHistoryRepository();
        
        try {
            $result = $historyRepo->update((int) $this->data['history']['id'], $data);
            
            if ($result) {
                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Histórico atualizado com sucesso!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'view-user/' . $this->data['history']['adms_user_id']);
                exit;
            } else {
                $_SESSION['error'] = 'Erro ao atualizar histórico. Tente novamente.';
                $this->showForm();
            }
        } catch (\Exception $e) {
            GenerateLog::generateLog("error", "Erro ao atualizar histórico de emprego.", [
                'id' => $this->data['history']['id'],
                'error' => $e->getMessage()
            ]);
            $_SESSION['error'] = 'Erro ao atualizar histórico: ' . $e->getMessage();
            $this->showForm();
        }
    }
}

