<?php

namespace App\adms\Controllers\Services;

use App\adms\Helpers\SendEmailService;
use App\adms\Helpers\SendWhatsAppService;
use App\adms\Models\Repository\ResetPasswordRepository;

class RecoverPassword
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    public function recoverPassword(array $data): bool 
    {
        // Instanciar o serviço para gerar a chave
        $valueGenerateKey = GenerateKeyService::generateKey();

        $data['key'] = $valueGenerateKey['key'];
        $data['recover_password'] = $valueGenerateKey['encryptedkey'];
        $data['validate_recover_password'] = date("Y-m-d H:i:s", strtotime('+1hour'));
        
        // Formatar a data e hora separadamente
        $formattedTime = date("H:i:s", strtotime($data['validate_recover_password']));
        $formattedDate = date("d/m/Y", strtotime($data['validate_recover_password']));

        // Instanciar Repository para resetar a senha
        $userUpdate = new ResetPasswordRepository();
        $result = $userUpdate->updateForgotPassword($data);

        // Acessa o IF se o repository retornou TRUE
        if(!$result){
            return false;

        }

        $name = explode(" ", $data['user']['name']);
        $firstName = $name[0];

        // Garantir que a URL tenha protocolo (http/https) e não tenha barra duplicada
        $baseUrl = rtrim($_ENV['URL_ADM'], '/');
        if (!str_starts_with($baseUrl, 'http://') && !str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://' . ltrim($baseUrl, '/');
        }
        $url = $baseUrl . '/reset-password/' . $data['key'];

        // Método de entrega: email, whatsapp ou both
        $deliveryMethod = $data['form']['delivery_method'] ?? 'email';

        $sentEmail = false;
        $sentWhatsApp = false;

        // --- Envio por E-MAIL ---
        if ($deliveryMethod === 'email' || $deliveryMethod === 'both') {
            $subject = "Recuperar Senha.";

            $body = "<p>Prezado $firstName</p>";
            $body .= "<p>Você solicitou a alteração de sua senha.</p>";
            $body .= "<p>Para continuar, clique no link abaixo ou cole o endereço no seu navegador: </p>";
            $body .= "<p><a href='$url'>$url</a></p>";
            $body .= "<p>Por questões de segurança esse link é válido somente até as $formattedTime do dia $formattedDate. Caso esse prazo esteja expirado, será necessário solicitar outro link.</p>";
            $body .= "<p>Se você não solicitou essa alteração, nenhuma ação é necessária. Sua senha permanecerá a mesma até que você solicite um novo link.</p>";

            $altBody = "Prezado $firstName\n\n";
            $altBody .= "Você solicitou a alteração de sua senha.\n\n";
            $altBody .= "Para continuar, clique no link abaixo ou cole o endereço no seu navegador: \n\n";
            $altBody .= "$url\n\n";
            $altBody .= "Por questões de segurança esse link é válido somente até as $formattedTime do dia $formattedDate. Caso esse prazo esteja expirado, será necessário solicitar outro link.\n\n";
            $altBody .= "Se você não solicitou essa alteração, nenhuma ação é necessária. Sua senha permanecerá a mesma até que você solicite um novo link.\n\n";

            $sentEmail = SendEmailService::sendEmail(
                $data['user']['email'],
                $data['user']['name'],
                $subject,
                $body,
                $altBody
            );
        }

        // --- Envio por WHATSAPP ---
        if ($deliveryMethod === 'whatsapp' || $deliveryMethod === 'both') {
            $phone = $data['user']['celular'] ?? '';

            if (!empty($phone)) {
                $mensagem = "Prezado {$firstName},\n\n";
                $mensagem .= "Você solicitou a alteração de sua senha.\n\n";
                $mensagem .= "Para continuar, acesse o link abaixo (ou copie e cole no navegador):\n";
                $mensagem .= "{$url}\n\n";
                $mensagem .= "Por questões de segurança, esse link é válido somente até as {$formattedTime} do dia {$formattedDate}. ";
                $mensagem .= "Caso esse prazo esteja expirado, será necessário solicitar outro link.\n\n";
                $mensagem .= "Se você não solicitou essa alteração, nenhuma ação é necessária. Sua senha permanecerá a mesma até que você solicite um novo link.\n";

                $resultWhats = SendWhatsAppService::sendMessage($phone, $mensagem);
                $sentWhatsApp = $resultWhats['success'] ?? false;
            }
        }

        // Regras de retorno:
        // - email: precisa ter enviado e-mail
        // - whatsapp: precisa ter enviado WhatsApp
        // - both: considera sucesso se pelo menos um dos dois canais funcionar
        if ($deliveryMethod === 'email') {
            return $sentEmail;
        }

        if ($deliveryMethod === 'whatsapp') {
            return $sentWhatsApp;
        }

        // both
        return ($sentEmail || $sentWhatsApp);
    }
}
