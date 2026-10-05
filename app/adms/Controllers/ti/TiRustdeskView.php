<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TiRustdeskRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Models\Services\TiRustdeskSecretService;
use App\adms\Views\Services\LoadViewService;

final class TiRustdeskView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $registroId = (int) $id;
        $registro = (new TiRustdeskRepository())->getById($registroId);
        if ($registro === null) {
            $_SESSION['msg'] = 'Cadastro RustDesk não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-rustdesk');
            exit;
        }

        $this->data['registro'] = $registro;
        $this->data['encryption_ok'] = TiRustdeskSecretService::isConfigured();
        $this->data['csrf_reveal'] = CSRFHelper::generateCSRFToken('form_ti_rustdesk_reveal');
        $returnUrl = ($_ENV['URL_ADM'] ?? '') . 'ti-rustdesk-view/' . $registroId;
        $this->data['log_resumo'] = LogResumoService::getResumo('ti_rustdesk', $registroId, $returnUrl);

        $pageElements = [
            'title_head' => 'RustDesk (TI)',
            'menu' => 'ti-rustdesk',
            'buttonPermission' => ['TiRustdesk', 'TiRustdeskUpdate', 'TiRustdeskReveal'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/rustdesk/view', $this->data))->loadView();
    }
}
