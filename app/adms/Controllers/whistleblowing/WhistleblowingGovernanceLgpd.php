<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UrlAdmHelper;
use App\adms\Models\Repository\WhistleblowingConfigRepository;
use App\adms\Models\Repository\WhistleblowingMessagesRepository;
use App\adms\Models\Repository\WhistleblowingRetentionRunsRepository;
use App\adms\Models\Services\WhistleblowingChannelSecurityService;
use App\adms\Models\Services\WhistleblowingRetentionService;
use App\adms\Views\Services\LoadViewService;

/**
 * Governança LGPD do Canal de Denúncias — política de retenção e histórico de execuções.
 */
final class WhistleblowingGovernanceLgpd
{
    private array $data = [];

    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->runManual();
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;

        $configRepo = new WhistleblowingConfigRepository();
        $this->data['config'] = [
            'archive_years' => $configRepo->getRetentionArchiveYears(),
            'delete_years' => $configRepo->getRetentionDeleteYears(),
            'cron_enabled' => $configRepo->isCronEnabled(),
            'cron_time' => $configRepo->getCronTime(),
            'cron_line' => $configRepo->buildSuggestedCronLine(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/')),
            'token_configured' => $configRepo->hasHttpCronToken(),
            'key_configured' => WhistleblowingChannelSecurityService::hasStrongEncryptionKey(),
            'rate_limit_max' => $configRepo->getRateLimitMaxAttempts(),
            'rate_limit_window' => $configRepo->getRateLimitWindowMinutes(),
        ];

        $runsRepo = new WhistleblowingRetentionRunsRepository();
        $this->data['runs'] = $runsRepo->listRuns($page, $perPage);
        $this->data['total_runs'] = $runsRepo->countRuns();
        $this->data['last_run'] = $runsRepo->getLastRun();
        $this->data['attachment_stats'] = (new WhistleblowingMessagesRepository())->getAttachmentStorageStats();
        $this->data['page'] = $page;
        $this->data['per_page'] = $perPage;
        $this->data['total_pages'] = max(1, (int) ceil($this->data['total_runs'] / $perPage));
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('whistleblowing_governance');
        $this->data['url_adm'] = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';

        $pageElements = [
            'title_head' => 'Governança LGPD — Canal de Denúncias',
            'menu' => 'whistleblowing-governance',
            'buttonPermission' => ['WhistleblowingGovernanceLgpd'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/whistleblowing/governance', $this->data))->loadView();
    }

    private function runManual(): void
    {
        if (!CSRFHelper::validateCSRFToken('whistleblowing_governance', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . UrlAdmHelper::to('whistleblowing-governance'));
            exit;
        }

        try {
            $result = (new WhistleblowingRetentionService())->run('manual');
            $_SESSION['msg'] = sprintf(
                'Retenção executada: %d arquivada(s), %d excluída(s), %d arquivo(s) removido(s).',
                (int) ($result['archived'] ?? 0),
                (int) ($result['deleted'] ?? 0),
                (int) ($result['attachments_deleted'] ?? 0)
            );
            $_SESSION['msg_type'] = 'success';
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Falha na execução da retenção: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . UrlAdmHelper::to('whistleblowing-governance'));
        exit;
    }
}
