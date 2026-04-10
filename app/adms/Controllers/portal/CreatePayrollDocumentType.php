<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UrlAdmHelper;
use App\adms\Models\Repository\PayrollDocumentTypesRepository;
use App\adms\Views\Services\LoadViewService;

class CreatePayrollDocumentType
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        $pageElements = [
            'title_head' => 'Criar tipo de documento (RH)',
            'menu' => 'create-payroll-document-type',
            'buttonPermission' => ['ListPayrollDocumentTypes'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/create_payroll_document_type', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_payroll_document_type', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . UrlAdmHelper::to('create-payroll-document-type'));
            exit;
        }

        $code = preg_replace('/[^a-z_]/', '', strtolower(trim((string)($_POST['code'] ?? ''))));
        $name = trim((string)($_POST['name'] ?? ''));
        if ($code === '' || $name === '') {
            $_SESSION['error'] = 'Código e nome são obrigatórios.';
            return;
        }
        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $code)) {
            $_SESSION['error'] = 'Código deve começar com letra e conter apenas letras minúsculas, números e underscore.';
            return;
        }

        $signatureAuth = strtolower(trim((string)($_POST['signature_auth'] ?? 'none')));
        if (!self::allowedSignatureAuth($signatureAuth)) {
            $signatureAuth = 'none';
        }

        $rulesJson = self::normalizeRulesJson((string)($_POST['rules_json'] ?? ''));
        if ($rulesJson === false) {
            $_SESSION['error'] = 'Regras adicionais (JSON) inválidas. Use JSON válido ou deixe em branco.';
            return;
        }

        $repo = new PayrollDocumentTypesRepository();
        if ($repo->findByCode($code) !== null) {
            $_SESSION['error'] = 'Este código já está em uso.';
            return;
        }

        $payload = [
            'code' => $code,
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

        $id = $repo->create($payload);
        if ($id > 0) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Tipo de documento criado com sucesso.</div>';
            GenerateLog::generateLog('info', 'PayrollDocumentType criado.', ['id' => $id, 'code' => $code]);
            header('Location: ' . UrlAdmHelper::to('list-payroll-document-types'));
            exit;
        }

        $_SESSION['error'] = 'Erro ao criar tipo. Tente novamente.';
    }

    /** @return true|false false = JSON inválido */
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

    public static function allowedSignatureAuth(string $v): bool
    {
        return in_array($v, ['none', 'password', 'otp_whatsapp', 'otp_email', 'otp_whatsapp_fallback_email'], true);
    }
}
