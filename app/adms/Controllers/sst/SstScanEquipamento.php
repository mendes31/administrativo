<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEquipamentoSettingsRepository;
use App\adms\Models\Services\SstEquipamentoQrScanService;
use App\adms\Views\Services\LoadViewService;

/**
 * Sem token: abre câmera para ler QR dentro do app.
 * Com token: exibe dados do equipamento e vistoria aberta para iniciar.
 */
class SstScanEquipamento
{
    private array $data = [];

    public function index(string $token = ''): void
    {
        $token = trim($token);
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if ($userId <= 0) {
            $_SESSION['error'] = 'Faça login para consultar o equipamento.';
            $return = \App\adms\Helpers\ReturnUrlHelper::sanitize(
                rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/')
                . '/sst-scan-equipamento'
                . ($token !== '' ? '/' . rawurlencode($token) : '')
            );
            if ($return !== null) {
                $_SESSION['return_url'] = $return;
            }
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        if ($token === '') {
            $this->showScanner();
            return;
        }

        $context = (new SstEquipamentoQrScanService())->getScanContext($token, $userId);

        if (!$context['ok']) {
            $_SESSION['msg'] = $context['message'];
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-scan-equipamento');
            exit;
        }

        $this->showScanResult($context, $token);
    }

    /** @param array<string, mixed> $context */
    private function showScanResult(array $context, string $token): void
    {
        $pageElements = [
            'title_head' => 'Equipamento identificado - SST',
            'menu' => 'sst-minhas-equipamento-vistorias',
            'buttonPermission' => [
                'SstScanEquipamento',
                'SstExecuteEquipamentoVistoria',
                'SstMinhasEquipamentoVistorias',
                'SstViewEquipamento',
                'SstGenerateEquipamentoVistoria',
                'SstRegisterEquipamentoRecarga',
            ],
        ];
        $this->data['scan_context'] = $context;
        $this->data['scan_token'] = $token;
        $this->data['settings'] = (new SstEquipamentoSettingsRepository())->get();
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/scan_result', $this->data))->loadView();
    }

    private function showScanner(): void
    {
        $pageElements = [
            'title_head' => 'Ler QR equipamento - SST',
            'menu' => 'sst-minhas-equipamento-vistorias',
            'buttonPermission' => ['SstScanEquipamento', 'SstExecuteEquipamentoVistoria', 'SstMinhasEquipamentoVistorias'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/scan_qr', $this->data))->loadView();
    }
}

