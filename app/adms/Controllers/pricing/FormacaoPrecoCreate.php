<?php

declare(strict_types=1);

namespace App\adms\Controllers\pricing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\FormacaoPrecoRepository;
use App\adms\Views\Services\LoadViewService;

final class FormacaoPrecoCreate
{
    private array $data = [];

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];

        if (
            isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_formacao_preco', (string) $this->data['form']['csrf_token'])
        ) {
            $this->save();
            return;
        }

        if (($this->data['form']['icms_padrao'] ?? '') === '') {
            $this->data['form']['icms_padrao'] = '12,00';
            $this->data['form']['pis_padrao'] = '1,65';
            $this->data['form']['cofins_padrao'] = '7,60';
        }

        $this->viewForm();
    }

    private function viewForm(): void
    {
        $pageElements = [
            'title_head' => 'Nova formação de preço',
            'menu' => 'formacao-preco',
            'buttonPermission' => ['FormacaoPreco'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/pricing/create', $this->data))->loadView();
    }

    private function save(): void
    {
        $repo = new FormacaoPrecoRepository();
        [$errors, $payload] = $repo->validateHeader($this->data['form'] ?? []);
        if ($errors !== []) {
            $this->data['errors'] = $errors;
            $this->viewForm();
            return;
        }

        $id = $repo->create($payload, (int) ($_SESSION['user_id'] ?? 0));
        if ($id) {
            $_SESSION['msg'] = 'Precificação cadastrada. Inclua os produtos e as metas.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'formacao-preco-update/' . $id);
            return;
        }

        $this->data['errors'] = ['Não foi possível cadastrar a precificação.'];
        $this->viewForm();
    }
}
