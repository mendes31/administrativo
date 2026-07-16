<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\WhistleblowingAccessLogRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingNotificationService;
use App\adms\Models\Services\WhistleblowingPermissionService;
use App\adms\Models\Services\WhistleblowingProtocolService;

/**
 * Atualização de status e responsável da denúncia.
 */
class WhistleblowingUpdateStatus
{
    public function index(string|int|null $id = null): void
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect((int) ($id ?? 0), 'Requisição inválida.');
            return;
        }

        $reportId = (int) $id;

        if (!CSRFHelper::validateCSRFToken('whistleblowing_status', $_POST['csrf_token'] ?? '')) {
            $this->redirect($reportId, 'Token CSRF inválido.');
            return;
        }

        $repo = new WhistleblowingReportsRepository();
        $report = $repo->getReportById($reportId);
        if (!$report) {
            $this->redirect(0, 'Denúncia não encontrada.');
            return;
        }

        if (!WhistleblowingPermissionService::canAccessReport($report)) {
            $this->redirect($reportId, 'Você não tem permissão para alterar esta denúncia.');
            return;
        }

        $newStatus = trim((string) ($_POST['status'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $assignedUserId = !empty($_POST['assigned_user_id']) ? (int) $_POST['assigned_user_id'] : null;
        $riskLevel = trim((string) ($_POST['risk_level'] ?? ''));
        $closureOutcome = trim((string) ($_POST['closure_outcome'] ?? ''));
        $closureReason = trim((string) ($_POST['closure_reason'] ?? ''));

        if ($newStatus === 'Encerrada') {
            if (!in_array($closureOutcome, WhistleblowingProtocolService::CLOSURE_OUTCOMES, true)) {
                $this->redirect($reportId, 'Selecione o resultado do encerramento.');
                return;
            }
            if ($closureReason === '') {
                $this->redirect($reportId, 'Informe o motivo do encerramento.');
                return;
            }
        }

        $updateData = [];
        $shouldScheduleRetention = false;
        $closedAtForRetention = null;
        $shouldClearRetention = false;
        $oldStatus = (string) ($report['status'] ?? '');
        $oldRisk = (string) ($report['risk_level'] ?? '');

        if (in_array($newStatus, WhistleblowingProtocolService::STATUSES, true) && $newStatus !== $oldStatus) {
            $updateData['status'] = $newStatus;
            if ($newStatus === 'Encerrada') {
                $closedAtForRetention = date('Y-m-d H:i:s');
                $updateData['closed_at'] = $closedAtForRetention;
                $shouldScheduleRetention = true;
            } elseif ($oldStatus === 'Encerrada') {
                $shouldClearRetention = true;
            }
        }
        if ($assignedUserId !== null) {
            $updateData['assigned_user_id'] = $assignedUserId > 0 ? $assignedUserId : null;
        }
        if (in_array($riskLevel, WhistleblowingProtocolService::RISK_LEVELS, true)) {
            $updateData['risk_level'] = $riskLevel;
        }

        if ($updateData !== []) {
            $repo->updateReport($reportId, $updateData);
            if ($shouldScheduleRetention && $closedAtForRetention !== null) {
                $repo->scheduleRetentionFromClosure($reportId, $closedAtForRetention);
            } elseif ($shouldClearRetention) {
                $repo->clearRetentionSchedule($reportId);
            }
        }

        if ($newStatus === 'Encerrada') {
            $repo->saveClosure($reportId, $closureOutcome, $closureReason);
        }

        $effectiveRisk = $updateData['risk_level'] ?? $oldRisk;
        if (isset($updateData['risk_level']) && $effectiveRisk !== $oldRisk && ($updateData['status'] ?? $oldStatus) !== 'Encerrada') {
            $repo->recalculateSlaDeadline(
                $reportId,
                (string) ($report['category'] ?? ''),
                $effectiveRisk,
                (string) ($report['created_at'] ?? date('Y-m-d H:i:s'))
            );
        }

        if (isset($updateData['status'])) {
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $repo->logStatusChange(
                $reportId,
                $oldStatus !== '' ? $oldStatus : null,
                $updateData['status'],
                $userId > 0 ? $userId : null,
                $notes !== '' ? $notes : null
            );
            if ($userId > 0) {
                (new WhistleblowingAccessLogRepository())->log($reportId, $userId, 'status_change');
            }

            (new WhistleblowingNotificationService())->notifyStatusChange(
                $reportId,
                (string) ($report['protocol'] ?? ''),
                isset($report['committee_id']) ? (int) $report['committee_id'] : null,
                $oldStatus,
                $updateData['status']
            );
        }

        $_SESSION['msg'] = 'Denúncia atualizada com sucesso.';
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
