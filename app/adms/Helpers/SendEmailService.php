<?php

namespace App\adms\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\adms\Models\Repository\AdmsEmailConfigRepository;
use App\adms\Helpers\GenerateLog;

class SendEmailService
{
    public static function sendEmail(string $email, string $name, string $subject, string $body, string $altBody) : bool
    {
        $mail = new PHPMailer(true);

        // Buscar configuração do banco
        $repo = new AdmsEmailConfigRepository();
        $config = $repo->getConfig();

        try {
            //Configurações do servidor
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                   //Habilita saída de depuração detalhada
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();                                            //Envia via SMTP
            $mail->Host       = $config['host'] ?? '';                     //Define o servidor SMTP para enviar
            $mail->SMTPAuth   = true;                                   //Habilita autenticação SMTP
            $mail->Username   = $config['username'] ?? '';                 //Nome de usuário SMTP
            $mail->Password   = $config['password'] ?? '';                 //senha SMTP
            $mail->SMTPSecure = $config['encryption'] ?? '';                 //Habilita criptografia TLS implícita
            $mail->Port       = $config['port'] ?? 587;

            //Recipients
            $mail->setFrom($config['from_email'] ?? '', $config['from_name'] ?? '');
            $mail->addAddress($email, $name);                           //Adiciona um destinatário

            //Content
            $mail->isHTML(true);                                        //Define o formato do e-mail para HTML
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody;

            $mail->send();

            GenerateLog::generateLog("info", "Email enviado com sucesso.", ['email' => $email, 'subject' => $subject]);

            return true;

        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Email não enviado", ['email' => $email, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
