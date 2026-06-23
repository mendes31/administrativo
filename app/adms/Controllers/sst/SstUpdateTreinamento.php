<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstTreinamentoCatalogHelper;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Views\Services\LoadViewService;

class SstUpdateTreinamento
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int) $id);
            return;
        }
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamentos');
            exit;
        }
        $repo = new SstTreinamentosRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamentos');
            exit;
        }
        $this->data['entity'] = $this->entityConfig();
        $pageElements = [
            'title_head' => 'Editar Treinamento SST',
            'menu' => 'sst-list-treinamentos',
            'buttonPermission' => ['SstUpdateTreinamento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/treinamentos/form', $this->data))->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_treinamentos_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-treinamento/' . $id);
            exit;
        }
        $repo = new SstTreinamentosRepository();
        $data = SstTreinamentoCatalogHelper::parseFormData($_POST);
        $error = SstTreinamentoCatalogHelper::validate($data, $repo, $id);
        if ($error !== null) {
            $_SESSION['msg'] = $error;
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-treinamento/' . $id);
            exit;
        }
        if ($repo->update($id, $data)) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-treinamento/' . $id);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-treinamento/' . $id);
        }
        exit;
    }

    /** @return array<string, mixed> */
    private function entityConfig(): array
    {
        $cfg = require dirname(__DIR__, 4) . '/scripts/sst_entities_config.php';

        return $cfg['treinamentos'];
    }
}
