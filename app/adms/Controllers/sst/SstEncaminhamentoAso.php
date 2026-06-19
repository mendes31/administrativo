<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Formulário para gerar encaminhamento / autorização de ASO.
 */
class SstEncaminhamentoAso
{
    private array $data = [];

    public function index(): void
    {
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['categorias'] = SstCategoriaAsoHelper::all();
        $this->data['prefill_user_id'] = (int) ($_GET['adms_user_id'] ?? 0);
        $this->data['prefill_categoria'] = trim((string) ($_GET['categoria'] ?? ''));

        $pageElements = [
            'title_head' => 'Encaminhamento ASO - SST',
            'menu' => 'sst-list-asos',
            'buttonPermission' => ['SstEncaminhamentoAso', 'SstExportEncaminhamentoAsoPdf'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/encaminhamento_aso/form', $this->data))->loadView();
    }
}
