<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Repository\EmploymentHistoryRepository;

class UserOffboardingNotificationService
{
    public function sendInactivationRequestOnce(
        int $historyId,
        int $userId,
        string $userName,
        ?string $actorEmail = null,
        ?string $actorName = null
    ): void
    {
        if ($historyId <= 0 || $userId <= 0) {
            return;
        }

        $historyRepo = new EmploymentHistoryRepository();
        $history = $historyRepo->getById($historyId);
        if (!$history) {
            return;
        }

        if (!empty($history['inactivation_email_sent_at'])) {
            return;
        }

        $subject = 'Inativar usuário';
        $safeName = trim($userName) !== '' ? trim($userName) : ('ID ' . $userId);
        $bodyText = 'Favor inativar o seguinte usuário ' . $safeName . ' em todos os sistemas aos quais ele possui acesso.'
            . "\n"
            . 'Caso o usuário já esteja inativo, favor desconsiderar esta solicitação.';

        $bodyHtml = nl2br(htmlspecialchars($bodyText, ENT_QUOTES, 'UTF-8'));

        $ok = SendEmailService::sendEmail(
            'chamados@tiaraju.com.br',
            'Chamados TI',
            $subject,
            $bodyHtml,
            $bodyText,
            $actorEmail,
            $actorName,
            $actorName,
            $actorEmail,
            true
        );

        if ($ok) {
            call_user_func([$historyRepo, 'markInactivationEmailSent'], $historyId);
            GenerateLog::generateLog('info', 'E-mail de inativação de acessos enviado.', [
                'history_id' => $historyId,
                'user_id' => $userId,
                'user_name' => $safeName,
            ]);
            return;
        }

        call_user_func([$historyRepo, 'markInactivationEmailFailed'], $historyId, 'Falha no envio via SMTP/SendEmailService.');
        GenerateLog::generateLog('error', 'Falha ao enviar e-mail de inativação de acessos.', [
            'history_id' => $historyId,
            'user_id' => $userId,
            'user_name' => $safeName,
        ]);
    }
}

