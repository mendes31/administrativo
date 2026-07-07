<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\WhistleblowingAccessLogRepository;
use App\adms\Models\Repository\WhistleblowingMessagesRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingUploadService;

/**
 * Resposta do comitê ao denunciante (ou nota interna).
 */
class WhistleblowingReplyReport
{
    public function index(string|int|null $id = null): void
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect((int) ($id ?? 0), 'Requisição inválida.');
            return;
        }

        $reportId = (int) $id;

        if (!CSRFHelper::validateCSRFToken('whistleblowing_reply', $_POST['csrf_token'] ?? '')) {
            $this->redirect($reportId, 'Token CSRF inválido.');
            return;
        }

        $message = trim((string) ($_POST['message'] ?? ''));
        $isInternal = isset($_POST['is_internal_note']);
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if ($message === '') {
            $this->redirect($reportId, 'A mensagem é obrigatória.');
            return;
        }

        $repo = new WhistleblowingReportsRepository();
        $report = $repo->getReportById($reportId);
        if (!$report) {
            $this->redirect(0, 'Denúncia não encontrada.');
            return;
        }

        $messagesRepo = new WhistleblowingMessagesRepository();
        $messageId = $messagesRepo->createMessage($reportId, $message, 'comite', $userId > 0 ? $userId : null, $isInternal);

        if (!$messageId) {
            $this->redirect($reportId, 'Erro ao enviar mensagem.');
            return;
        }

        $uploadService = new WhistleblowingUploadService();
        $uploads = $uploadService->handleMultiple($_FILES['attachments'] ?? null);
        foreach ($uploads as $upload) {
            $messagesRepo->createAttachment(
                $reportId,
                $messageId,
                $upload['stored_name'],
                $upload['original_name'],
                $upload['mime_type'],
                $upload['size_bytes'],
                'comite'
            );
        }

        if (!$isInternal && empty($report['first_response_at'])) {
            $repo->updateReport($reportId, ['first_response_at' => date('Y-m-d H:i:s')]);
        }

        if ($userId > 0) {
            (new WhistleblowingAccessLogRepository())->log($reportId, $userId, $isInternal ? 'internal_note' : 'reply');
        }

        $_SESSION['msg'] = $isInternal ? 'Nota interna registrada.' : 'Resposta enviada ao denunciante.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $_ENV['URL_ADM'] . 'view-denuncia/' . $reportId);
        exit;
    }

    private function redirect(int $reportId, string $msg): void
    {
        $_SESSION['msg'] = $msg;
        $_SESSION['msg_type'] = 'danger';
        if ($reportId > 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'view-denuncia/' . $reportId);
        } else {
            header('Location: ' . $_ENV['URL_ADM'] . 'denuncias');
        }
        exit;
    }
}
