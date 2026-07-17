<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SendEmailService;
use App\adms\Helpers\WhistleblowingPublicUrlHelper;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\WhistleblowingCommitteesRepository;
use App\adms\Models\Repository\WhistleblowingConfigRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;

/**
 * Notificações do Canal de Denúncias — e-mail e sino/push internos.
 */
final class WhistleblowingNotificationService
{
    public function notifyNewReport(
        int $reportId,
        string $protocol,
        string $category,
        string $riskLevel,
        ?int $committeeId
    ): void {
        $this->notifyCommittee(
            $reportId,
            $protocol,
            $committeeId,
            'Nova denúncia — ' . $protocol,
            'Foi registrada uma nova denúncia no Canal de Denúncias.',
            [
                ['Classificação', $category],
                ['Risco', $riskLevel],
            ],
            'whistleblowing_new_report'
        );
    }

    public function notifyWhistleblowerReply(int $reportId, string $protocol, ?int $committeeId): void
    {
        $config = new WhistleblowingConfigRepository();
        if (!$config->isNotifyCommitteeOnReply()) {
            return;
        }

        $this->notifyCommittee(
            $reportId,
            $protocol,
            $committeeId,
            'Complemento do denunciante — ' . $protocol,
            'O denunciante enviou nova informação ou anexo na denúncia.',
            [],
            'whistleblowing_whistleblower_reply'
        );
    }

    public function notifyStatusChange(
        int $reportId,
        string $protocol,
        ?int $committeeId,
        string $fromStatus,
        string $toStatus
    ): void {
        $config = new WhistleblowingConfigRepository();
        if (!$config->isNotifyCommitteeOnStatusChange()) {
            return;
        }

        $this->notifyCommittee(
            $reportId,
            $protocol,
            $committeeId,
            'Status alterado — ' . $protocol,
            'O status da denúncia foi alterado.',
            [
                ['De', $fromStatus],
                ['Para', $toStatus],
            ],
            'whistleblowing_status_change'
        );
    }

    public function notifySlaBreach(int $reportId, string $protocol, ?int $committeeId): void
    {
        $config = new WhistleblowingConfigRepository();
        if (!$config->isNotifyCommitteeOnSlaBreach()) {
            return;
        }

        $this->notifyCommittee(
            $reportId,
            $protocol,
            $committeeId,
            'SLA estourado — ' . $protocol,
            'A denúncia está sem primeira resposta do comitê após o prazo configurado.',
            [],
            'whistleblowing_sla_breach'
        );
    }

    public function notifyClosureSlaBreach(int $reportId, string $protocol, ?int $committeeId): void
    {
        $config = new WhistleblowingConfigRepository();
        if (!$config->isSlaClosureEnabled() || !$config->isNotifyCommitteeOnSlaClosureBreach()) {
            return;
        }

        $this->notifyCommittee(
            $reportId,
            $protocol,
            $committeeId,
            'SLA de encerramento estourado — ' . $protocol,
            'A denúncia permanece aberta após o prazo configurado para encerramento.',
            [],
            'whistleblowing_sla_closure_breach'
        );
    }

    public function notifyReporterInactivity(
        int $reportId,
        string $protocol,
        ?int $committeeId,
        string $deadline
    ): void {
        $config = new WhistleblowingConfigRepository();
        if (!$config->isReporterInactivityEnabled()) {
            return;
        }

        $formattedDeadline = strtotime($deadline) !== false
            ? date('d/m/Y H:i', strtotime($deadline))
            : $deadline;
        $this->notifyCommittee(
            $reportId,
            $protocol,
            $committeeId,
            'Retorno do denunciante vencido — ' . $protocol,
            'O prazo para retorno do denunciante venceu. A denúncia permanece aberta e deve ser avaliada pelo comitê.',
            [
                ['Prazo encerrado em', $formattedDeadline],
                ['Ação', 'Avaliar o caso e decidir manualmente se deve ser encerrado'],
            ],
            'whistleblowing_reporter_inactivity'
        );
    }

