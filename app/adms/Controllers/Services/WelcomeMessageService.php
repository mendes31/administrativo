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
     * Envia mensagem de acesso (boas-vindas ou desbloqueio),
     * de acordo com o contexto informado e os flags configurados.
     *
     * @param array $user Dados do usuário, incluindo:
     *                    id, name, email, username, celular,
     *                    enviar_boas_vindas_email, enviar_boas_vindas_whatsapp
     * @param int|null $triggerUserId ID do usuário que disparou (logado)
     * @param string $context Contexto da mensagem: 'welcome' (padrão) ou 'unlock'
     * @param string|null $plainPassword Senha inicial/provisória a ser comunicada (opcional)
     */
    public static function sendForNewUser(array $user, ?int $triggerUserId = null, string $context = 'welcome', ?string $plainPassword = null): void
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

            // Buscar política de senha para montar os requisitos mínimos
            $policyRepo = new \App\adms\Models\Repository\AdmsPasswordPolicyRepository();
            $policy = $policyRepo->getPolicy();

            $minLen   = $policy->comprimento_minimo      ?? 6;
            $minUpper = $policy->min_maiusculas          ?? 0;
            $minLower = $policy->min_minusculas          ?? 0;
            $minDigit = $policy->min_digitos             ?? 0;
            $minSpec  = $policy->min_nao_alfanumericos   ?? 0;

            $requirementsLines = [];
            $requirementsLines[] = "- Mínimo de {$minLen} caracteres.";
            if ($minUpper > 0) $requirementsLines[] = "- Pelo menos {$minUpper} letra(s) maiúscula(s).";
            if ($minLower > 0) $requirementsLines[] = "- Pelo menos {$minLower} letra(s) minúscula(s).";
            if ($minDigit > 0) $requirementsLines[] = "- Pelo menos {$minDigit} dígito(s).";
            if ($minSpec > 0)  $requirementsLines[] = "- Pelo menos {$minSpec} caractere(s) especial(is).";
            $requirementsHtml = '';
            $requirementsText = '';
            if (!empty($requirementsLines)) {
                $requirementsHtml = '<ul>';
                foreach ($requirementsLines as $line) {
                    $requirementsHtml .= '<li>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>';
                }
                $requirementsHtml .= '</ul>';

                $requirementsText = implode("\n", $requirementsLines);
            }

            // Definir assunto e textos conforme contexto
            if ($context === 'unlock') {
                $subject = 'Senha provisória do Portal Interno Tiaraju';

                $bodyHtml = '
                    <p>Olá, <strong>' . htmlspecialchars($name ?: $username, ENT_QUOTES, 'UTF-8') . '</strong>!</p>
                    <p>Sua conta no Portal Interno da Tiaraju foi desbloqueada e uma <strong>senha provisória</strong> foi definida.</p>
                    <p>
                        <strong>Usuário:</strong> ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '<br>';

                if ($plainPassword !== null && $plainPassword !== '') {
                    $bodyHtml .= '
                        <strong>Senha provisória:</strong> ' . htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8') . '<br>';
                }

                $bodyHtml .= '
                    </p>
                    <p>
                        Acesse o sistema pelo link abaixo e, após o login, será solicitado que você <strong>altere sua senha</strong>:
                        <br>
                        <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '</a>
                    </p>
                    <p>A nova senha deve seguir, no mínimo, os seguintes requisitos:</p>
                    ' . $requirementsHtml . '
                    <p>Em caso de dúvidas, procure a equipe de TI.</p>
                ';

                $bodyText =
                    'Olá, ' . ($name ?: $username) . "!\n\n" .
                    "Sua conta no Portal Interno da Tiaraju foi desbloqueada e uma SENHA PROVISÓRIA foi definida.\n\n" .
                    "Usuário: {$username}\n";

                if ($plainPassword !== null && $plainPassword !== '') {
                    $bodyText .= "Senha provisória: {$plainPassword}\n";
                }

                $bodyText .= "\nAcesse: {$loginUrl}\n" .
                    "Após o login, será solicitado que você ALTERE sua senha.\n\n" .
                    "A nova senha deve seguir, no mínimo, os requisitos abaixo:\n" .
                    $requirementsText . "\n\n" .
                    "Em caso de dúvidas, procure a equipe de TI.";
            } else {
                // Contexto padrão: boas-vindas (usuário novo)
                $subject = 'Bem-vindo(a) ao Portal Interno Tiaraju';

                $bodyHtml = '
                    <p>Olá, <strong>' . htmlspecialchars($name ?: $username, ENT_QUOTES, 'UTF-8') . '</strong>!</p>
                    <p>Seu acesso ao Portal Interno da Tiaraju foi criado.</p>
                    <p>
                        <strong>Usuário:</strong> ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '<br>';

                if ($plainPassword !== null && $plainPassword !== '') {
                    $bodyHtml .= '
                        <strong>Senha inicial:</strong> ' . htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8') . '<br>';
                }

                $bodyHtml .= '
                    </p>
                    <p>
                        Você pode acessar o sistema pelo link abaixo:<br>
                        <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '</a>
                    </p>
                    <p>
                        Após o primeiro acesso, utilize o menu apropriado para alterar sua senha. A nova senha deve seguir,
                        no mínimo, os seguintes requisitos:
                    </p>
                    ' . $requirementsHtml . '
                    <p>Se você tiver qualquer dificuldade de acesso, entre em contato com a equipe de TI.</p>
                ';

                $bodyText =
                    'Olá, ' . ($name ?: $username) . "!\n\n" .
                    "Seu acesso ao Portal Interno da Tiaraju foi criado.\n\n" .
                    "Usuário: {$username}\n";

                if ($plainPassword !== null && $plainPassword !== '') {
                    $bodyText .= "Senha inicial: {$plainPassword}\n";
                }

                $bodyText .= "\nAcesse: {$loginUrl}\n" .
                    "Após o primeiro acesso, utilize o menu apropriado para alterar sua senha.\n\n" .
                    "A nova senha deve seguir, no mínimo, os requisitos abaixo:\n" .
                    $requirementsText . "\n\n" .
                    "Em caso de dúvidas, procure a equipe de TI.";
            }

            $emailSent = false;
            $whatsSent = false;

            if ($sendEmail && $email !== '') {
                $emailSent = SendEmailService::sendEmail($email, $name ?: $username, $subject, $bodyHtml, $bodyText);
            }

            if ($sendWhats && $phone !== '') {
                // Para WhatsApp, reutilizar a versão texto da mesma mensagem
                $whatsMessage = $bodyText;
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

