<?php

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Models\Repository\SacSlaRulesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SacUpdateSlaRule
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da regra de SLA não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-sla-rules");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
            return;
        }

        $rulesRepo = new SacSlaRulesRepository();
        $this->data['rule'] = $rulesRepo->getRuleById((int)$id);

        if (!$this->data['rule']) {
            $_SESSION['msg'] = "Regra de SLA não encontrada.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-sla-rules");
            exit;
        }

        $categoriesRepo = new SacCategoriesRepository();
        $this->data['categories'] = $categoriesRepo->getActiveCategories();

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

        $pageElements = [
            'title_head' => 'Editar Regra de SLA - SAC',
            'menu' => 'sac-list-sla-rules',
            'buttonPermission' => ['SacUpdateSlaRule'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/slaRules/form", $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('sac_sla_rule_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = "Token de segurança inválido. Tente novamente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-update-sla-rule/" . $id);
            exit;
        }

        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'priority' => $_POST['priority'] ?? null,
            'response_time_hours' => $_POST['response_time_hours'] ?? '',
            'resolution_time_hours' => $_POST['resolution_time_hours'] ?? '',
            'escalation_enabled' => isset($_POST['escalation_enabled']) ? (int)$_POST['escalation_enabled'] : 0,
            'escalation_after_hours' => !empty($_POST['escalation_after_hours']) ? (int)$_POST['escalation_after_hours'] : null,
            'escalation_user_id' => !empty($_POST['escalation_user_id']) ? (int)$_POST['escalation_user_id'] : null,
            'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
        ];

        if (empty($data['name'])) {
            $_SESSION['msg'] = "O nome da regra é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-update-sla-rule/" . $id);
            exit;
        }

        if (empty($data['response_time_hours']) || empty($data['resolution_time_hours'])) {
            $_SESSION['msg'] = "Os tempos de resposta e resolução são obrigatórios.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-update-sla-rule/" . $id);
            exit;
        }

        $rulesRepo = new SacSlaRulesRepository();
        $result = $rulesRepo->updateRule($id, $data);

        if ($result) {
            $_SESSION['msg'] = "Regra de SLA atualizada com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-sla-rules");
        } else {
            $_SESSION['msg'] = "Erro ao atualizar regra de SLA.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-update-sla-rule/" . $id);
        }
        exit;
    }
}
