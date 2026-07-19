<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\LogJustificativasRepository;
use App\adms\Models\Services\RhCandidatoAnexoService;
use App\adms\Models\Services\SensitiveActionService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\Validation\ValidationRhCandidatoService;

class RhCandidatosEdit
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $repo = new RhCandidatosRepository();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST['form'] ?? [];
            $this->update($repo);
            return;
        }

        if (!(int)$id) {
            GenerateLog::generateLog('error', 'Candidato não encontrado para edição', ['id' => (int)$id]);
            $_SESSION['error'] = "Candidato não encontrado!";
            header("Location: {$_ENV['URL_ADM']}rh-candidatos");
            return;
        }

        $candidato = $repo->getById((int)$id);
        if (!$candidato) {
            GenerateLog::generateLog('error', 'Candidato não encontrado para edição', ['id' => (int)$id]);
            $_SESSION['error'] = "Candidato não encontrado!";
            header("Location: {$_ENV['URL_ADM']}rh-candidatos");
            return;
        }

        if (!\App\adms\Models\Services\RhCandidatoPermissionService::canEditCandidato((int) $id)) {
            $_SESSION['error'] = 'Acesso não autorizado a este candidato.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos');
            return;
        }

        $this->data['form']   = $candidato;
        $this->data['anexos'] = $repo->getAnexosByCandidato((int)$id);
        $this->viewForm();
    }

    private function viewForm(): void
    {
        $pageElements = [
            'title_head' => 'Editar Candidato',
            'menu'       => 'rh-candidatos',
            'buttonPermission' => ['RhCandidatos', 'RhCandidatosEdit'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/candidatos/edit', $this->data);
        $loadView->loadView();
    }

    private function update(RhCandidatosRepository $repo): void
    {
        $form = $_POST['form'] ?? [];
        $this->data['form'] = $form;

        $id = (int)($form['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "ID inválido.";
            header("Location: {$_ENV['URL_ADM']}rh-candidatos");
            return;
        }

        if (!\App\adms\Models\Services\RhCandidatoPermissionService::canEditCandidato($id)) {
            $_SESSION['error'] = 'Acesso não autorizado a este candidato.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos');
            return;
        }

        $candidatoAtual = $repo->getById($id);
        if (!$candidatoAtual) {
            $_SESSION['error'] = 'Candidato não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos');
            return;
        }

        // status_processo é projeção: não aceitar edição livre do formulário
        $form['status_processo'] = \App\adms\Models\Services\RhCandidatoStatusProcessoProjector::resolveForManualEdit(
            (string) ($candidatoAtual['status_processo'] ?? ''),
            $repo->listStatusVinculosAtivos($id),
            !empty($form['marcar_contratado'])
        );
        $this->data['form'] = $form;

        // Validação de campos do candidato
        $validator = new ValidationRhCandidatoService();
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
                // Upload opcional de novo currículo/anexo
                if (!empty($_FILES['curriculo']['name'] ?? '')) {
                    $this->handleUploadCurriculo($id, $_FILES['curriculo']);
                }

                // Vincular justificativa ao último log de alteração deste candidato
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
                    $stmt->bindValue(':tabela', 'rh_candidatos', \PDO::PARAM_STR);
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

                $_SESSION['success'] = "Candidato atualizado com sucesso!";
                header("Location: {$_ENV['URL_ADM']}rh-candidatos-view/{$id}");
                return;
            }

            $_SESSION['error'] = "Erro: Candidato não foi atualizado.";
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar candidato.', [
                'id'    => $id,
                'form'  => $form,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = "Erro inesperado ao atualizar candidato.";
        }

        $this->viewForm();
    }

    /**
     * Manipula upload de currículo/anexo para o candidato informado.
     */
    private function handleUploadCurriculo(int $candidatoId, array $file): void
    {
        try {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
                && trim((string) ($file['name'] ?? '')) === '') {
                return;
            }

            $stored = RhCandidatoAnexoService::storeCurriculo($candidatoId, $file);
            if (!($stored['ok'] ?? false)) {
                $_SESSION['error'] = $stored['error'] ?? 'Erro ao salvar currículo.';
                return;
            }

            $repo = new RhCandidatosRepository();
            $repo->addAnexo($candidatoId, [
                'tipo' => 'curriculo',
                'arquivo_caminho' => $stored['relative_path'],
                'nome_original' => $stored['original_name'],
            ]);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao fazer upload de currículo (edição).', [
                'candidato_id' => $candidatoId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


