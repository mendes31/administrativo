<?php
namespace App\adms\Controllers\settings;

use App\adms\Models\Repository\AdmsEmailConfigRepository;

class TestEmailConfig
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-email-config');
            exit;
        }
        $repo = new AdmsEmailConfigRepository();
        $config = $repo->getConfig();
        if (empty($config)) {
            $_SESSION['msg'] = 'Configure o e-mail primeiro antes de testar.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-email-config');
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
        header('Location: ' . $_ENV['URL_ADM'] . 'list-email-config');
        exit;
    }

    private function sendTestEmail(array $config): bool
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($config['username']);
            $mail->isHTML(true);
            $mail->Subject = 'Teste de Configuração - Sistema Administrativo';
            $mail->Body = '<h2>✅ Teste de Configuração de E-mail</h2><p>Este e-mail foi enviado automaticamente para testar a configuração do servidor SMTP.</p><p><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</p><p><strong>Servidor:</strong> ' . $config['host'] . ':' . $config['port'] . '</p><p><strong>Criptografia:</strong> ' . $config['encryption'] . '</p><hr><p><em>Se você recebeu este e-mail, a configuração está funcionando corretamente!</em></p>';
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("Erro no teste de e-mail: " . $e->getMessage());
            return false;
        }
    }
} 