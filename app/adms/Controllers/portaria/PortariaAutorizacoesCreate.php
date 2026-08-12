<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PortariaAutorizacoesRepository;
use App\adms\Models\Repository\PortariaVisitantesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaAutorizacoesCreate
{
    private array $data = [];

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [
            'visitante_id' => (int) ($_GET['visitante_id'] ?? 0),
            'data_inicio' => date('Y-m-d'),
            'data_fim' => date('Y-m-d'),
            'origem' => 'agendada',
        ];
        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_portaria_autorizacao', (string) $this->data['form']['csrf_token'])) {
            $errors = [];
            if ((int) ($this->data['form']['visitante_id'] ?? 0) <= 0) {
                $errors[] = 'Selecione o visitante.';
            }
            if ((int) ($this->data['form']['anfitriao_user_id'] ?? 0) <= 0) {
                $errors[] = 'Selecione o anfitrião.';
            }
            if (empty($this->data['form']['data_inicio']) || empty($this->data['form']['data_fim'])) {
                $errors[] = 'Informe o período da autorização.';
            }
            if ($errors === []) {
                $this->data['form']['criado_por_user_id'] = (int) ($_SESSION['user_id'] ?? 0);
                $id = (new PortariaAutorizacoesRepository())->create($this->data['form']);
                if ($id) {
                    $_SESSION['msg'] = 'Autorização criada com sucesso.';
                    $_SESSION['msg_type'] = 'success';
                    header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-autorizacoes-view/' . $id);
                    return;
                }
                $errors[] = 'Não foi possível criar a autorização.';
            }
            $this->data['errors'] = $errors;
        }
        $this->data['visitantes'] = (new PortariaVisitantesRepository())->getAll(['ativo' => 1]);
        $this->data['usuarios'] = (new UsersRepository())->getAllUsersSelect();
        $this->data['departamentos'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Cadastrar Autorização',
            'menu' => 'portaria-autorizacoes',
            'buttonPermission' => ['PortariaAutorizacoes'],
        ]));
        (new LoadViewService('adms/Views/portaria/autorizacoes/create', $this->data))->loadView();
    }
}
