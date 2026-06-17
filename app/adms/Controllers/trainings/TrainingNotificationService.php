<?php

namespace App\adms\Controllers\trainings;

use App\adms\Helpers\InternalPushNotificationHelper;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Services\NotificationSettingsService;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Helpers\GenerateLog;

class TrainingNotificationService
{
    private TrainingUsersRepository $trainingUsersRepo;
    private UsersRepository $usersRepo;
    private TrainingsRepository $trainingsRepo;

    public function __construct()
    {
        $this->trainingUsersRepo = new TrainingUsersRepository();
        $this->usersRepo = new UsersRepository();
        $this->trainingsRepo = new TrainingsRepository();
    }

    /**
     * Envia notificações para treinamentos pendentes
     */
    public function sendPendingTrainingNotifications(): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
            'disabled' => false,
        ];

        if (!NotificationSettingsService::isAnyEnabled('training_pending_email', 'training_pending_inapp')) {
            $results['disabled'] = true;
            return $results;
        }

        try {
            $pendingTrainings = $this->trainingUsersRepo->getExpiringTrainingsForNotification();

            foreach ($pendingTrainings as $training) {
                $outcome = $this->notifyPendingTraining($training);

                if ($outcome['sent']) {
                    $results['sent']++;
                    $this->trainingUsersRepo->markAsNotified($training['user_id'], $training['training_id'], 'pending');
                } elseif ($outcome['attempted']) {
                    $results['failed']++;
                    $results['errors'][] = $outcome['error'] ?? "Falha ao notificar {$training['user_email']} - {$training['training_name']}";
                }
            }

            GenerateLog::generateLog("info", "Notificações de treinamentos pendentes enviadas", $results);
            
        } catch (\Exception $e) {
            GenerateLog::generateLog("error", "Erro ao enviar notificações pendentes", ['error' => $e->getMessage()]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Envia notificações para treinamentos próximos do vencimento (30 dias)
     */
    public function sendExpiringTrainingNotifications(): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
            'disabled' => false,
        ];

        if (!NotificationSettingsService::isAnyEnabled('training_expiring_email', 'training_expiring_inapp')) {
            $results['disabled'] = true;
            return $results;
        }

        try {
            $expiringTrainings = $this->trainingUsersRepo->getExpiringTrainingsForNotification(30);

            foreach ($expiringTrainings as $training) {
                $outcome = $this->notifyExpiringTraining($training);

                if ($outcome['sent']) {
                    $results['sent']++;
                    $this->trainingUsersRepo->markAsNotified($training['user_id'], $training['training_id'], 'expiring');
                } elseif ($outcome['attempted']) {
                    $results['failed']++;
                    $results['errors'][] = $outcome['error'] ?? "Falha ao notificar {$training['user_email']} - {$training['training_name']}";
                }
            }

            GenerateLog::generateLog("info", "Notificações de treinamentos próximos do vencimento enviadas", $results);
            
        } catch (\Exception $e) {
            GenerateLog::generateLog("error", "Erro ao enviar notificações de vencimento", ['error' => $e->getMessage()]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Envia notificações para treinamentos vencidos
     */
    public function sendExpiredTrainingNotifications(): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
            'disabled' => false,
        ];

        if (!NotificationSettingsService::isAnyEnabled('training_expired_email', 'training_expired_inapp')) {
            $results['disabled'] = true;
            return $results;
        }

        try {
            $expiredTrainings = $this->trainingUsersRepo->getExpiredTrainingsForNotification();

            foreach ($expiredTrainings as $training) {
                $outcome = $this->notifyExpiredTraining($training);

                if ($outcome['sent']) {
                    $results['sent']++;
                    $this->trainingUsersRepo->markAsNotified($training['user_id'], $training['training_id'], 'expired');
                } elseif ($outcome['attempted']) {
                    $results['failed']++;
                    $results['errors'][] = $outcome['error'] ?? "Falha ao notificar {$training['user_email']} - {$training['training_name']}";
                }
            }

            GenerateLog::generateLog("info", "Notificações de treinamentos vencidos enviadas", $results);
            
        } catch (\Exception $e) {
            GenerateLog::generateLog("error", "Erro ao enviar notificações de vencidos", ['error' => $e->getMessage()]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Envia notificação de novo treinamento obrigatório
     */
    public function sendNewTrainingNotification(int $userId, int $trainingId): bool
    {
        if (!NotificationSettingsService::isAnyEnabled('training_new_mandatory_email', 'training_new_mandatory_inapp')) {
            return false;
        }

        try {
            $user = $this->usersRepo->getUser($userId);
            $training = $this->trainingsRepo->getTraining($trainingId);

            if (!$user || !$training) {
                return false;
            }

            $sentSomething = false;

            if (NotificationSettingsService::isEnabled('training_new_mandatory_email')) {
                $subject = "Novo Treinamento Obrigatório: {$training['nome']}";
                $body = $this->getNewTrainingEmailBody($user, $training);
                $altBody = $this->getNewTrainingEmailAltBody($user, $training);
                $email = trim((string) ($user['email'] ?? ''));

                if ($email !== '' && SendEmailService::sendEmail($email, $user['name'], $subject, $body, $altBody)) {
                    $sentSomething = true;
                    GenerateLog::generateLog('info', 'E-mail de novo treinamento enviado', [
                        'user_id' => $userId,
                        'training_id' => $trainingId,
                        'user_email' => $email,
                    ]);
                }
            }

            if (NotificationSettingsService::isEnabled('training_new_mandatory_inapp')) {
                $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
                InternalPushNotificationHelper::notifyUser([
                    'user_id' => $userId,
                    'type' => 'training_alert_new',
                    'title' => 'Novo treinamento',
                    'message' => 'Treinamento obrigatório: ' . ($training['nome'] ?? ''),
                    'link_url' => $base . '/list-trainings',
                    'entity_type' => 'adms_training',
                    'entity_id' => $trainingId,
                ]);
                $sentSomething = true;
            }

            return $sentSomething;
            
        } catch (\Exception $e) {
            GenerateLog::generateLog("error", "Erro ao enviar notificação de novo treinamento", [
                'user_id' => $userId,
                'training_id' => $trainingId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @param array<string, mixed> $training
     * @return array{sent: bool, attempted: bool, error?: string}
     */
    private function notifyPendingTraining(array $training): array
    {
        return $this->dispatchTrainingChannels(
            $training,
            'training_pending_email',
            'training_pending_inapp',
            'training_alert_pending',
            'Treinamento pendente',
            fn(): bool => $this->sendPendingTrainingEmailOnly($training)
        );
    }

    /**
     * @param array<string, mixed> $training
     * @return array{sent: bool, attempted: bool, error?: string}
     */
    private function notifyExpiringTraining(array $training): array
    {
        return $this->dispatchTrainingChannels(
            $training,
            'training_expiring_email',
            'training_expiring_inapp',
            'training_alert_expiring',
            'Treinamento a vencer',
            fn(): bool => $this->sendExpiringTrainingEmailOnly($training)
        );
    }

    /**
     * @param array<string, mixed> $training
     * @return array{sent: bool, attempted: bool, error?: string}
     */
    private function notifyExpiredTraining(array $training): array
    {
        return $this->dispatchTrainingChannels(
            $training,
            'training_expired_email',
            'training_expired_inapp',
            'training_alert_expired',
            'Treinamento vencido',
            fn(): bool => $this->sendExpiredTrainingEmailOnly($training),
            50
        );
    }

    /**
     * @param array<string, mixed> $training
     * @return array{sent: bool, attempted: bool, error?: string}
     */
    private function dispatchTrainingChannels(
        array $training,
        string $emailKey,
        string $inAppKey,
        string $notifType,
        string $pushTitle,
        callable $sendEmail,
        int $priority = 0
    ): array {
        $sent = false;
        $attempted = false;
        $error = null;

        if (NotificationSettingsService::isEnabled($inAppKey)) {
            $attempted = true;
            $this->dispatchTrainingPush(
                $training,
                $notifType,
                $pushTitle,
                (string) ($training['training_name'] ?? $pushTitle),
                $priority
            );
            $sent = true;
        }

        if (NotificationSettingsService::isEnabled($emailKey)) {
            $email = trim((string) ($training['user_email'] ?? ''));
            if ($email === '') {
                if (!$sent) {
                    $attempted = true;
                    $error = 'Colaborador sem e-mail cadastrado';
                }
            } else {
                $attempted = true;
                if ($sendEmail()) {
                    $sent = true;
                } elseif ($error === null) {
                    $error = "Falha ao enviar e-mail para {$email}";
                }
            }
        }

        return ['sent' => $sent, 'attempted' => $attempted, 'error' => $error];
    }

    /**
     * Envia apenas e-mail para treinamento pendente
     */
    private function sendPendingTrainingEmailOnly(array $training): bool
    {
        $subject = "Treinamento Pendente: {$training['training_name']}";
        
        $body = $this->getPendingTrainingEmailBody($training);
        $altBody = $this->getPendingTrainingEmailAltBody($training);

        return SendEmailService::sendEmail(
            $training['user_email'],
            $training['user_name'],
            $subject,
            $body,
            $altBody
        );
    }

    /**
     * Envia apenas e-mail para treinamento próximo do vencimento
     */
    private function sendExpiringTrainingEmailOnly(array $training): bool
    {
        $subject = "Treinamento Próximo do Vencimento: {$training['training_name']}";
        
        $body = $this->getExpiringTrainingEmailBody($training);
        $altBody = $this->getExpiringTrainingEmailAltBody($training);

        return SendEmailService::sendEmail(
            $training['user_email'],
            $training['user_name'],
            $subject,
            $body,
            $altBody
        );
    }

    /**
     * Envia apenas e-mail para treinamento vencido
     */
    private function sendExpiredTrainingEmailOnly(array $training): bool
    {
        $subject = "URGENTE: Treinamento Vencido - {$training['training_name']}";
        
        $body = $this->getExpiredTrainingEmailBody($training);
        $altBody = $this->getExpiredTrainingEmailAltBody($training);

        return SendEmailService::sendEmail(
            $training['user_email'],
            $training['user_name'],
            $subject,
            $body,
            $altBody
        );
    }

    /**
     * @param array<string, mixed> $training
     */
    private function dispatchTrainingPush(array $training, string $notifType, string $pushTitle, string $message, int $priority = 0): void
    {
        $userId = (int) ($training['user_id'] ?? 0);
        $trainingId = (int) ($training['training_id'] ?? 0);
        if ($userId <= 0 || $trainingId <= 0) {
            return;
        }

        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        InternalPushNotificationHelper::notifyUser([
            'user_id' => $userId,
            'type' => $notifType,
            'title' => $pushTitle,
            'message' => $message,
            'link_url' => $base . '/list-trainings',
            'entity_type' => 'adms_training',
            'entity_id' => $trainingId,
            'priority' => $priority,
        ]);
    }

    /**
     * Gera o corpo do email para novo treinamento
     */
    private function getNewTrainingEmailBody(array $user, array $training): string
    {
        $firstName = explode(' ', $user['name'])[0];
        
        $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
        $body .= "<h2 style='color: #2c3e50;'>Novo Treinamento Obrigatório</h2>";
        $body .= "<p>Prezado(a) <strong>{$firstName}</strong>,</p>";
        $body .= "<p>Informamos que foi atribuído um novo treinamento obrigatório para o seu cargo:</p>";
        $body .= "<div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;'>";
        $body .= "<h3 style='margin: 0 0 10px 0; color: #007bff;'>{$training['nome']}</h3>";
        $body .= "<p><strong>Código:</strong> {$training['codigo']}</p>";
        $body .= "<p><strong>Versão:</strong> {$training['versao']}</p>";
        $body .= "<p><strong>Tipo:</strong> {$training['tipo']}</p>";
        if (!empty($training['carga_horaria'])) {
            $body .= "<p><strong>Carga Horária:</strong> {$training['carga_horaria']} horas</p>";
        }
        $body .= "</div>";
        $body .= "<p>Este treinamento é obrigatório para o seu cargo e deve ser realizado o quanto antes.</p>";
        $body .= "<p>Para mais informações, entre em contato com o departamento de RH.</p>";
        $body .= "<p>Atenciosamente,<br><strong>Departamento de Recursos Humanos</strong></p>";
        $body .= "</div>";

        return $body;
    }

    /**
     * Gera o corpo alternativo do email para novo treinamento
     */
    private function getNewTrainingEmailAltBody(array $user, array $training): string
    {
        $firstName = explode(' ', $user['name'])[0];
        
        $altBody = "Novo Treinamento Obrigatório\n\n";
        $altBody .= "Prezado(a) {$firstName},\n\n";
        $altBody .= "Informamos que foi atribuído um novo treinamento obrigatório para o seu cargo:\n\n";
        $altBody .= "TREINAMENTO: {$training['nome']}\n";
        $altBody .= "CÓDIGO: {$training['codigo']}\n";
        $altBody .= "VERSÃO: {$training['versao']}\n";
        $altBody .= "TIPO: {$training['tipo']}\n";
        if (!empty($training['carga_horaria'])) {
            $altBody .= "CARGA HORÁRIA: {$training['carga_horaria']} horas\n";
        }
        $altBody .= "\nEste treinamento é obrigatório para o seu cargo e deve ser realizado o quanto antes.\n\n";
        $altBody .= "Para mais informações, entre em contato com o departamento de RH.\n\n";
        $altBody .= "Atenciosamente,\nDepartamento de Recursos Humanos";

        return $altBody;
    }

    /**
     * Gera o corpo do email para treinamento pendente
     */
    private function getPendingTrainingEmailBody(array $training): string
    {
        $firstName = explode(' ', $training['user_name'])[0];
        
        $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
        $body .= "<h2 style='color: #f39c12;'>Treinamento Pendente</h2>";
        $body .= "<p>Prezado(a) <strong>{$firstName}</strong>,</p>";
        $body .= "<p>Você possui um treinamento obrigatório pendente:</p>";
        $body .= "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #f39c12; margin: 20px 0;'>";
        $body .= "<h3 style='margin: 0 0 10px 0; color: #f39c12;'>{$training['training_name']}</h3>";
        $body .= "<p><strong>Código:</strong> {$training['codigo']}</p>";
        $body .= "<p><strong>Status:</strong> <span style='color: #f39c12; font-weight: bold;'>PENDENTE</span></p>";
        $body .= "</div>";
        $body .= "<p>Por favor, realize este treinamento o quanto antes para manter sua certificação em dia.</p>";
        $body .= "<p>Para mais informações, entre em contato com o departamento de RH.</p>";
        $body .= "<p>Atenciosamente,<br><strong>Departamento de Recursos Humanos</strong></p>";
        $body .= "</div>";

        return $body;
    }

    /**
     * Gera o corpo alternativo do email para treinamento pendente
     */
    private function getPendingTrainingEmailAltBody(array $training): string
    {
        $firstName = explode(' ', $training['user_name'])[0];
        
        $altBody = "Treinamento Pendente\n\n";
        $altBody .= "Prezado(a) {$firstName},\n\n";
        $altBody .= "Você possui um treinamento obrigatório pendente:\n\n";
        $altBody .= "TREINAMENTO: {$training['training_name']}\n";
        $altBody .= "CÓDIGO: {$training['codigo']}\n";
        $altBody .= "STATUS: PENDENTE\n\n";
        $altBody .= "Por favor, realize este treinamento o quanto antes para manter sua certificação em dia.\n\n";
        $altBody .= "Para mais informações, entre em contato com o departamento de RH.\n\n";
        $altBody .= "Atenciosamente,\nDepartamento de Recursos Humanos";

        return $altBody;
    }

    /**
     * Gera o corpo do email para treinamento próximo do vencimento
     */
    private function getExpiringTrainingEmailBody(array $training): string
    {
        $firstName = explode(' ', $training['user_name'])[0];
        $daysLeft = $training['days_until_expiry'];
        
        $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
        $body .= "<h2 style='color: #e74c3c;'>Treinamento Próximo do Vencimento</h2>";
        $body .= "<p>Prezado(a) <strong>{$firstName}</strong>,</p>";
        $body .= "<p>Seu treinamento vence em <strong>{$daysLeft} dias</strong>:</p>";
        $body .= "<div style='background-color: #f8d7da; padding: 15px; border-left: 4px solid #e74c3c; margin: 20px 0;'>";
        $body .= "<h3 style='margin: 0 0 10px 0; color: #e74c3c;'>{$training['training_name']}</h3>";
        $body .= "<p><strong>Código:</strong> {$training['codigo']}</p>";
        $body .= "<p><strong>Data de Vencimento:</strong> {$training['expiry_date']}</p>";
        $body .= "<p><strong>Dias Restantes:</strong> <span style='color: #e74c3c; font-weight: bold;'>{$daysLeft} dias</span></p>";
        $body .= "</div>";
        $body .= "<p><strong>ATENÇÃO:</strong> É necessário realizar a reciclagem deste treinamento antes da data de vencimento.</p>";
        $body .= "<p>Para mais informações, entre em contato com o departamento de RH.</p>";
        $body .= "<p>Atenciosamente,<br><strong>Departamento de Recursos Humanos</strong></p>";
        $body .= "</div>";

        return $body;
    }

    /**
     * Gera o corpo alternativo do email para treinamento próximo do vencimento
     */
    private function getExpiringTrainingEmailAltBody(array $training): string
    {
        $firstName = explode(' ', $training['user_name'])[0];
        $daysLeft = $training['days_until_expiry'];
        
        $altBody = "Treinamento Próximo do Vencimento\n\n";
        $altBody .= "Prezado(a) {$firstName},\n\n";
        $altBody .= "Seu treinamento vence em {$daysLeft} dias:\n\n";
        $altBody .= "TREINAMENTO: {$training['training_name']}\n";
        $altBody .= "CÓDIGO: {$training['codigo']}\n";
        $altBody .= "DATA DE VENCIMENTO: {$training['expiry_date']}\n";
        $altBody .= "DIAS RESTANTES: {$daysLeft} dias\n\n";
        $altBody .= "ATENÇÃO: É necessário realizar a reciclagem deste treinamento antes da data de vencimento.\n\n";
        $altBody .= "Para mais informações, entre em contato com o departamento de RH.\n\n";
        $altBody .= "Atenciosamente,\nDepartamento de Recursos Humanos";

        return $altBody;
    }

    /**
     * Gera o corpo do email para treinamento vencido
     */
    private function getExpiredTrainingEmailBody(array $training): string
    {
        $firstName = explode(' ', $training['user_name'])[0];
        
        $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
        $body .= "<h2 style='color: #c0392b;'>URGENTE: Treinamento Vencido</h2>";
        $body .= "<p>Prezado(a) <strong>{$firstName}</strong>,</p>";
        $body .= "<p><strong>ATENÇÃO:</strong> Seu treinamento está VENCIDO:</p>";
        $body .= "<div style='background-color: #f5c6cb; padding: 15px; border-left: 4px solid #c0392b; margin: 20px 0;'>";
        $body .= "<h3 style='margin: 0 0 10px 0; color: #c0392b;'>{$training['training_name']}</h3>";
        $body .= "<p><strong>Código:</strong> {$training['codigo']}</p>";
        $body .= "<p><strong>Data de Vencimento:</strong> {$training['expiry_date']}</p>";
        $body .= "<p><strong>Status:</strong> <span style='color: #c0392b; font-weight: bold; font-size: 16px;'>VENCIDO</span></p>";
        $body .= "</div>";
        $body .= "<p><strong>URGENTE:</strong> É necessário realizar a reciclagem deste treinamento IMEDIATAMENTE.</p>";
        $body .= "<p>Entre em contato com o departamento de RH o quanto antes.</p>";
        $body .= "<p>Atenciosamente,<br><strong>Departamento de Recursos Humanos</strong></p>";
        $body .= "</div>";

        return $body;
    }

    /**
     * Gera o corpo alternativo do email para treinamento vencido
     */
    private function getExpiredTrainingEmailAltBody(array $training): string
    {
        $firstName = explode(' ', $training['user_name'])[0];
        
        $altBody = "URGENTE: Treinamento Vencido\n\n";
        $altBody .= "Prezado(a) {$firstName},\n\n";
        $altBody .= "ATENÇÃO: Seu treinamento está VENCIDO:\n\n";
        $altBody .= "TREINAMENTO: {$training['training_name']}\n";
        $altBody .= "CÓDIGO: {$training['codigo']}\n";
        $altBody .= "DATA DE VENCIMENTO: {$training['expiry_date']}\n";
        $altBody .= "STATUS: VENCIDO\n\n";
        $altBody .= "URGENTE: É necessário realizar a reciclagem deste treinamento IMEDIATAMENTE.\n\n";
        $altBody .= "Entre em contato com o departamento de RH o quanto antes.\n\n";
        $altBody .= "Atenciosamente,\nDepartamento de Recursos Humanos";

        return $altBody;
    }

    /**
     * Envia relatório consolidado de pendências para cada instrutor
     */
    public function sendInstructorPendingReport(): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];
        try {
            // Buscar todos os treinamentos
            $trainings = $this->trainingsRepo->getAllTrainings(1, 10000);
            $usersRepo = $this->usersRepo;
            foreach ($trainings as $training) {
                // Buscar todos os colaboradores pendentes, próximos do vencimento ou vencidos para este treinamento
                $pendings = $this->trainingUsersRepo->getUsersByTraining($training['id']);
                if (empty($pendings)) continue;

                // Buscar dados do instrutor
                $instructorEmail = null;
                $instructorName = $training['instrutor'] ?? '';
                if (!empty($training['instructor_user_id'])) {
                    $user = $usersRepo->getUser($training['instructor_user_id']);
                    $instructorEmail = $user['email'] ?? null;
                    $instructorName = $user['name'] ?? $instructorName;
                } elseif (!empty($training['instructor_email'])) {
                    $instructorEmail = $training['instructor_email'];
                }
                if (!$instructorEmail) continue;

                // Montar relatório
                $hasReciclagem = false;
                foreach ($pendings as $p) {
                    if (!empty($p['reciclagem_periodo']) && $p['reciclagem_periodo'] > 0) {
                        $hasReciclagem = true;
                        break;
                    }
                }
                $body = $this->buildInstructorReportBody($training, $pendings, $hasReciclagem);
                $altBody = strip_tags($body);
                $subject = "Relatório de pendências: Treinamento {$training['nome']}";
                $sent = SendEmailService::sendEmail($instructorEmail, $instructorName, $subject, $body, $altBody);
                if ($sent) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Falha ao enviar relatório para {$instructorEmail} ({$training['nome']})";
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        return $results;
    }

    /**
     * Monta o corpo do relatório para o instrutor
     */
    private function buildInstructorReportBody(array $training, array $pendings, bool $hasReciclagem): string
    {
        $body = "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>";
        $body .= "<h2 style='color: #2c3e50;'>Relatório de Pendências - {$training['nome']}</h2>";
        $body .= "<p>Prezado(a) responsável,</p>";
        $body .= "<p>Segue a lista de colaboradores que precisam realizar o treinamento <strong>{$training['nome']}</strong>:</p>";
        $body .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;width:100%;'>";
        $body .= "<thead><tr><th>Colaborador</th><th>E-mail</th><th>Cargo</th><th>Departamento</th><th>Status</th>";
        if ($hasReciclagem) $body .= "<th>Validade</th>";
        $body .= "</tr></thead><tbody>";
        foreach ($pendings as $p) {
            $status = $p['status'] ?? 'pendente';
            $validade = '';
            if ($hasReciclagem && !empty($p['validade'])) {
                $validade = (new \DateTime($p['validade']))->format('d/m/Y');
            }
            $body .= "<tr>";
            $body .= "<td>".htmlspecialchars($p['user_name'])."</td>";
            $body .= "<td>".htmlspecialchars($p['email'] ?? $p['user_email'] ?? '')."</td>";
            $body .= "<td>".htmlspecialchars($p['position'] ?? $p['position_name'] ?? '')."</td>";
            $body .= "<td>".htmlspecialchars($p['department'] ?? $p['department_name'] ?? '')."</td>";
            $body .= "<td>".ucfirst($status)."</td>";
            if ($hasReciclagem) $body .= "<td>".($validade ?: '-')."</td>";
            $body .= "</tr>";
        }
        $body .= "</tbody></table>";
        $body .= "<p>Por favor, agende as turmas necessárias ou entre em contato com os colaboradores.</p>";
        $body .= "<p>Atenciosamente,<br><strong>Departamento de RH</strong></p>";
        $body .= "</div>";
        return $body;
    }
} 