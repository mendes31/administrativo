<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\BranchesRepository;
use App\adms\Models\Repository\TiSistemaRepository;
use App\adms\Views\Services\LoadViewService;

final class TiSistemasUpdate
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $sistemaId = (int) $id;
        $repo = new TiSistemaRepository();
        $sistema = $repo->getById($sistemaId);
        if ($sistema === null) {
            $_SESSION['msg'] = 'Sistema não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas');
            exit;
        }

        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: null;

        if (
            is_array($this->data['form'])
            && isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_ti_sistema', (string) $this->data['form']['csrf_token'])
        ) {
            $this->save($sistemaId);
            return;
        }

        $this->data['form'] = $sistema;
        $this->viewForm($sistemaId);
    }

    private function viewForm(int $sistemaId): void
    {
        $this->data['tipos'] = TiSistemaRepository::TIPOS;
        $this->data['filiais'] = (new BranchesRepository())->getAllBranchesSelect();
        $this->data['sistema_id'] = $sistemaId;
        $pageElements = [
            'title_head' => 'Editar Sistema (TI)',
            'menu' => 'ti-sistemas',
            'buttonPermission' => ['TiSistemas', 'TiSistemasView'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/sistemas/update', $this->data))->loadView();
    }

    private function save(int $sistemaId): void
    {
        $repo = new TiSistemaRepository();
        [$errors, $payload] = $repo->validateAndNormalize($this->data['form'] ?? [], $sistemaId);

        if ($errors !== []) {
            $this->data['errors'] = $errors;
            $this->viewForm($sistemaId);
            return;
        }

        $ok = $repo->update($sistemaId, $payload, (int) ($_SESSION['user_id'] ?? 0));

        if ($ok) {
            $_SESSION['msg'] = 'Sistema atualizado com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas-view/' . $sistemaId);
            return;
        }

        $this->data['errors'] = ['Não foi possível atualizar. Verifique se a tag do equipamento já existe.'];
        $this->viewForm($sistemaId);
    }
}
