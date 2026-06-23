<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstTreinamentoCatalogHelper;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Views\Services\LoadViewService;

class SstCreateTreinamento
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $repo = new SstTreinamentosRepository();
        $this->data['item'] = ['codigo' => $repo->getProximoCodigo()];
        $this->data['entity'] = $this->entityConfig();
        $pageElements = [
            'title_head' => 'Novo Treinamento SST',
            'menu' => 'sst-list-treinamentos',
            'buttonPermission' => ['SstCreateTreinamento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/treinamentos/form', $this->data))->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_treinamentos_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamentos');
            exit;
        }
        $repo = new SstTreinamentosRepository();
        $data = SstTreinamentoCatalogHelper::parseFormData($_POST);
        if (empty($data['codigo'])) {
            $data['codigo'] = $repo->getProximoCodigo();
        }
        $error = SstTreinamentoCatalogHelper::validate($data, $repo);
        if ($error !== null) {
            $_SESSION['msg'] = $error;
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-treinamento');
            exit;
        }
        $newId = $repo->create($data);
        if ($newId) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-treinamento/' . $newId);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-treinamento');
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
