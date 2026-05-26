<?php

namespace App\adms\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use App\adms\Models\Repository\AdmsEmailConfigRepository;
use App\adms\Helpers\GenerateLog;

class SendEmailService
{
    /**
     * Envia e-mail via SMTP com remetente (From) da configuração global.
     *
     * @param string|null $replyToEmail Se definido, cabeçalho Reply-To (ex.: organizador da reserva). Muitos servidores
     *                                   exigem que o From coincida com a conta SMTP; usar Reply-To é o padrão seguro.
     * @param string|null $replyToName Nome opcional para Reply-To
     * @param string|null $fromDisplayNameOverride Nome amigável no cabeçalho From (o endereço continua o da config, salvo override abaixo).
     * @param string|null $fromAddressOverride E-mail no From; só aplicado se ROOM_BOOKING_SMTP_USE_ORGANIZER_AS_FROM=true no .env (evita SPF/DMARC quebrados por defeito).
     * @param bool $forceFromAddressOverride Quando true, aplica fromAddressOverride independentemente da flag de ambiente.
     */
    /**
     * @param array<int, array{path:string, name:string}> $attachments Arquivos a anexar [{path, name}, ...]
     */
    public static function sendEmail(
        string $email,
        string $name,
        string $subject,
        string $body,
        string $altBody,
        ?string $replyToEmail = null,
        ?string $replyToName = null,
        ?string $fromDisplayNameOverride = null,
        ?string $fromAddressOverride = null,
        bool $forceFromAddressOverride = false,
        array $attachments = [],
    ): bool {
        $mail = new PHPMailer(true);

        // Buscar configuração do banco
        $repo = new AdmsEmailConfigRepository();
        $config = $repo->getConfig();

        $smtpDebugLog = '';

        try {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function ($str, $level) use (&$smtpDebugLog) {
                $smtpDebugLog .= "[L{$level}] {$str}";
            };
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();                                            //Envia via SMTP
            $mail->Host       = $config['host'] ?? '';                     //Define o servidor SMTP para enviar
            $mail->SMTPAuth   = true;                                   //Habilita autenticação SMTP
            $mail->Username   = $config['username'] ?? '';                 //Nome de usuário SMTP
            $mail->Password   = $config['password'] ?? '';                 //senha SMTP
            $mail->SMTPSecure = $config['encryption'] ?? '';                 //Habilita criptografia TLS implícita
            $mail->Port       = $config['port'] ?? 587;

            //Recipients — From: por defeito conta SMTP; nome pode mostrar o organizador
            $cfgFromEmail = trim((string) ($config['from_email'] ?? ''));
            $cfgFromName = trim((string) ($config['from_name'] ?? ''));
            $fromEmail = $cfgFromEmail;
            $fromName = $cfgFromName !== '' ? $cfgFromName : $cfgFromEmail;
            $displayName = $fromDisplayNameOverride !== null ? trim($fromDisplayNameOverride) : '';
            if ($displayName !== '') {
                $fromName = $displayName;
            }

            $useOrganizerFrom = filter_var($_ENV['ROOM_BOOKING_SMTP_USE_ORGANIZER_AS_FROM'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
            $overrideAddr = $fromAddressOverride !== null ? trim($fromAddressOverride) : '';
            if (($useOrganizerFrom || $forceFromAddressOverride) && $overrideAddr !== '' && filter_var($overrideAddr, FILTER_VALIDATE_EMAIL)) {
                $fromEmail = $overrideAddr;
                if ($displayName === '') {
                    $fromName = $overrideAddr;
                }
                $smtpUser = trim((string) ($config['username'] ?? ''));
                if ($smtpUser !== '' && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
                    $mail->Sender = $smtpUser;
                } elseif ($cfgFromEmail !== '' && filter_var($cfgFromEmail, FILTER_VALIDATE_EMAIL)) {
                    $mail->Sender = $cfgFromEmail;
                }
            }

            $mail->setFrom($fromEmail, $fromName);
            $replyToEmail = $replyToEmail !== null ? trim($replyToEmail) : '';
            if ($replyToEmail !== '' && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyToEmail, $replyToName !== null ? trim($replyToName) : '');
            }
            $mail->addAddress($email, $name);                           //Adiciona um destinatário

            //Content
            $mail->isHTML(true);                                        //Define o formato do e-mail para HTML
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody;

            foreach ($attachments as $att) {
                $filePath = $att['path'] ?? '';
                $fileName = $att['name'] ?? basename($filePath);
                if ($filePath !== '' && is_file($filePath)) {
                    $mail->addAttachment($filePath, $fileName);
                }
            }

            $mail->send();

            GenerateLog::generateLog("info", "Email enviado com sucesso.", ['email' => $email, 'subject' => $subject]);

            return true;

        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Email não enviado", [
                'email' => $email,
                'error' => $e->getMessage(),
                'smtp_debug' => $smtpDebugLog,
            ]);
            return false;
        }
    }
}
