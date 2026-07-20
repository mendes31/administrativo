<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Views\Services\LoadViewService;

class CreatePolicyCategory
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (
            isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_create_policy_category', $this->data['form']['csrf_token'])
        ) {
            $this->save();
            return;
        }

        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head'       => 'Cadastrar Categoria de Política',
            'menu' => 'list-policy-categories',
            'buttonPermission' => ['ListPolicyCategories'],
        ];

        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/categories/create', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        $name = trim($form['name'] ?? '');
        $ativo = !empty($form['ativo']);

        if ($name === '') {
            $_SESSION['msg'] = "<div class='alert alert-danger'>O nome da categoria é obrigatório.</div>";
            $this->view();
            return;
        }

        $repo = new PoliciesRepository();

        try {
            $id = $repo->createCategoria($name, $ativo);

            if ($id) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 0);
                LogAlteracaoService::registrarAlteracao(
                    'adms_policies_categorias',
                    (int) $id,
                    $usuarioId,
                    'insert',
                    [],
                    ['id' => (int) $id, 'name' => $name, 'ativo' => $ativo ? 1 : 0]
                );

                $_SESSION['msg'] = "<div class='alert alert-success'>Categoria de política criada com sucesso.</div>";
                header('Location: ' . $_ENV['URL_ADM'] . 'list-policy-categories');
                exit;
            }

            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao criar categoria de política.</div>";
        } catch (\Throwable $e) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao criar categoria de política: " . $e->getMessage() . "</div>";
        }

        $this->view();
    }
}

