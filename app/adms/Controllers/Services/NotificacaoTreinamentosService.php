<?php

namespace App\adms\Controllers\Services;

use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Helpers\SendEmailService;
// use PHPMailer\PHPMailer\PHPMailer; // Descomente se for usar PHPMailer

class NotificacaoTreinamentosService
{
    /**
     * Busca colaboradores com treinamentos pendentes, vencidos ou próximos do vencimento
     * e envia notificações por e-mail.
     */
    public function notificarTreinamentosPendentes()
    {
        $trainingUsersRepo = new TrainingUsersRepository();
        $usersRepo = new UsersRepository();

        // Buscar todos os vínculos de treinamentos vencidos e próximos do vencimento
        $vencidos = $trainingUsersRepo->getExpiringTrainings(); // status vencido e proximo_vencimento

        foreach ($vencidos as $treinamento) {
            $email = $treinamento['user_email'] ?? $treinamento['email'] ?? null;
            $nome = $treinamento['user_name'] ?? $treinamento['name'] ?? null;
            if ($email && $nome) {
                $this->enviarEmailNotificacao($email, $nome, $treinamento);
            }
        }
    }

    /**
     * Envia e-mail de notificação para o colaborador
     */
    private function enviarEmailNotificacao($email, $nome, $treinamento)
    {
        $assunto = "[Treinamento] Pendência de Treinamento";
        $mensagem = "Olá $nome,<br><br>Você possui o treinamento <b>{$treinamento['training_name']}</b> com status <b>{$treinamento['status']}</b>.<br>Por favor, acesse o sistema para regularizar sua situação.<br><br>Atenciosamente,<br>Equipe RH";
        $altBody = "Olá $nome,\nVocê possui o treinamento {$treinamento['training_name']} com status {$treinamento['status']}. Por favor, acesse o sistema para regularizar sua situação.";
        SendEmailService::sendEmail($email, $nome, $assunto, $mensagem, $altBody);
    }
} 