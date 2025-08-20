<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\TrainingApplicationsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class CreateTestData
{
    public function index(): void
    {
        $data = [
            'title_head' => 'Criar Dados de Teste para Notificações',
            'menu' => 'gestao_treinamentos',
            'buttonPermission' => ['CreateTestData'],
        ];

        // Adiciona dados de layout e permissões
        $pageLayout = new PageLayoutService();
        $data = $pageLayout->configurePageElements($data);

        // Carregar a view
        $loadView = new LoadViewService('adms/Views/trainings/createTestData', $data);
        $loadView->loadView();
    }

    public function createTestData(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'create-test-data');
            exit;
        }

        try {
            $this->generateTestData();
            $_SESSION['msg'] = "Dados de teste criados com sucesso! Agora você pode testar as notificações.";
            $_SESSION['msg_type'] = "success";
        } catch (\Exception $e) {
            $_SESSION['msg'] = "Erro ao criar dados de teste: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'create-test-data');
        exit;
    }

    private function generateTestData(): void
    {
        $trainingUsersRepo = new TrainingUsersRepository();
        $trainingsRepo = new TrainingsRepository();
        $usersRepo = new UsersRepository();
        $applicationsRepo = new TrainingApplicationsRepository();

        // Buscar usuários existentes
        $users = $usersRepo->getAllUsers(1, 10); // Primeiros 10 usuários
        if (empty($users)) {
            throw new \Exception("Nenhum usuário encontrado. Crie usuários primeiro.");
        }

        // Buscar treinamentos existentes
        $trainings = $trainingsRepo->getAllTrainings(1, 5); // Primeiros 5 treinamentos
        if (empty($trainings)) {
            throw new \Exception("Nenhum treinamento encontrado. Crie treinamentos primeiro.");
        }

        $created = 0;

        foreach ($users as $user) {
            foreach ($trainings as $training) {
                // Criar vínculo de treinamento
                $trainingUsersRepo->insertOrUpdate($user['id'], $training['id'], 'dentro_do_prazo');

                // Criar aplicação vencida (30 dias atrás)
                $dataVencida = date('Y-m-d', strtotime('-30 days'));
                $applicationsRepo->insert([
                    'adms_user_id' => $user['id'],
                    'adms_training_id' => $training['id'],
                    'data_realizacao' => $dataVencida,
                    'nota' => 8.5,
                    'status' => 'concluido',
                    'observacoes' => 'Dados de teste - aplicação vencida'
                ]);

                // Atualizar status para vencido
                $trainingUsersRepo->updateStatus($user['id'], $training['id'], 'vencido');
                $created++;
            }
        }

        // Criar alguns treinamentos próximos do vencimento (15 dias atrás)
        foreach (array_slice($users, 0, 3) as $user) {
            foreach (array_slice($trainings, 0, 2) as $training) {
                $dataProximoVencimento = date('Y-m-d', strtotime('-15 days'));
                $applicationsRepo->insert([
                    'adms_user_id' => $user['id'],
                    'adms_training_id' => $training['id'],
                    'data_realizacao' => $dataProximoVencimento,
                    'nota' => 9.0,
                    'status' => 'concluido',
                    'observacoes' => 'Dados de teste - próximo vencimento'
                ]);

                $trainingUsersRepo->updateStatus($user['id'], $training['id'], 'proximo_vencimento');
                $created++;
            }
        }
    }
} 