<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\Validation\ValidationRhCandidatoService;

class RhCandidatosCreate
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = $_POST['form'] ?? [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validação CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!CSRFHelper::validateCSRFToken('form_create_rh_candidato', $csrfToken)) {
                $_SESSION['error'] = "Token de segurança inválido ou expirado. Recarregue a página e tente novamente.";
                $this->viewForm();
                return;
            }

            $this->store();
            return;
        }

        $this->viewForm();
    }

    private function viewForm(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Candidato',
            'menu'       => 'rh-candidatos',
            'buttonPermission' => ['RhCandidatos', 'RhCandidatosCreate'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/candidatos/create', $this->data);
        $loadView->loadView();
    }

    private function store(): void
    {
        $form = $_POST['form'] ?? [];
        $this->data['form'] = $form;

        // Validação de campos do candidato
        $validator = new ValidationRhCandidatoService();
        $errors = $validator->validate($form);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $this->viewForm();
            return;
        }

        try {
            $repo = new RhCandidatosRepository();
            $id = $repo->create($form);

            if ($id) {
                // Upload opcional de currículo
                if (!empty($_FILES['curriculo']['name'] ?? '')) {
                    $this->handleUploadCurriculo($id, $_FILES['curriculo']);
                }

                $_SESSION['success'] = "Candidato cadastrado com sucesso!";
                header("Location: {$_ENV['URL_ADM']}rh-candidatos-view/{$id}");
                return;
            }

            $_SESSION['error'] = "Erro: Candidato não foi cadastrado.";
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao cadastrar candidato.', [
                'form'  => $form,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = "Erro inesperado ao cadastrar candidato.";
        }

        $this->viewForm();
    }

    /**
     * Manipula upload de currículo para o candidato informado.
     */
    private function handleUploadCurriculo(int $candidatoId, array $file): void
    {
        try {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return;
            }

            $allowed = ['pdf', 'doc', 'docx'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $_SESSION['error'] = "Tipo de arquivo não permitido para currículo. Use PDF ou DOC/DOCX.";
                return;
            }

            $projectRoot = dirname(__DIR__, 4);
            $baseDir = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'adms' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'rh_candidatos';
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0775, true);
            }

            $candDir = $baseDir . DIRECTORY_SEPARATOR . $candidatoId;
            if (!is_dir($candDir)) {
                mkdir($candDir, 0775, true);
            }

            $safeName = uniqid('cv_', true) . '.' . $ext;
            $destPath = $candDir . DIRECTORY_SEPARATOR . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                $_SESSION['error'] = "Erro ao salvar arquivo de currículo.";
                return;
            }

            // Caminho relativo usado pelo ServeFile
            $relativePath = 'rh_candidatos/' . $candidatoId . '/' . $safeName;

            $repo = new RhCandidatosRepository();
            $repo->addAnexo($candidatoId, [
                'tipo'           => 'curriculo',
                'arquivo_caminho'=> $relativePath,
                'nome_original'  => $file['name'],
            ]);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao fazer upload de currículo.', [
                'candidato_id' => $candidatoId,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}


