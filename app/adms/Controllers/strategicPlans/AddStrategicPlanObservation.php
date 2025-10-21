<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicPlans;

use App\adms\Models\Repository\StrategicPlanObservationsRepository;
use App\adms\Controllers\Services\StrategicPlanNotificationService;

class AddStrategicPlanObservation
{
    private StrategicPlanObservationsRepository $observationsRepo;
    private StrategicPlanNotificationService $notificationService;

    public function __construct()
    {
        $this->observationsRepo = new StrategicPlanObservationsRepository();
        $this->notificationService = new StrategicPlanNotificationService();
    }

    /**
     * Adicionar nova observação
     */
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
            exit;
        }

        // Validar dados
        $strategicPlanId = filter_input(INPUT_POST, 'strategic_plan_id', FILTER_VALIDATE_INT);
        $observation = filter_input(INPUT_POST, 'observation', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$strategicPlanId || !$observation) {
            $_SESSION['msg'] = "Dados inválidos!";
            $_SESSION['msg_type'] = "danger";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
            exit;
        }

        // Verificar se o usuário está logado
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['msg'] = "Usuário não logado!";
            $_SESSION['msg_type'] = "danger";
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        // Verificar se o usuário tem permissão para adicionar observações a este plano
        $plansRepo = new \App\adms\Models\Repository\StrategicPlansRepository();
        $plan = $plansRepo->getById($strategicPlanId);
        
        if (!$plan) {
            $_SESSION['msg'] = "Plano estratégico não encontrado!";
            $_SESSION['msg_type'] = "danger";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
            exit;
        }

        // Verificar se o usuário tem permissão para adicionar observações a este plano
        if (!$this->hasFullAccess()) {
            $userDepartmentId = $_SESSION['user_department_id'] ?? null;
            if ($userDepartmentId && $plan['department_id'] != $userDepartmentId) {
                $_SESSION['msg'] = "Você não tem permissão para adicionar observações a este plano!";
                $_SESSION['msg_type'] = "danger";
                header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
                exit;
            }
        }

        try {
            // Adicionar observação
            $observationId = $this->observationsRepo->addObservation(
                $strategicPlanId,
                $_SESSION['user_id'],
                $observation
            );

            if ($observationId) {
                // Enviar notificação por email
                $this->notificationService->sendObservationNotification(
                    $strategicPlanId,
                    $_SESSION['user_id'],
                    $observation
                );

                $_SESSION['msg'] = "Observação adicionada com sucesso!";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Erro ao adicionar observação!";
                $_SESSION['msg_type'] = "danger";
            }
        } catch (\Exception $e) {
            $_SESSION['msg'] = "Erro interno: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
        }

        // Redirecionar de volta para a visualização do plano
        header('Location: ' . $_ENV['URL_ADM'] . 'view-strategic-plan/' . $strategicPlanId);
        exit;
    }

    /**
     * Verifica se o usuário tem acesso total (super admin ou departamento Diretoria)
     */
    private function hasFullAccess(): bool
    {
        // Super administrador (nível 1) tem acesso total
        if (isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1) {
            return true;
        }

        // Usuários do departamento "Diretoria" também têm acesso total
        if (isset($_SESSION['user_department']) && $_SESSION['user_department'] === 'Diretoria') {
            return true;
        }

        return false;
    }
}
