<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SendEmailService;
use App\adms\Models\Repository\WhistleblowingCommitteesRepository;

/**
 * Notifica membros do comitê por e-mail ao receber nova denúncia (sem conteúdo do relato).
 */
final class WhistleblowingCommitteeNotificationService
{
    public function notifyNewReport(
        int $reportId,
        string $protocol,
        string $category,
        string $riskLevel,
        ?int $committeeId
    ): void {
        if ($committeeId === null || $committeeId <= 0) {
            return;
        }

        $contacts = (new WhistleblowingCommitteesRepository())->getMemberEmailContacts($committeeId);
        if ($contacts === []) {
            return;
        }

        $urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $link = $urlAdm . '/view-denuncia/' . $reportId;
        $subject = 'Nova denúncia — ' . $protocol;

        $bodyHtml = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#333;">'
            . '<p>Foi registrada uma nova denúncia no Canal de Denúncias.</p>'
            . '<table style="border-collapse:collapse;margin:12px 0;">'
            . '<tr><td style="padding:4px 12px 4px 0;"><strong>Protocolo:</strong></td><td>' . htmlspecialchars($protocol) . '</td></tr>'
            . '<tr><td style="padding:4px 12px 4px 0;"><strong>Classificação:</strong></td><td>' . htmlspecialchars($category) . '</td></tr>'
            . '<tr><td style="padding:4px 12px 4px 0;"><strong>Risco:</strong></td><td>' . htmlspecialchars($riskLevel) . '</td></tr>'
            . '</table>'
            . '<p>Por segurança, o texto do relato <strong>não</strong> é enviado por e-mail. Acesse o sistema para triagem:</p>'
            . '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>'
            . '<p style="color:#666;font-size:12px;">Mensagem automática — Canal de Denúncias Tiaraju.</p>'
            . '</div>';

        $bodyText = "Nova denúncia: {$protocol}\nClassificação: {$category}\nRisco: {$riskLevel}\nAcesse: {$link}";

        foreach ($contacts as $contact) {
            $email = trim((string) ($contact['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
                error_log('WhistleblowingCommitteeNotificationService: ' . $e->getMessage());
            }
        }
    }
}
