<?php

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Models\Repository\SacSlaRulesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SacCreateSlaRule
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        $categoriesRepo = new SacCategoriesRepository();
        $this->data['categories'] = $categoriesRepo->getActiveCategories();

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

        $pageElements = [
            'title_head' => 'Nova Regra de SLA - SAC',
            'menu' => 'sac-list-sla-rules',
            'buttonPermission' => ['SacCreateSlaRule'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/slaRules/form", $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sac_sla_rule_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = "Token de segurança inválido. Tente novamente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-sla-rule");
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
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-sla-rule");
            exit;
        }

        if (empty($data['response_time_hours']) || empty($data['resolution_time_hours'])) {
            $_SESSION['msg'] = "Os tempos de resposta e resolução são obrigatórios.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-sla-rule");
            exit;
        }

        $rulesRepo = new SacSlaRulesRepository();
        $ruleId = $rulesRepo->createRule($data);

        if ($ruleId) {
            $_SESSION['msg'] = "Regra de SLA cadastrada com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-sla-rules");
        } else {
            $_SESSION['msg'] = "Erro ao cadastrar regra de SLA.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-sla-rule");
        }
        exit;
    }
}
