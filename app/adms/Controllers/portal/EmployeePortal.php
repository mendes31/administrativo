<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Repository\EmployeeTicketsRepository;
use App\adms\Models\Repository\EmploymentHistoryRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para o Portal do Colaborador (Dashboard)
 */
class EmployeePortal
{
    private array|string|null $data = null;

    public function index(): void
    {
        $employeeId = $_SESSION['user_id'] ?? 0;
        
        if (empty($employeeId)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Usuário não identificado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'dashboard');
            exit;
        }

        // Buscar solicitações pendentes
        $requestsRepo = new EmployeeRequestsRepository();
        $this->data['pending_requests'] = $requestsRepo->getAll(['employee_id' => $employeeId, 'status' => 'pending'], 1, 5);
        $this->data['total_requests'] = count($requestsRepo->getAll(['employee_id' => $employeeId], 1, 1000));

        // Buscar chamados abertos
        $ticketsRepo = new EmployeeTicketsRepository();
        $this->data['open_tickets'] = $ticketsRepo->getAll(['employee_id' => $employeeId, 'status' => 'open'], 1, 5);
        $this->data['total_tickets'] = count($ticketsRepo->getAll(['employee_id' => $employeeId], 1, 1000));
        
        // Buscar informações do colaborador
        $usersRepo = new UsersRepository();
        $this->data['employee_info'] = $usersRepo->getUser($employeeId);
        
        // Buscar histórico e calcular tempo de casa
        $historyRepo = new EmploymentHistoryRepository();
        $this->data['employment_history'] = $historyRepo->getByUserId($employeeId);
        $this->data['total_tenure'] = $historyRepo->calculateTotalTenure($employeeId);

        $this->data['total_vagas_internas'] = 0;
        try {
            $internas = (new \App\adms\Models\Repository\RhVagasRepository())->listInternas([], 1, 1);
            $this->data['total_vagas_internas'] = (int) ($internas['total'] ?? 0);
        } catch (\Throwable $e) {
            $this->data['total_vagas_internas'] = 0;
        }

        $this->data['sst_treinamentos_pendentes'] = [];
        $this->data['sst_treinamentos_pendentes_count'] = 0;
        $this->data['sst_epi_fichas_pendentes_count'] = 0;
        try {
            $this->data['sst_treinamentos_pendentes'] = (new \App\adms\Models\Services\SstPendenciasService())
                ->getPendenciasTreinamentoPorUsuario((int) $employeeId, true);
            $this->data['sst_treinamentos_pendentes_count'] = count($this->data['sst_treinamentos_pendentes']);
        } catch (\Throwable $e) {
            $this->data['sst_treinamentos_pendentes'] = [];
        }
        try {
            $fichas = (new \App\adms\Models\Repository\SstEpiFichasRepository())->getByUserId((int) $employeeId, 100);
            $this->data['sst_epi_fichas_pendentes_count'] = count(array_filter(
                $fichas,
                static fn (array $f): bool => ($f['status_assinatura'] ?? '') === 'Pendente'
            ));
        } catch (\Throwable $e) {
            $this->data['sst_epi_fichas_pendentes_count'] = 0;
        }

        $pageElements = [
            'title_head' => 'Portal do Colaborador',
            'menu' => 'employee-portal',
            'buttonPermission' => [
                'ListEmployeeRequests',
                'CreateEmployeeRequest',
                'ListEmployeeTickets',
                'CreateEmployeeTicket',
                'MyPayrollDocuments',
                'VagasInternas',
                'MyEpiDeliveries',
                'MySstTreinamentos',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/dashboard', $this->data);
        $loadView->loadView();
    }
}

