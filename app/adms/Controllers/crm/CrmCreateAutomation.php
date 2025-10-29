<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmAutomationsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Criar Automação do CRM
 * 
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmCreateAutomation
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        // Dados para selects
        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

        $pageElements = [
            'title_head' => 'Nova Automação - CRM',
            'menu' => 'crm-list-automations',
            'buttonPermission' => ['CrmCreateAutomation'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/automations/form", $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? null,
            'entity_type' => $_POST['entity_type'] ?? 'opportunity',
            'trigger_event' => $_POST['trigger_event'] ?? 'created',
            'action_type' => $_POST['action_type'] ?? 'send_notification',
            'priority' => (int)($_POST['priority'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Construir condições
        $conditions = [];
        if (!empty($_POST['condition_field']) && !empty($_POST['condition_value'])) {
            $conditions[$_POST['condition_field']] = [
                'operator' => $_POST['condition_operator'] ?? '=',
                'value' => $_POST['condition_value']
            ];
        }
        $data['trigger_conditions'] = !empty($conditions) ? json_encode($conditions) : null;

        // Construir configuração da ação
        $actionConfig = [];
        switch ($data['action_type']) {
            case 'send_email':
                $actionConfig = [
                    'to' => $_POST['action_email_to'] ?? '',
                    'subject' => $_POST['action_email_subject'] ?? '',
                    'body' => $_POST['action_email_body'] ?? ''
                ];
                break;
            
            case 'send_whatsapp':
                $actionConfig = [
                    'phone' => $_POST['action_whatsapp_phone'] ?? '',
                    'message' => $_POST['action_whatsapp_message'] ?? ''
                ];
                break;
            
            case 'create_activity':
                $actionConfig = [
                    'type' => $_POST['action_activity_type'] ?? 'Tarefa',
                    'title' => $_POST['action_activity_title'] ?? '',
                    'description' => $_POST['action_activity_description'] ?? '',
                    'responsible_user_id' => $_POST['action_activity_responsible'] ?? null,
                    'priority' => $_POST['action_activity_priority'] ?? 'Média'
                ];
                break;
            
            case 'create_note':
                $actionConfig = [
                    'content' => $_POST['action_note_content'] ?? '',
                    'is_important' => isset($_POST['action_note_important']) ? 1 : 0
                ];
                break;
            
            case 'send_notification':
                $actionConfig = [
                    'user_id' => $_POST['action_notification_user'] ?? null,
                    'message' => $_POST['action_notification_message'] ?? ''
                ];
                break;
        }
        $data['action_config'] = !empty($actionConfig) ? json_encode($actionConfig) : null;

        // Validações
        if (empty($data['name'])) {
            $_SESSION['msg'] = "O nome da automação é obrigatório.";
            $_SESSION['msg_type'] = "warning";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-automation");
            exit;
        }

        $repo = new CrmAutomationsRepository();
        $result = $repo->createAutomation($data);

        if ($result) {
            $_SESSION['msg'] = "Automação criada com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-automations");
        } else {
            $_SESSION['msg'] = "Erro ao criar automação.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-automation");
        }
        exit;
    }
}

