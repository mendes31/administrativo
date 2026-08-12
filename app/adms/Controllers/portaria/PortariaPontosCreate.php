<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\BranchesRepository;
use App\adms\Models\Repository\PortariaPontosRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaPontosCreate
{
    private array $data = [];

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_portaria_ponto', (string) $this->data['form']['csrf_token'])) {
            if (trim((string) ($this->data['form']['nome'] ?? '')) === '') {
                $this->data['errors'] = ['Informe o nome do ponto de controle.'];
            } else {
                $this->data['form']['ativo'] = 1;
                if ((new PortariaPontosRepository())->create($this->data['form'])) {
                    $_SESSION['msg'] = 'Ponto de controle cadastrado.';
                    $_SESSION['msg_type'] = 'success';
                    header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-pontos');
                    return;
                }
                $this->data['errors'] = ['Não foi possível cadastrar. Verifique se o código já existe.'];
            }
        }
        $this->data['filiais'] = (new BranchesRepository())->getAllBranchesSelect();
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Cadastrar Ponto de Controle',
            'menu' => 'portaria-pontos',
            'buttonPermission' => ['PortariaPontos'],
        ]));
        (new LoadViewService('adms/Views/portaria/pontos/create', $this->data))->loadView();
    }
}
