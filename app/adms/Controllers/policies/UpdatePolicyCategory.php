<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Views\Services\LoadViewService;

class UpdatePolicyCategory
{
    private array|string|null $data = null;

    public function index(string $id = ''): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        $repo = new PoliciesRepository();
        $categoryId = (int) ($id ?: ($_GET['id'] ?? 0));

        if ($categoryId <= 0) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>ID inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-policy-categories');
            return;
        }

        if (
            isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_update_policy_category', $this->data['form']['csrf_token'])
        ) {
            $this->save($categoryId, $repo);
            return;
        }

        $this->data['category'] = $repo->getCategoriaById($categoryId);
        if (!$this->data['category']) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Categoria não encontrada.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-policy-categories');
            return;
        }

        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head'       => 'Editar Categoria de Política',
            'menu' => 'list-policy-categories',
            'buttonPermission' => ['ListPolicyCategories'],
        ];

        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/categories/update', $this->data);
        $loadView->loadView();
    }

    private function save(int $id, PoliciesRepository $repo): void
    {
        $form = $this->data['form'] ?? [];
        $name = trim($form['name'] ?? '');
        $ativo = !empty($form['ativo']);

        if ($name === '') {
            $_SESSION['msg'] = "<div class='alert alert-danger'>O nome da categoria é obrigatório.</div>";
            $this->view();
            return;
        }

        $old = $repo->getCategoriaById($id) ?? [];

        try {
            $ok = $repo->updateCategoria($id, $name, $ativo);
            if ($ok) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 0);
                LogAlteracaoService::registrarAlteracao(
                    'adms_policies_categorias',
                    $id,
                    $usuarioId,
                    'update',
                    $old,
                    ['id' => $id, 'name' => $name, 'ativo' => $ativo ? 1 : 0]
                );

                $_SESSION['msg'] = "<div class='alert alert-success'>Categoria de política atualizada com sucesso.</div>";
                header('Location: ' . $_ENV['URL_ADM'] . 'list-policy-categories');
                exit;
            }

            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao atualizar categoria de política.</div>";
        } catch (\Throwable $e) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao atualizar categoria de política: " . $e->getMessage() . "</div>";
        }

        $this->view();
    }
}

