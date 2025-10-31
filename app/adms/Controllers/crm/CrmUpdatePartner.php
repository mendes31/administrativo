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
 * Controller para editar Parceiro CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmUpdatePartner
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do parceiro não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-partners");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
            return;
        }

        // Buscar dados do parceiro
        $partnersRepo = new CrmPartnersRepository();
        $this->data['partner'] = $partnersRepo->getPartner((int)$id);

        if (!$this->data['partner']) {
            $_SESSION['msg'] = "Parceiro não encontrado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-partners");
            exit;
        }
        
        // DEBUG: Verificar dados carregados
        error_log("=== DEBUG CrmUpdatePartner ===");
        error_log("Partner ID: " . $id);
        error_log("CEP: " . ($this->data['partner']['zip_code'] ?? 'NULL'));
        error_log("Estado: " . ($this->data['partner']['state'] ?? 'NULL'));
        error_log("Cidade: " . ($this->data['partner']['city'] ?? 'NULL'));
        error_log("Bairro: " . ($this->data['partner']['neighborhood'] ?? 'NULL'));
        error_log("Logradouro: " . ($this->data['partner']['address'] ?? 'NULL'));

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

        // Carregar campos customizáveis e valores
        $customFieldsRepo = new CrmCustomFieldsRepository();
        $this->data['custom_fields'] = $customFieldsRepo->getFieldsByEntity('partner');
        $this->data['custom_field_values'] = $customFieldsRepo->getPartnerFieldValues((int)$id);
        
        // Carregar tags disponíveis e tags do parceiro
        $tagsRepo = new CrmTagsRepository();
        $this->data['tags'] = $tagsRepo->getAllTags();
        $this->data['partner_tags'] = $tagsRepo->getPartnerTags((int)$id);

        // Layout
        $pageElements = [
            'title_head' => 'Editar Parceiro - CRM',
            'menu' => 'crm-list-partners',
            'buttonPermission' => ['CrmUpdatePartner'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/partners/form", $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        // DEBUG: Verificar dados recebidos no POST
        error_log("=== DEBUG UPDATE PARTNER ===");
        error_log("ID: " . $id);
        error_log("POST recebido - responsible_user_id:");
        error_log("  Valor RAW: " . var_export($_POST['responsible_user_id'] ?? 'NOT_SET', true));
        error_log("  empty(): " . (empty($_POST['responsible_user_id']) ? 'true' : 'false'));
        error_log("  is_numeric(): " . (isset($_POST['responsible_user_id']) && is_numeric($_POST['responsible_user_id']) ? 'true' : 'false'));
        error_log("POST recebido - Endereço:");
        error_log("  state: " . ($_POST['state'] ?? 'NULL'));
        error_log("  city: " . ($_POST['city'] ?? 'NULL'));
        error_log("  neighborhood: " . ($_POST['neighborhood'] ?? 'NULL'));
        error_log("  address: " . ($_POST['address'] ?? 'NULL'));
        error_log("  zip_code: " . ($_POST['zip_code'] ?? 'NULL'));
        
        $country = $_POST['country'] ?? 'BR';
        
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
            'id' => $id, // Prioriza ID da URL
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
            'status' => $_POST['status'] ?? 'Ativo',
            'responsible_user_id' => (!empty($_POST['responsible_user_id']) && is_numeric($_POST['responsible_user_id'])) ? (int)$_POST['responsible_user_id'] : null,
            'department_id' => (!empty($_POST['department_id']) && is_numeric($_POST['department_id'])) ? (int)$_POST['department_id'] : null,
            'estimated_revenue' => $_POST['estimated_revenue'] ?? 0,
            'notes' => $_POST['notes'] ?? null,
        ];

        // Validações
        if (empty($data['name']) || empty($data['segment'])) {
            $_SESSION['msg'] = "Campos obrigatórios não preenchidos.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-update-partner/" . $id);
            exit;
        }

        $partnersRepo = new CrmPartnersRepository();
        $result = $partnersRepo->updatePartner($data);

        if ($result) {
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
                $customFieldsRepo->savePartnerFieldValues($id, $customFieldValues);
            }
            
            // Atualizar tags
            $tagsRepo = new CrmTagsRepository();
            
            // Remover todas as tags antigas
            $oldTags = $tagsRepo->getPartnerTags($id);
            foreach ($oldTags as $oldTag) {
                $tagsRepo->removeTagFromPartner($id, $oldTag['id']);
            }
            
            // Adicionar novas tags
            if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
                foreach ($_POST['tags'] as $tagId) {
                    $tagsRepo->attachTagToPartner($id, (int)$tagId);
                }
            }

            $_SESSION['msg'] = "Parceiro atualizado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-partner/" . $data['id']);
        } else {
            $_SESSION['msg'] = "Erro ao atualizar parceiro.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-update-partner/" . $data['id']);
        }
        exit;
    }
}

