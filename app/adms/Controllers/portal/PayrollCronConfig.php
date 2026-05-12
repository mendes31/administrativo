<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UrlAdmHelper;
use App\adms\Models\Repository\PayrollCronConfigRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Configuração do token HTTP do cron de lembretes (sem variáveis .env).
 */
class PayrollCronConfig
{
    private array $data = [];

    public function index(string|null $param = null): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->save();
            return;
        }

        $repo = new PayrollCronConfigRepository();
        $this->data['cron_row'] = $repo->getRow();
        $this->data['token_configured'] = $repo->hasHttpCronToken();
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('form_payroll_cron_config');
        $cronId = (int) ($this->data['cron_row']['id'] ?? 0);
        if ($cronId > 0) {
            $returnUrl = UrlAdmHelper::to('payroll-cron-config');
            $this->data['log_resumo'] = LogResumoService::getResumo('adms_payroll_cron_config', $cronId, $returnUrl);
        }

        $pageElements = [
            'title_head' => 'Cron — lembretes de folha (RH)',
            'menu' => 'payroll-cron-config',
            'buttonPermission' => ['PayrollCronConfig'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/portal/payroll_cron_config', $this->data))->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_payroll_cron_config', (string)($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Token de segurança inválido. Atualize a página.</div>';
            header('Location: ' . UrlAdmHelper::to('payroll-cron-config'));
            exit;
        }

        $repo = new PayrollCronConfigRepository();
        if (!empty($_POST['clear_token'])) {
            $repo->saveHttpCronToken('');
            $_SESSION['msg'] = '<div class="alert alert-success">Token removido. O endpoint HTTP do cron ficou desativado até definir um novo token.</div>';
            header('Location: ' . UrlAdmHelper::to('payroll-cron-config'));
            exit;
        }

        $new = trim((string)($_POST['http_cron_token'] ?? ''));
        if ($new === '') {
            $_SESSION['msg'] = '<div class="alert alert-info">Nenhuma alteração: informe um novo token ou use &quot;Limpar token&quot;.</div>';
            header('Location: ' . UrlAdmHelper::to('payroll-cron-config'));
            exit;
        }

        if (strlen($new) < 16) {
            $_SESSION['msg'] = '<div class="alert alert-warning">Use um token com pelo menos 16 caracteres (recomendado: 32+ aleatórios).</div>';
            header('Location: ' . UrlAdmHelper::to('payroll-cron-config'));
            exit;
        }

        $repo->saveHttpCronToken($new);
        $_SESSION['msg'] = '<div class="alert alert-success">Token guardado. Atualize o agendador (wget/curl) com o novo valor em <code>?token=</code>.</div>';
        header('Location: ' . UrlAdmHelper::to('payroll-cron-config'));
        exit;
    }
}
