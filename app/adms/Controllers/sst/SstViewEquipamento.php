<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEquipamentoSettingsRepository;
use App\adms\Models\Repository\SstEquipamentoRecargasRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Views\Services\LoadViewService;

class SstViewEquipamento
{
    private array $data = [];

    public function index(string|int $id = 0): void
    {
        $id = (int) $id;
        $repo = new SstEquipamentosRepository();
        $item = $repo->getById($id);
        if (!$item) {
            $_SESSION['msg'] = 'Equipamento não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }
        $this->data['item'] = $item;
        $this->data['qr_token'] = $repo->ensureQrToken($id);
        $this->data['qr_scan_url'] = \App\adms\Helpers\SstEquipamentoQrHelper::buildScanUrl((string) $this->data['qr_token']);
        $this->data['historico'] = $repo->getVistoriaHistorico($id);
        $this->data['historico_recargas'] = (new SstEquipamentoRecargasRepository())->listByEquipamento($id);
        $this->data['settings'] = (new SstEquipamentoSettingsRepository())->get();
        $pageElements = [
            'title_head' => 'Equipamento ' . ($item['codigo'] ?? '') . ' - SST',
            'menu' => 'sst-list-equipamentos',
            'buttonPermission' => [
                'SstViewEquipamento', 'SstUpdateEquipamento', 'SstDeleteEquipamento',
                'SstExecuteEquipamentoVistoria', 'SstGenerateEquipamentoVistoria',
                'SstExportEquipamentoQr', 'SstScanEquipamento',
                'SstRegisterEquipamentoRecarga', 'SstExportEquipamentoAuditoriaPdf',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/view', $this->data))->loadView();
    }
}
