<?php

namespace App\adms\Controllers\settings;

use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\AdmsEmailConfigRepository;

class EmailConfig
{
    public function index(): void
    {
        $repo = new AdmsEmailConfigRepository();
        $data = [
            'title_head' => 'Configuração de E-mail',
            'menu' => 'email-config',
            'buttonPermission' => ['EmailConfig'],
            'email_config' => $repo->getConfig(),
        ];
        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));
        $loadView = new LoadViewService('adms/Views/settings/emailConfig', $data);
        $loadView->loadView();
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'email-config');
            exit;
        }
        $repo = new AdmsEmailConfigRepository();
        $config = [
            'host' => $_POST['MAIL_HOST'] ?? '',
            'username' => $_POST['MAIL_USERNAME'] ?? '',
            'password' => $_POST['MAIL_PASSWORD'] ?? '',
            'port' => $_POST['MAIL_PORT'] ?? '',
            'encryption' => $_POST['MAIL_ENCRYPTI'] ?? '',
            'from_email' => $_POST['EMAIL_TI'] ?? '',
            'from_name' => $_POST['NAME_EMAIL_TI'] ?? '',
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

    public function test(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'email-config');
            exit;
        }

        $repo = new AdmsEmailConfigRepository();
        $config = $repo->getConfig();
        
        if (empty($config)) {
            $_SESSION['msg'] = 'Configure o e-mail primeiro antes de testar.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'email-config');
            exit;
        }

        try {
            $result = $this->sendTestEmail($config);
            if ($result) {
                $_SESSION['msg'] = 'E-mail de teste enviado com sucesso! Verifique sua caixa de entrada.';
                $_SESSION['msg_type'] = 'success';
            } else {
                $_SESSION['msg'] = 'Erro ao enviar e-mail de teste. Verifique as configurações.';
                $_SESSION['msg_type'] = 'danger';
            }
        } catch (\Exception $e) {
            $_SESSION['msg'] = 'Erro ao testar e-mail: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'email-config');
        exit;
    }

    private function sendTestEmail(array $config): bool
    {
        // Configurar PHPMailer
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];
            $mail->CharSet = 'UTF-8';

            // Remetente
            $mail->setFrom($config['from_email'], $config['from_name']);
            
            // Destinatário (enviar para o próprio e-mail configurado)
            $mail->addAddress($config['username']);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = 'Teste de Configuração - Sistema Administrativo';
            $mail->Body = '
                <h2>✅ Teste de Configuração de E-mail</h2>
                <p>Este e-mail foi enviado automaticamente para testar a configuração do servidor SMTP.</p>
                <p><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</p>
                <p><strong>Servidor:</strong> ' . $config['host'] . ':' . $config['port'] . '</p>
                <p><strong>Criptografia:</strong> ' . $config['encryption'] . '</p>
                <hr>
                <p><em>Se você recebeu este e-mail, a configuração está funcionando corretamente!</em></p>
            ';

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("Erro no teste de e-mail: " . $e->getMessage());
            return false;
        }
    }

    private function getCurrentConfig(): array
    {
        return [
            'MAIL_HOST' => $_ENV['MAIL_HOST'] ?? '',
            'MAIL_USERNAME' => $_ENV['MAIL_USERNAME'] ?? '',
            'MAIL_PASSWORD' => $_ENV['MAIL_PASSWORD'] ?? '',
            'MAIL_PORT' => $_ENV['MAIL_PORT'] ?? '',
            'MAIL_ENCRYPTI' => $_ENV['MAIL_ENCRYPTI'] ?? '',
            'EMAIL_TI' => $_ENV['EMAIL_TI'] ?? '',
            'NAME_EMAIL_TI' => $_ENV['NAME_EMAIL_TI'] ?? '',
        ];
    }

    private function saveConfigToEnv(array $config): bool
    {
        $envPath = __DIR__ . '/../../../.env';
        if (!file_exists($envPath)) return false;
        $env = file_get_contents($envPath);
        foreach ($config as $key => $value) {
            $env = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $key . '=' . $value, $env);
        }
        return file_put_contents($envPath, $env) !== false;
    }
} 