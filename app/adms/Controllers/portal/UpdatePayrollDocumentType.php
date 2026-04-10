<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UrlAdmHelper;
use App\adms\Models\Repository\PayrollDocumentTypesRepository;
use App\adms\Views\Services\LoadViewService;

class UpdatePayrollDocumentType
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if ((int)$id <= 0) {
            $_SESSION['error'] = 'Registo não encontrado.';
            header('Location: ' . UrlAdmHelper::to('list-payroll-document-types'));
            exit;
        }

        $repo = new PayrollDocumentTypesRepository();
        $this->data['type'] = $repo->findById((int)$id);
        if ($this->data['type'] === null) {
            $_SESSION['error'] = 'Registo não encontrado.';
            header('Location: ' . UrlAdmHelper::to('list-payroll-document-types'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
        }

        $pageElements = [
            'title_head' => 'Editar tipo de documento (RH)',
            'menu' => 'update-payroll-document-type',
            'buttonPermission' => ['ListPayrollDocumentTypes'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/update_payroll_document_type', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_payroll_document_type', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['error'] = 'Nome é obrigatório.';
            return;
        }

        $signatureAuth = strtolower(trim((string)($_POST['signature_auth'] ?? 'none')));
        if (!CreatePayrollDocumentType::allowedSignatureAuth($signatureAuth)) {
            $signatureAuth = 'none';
        }

        $rulesJson = self::normalizeRulesJson((string)($_POST['rules_json'] ?? ''));
        if ($rulesJson === false) {
            $_SESSION['error'] = 'Regras adicionais (JSON) inválidas. Use JSON válido ou deixe em branco.';
            return;
        }

        $payload = [
            'name' => $name,
            'description' => trim((string)($_POST['description'] ?? '')) ?: null,
            'default_title_prefix' => trim((string)($_POST['default_title_prefix'] ?? '')) ?: null,
            'icon' => trim((string)($_POST['icon'] ?? '')) ?: null,
            'requires_signature' => isset($_POST['requires_signature']) && $_POST['requires_signature'] === '1',
            'signature_auth' => $signatureAuth,
            'require_auth_download' => isset($_POST['require_auth_download']) && $_POST['require_auth_download'] === '1',
            'rules_json' => $rulesJson,
            'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === '1',
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];

        $repo = new PayrollDocumentTypesRepository();
        if ($repo->update($id, $payload)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Tipo de documento atualizado.</div>';
            GenerateLog::generateLog('info', 'PayrollDocumentType atualizado.', ['id' => $id]);
            header('Location: ' . UrlAdmHelper::to('list-payroll-document-types'));
            exit;
        }

        $_SESSION['error'] = 'Erro ao atualizar. Tente novamente.';
    }

    /** @return string|null|false */
    private static function normalizeRulesJson(string $raw): string|null|false
    {
        $t = trim($raw);
        if ($t === '') {
            return null;
        }
        json_decode($t, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $t;
    }
}
