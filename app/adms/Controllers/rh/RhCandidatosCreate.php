<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\LgpdAuditHelper;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Services\RhCandidatoAnexoService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\Validation\ValidationRhCandidatoService;

class RhCandidatosCreate
{
    public const LGPD_TERMO_TIPO = 'curriculo_candidato';

    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = $_POST['form'] ?? [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $termoRepo = new LgpdTermosRepository();
        $this->data['lgpd_termo'] = $termoRepo->getTermoAtivoPorTipo(self::LGPD_TERMO_TIPO);

        $pageElements = [
            'title_head' => 'Cadastrar Candidato',
            'menu' => 'rh-candidatos',
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
        $form['status_processo'] = 'candidatado';
        $this->data['form'] = $form;

        $validator = new ValidationRhCandidatoService();
        $errors = $validator->validate($form);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $this->viewForm();
            return;
        }

        $termoRepo = new LgpdTermosRepository();
        $termo = $termoRepo->getTermoAtivoPorTipo(self::LGPD_TERMO_TIPO);

        if ($termo === null) {
            $_SESSION['error'] = 'Não há termo LGPD ativo para currículos. Cadastre o termo (tipo curriculo_candidato) antes de incluir candidatos.';
            $this->viewForm();
            return;
        }

        if (empty($_POST['lgpd_consent']) || $_POST['lgpd_consent'] !== '1') {
            $_SESSION['error'] = 'É obrigatório registrar o consentimento LGPD do titular (candidato) para tratar o currículo.';
            $this->viewForm();
            return;
        }

        try {
            $consentId = $this->registrarConsentimento($form, $termo);
            if ($consentId === false) {
                $_SESSION['error'] = 'Não foi possível registrar o consentimento LGPD.';
                $this->viewForm();
                return;
            }

            $form['lgpd_termo_id'] = (int) ($termo['id'] ?? 0);
            $form['lgpd_consentimento_id'] = $consentId;
            $form['lgpd_data_consentimento'] = date('Y-m-d H:i:s');

            $repo = new RhCandidatosRepository();
            $id = $repo->create($form);

            if ($id) {
                if (!empty($_FILES['curriculo']['name'] ?? '')) {
                    $this->handleUploadCurriculo((int) $id, $_FILES['curriculo']);
                }

                $_SESSION['success'] = "Candidato cadastrado com sucesso!";
                header("Location: {$_ENV['URL_ADM']}rh-candidatos-view/{$id}");
                return;
            }

            $_SESSION['error'] = "Erro: Candidato não foi cadastrado.";
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao cadastrar candidato.', [
                'form' => $form,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = "Erro inesperado ao cadastrar candidato.";
        }

        $this->viewForm();
    }

    /**
     * @param array<string, mixed> $form
     * @param array<string, mixed> $termo
     */
    private function registrarConsentimento(array $form, array $termo): int|false
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $auditHelper = new LgpdAuditHelper();
        $auditData = $auditHelper::collectTechnicalData();

        $consentData = [
            'titular_nome' => trim((string) ($form['nome'] ?? 'Candidato')),
            'titular_email' => $form['email'] ?? null,
            'finalidade' => 'Recrutamento e seleção — tratamento de currículo e dados pessoais do candidato.',
            'canal' => 'rh_candidatos_create',
            'data_consentimento' => date('Y-m-d H:i:s'),
            'status' => 'Ativo',
            'versao_termo' => $termo['versao'] ?? '1.0',
            'lgpd_termo_id' => (int) ($termo['id'] ?? 0),
            'created_by_user_id' => $userId > 0 ? $userId : null,
            'collection_method' => 'checkbox_operador_rh',
        ];

        $consentData = array_merge($consentData, $auditData);
        $consentData['consent_hash'] = $auditHelper::generateConsentHash($consentData);

        $repoConsent = new LgpdConsentimentosRepository();

        return $repoConsent->create($consentData);
    }

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
            GenerateLog::generateLog('error', 'Erro ao fazer upload de currículo.', [
                'candidato_id' => $candidatoId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
