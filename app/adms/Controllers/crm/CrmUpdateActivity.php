<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmActivitiesRepository;

/**
 * Controller para atualizar Atividade CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmUpdateActivity
{
    public function index(string|int|null $id = null): void
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['msg'] = "Requisição inválida.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-activities");
            exit;
        }

        $repository = new CrmActivitiesRepository();
        
        $data = [
            'id' => (int)$id,
            'type' => $_POST['type'] ?? '',
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'scheduled_date' => $_POST['scheduled_date'] ?? null,
            'duration_minutes' => $_POST['duration_minutes'] ?? null,
            'partner_id' => $_POST['partner_id'] ?? null,
            'opportunity_id' => $_POST['opportunity_id'] ?? null,
            'status' => $_POST['status'] ?? 'Pendente',
            'priority' => $_POST['priority'] ?? 'Média',
        ];

        // Validações básicas
        if (empty($data['type']) || empty($data['title'])) {
            $_SESSION['msg'] = "Tipo e Título são obrigatórios.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . ($_POST['redirect_to'] ?? 'crm-list-activities'));
            exit;
        }
        
        // Verificar conflito de horário (para edição)
        if (!empty($data['scheduled_date'])) {
            // Buscar responsible_user_id da atividade atual
            $currentActivity = $repository->getActivityById((int)$id);
            if ($currentActivity) {
                $responsibleUserId = $currentActivity['responsible_user_id'];
                
                $conflicts = $repository->checkScheduleConflict(
                    (int)$responsibleUserId,
                    $data['scheduled_date'],
                    $data['duration_minutes'] ? (int)$data['duration_minutes'] : null,
                    (int)$id // Excluir a atividade atual da verificação
                );
                
                if (!empty($conflicts)) {
                    $conflictDetails = [];
                    foreach ($conflicts as $conflict) {
                        $conflictDetails[] = "• {$conflict['title']} ({$conflict['start']} - {$conflict['end']})";
                    }
                    
                    $_SESSION['msg'] = "⚠️ CONFLITO DE HORÁRIO DETECTADO!\n\n" .
                                       "Já existe(m) atividade(s) agendada(s) neste horário:\n\n" .
                                       implode("\n", $conflictDetails) . "\n\n" .
                                       "A atividade não foi atualizada.";
                    $_SESSION['msg_type'] = "warning";
                    
                    header("Location: " . $_ENV['URL_ADM'] . ($_POST['redirect_to'] ?? 'crm-list-activities'));
                    exit;
                }
            }
        }

        $result = $repository->updateActivity($data);

        if ($result) {
            $_SESSION['msg'] = "Atividade atualizada com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao atualizar atividade.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . ($_POST['redirect_to'] ?? 'crm-list-activities'));
        exit;
    }
}

