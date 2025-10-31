<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Models\Repository\CrmCustomFieldsRepository;
use App\adms\Models\Repository\CrmTagsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Helpers\CountryHelper;
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
        
        // Filtrar apenas usuários do departamento comercial (respeitando hierarquia)
        $permissionService = new \App\adms\Models\Services\CrmPermissionService();
        $this->data['users'] = $permissionService::getCommercialDepartmentUsers();
        
        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();

        // Carregar campos customizáveis para parceiros
        $customFieldsRepo = new CrmCustomFieldsRepository();
        $this->data['custom_fields'] = $customFieldsRepo->getFieldsByEntity('partner');
        
        // Carregar tags disponíveis
        $tagsRepo = new CrmTagsRepository();
        $this->data['tags'] = $tagsRepo->getAllTags();

        // Layout
        $pageElements = [
            'title_head' => 'Novo Parceiro - CRM',
            'menu' => 'crm-list-partners', // Manter "Listar Parceiros" selecionado
            'buttonPermission' => ['CrmCreatePartner'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/partners/form", $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $country = $_POST['country'] ?? 'BR';
        $ddi = CountryHelper::getDDI($country);
        
        // Concatenar DDI com telefone/celular
        $phone = $_POST['phone'] ?? null;
        $mobile = $_POST['mobile'] ?? null;
        
        if ($phone) {
            $phone = CountryHelper::formatPhoneWithDDI($phone, $country);
        }
        
        if ($mobile) {
            $mobile = CountryHelper::formatPhoneWithDDI($mobile, $country);
        }
        
        $data = [
            'code' => $_POST['code'] ?? '',
            'name' => $_POST['name'] ?? '',
            'trading_name' => $_POST['trading_name'] ?? null,
            'type_person' => $_POST['type_person'] ?? 'PJ',
            'document' => $_POST['document'] ?? null,
            'email' => $_POST['email'] ?? null,
            'phone' => $phone,
            'mobile' => $mobile,
            'website' => $_POST['website'] ?? null,
            'zip_code' => $_POST['zip_code'] ?? null,
            'address' => $_POST['address'] ?? null,
            'number' => $_POST['number'] ?? null,
            'complement' => $_POST['complement'] ?? null,
            'neighborhood' => $_POST['neighborhood'] ?? null,
            'city' => $_POST['city'] ?? null,
            'state' => $_POST['state'] ?? null,
            'country' => $country,
            'segment' => $_POST['segment'] ?? 'Farma',
            'partner_type' => $_POST['partner_type'] ?? 'Lead',
            'source' => $_POST['source'] ?? null,
            'priority' => $_POST['priority'] ?? 'Média',
            'responsible_user_id' => (!empty($_POST['responsible_user_id']) && is_numeric($_POST['responsible_user_id'])) ? (int)$_POST['responsible_user_id'] : null,
            'department_id' => (!empty($_POST['department_id']) && is_numeric($_POST['department_id'])) ? (int)$_POST['department_id'] : null,
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
                
                // Tratar checkbox (array) e outros tipos
                if (isset($_POST[$fieldName])) {
                    if (is_array($_POST[$fieldName])) {
                        // Checkbox: converter array para string separada por vírgulas
                        $customFieldValues[$field['id']] = implode(',', $_POST[$fieldName]);
                    } else {
                        // Outros tipos: usar valor direto
                        $customFieldValues[$field['id']] = $_POST[$fieldName];
                    }
                } elseif ($field['field_type'] === 'checkbox') {
                    // Checkbox não marcado: salvar vazio
                    $customFieldValues[$field['id']] = '';
                }
            }
            
            if (!empty($customFieldValues)) {
                $customFieldsRepo->savePartnerFieldValues($partnerId, $customFieldValues);
            }
            
            // Salvar tags
            if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
                $tagsRepo = new CrmTagsRepository();
                foreach ($_POST['tags'] as $tagId) {
                    $tagsRepo->attachTagToPartner($partnerId, (int)$tagId);
                }
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