    /**
     * @param array<string, mixed> $report Linha hidratada ou bruta com reporter_email
     */
    public function notifyReporterOnCommitteeReply(array $report): void
    {
        $config = new WhistleblowingConfigRepository();
        if (!$config->isNotifyReporterOnReply()) {
            return;
        }

        $email = trim((string) ($report['reporter_email'] ?? ''));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $protocol = (string) ($report['protocol'] ?? '');
        $publicUrl = WhistleblowingPublicUrlHelper::baseUrl() . 'acompanhar';
        $subject = 'Nova resposta na denúncia ' . $protocol;
        $deadline = strtotime((string) ($report['reporter_response_deadline'] ?? ''));
        $deadlineHtml = $deadline !== false
            ? '<p>O comitê aguarda seu retorno até <strong>' . date('d/m/Y H:i', $deadline) . '</strong>.</p>'
            : '';
        $deadlineText = $deadline !== false
            ? "\nRetorne até: " . date('d/m/Y H:i', $deadline)
            : '';

        $bodyHtml = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#333;">'
            . '<p>Há uma nova resposta do comitê na sua denúncia <strong>' . htmlspecialchars($protocol) . '</strong>.</p>'
            . $deadlineHtml
            . '<p>Por segurança, o conteúdo da resposta <strong>não</strong> é enviado por e-mail.</p>'
            . '<p>Acesse o canal com seu protocolo e senha:</p>'
            . '<p><a href="' . htmlspecialchars($publicUrl) . '">' . htmlspecialchars($publicUrl) . '</a></p>'
            . '<p style="color:#666;font-size:12px;">Mensagem automática — Canal de Denúncias.</p>'
            . '</div>';

        $bodyText = "Nova resposta na denúncia {$protocol}.{$deadlineText}\nConsulte em: {$publicUrl}";

        try {
            SendEmailService::sendEmail($email, 'Denunciante', $subject, $bodyHtml, $bodyText);
        } catch (\Throwable $e) {
            error_log('WhistleblowingNotificationService::notifyReporterOnCommitteeReply: ' . $e->getMessage());
        }
    }

    /**
     * @param list<array{0: string, 1: string}> $extraRows
     */
    private function notifyCommittee(
        int $reportId,
        string $protocol,
        ?int $committeeId,
        string $subject,
        string $intro,
        array $extraRows,
        string $eventType
    ): void {
        if ($committeeId === null || $committeeId <= 0) {
            return;
        }

        $committeesRepo = new WhistleblowingCommitteesRepository();
        $urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $link = $urlAdm . '/view-denuncia/' . $reportId;

        $rowsHtml = '';
        $rowsText = '';
        $rowsHtml .= '<tr><td style="padding:4px 12px 4px 0;"><strong>Protocolo:</strong></td><td>' . htmlspecialchars($protocol) . '</td></tr>';
        $rowsText .= "Protocolo: {$protocol}\n";
        foreach ($extraRows as [$label, $value]) {
            $rowsHtml .= '<tr><td style="padding:4px 12px 4px 0;"><strong>' . htmlspecialchars($label) . ':</strong></td><td>' . htmlspecialchars($value) . '</td></tr>';
            $rowsText .= "{$label}: {$value}\n";
        }

        $bodyHtml = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#333;">'
            . '<p>' . htmlspecialchars($intro) . '</p>'
            . '<table style="border-collapse:collapse;margin:12px 0;">' . $rowsHtml . '</table>'
            . '<p>Por segurança, o texto do relato <strong>não</strong> é enviado por e-mail. Acesse o sistema:</p>'
            . '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>'
            . '<p style="color:#666;font-size:12px;">Mensagem automática — Canal de Denúncias Tiaraju.</p>'
            . '</div>';

        $bodyText = $intro . "\n" . $rowsText . "Acesse: {$link}";

        foreach ($committeesRepo->getMemberEmailContacts($committeeId) as $contact) {
            $email = trim((string) ($contact['email'] ?? ''));
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }
            try {
                SendEmailService::sendEmail(
                    $email,
                    (string) ($contact['name'] ?? 'Membro do comitê'),
                    $subject,
                    $bodyHtml,
                    $bodyText
                );
            } catch (\Throwable $e) {
                error_log('WhistleblowingNotificationService email: ' . $e->getMessage());
            }
        }

        $notifRepo = new NotificationsRepository();
        foreach ($committeesRepo->getMemberIds($committeeId) as $userId) {
            if ($userId <= 0) {
                continue;
            }
            try {
                $notifRepo->create([
                    'user_id' => $userId,
                    'type' => $eventType,
                    'title' => $subject,
                    'message' => $intro,
                    'link_url' => $link,
                    'entity_type' => 'whistleblowing_report',
                    'entity_id' => $reportId,
                ]);
            } catch (\Throwable $e) {
                error_log('WhistleblowingNotificationService push: ' . $e->getMessage());
            }
        }
    }
}
