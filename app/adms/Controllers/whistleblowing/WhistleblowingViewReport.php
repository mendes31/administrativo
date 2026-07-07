<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\WhistleblowingAccessLogRepository;
use App\adms\Models\Repository\WhistleblowingMessagesRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingProtocolService;
use App\adms\Models\Services\WhistleblowingUploadService;
use App\adms\Views\Services\LoadViewService;

/**
 * Visualização interna de denúncia com linha do tempo e mensagens.
 */
class WhistleblowingViewReport
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (is_string($id) && str_starts_with($id, 'download-attachment/')) {
            $parts = explode('/', $id, 2);
            $this->downloadAttachment((int) ($parts[1] ?? 0));
            return;
        }

        if (!$id || !is_numeric($id)) {
            $this->redirectList('ID da denúncia não informado.');
            return;
        }

        $reportId = (int) $id;
        $repo = new WhistleblowingReportsRepository();
        $report = $repo->getReportById($reportId);

        if (!$report) {
            $this->redirectList('Denúncia não encontrada.');
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            (new WhistleblowingAccessLogRepository())->log($reportId, $userId, 'view');
        }

        $messagesRepo = new WhistleblowingMessagesRepository();
        $this->data['report'] = $report;
        $this->data['messages'] = $messagesRepo->getMessagesByReportId($reportId, true);
        $this->data['attachments'] = $messagesRepo->getAttachmentsByReportId($reportId);
        $this->data['status_log'] = $repo->getStatusLog($reportId);
        $this->data['access_log'] = (new WhistleblowingAccessLogRepository())->getByReportId($reportId);
        $this->data['statuses'] = WhistleblowingProtocolService::STATUSES;
        $this->data['risk_levels'] = WhistleblowingProtocolService::RISK_LEVELS;
        $this->data['csrf_reply'] = CSRFHelper::generateCSRFToken('whistleblowing_reply');
        $this->data['csrf_status'] = CSRFHelper::generateCSRFToken('whistleblowing_status');

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

        $pageElements = [
            'title_head' => 'Denúncia ' . ($report['protocol'] ?? '') . ' — Canal de Denúncias',
            'menu' => 'denuncias',
            'buttonPermission' => ['WhistleblowingReplyReport', 'WhistleblowingUpdateStatus'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/whistleblowing/reports/view', $this->data);
        $loadView->loadView();
    }

    public function downloadAttachment(string|int|null $id = null): void
    {
        if (!$id) {
            http_response_code(404);
            exit;
        }

        $attachmentId = (int) $id;
        $messagesRepo = new WhistleblowingMessagesRepository();
        $attachment = $messagesRepo->getAttachmentById($attachmentId);

        if (!$attachment) {
            http_response_code(404);
            exit;
        }

        $reportId = (int) ($attachment['report_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId > 0 && $reportId > 0) {
            (new WhistleblowingAccessLogRepository())->log($reportId, $userId, 'download_attachment');
        }

        $path = WhistleblowingUploadService::getFilePath((string) ($attachment['stored_name'] ?? ''));
        if (!is_readable($path)) {
            http_response_code(404);
            exit;
        }

        $name = (string) ($attachment['original_name'] ?? 'arquivo');
        $mime = (string) ($attachment['mime_type'] ?? 'application/octet-stream');

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    private function redirectList(string $msg): void
    {
        $_SESSION['msg'] = $msg;
        $_SESSION['msg_type'] = 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'denuncias');
        exit;
    }
}
