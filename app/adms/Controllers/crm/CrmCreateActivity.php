<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmActivitiesRepository;

/**
 * Controller para criar Atividade CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmCreateActivity
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['msg'] = "Método não permitido.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        $data = [
            'type' => $_POST['type'] ?? 'Tarefa',
            'partner_id' => $_POST['partner_id'] ?? null,
            'opportunity_id' => $_POST['opportunity_id'] ?? null,
            'responsible_user_id' => $_POST['responsible_user_id'] ?? $_SESSION['user_id'],
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'scheduled_date' => $_POST['scheduled_date'] ?? null,
            'duration_minutes' => $_POST['duration_minutes'] ?? null,
            'status' => 'Pendente',
            'priority' => $_POST['priority'] ?? 'Média',
            'reminder_date' => $_POST['reminder_date'] ?? null,
        ];

        // Validações
        if (empty($data['title'])) {
            $_SESSION['msg'] = "O título da atividade é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            $this->redirectBack($data);
            exit;
        }

        if (empty($data['opportunity_id']) && empty($data['partner_id'])) {
            $_SESSION['msg'] = "A atividade deve estar vinculada a uma oportunidade ou parceiro.";
            $_SESSION['msg_type'] = "danger";
            $this->redirectBack($data);
            exit;
        }

        $activitiesRepo = new CrmActivitiesRepository();
        
        // Verificar se é um reenvio com confirmação de conflito
        $forceSchedule = isset($_POST['force_schedule']) && $_POST['force_schedule'] === '1';
        
        // Verificar conflito de horário (apenas se não for forçado)
        if (!$forceSchedule && !empty($data['scheduled_date'])) {
            $conflicts = $activitiesRepo->checkScheduleConflict(
                (int)$data['responsible_user_id'],
                $data['scheduled_date'],
                $data['duration_minutes'] ? (int)$data['duration_minutes'] : null
            );
            
            if (!empty($conflicts)) {
                // Armazenar dados na sessão para modal de confirmação
                $_SESSION['schedule_conflict'] = [
                    'conflicts' => $conflicts,
                    'pending_data' => $data
                ];
                
                $this->redirectBack($data);
                exit;
            }
        }
        
        $result = $activitiesRepo->createActivity($data);

        if ($result) {
            $_SESSION['msg'] = "Atividade criada com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao criar atividade.";
            $_SESSION['msg_type'] = "danger";
        }

        $this->redirectBack($data);
    }

    private function redirectBack(array $data): void
    {
        if (!empty($data['opportunity_id'])) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-opportunity/" . $data['opportunity_id']);
        } elseif (!empty($data['partner_id'])) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-partner/" . $data['partner_id']);
        } else {
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
        }
        exit;
    }
}

