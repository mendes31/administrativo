<?php

namespace App\adms\Controllers\settings;

use App\adms\Models\Repository\AdmsEmailConfigRepository;

class CreateEmailConfig
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'email-config');
            exit;
        }

        $repo = new AdmsEmailConfigRepository();
        $config = [
            'host'           => $_POST['MAIL_HOST'] ?? '',
            'username'       => $_POST['MAIL_USERNAME'] ?? '',
            'password'       => $_POST['MAIL_PASSWORD'] ?? '',
            'port'           => $_POST['MAIL_PORT'] ?? '',
            'encryption'     => $_POST['MAIL_ENCRYPTI'] ?? '',
            'from_email'     => $_POST['EMAIL_TI'] ?? '',
            'from_name'      => $_POST['NAME_EMAIL_TI'] ?? '',
            'test_recipient' => $_POST['TEST_RECIPIENT'] ?? '',
            'rh_entrevista_send_enabled' => isset($_POST['RH_ENTREVISTA_SEND_ENABLED']) ? 1 : 0,
        ];

        $ok = $repo->saveConfig($config);

        if ($ok) {
            $_SESSION['msg'] = 'Configurações de e-mail salvas com sucesso!';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao salvar configurações.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'email-config');
        exit;
    }
}