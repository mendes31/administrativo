<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\WhistleblowingAccessLogRepository;
use App\adms\Models\Repository\WhistleblowingMessagesRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingAttachmentFilenameHelper;
use App\adms\Models\Services\WhistleblowingPermissionService;
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

        if (!WhistleblowingPermissionService::canAccessReport($report)) {
            $this->redirectList('Você não tem permissão para visualizar esta denúncia.');
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId > 0 && $this->isNewReportVisit($reportId)) {
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
        $this->data['closure_outcomes'] = WhistleblowingProtocolService::CLOSURE_OUTCOMES;
        $this->data['csrf_reply'] = CSRFHelper::generateCSRFToken('whistleblowing_reply');
        $this->data['csrf_status'] = CSRFHelper::generateCSRFToken('whistleblowing_status');
        $this->data['status_form'] = is_array($_SESSION['whistleblowing_status_form'] ?? null)
            ? $_SESSION['whistleblowing_status_form']
            : [];
        unset($_SESSION['whistleblowing_status_form']);

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

        $pageElements = [
            'title_head' => 'Denúncia ' . ($report['protocol'] ?? '') . ' — Canal de Denúncias',
            'menu' => 'denuncias',
            'buttonPermission' => ['WhistleblowingReplyReport', 'WhistleblowingUpdateStatus', 'WhistleblowingExportAccessLog'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/whistleblowing/reports/view', $this->data);
        $loadView->loadView();
    }

    /**
     * Uma visita começa ao entrar vindo de outra tela (ou por acesso direto).
     * F5 e redirecionamentos das ações da própria denúncia não geram novo "view".
     */
    private function isNewReportVisit(int $reportId): bool
    {
        if (!empty($_SESSION['whistleblowing_skip_next_view'][$reportId])) {
            unset($_SESSION['whistleblowing_skip_next_view'][$reportId]);
            return false;
        }

        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer === '') {
            $key = 'whistleblowing_direct_visit_' . $reportId;
            $lastDirectVisit = (int) ($_SESSION[$key] ?? 0);
            $_SESSION[$key] = time();

            return $lastDirectVisit === 0 || (time() - $lastDirectVisit) > 1800;
        }

        $path = trim((string) parse_url($referer, PHP_URL_PATH), '/');
        $currentSuffix = 'view-denuncia/' . $reportId;

        return !str_ends_with($path, $currentSuffix);
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
        if ($userId <= 0) {
            http_response_code(403);
            exit;
        }

        if ($reportId > 0) {
            $report = (new WhistleblowingReportsRepository())->getReportById($reportId);
            if (!$report || !WhistleblowingPermissionService::canAccessReport($report)) {
                http_response_code(403);
                exit;
            }
            (new WhistleblowingAccessLogRepository())->log($reportId, $userId, 'download_attachment');
        }

        $uploadService = new WhistleblowingUploadService();
        $storedName = (string) ($attachment['stored_name'] ?? '');
        $path = WhistleblowingUploadService::getFilePath($storedName);

        if (!is_readable($path)) {
            $this->redirectReport($reportId, 'Arquivo não encontrado no servidor.', 'danger');
            return;
        }

        $contents = $uploadService->readFileContents($storedName);
        if ($contents === null) {
            if (str_ends_with(strtolower($storedName), '.enc')) {
                $this->redirectReport(
                    $reportId,
                    'Não foi possível descriptografar o anexo. Se a chave de criptografia foi alterada após o envio, restaure a chave original em Configuração ou solicite novo envio do arquivo.',
                    'warning'
                );
                return;
            }

            $this->redirectReport($reportId, 'Não foi possível ler o anexo.', 'danger');
            return;
        }

        $name = WhistleblowingAttachmentFilenameHelper::resolve($attachment);
        $mime = (string) ($attachment['mime_type'] ?? 'application/octet-stream');
        if ($mime === '' || $mime === 'application/octet-stream') {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mimeMap = [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'mp4' => 'video/mp4',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];
            $mime = $mimeMap[$ext] ?? $mime;
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . WhistleblowingAttachmentFilenameHelper::contentDispositionHeader($name));
        header('Content-Length: ' . (string) strlen($contents));
        echo $contents;
        exit;
    }

    private function redirectList(string $msg): void
    {
        $_SESSION['msg'] = $msg;
        $_SESSION['msg_type'] = 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'denuncias');
        exit;
    }

    private function redirectReport(int $reportId, string $msg, string $type = 'danger'): void
    {
        $_SESSION['msg'] = $msg;
        $_SESSION['msg_type'] = $type;
        header('Location: ' . $_ENV['URL_ADM'] . 'view-denuncia/' . $reportId);
        exit;
    }
}
