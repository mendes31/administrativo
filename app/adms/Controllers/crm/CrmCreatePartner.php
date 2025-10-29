<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Models\Repository\CrmCustomFieldsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar Parceiro CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmCreatePartner
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        // Gerar próximo código
        $partnersRepo = new CrmPartnersRepository();
        $this->data['next_code'] = $partnersRepo->getNextPartnerCode();

        // Dados para os selects
        $this->data['segments'] = ['Farma', 'Suplementos', 'Ambos'];
        $this->data['partner_types'] = ['Lead', 'Cliente', 'Prospect'];
        $this->data['priorities'] = ['Baixa', 'Média', 'Alta', 'Urgente'];
        $this->data['type_persons'] = ['PF' => 'Pessoa Física', 'PJ' => 'Pessoa Jurídica'];
        
        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();
        
        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();

        // Carregar campos customizáveis para parceiros
        $customFieldsRepo = new CrmCustomFieldsRepository();
        $this->data['custom_fields'] = $customFieldsRepo->getFieldsByEntity('partner');

        // Layout
        $pageElements = [
            'title_head' => 'Novo Parceiro - CRM',
            'menu' => 'crm-create-partner',
            'buttonPermission' => ['CrmCreatePartner'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/partners/form", $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $data = [
            'code' => $_POST['code'] ?? '',
            'name' => $_POST['name'] ?? '',
            'trading_name' => $_POST['trading_name'] ?? null,
            'type_person' => $_POST['type_person'] ?? 'PJ',
            'document' => $_POST['document'] ?? null,
            'email' => $_POST['email'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'mobile' => $_POST['mobile'] ?? null,
            'website' => $_POST['website'] ?? null,
            'zip_code' => $_POST['zip_code'] ?? null,
            'address' => $_POST['address'] ?? null,
            'number' => $_POST['number'] ?? null,
            'complement' => $_POST['complement'] ?? null,
            'neighborhood' => $_POST['neighborhood'] ?? null,
            'city' => $_POST['city'] ?? null,
            'state' => $_POST['state'] ?? null,
            'segment' => $_POST['segment'] ?? 'Farma',
            'partner_type' => $_POST['partner_type'] ?? 'Lead',
            'source' => $_POST['source'] ?? null,
            'priority' => $_POST['priority'] ?? 'Média',
            'responsible_user_id' => $_POST['responsible_user_id'] ?? null,
            'department_id' => $_POST['department_id'] ?? null,
            'estimated_revenue' => $_POST['estimated_revenue'] ?? 0,
            'notes' => $_POST['notes'] ?? null,
        ];

        // Validações básicas
        if (empty($data['name'])) {
            $_SESSION['msg'] = "O nome é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-partner");
            exit;
        }

        if (empty($data['segment'])) {
            $_SESSION['msg'] = "O segmento é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-partner");
            exit;
        }

        $partnersRepo = new CrmPartnersRepository();
        $partnerId = $partnersRepo->createPartner($data);

        if ($partnerId) {
            // Salvar campos customizáveis
            $customFieldsRepo = new CrmCustomFieldsRepository();
            $customFields = $customFieldsRepo->getFieldsByEntity('partner');
            
            $customFieldValues = [];
            foreach ($customFields as $field) {
                $fieldName = 'custom_field_' . $field['id'];
                if (isset($_POST[$fieldName])) {
                    $customFieldValues[$field['id']] = $_POST[$fieldName];
                }
            }
            
            if (!empty($customFieldValues)) {
                $customFieldsRepo->savePartnerFieldValues($partnerId, $customFieldValues);
            }

            $_SESSION['msg'] = "Parceiro cadastrado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-partners");
        } else {
            $_SESSION['msg'] = "Erro ao cadastrar parceiro.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-partner");
        }
        exit;
    }
}

