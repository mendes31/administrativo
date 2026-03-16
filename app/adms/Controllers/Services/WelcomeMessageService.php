<?php

namespace App\adms\Controllers\Services;

use App\adms\Helpers\SendEmailService;
use App\adms\Helpers\SendWhatsAppService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UsersRepository;

/**
 * Serviço para envio de mensagem de boas-vindas (e-mail / WhatsApp)
 */
class WelcomeMessageService
{
    /**
     * Envia mensagem de boas-vindas para um usuário recém-criado,
     * de acordo com os flags configurados no cadastro.
     *
     * @param array $user Dados do usuário, incluindo:
     *                    id, name, email, username, celular,
     *                    enviar_boas_vindas_email, enviar_boas_vindas_whatsapp
     * @param int|null $triggerUserId ID do usuário que disparou (logado)
     */
    public static function sendForNewUser(array $user, ?int $triggerUserId = null): void
    {
        try {
            $userId = (int)($user['id'] ?? 0);
            if ($userId <= 0) {
                return;
            }

            $sendEmail = !empty($user['enviar_boas_vindas_email']);
            $sendWhats = !empty($user['enviar_boas_vindas_whatsapp']);

            if (!$sendEmail && !$sendWhats) {
                return;
            }

            $name     = (string)($user['name'] ?? '');
            $email    = (string)($user['email'] ?? '');
            $username = (string)($user['username'] ?? '');
            $phone    = (string)($user['celular'] ?? '');

            $baseUrl  = rtrim($_ENV['URL_ADM'] ?? '', '/');
            if ($baseUrl === '') {
                $baseUrl = rtrim($_SERVER['REQUEST_SCHEME'] ?? 'http', ':/') . '://' .
                           ($_SERVER['HTTP_HOST'] ?? '') . '/administrativo';
            }

            $loginUrl   = $baseUrl . '/login';
            $passwordUrl = $baseUrl . '/update-password';

            $subject = 'Bem-vindo(a) ao Portal Interno Tiaraju';

            $bodyHtml = '
                <p>Olá, <strong>' . htmlspecialchars($name ?: $username, ENT_QUOTES, 'UTF-8') . '</strong>!</p>
                <p>Seu acesso ao Portal Interno da Tiaraju foi criado.</p>
                <p>
                    <strong>Usuário:</strong> ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '<br>
                </p>
                <p>
                    Você pode acessar o sistema pelo link abaixo:<br>
                    <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '</a>
                </p>
                <p>
                    No primeiro acesso, utilize a senha definida pelo administrador e, em seguida, altere sua senha
                    pelo menu apropriado ou diretamente pelo link:<br>
                    <a href="' . htmlspecialchars($passwordUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($passwordUrl, ENT_QUOTES, 'UTF-8') . '</a>
                </p>
                <p>Se você tiver qualquer dificuldade de acesso, entre em contato com a equipe de TI.</p>
            ';

            $bodyText =
                'Olá, ' . ($name ?: $username) . "!\n\n" .
                "Seu acesso ao Portal Interno da Tiaraju foi criado.\n\n" .
                "Usuário: {$username}\n\n" .
                "Acesse: {$loginUrl}\n" .
                "Após o primeiro acesso, altere sua senha em: {$passwordUrl}\n\n" .
                "Em caso de dúvidas, procure a equipe de TI.";

            $emailSent = false;
            $whatsSent = false;

            if ($sendEmail && $email !== '') {
                $emailSent = SendEmailService::sendEmail($email, $name ?: $username, $subject, $bodyHtml, $bodyText);
            }

            if ($sendWhats && $phone !== '') {
                $whatsMessage =
                    "Olá, " . ($name ?: $username) . "!\n\n" .
                    "Seu acesso ao Portal Interno da Tiaraju foi criado.\n\n" .
                    "Usuário: {$username}\n\n" .
                    "Acesse: {$loginUrl}\n" .
                    "Após o primeiro acesso, altere sua senha em: {$passwordUrl}\n\n" .
                    "Em caso de dúvidas, procure a equipe de TI.";

                $whatsResult = SendWhatsAppService::sendMessage($phone, $whatsMessage);
                $whatsSent = $whatsResult['success'] ?? false;
            }

            if ($emailSent || $whatsSent) {
                $repo = new UsersRepository();
                $sql = 'UPDATE adms_users 
                           SET boas_vindas_enviado_em = :dt,
                               boas_vindas_enviado_por = :by
                         WHERE id = :id';
                $stmt = $repo->getConnection()->prepare($sql);
                $stmt->bindValue(':dt', date('Y-m-d H:i:s'));
                $stmt->bindValue(':by', $triggerUserId ?: ($_SESSION['user_id'] ?? null), \PDO::PARAM_INT);
                $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
                $stmt->execute();
            }

            GenerateLog::generateLog('info', 'WelcomeMessageService: envio de boas-vindas concluído.', [
                'user_id' => $userId,
                'email_enviado' => $emailSent,
                'whatsapp_enviado' => $whatsSent,
            ]);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'WelcomeMessageService: erro ao enviar boas-vindas.', [
                'exception' => $e->getMessage(),
                'user' => $user['id'] ?? null,
            ]);
        }
    }
}

