<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PortariaVisitantesRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaVisitantesCreate
{
    private array $data = [];

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_portaria_visitante', (string) $this->data['form']['csrf_token'])) {
            $nome = trim((string) ($this->data['form']['nome'] ?? ''));
            if ($nome === '') {
                $this->data['errors'] = ['Informe o nome do visitante.'];
            } else {
                $this->data['form']['ativo'] = 1;
                $id = (new PortariaVisitantesRepository())->create($this->data['form']);
                if ($id) {
                    $_SESSION['msg'] = 'Visitante cadastrado com sucesso.';
                    $_SESSION['msg_type'] = 'success';
                    header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-visitantes-view/' . $id);
                    return;
                }
                $this->data['errors'] = ['Não foi possível cadastrar o visitante.'];
            }
        }
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Cadastrar Visitante',
            'menu' => 'portaria-visitantes',
            'buttonPermission' => ['PortariaVisitantes'],
        ]));
        (new LoadViewService('adms/Views/portaria/visitantes/create', $this->data))->loadView();
    }
}
