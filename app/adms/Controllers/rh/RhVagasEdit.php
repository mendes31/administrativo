<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\LogJustificativasRepository;
use App\adms\Models\Services\SensitiveActionService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Controllers\Services\Validation\ValidationRhVagaService;

class RhVagasEdit
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $repo = new RhVagasRepository();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST['form'] ?? [];
            $this->update($repo);
            return;
        }

        if (!(int)$id) {
            GenerateLog::generateLog('error', 'Vaga não encontrada para edição', ['id' => (int)$id]);
            $_SESSION['error'] = "Vaga não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-vagas");
            return;
        }

        $vaga = $repo->getById((int)$id);
        if (!$vaga) {
            GenerateLog::generateLog('error', 'Vaga não encontrada para edição', ['id' => (int)$id]);
            $_SESSION['error'] = "Vaga não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-vagas");
            return;
        }

        // Verificar se usuário tem permissão para editar esta vaga
        if (!RhPermissionService::canEditVaga($vaga)) {
            $_SESSION['error'] = "Você não tem permissão para editar esta vaga.";
            header("Location: {$_ENV['URL_ADM']}rh-vagas-view/{$vaga['id']}");
            return;
        }

        $this->data['form'] = $vaga;
        $this->viewForm();
    }

    private function viewForm(): void
    {
        // Carregar departamentos, cargos e usuários para selects
        $deptRepo = new \App\adms\Models\Repository\DepartmentsRepository();
        $posRepo = new \App\adms\Models\Repository\PositionsRepository();
        $userRepo = new \App\adms\Models\Repository\UsersRepository();

        $this->data['departments'] = $deptRepo->getAllDepartments(1, 1000) ?: [];
        $this->data['positions'] = $posRepo->getAllPositions(1, 1000) ?: [];
        $this->data['users'] = $userRepo->getAllUsersSelect() ?: [];

        $pageElements = [
            'title_head' => 'Editar Vaga',
            'menu'       => 'rh-vagas',
            'buttonPermission' => ['RhVagas', 'RhVagasEdit'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/vagas/edit', $this->data);
        $loadView->loadView();
    }

    private function update(RhVagasRepository $repo): void
    {
        $form = $_POST['form'] ?? [];
        $this->data['form'] = $form;

        $id = (int)($form['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "ID inválido.";
            header("Location: {$_ENV['URL_ADM']}rh-vagas");
            return;
        }

        // Validação de campos da vaga
        $validator = new ValidationRhVagaService();
        $errors = $validator->validate($form);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $this->viewForm();
            return;
        }

        // Validação de senha + justificativa para edição (ação sensível)
        $motivo   = trim($_POST['motivo'] ?? '');
        $password = $_POST['password'] ?? '';
        $validacao = SensitiveActionService::validarConfirmacao($password, $motivo, true);
        if (!$validacao['success']) {
            $_SESSION['error'] = $validacao['message'];
            $this->viewForm();
            return;
        }

        try {
            $ok = $repo->update($id, $form);

            if ($ok) {
                // Vincular justificativa ao último log de alteração desta vaga
                if (!empty($_SESSION['user_id'])) {
                    $logRepo = new LogAlteracoesRepository();
                    $sql = 'SELECT id FROM adms_log_alteracoes 
                            WHERE tabela = :tabela 
                              AND objeto_id = :objeto_id 
                              AND usuario_id = :usuario_id 
                            ORDER BY id DESC 
                            LIMIT 1';
                    $conn = $logRepo->getConnection();
                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(':tabela', 'rh_vagas', \PDO::PARAM_STR);
                    $stmt->bindValue(':objeto_id', $id, \PDO::PARAM_INT);
                    $stmt->bindValue(':usuario_id', (int)$_SESSION['user_id'], \PDO::PARAM_INT);
                    $stmt->execute();
                    $ultimoLog = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($ultimoLog && isset($ultimoLog['id'])) {
                        $logJustRepo = new LogJustificativasRepository();
                        $logJustRepo->insert([
                            'log_alteracao_id'   => $ultimoLog['id'],
                            'justificativa'      => $motivo,
                            'assinatura'         => $_SESSION['user_name'] ?? 'Usuário não identificado',
                            'data_justificativa' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                $_SESSION['success'] = "Vaga atualizada com sucesso!";
                header("Location: {$_ENV['URL_ADM']}rh-vagas-view/{$id}");
                return;
            }

            $_SESSION['error'] = "Erro: Vaga não foi atualizada.";
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar vaga.', [
                'id'    => $id,
                'form'  => $form,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = "Erro inesperado ao atualizar vaga.";
        }

        $this->viewForm();
    }
}

