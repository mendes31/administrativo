<?php

namespace App\adms\Controllers\Services;

use App\adms\Helpers\SendEmailService;
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

        // $sendEmail->sendEmail('rafael.oliveira@tiaraju.com.br', 'Rafael', 'Recuperar senha', 'Abaixo segue o link para recuperação de sua senha, o mesmo irá expirar em 60 minutos!</b>', 'Abaixo segue o link para recuperação de sua senha, o mesmo irá expirar em 60 minutos!');

        $name = explode(" ", $data['user']['name']);
        $firstName = $name[0];

        $subject = "Recuperar Senha.";
        $url = "{$_ENV['URL_ADM']}reset-password/{$data['key']}";

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
        $altBody .= "<p>Se você não solicitou essa alteração, nenhuma ação é necessária. Sua senha permanecerá a mesma até que você solicite um novo link.\n\n";


        // new = SendEmailService();

        $resultSendEmail = SendEmailService::sendEmail($data['user']['email'], $data['user']['name'], $subject, $body, $altBody);

        return $resultSendEmail;
    }
}
