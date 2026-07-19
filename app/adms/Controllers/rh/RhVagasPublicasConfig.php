<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhVagasPublicasConfigRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Configuração do portal público de vagas (CAPTCHA ativável).
 */
final class RhVagasPublicasConfig
{
    private array $data = [];

    public function index(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->save();
            return;
        }

        $repo = new RhVagasPublicasConfigRepository();
        $config = $repo->getConfig();

        $this->data = [
            'title_head' => 'Configuração — Portal de Vagas',
            'menu' => 'rh-vagas-publicas-config',
            'buttonPermission' => ['RhVagasPublicasConfig', 'RhVagas'],
            'csrf_token' => CSRFHelper::generateCSRFToken('form_rh_vagas_publicas_config'),
            'captcha_enabled' => (int) ($config['captcha_enabled'] ?? 0) === 1,
            'captcha_provider' => $repo->getCaptchaProvider(),
            'captcha_site_key' => $repo->getCaptchaSiteKey(),
            'captcha_secret_configured' => $repo->getCaptchaSecretKey() !== '',
            'portal_url' => rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/vagas-abertas',
        ];

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));

        (new LoadViewService('adms/Views/rh/vagas/publicasConfig', $this->data))->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken(
            'form_rh_vagas_publicas_config',
            (string) ($_POST['csrf_token'] ?? '')
        )) {
            $_SESSION['msg'] = 'Token de segurança inválido. Tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-vagas-publicas-config');
            exit;
        }

        $ok = (new RhVagasPublicasConfigRepository())->saveCaptcha([
            'captcha_enabled' => $_POST['captcha_enabled'] ?? '',
            'captcha_provider' => $_POST['captcha_provider'] ?? 'hcaptcha',
            'captcha_site_key' => $_POST['captcha_site_key'] ?? '',
            'captcha_secret_key' => $_POST['captcha_secret_key'] ?? '',
        ]);

        if ($ok) {
            $_SESSION['msg'] = 'Configuração do portal de vagas salva com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Não foi possível salvar a configuração. Verifique se a migration foi aplicada.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-vagas-publicas-config');
        exit;
    }
}
