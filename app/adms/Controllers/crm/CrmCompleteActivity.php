<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmActivitiesRepository;

/**
 * Controller para marcar Atividade como concluída
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmCompleteActivity
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da atividade não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        $data = [
            'outcome' => $_POST['outcome'] ?? 'Concluída',
            'outcome_notes' => $_POST['outcome_notes'] ?? null,
        ];

        $activitiesRepo = new CrmActivitiesRepository();
        
        // Buscar atividade para redirect
        $activity = $activitiesRepo->getActivityById((int)$id);
        
        if (!$activity) {
            $_SESSION['msg'] = "Atividade não encontrada.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        $result = $activitiesRepo->completeActivity((int)$id, $data);

        if ($result) {
            $_SESSION['msg'] = "Atividade marcada como concluída!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao concluir atividade.";
            $_SESSION['msg_type'] = "danger";
        }

        // Redirecionar de volta
        $redirectTo = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? '';
        
        if ($redirectTo === 'crm-list-activities') {
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-activities");
        } elseif (!empty($activity['opportunity_id'])) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-opportunity/" . $activity['opportunity_id']);
        } elseif (!empty($activity['partner_id'])) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-partner/" . $activity['partner_id']);
        } else {
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
        }
        exit;
    }
}

