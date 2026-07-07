<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UrlAdmHelper;
use App\adms\Models\Repository\WhistleblowingConfigRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Configuração do Canal de Denúncias (cron + criptografia) — sem depender do .env.
 */
class WhistleblowingConfig
{
    private array $data = [];

    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->save();
            return;
        }

        $repo = new WhistleblowingConfigRepository();
        $this->data['config_row'] = $repo->getRow();
        $this->data['token_configured'] = $repo->hasHttpCronToken();
        $this->data['key_configured'] = $repo->hasEncryptionKey();
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('whistleblowing_config');

        $configId = (int) ($this->data['config_row']['id'] ?? 0);
        if ($configId > 0) {
            $this->data['log_resumo'] = LogResumoService::getResumo(
                'adms_whistleblowing_config',
                $configId,
                UrlAdmHelper::to('whistleblowing-config')
            );
        }

        $pageElements = [
            'title_head' => 'Configuração — Canal de Denúncias',
            'menu' => 'whistleblowing-config',
            'buttonPermission' => ['WhistleblowingConfig'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/whistleblowing/config', $this->data))->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('whistleblowing_config', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));
            exit;
        }

        $repo = new WhistleblowingConfigRepository();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'clear_token') {
            $repo->saveHttpCronToken('');
            $_SESSION['msg'] = 'Token do cron removido.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));
            exit;
        }

        if ($action === 'clear_key') {
            $_SESSION['msg'] = 'A chave de criptografia não pode ser removida pela interface — defina uma nova chave se precisar rotacionar.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));
            exit;
        }

        if ($action === 'save_token') {
            $token = trim((string) ($_POST['http_cron_token'] ?? ''));
            if ($token !== '' && strlen($token) < 16) {
                $_SESSION['msg'] = 'Use um token com pelo menos 16 caracteres.';
                $_SESSION['msg_type'] = 'warning';
                header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));
                exit;
            }
            if ($token !== '') {
                $repo->saveHttpCronToken($token);
                $_SESSION['msg'] = 'Token do cron guardado com sucesso.';
                $_SESSION['msg_type'] = 'success';
            }
            header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));
            exit;
        }

        if ($action === 'save_key') {
            $key = trim((string) ($_POST['encryption_key'] ?? ''));
            if ($key !== '' && strlen($key) < 32) {
                $_SESSION['msg'] = 'A chave deve ter pelo menos 32 caracteres (recomendado: 64 hex aleatórios).';
                $_SESSION['msg_type'] = 'warning';
                header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));
                exit;
            }
            if ($key !== '') {
                if ($repo->hasEncryptionKey()) {
                    $_SESSION['msg'] = 'Chave de criptografia atualizada. Denúncias antigas só poderão ser lidas se a chave anterior for restaurada.';
                    $_SESSION['msg_type'] = 'warning';
                } else {
                    $_SESSION['msg'] = 'Chave de criptografia guardada com sucesso.';
                    $_SESSION['msg_type'] = 'success';
                }
                $repo->saveEncryptionKey($key);
            }
            header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));
            exit;
        }

        header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));
        exit;
    }
}
