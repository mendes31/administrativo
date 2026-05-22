<?php

declare(strict_types=1);

namespace App\adms\Controllers\Services;

use App\adms\Helpers\InternalPushNotificationHelper;
use App\adms\Models\Repository\StrategicPlansRepository;
use App\adms\Models\Repository\UsersRepository;

class StrategicPlanNotificationService
{
    private StrategicPlansRepository $plansRepo;
    private UsersRepository $usersRepo;

    public function __construct()
    {
        $this->plansRepo = new StrategicPlansRepository();
        $this->usersRepo = new UsersRepository();
    }

    /**
     * Enviar notificação de nova observação
     */
    public function sendObservationNotification(int $strategicPlanId, int $authorUserId, string $observation): bool
    {
        try {
            // Buscar dados do plano
            $plan = $this->plansRepo->getById($strategicPlanId);
            if (!$plan) {
                return false;
            }

            // Buscar dados do autor
            $author = $this->usersRepo->getUser($authorUserId);
            if (!$author) {
                return false;
            }

            // Determinar destinatários
            $recipients = $this->getNotificationRecipients($plan, $authorUserId);

            if (empty($recipients)) {
                return true; // Não há destinatários, mas não é erro
            }

            // Preparar dados do email
            $subject = "Nova observação no Plano Estratégico: {$plan['title']}";
            $message = $this->buildEmailMessage($plan, $author, $observation);

            $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
            $link = $base . '/view-strategic-plan/' . $strategicPlanId;
            $obsSnippet = mb_strlen($observation) > 120 ? mb_substr($observation, 0, 117) . '...' : $observation;

            foreach ($recipients as $recipient) {
                $this->sendEmail($recipient['email'], $recipient['name'], $subject, $message);

                $recipientUserId = (int) ($recipient['id'] ?? 0);
                if ($recipientUserId > 0) {
                    InternalPushNotificationHelper::notifyUser([
                        'user_id' => $recipientUserId,
                        'type' => 'strategic_plan_observation',
                        'title' => 'Plano estratégico — nova observação',
                        'message' => ($plan['title'] ?? 'Plano') . ': ' . $obsSnippet,
                        'link_url' => $link,
                        'entity_type' => 'adms_strategic_plan',
                        'entity_id' => $strategicPlanId,
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log("Erro ao enviar notificação de observação: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Determinar destinatários da notificação
     */
    private function getNotificationRecipients(array $plan, int $authorUserId): array
    {
        $recipients = [];

        // Adicionar responsável pelo plano (se não for o autor)
        if ($plan['responsible_id'] != $authorUserId) {
            $responsible = $this->usersRepo->getUser($plan['responsible_id']);
            if ($responsible) {
                $recipients[] = [
                    'id' => (int) ($responsible['id'] ?? 0),
                    'email' => $responsible['email'],
                    'name' => $responsible['name'],
                ];
            }
        }

        // Adicionar usuários do mesmo departamento (se não for o autor)
        $departmentUsers = $this->getDepartmentUsers($plan['department_id'], $authorUserId);
        foreach ($departmentUsers as $user) {
            $recipients[] = [
                'id' => (int) ($user['id'] ?? 0),
                'email' => $user['email'],
                'name' => $user['name'],
            ];
        }

        // Adicionar super administradores
        $superAdmins = $this->getSuperAdministrators($authorUserId);
        foreach ($superAdmins as $admin) {
            $recipients[] = [
                'id' => (int) ($admin['id'] ?? 0),
                'email' => $admin['email'],
                'name' => $admin['name'],
            ];
        }

        // Remover duplicatas por e-mail
        $uniqueRecipients = [];
        $emails = [];
        foreach ($recipients as $recipient) {
            if (!in_array($recipient['email'], $emails, true)) {
                $uniqueRecipients[] = $recipient;
                $emails[] = $recipient['email'];
            }
        }

        return $uniqueRecipients;
    }

    /**
     * Buscar usuários do departamento
     */
    private function getDepartmentUsers(int $departmentId, int $excludeUserId): array
    {
        $sql = 'SELECT id, name, email FROM adms_users 
                WHERE user_department_id = :department_id 
                AND id != :exclude_user_id 
                AND status = "Ativo"';
        
        $stmt = $this->plansRepo->getConnection()->prepare($sql);
        $stmt->execute([
            'department_id' => $departmentId,
            'exclude_user_id' => $excludeUserId
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Buscar super administradores
     */
    private function getSuperAdministrators(int $excludeUserId): array
    {
        $sql = 'SELECT u.id, u.name, u.email 
                FROM adms_users u
                JOIN adms_users_access_levels ual ON u.id = ual.adms_user_id
                WHERE ual.adms_access_level_id = 1 
                AND u.id != :exclude_user_id
                AND u.status = "Ativo"';
        
        $stmt = $this->plansRepo->getConnection()->prepare($sql);
        $stmt->execute(['exclude_user_id' => $excludeUserId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Construir mensagem do email
     */
    private function buildEmailMessage(array $plan, array $author, string $observation): string
    {
        $planUrl = $_ENV['URL_ADM'] . 'view-strategic-plan/' . $plan['id'];
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;'>
                    Nova Observação no Plano Estratégico
                </h2>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>{$plan['title']}</h3>
                    <p><strong>Departamento:</strong> {$plan['dep_name']}</p>
                    <p><strong>Responsável:</strong> {$plan['user_name']}</p>
                    <p><strong>Status:</strong> {$plan['status']}</p>
                </div>
                
                <div style='background-color: #e8f4fd; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0;'>
                    <h4 style='color: #2c3e50; margin-top: 0;'>Nova Observação:</h4>
                    <p style='margin: 0;'>{$observation}</p>
                    <p style='margin: 10px 0 0 0; font-size: 12px; color: #666;'>
                        <strong>Por:</strong> {$author['name']} | 
                        <strong>Data:</strong> " . date('d/m/Y H:i') . "
                    </p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$planUrl}' 
                       style='background-color: #3498db; color: white; padding: 12px 24px; 
                              text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Ver Plano Completo
                    </a>
                </div>
                
                <div style='border-top: 1px solid #ddd; padding-top: 15px; font-size: 12px; color: #666;'>
                    <p>Esta é uma notificação automática do sistema de Planejamento Estratégico.</p>
                    <p>Para parar de receber estas notificações, entre em contato com o administrador do sistema.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $message;
    }

    /**
     * Enviar email
     */
    private function sendEmail(string $to, string $name, string $subject, string $message): bool
    {
        try {
            // Configurações do email
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . ($_ENV['EMAIL_FROM_NAME'] ?? 'Sistema Administrativo') . ' <' . ($_ENV['EMAIL_FROM'] ?? 'noreply@empresa.com') . '>',
                'Reply-To: ' . ($_ENV['EMAIL_REPLY_TO'] ?? 'noreply@empresa.com'),
                'X-Mailer: PHP/' . phpversion()
            ];

            $headersString = implode("\r\n", $headers);

            // Enviar email
            $success = mail($to, $subject, $message, $headersString);

            if (!$success) {
                error_log("Falha ao enviar email para: {$to}");
                return false;
            }

            return true;
        } catch (\Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
}
