<?php



declare(strict_types=1);



namespace App\adms\Controllers\whistleblowing;



use App\adms\Controllers\Services\PageLayoutService;

use App\adms\Helpers\CSRFHelper;

use App\adms\Helpers\UrlAdmHelper;

use App\adms\Helpers\WhistleblowingPublicUrlHelper;

use App\adms\Models\Repository\WhistleblowingConfigRepository;

use App\adms\Models\Repository\WhistleblowingMessagesRepository;

use App\adms\Models\Repository\WhistleblowingReportsRepository;

use App\adms\Models\Repository\WhistleblowingRetentionRunsRepository;

use App\adms\Models\Services\LogResumoService;

use App\adms\Models\Services\WhistleblowingChannelSecurityService;

use App\adms\Models\Services\WhistleblowingKeyRotationService;

use App\adms\Views\Services\LoadViewService;



/**

 * Configuração do Canal de Denúncias (cron + criptografia + políticas) — sem depender do .env.

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

        $this->data['channel_public_ok'] = WhistleblowingChannelSecurityService::isPublicChannelAvailable();

        $this->data['retention_archive_years'] = $repo->getRetentionArchiveYears();

        $this->data['retention_delete_years'] = $repo->getRetentionDeleteYears();

        $this->data['cron_enabled'] = $repo->isCronEnabled();

        $this->data['cron_time'] = $repo->getCronTime();

        $this->data['rate_limit_max_attempts'] = $repo->getRateLimitMaxAttempts();

        $this->data['rate_limit_window_minutes'] = $repo->getRateLimitWindowMinutes();

        $this->data['sla_first_response_hours'] = $repo->getSlaFirstResponseHours();

        $this->data['sla_hours_critico'] = $repo->getSlaHoursCritico();

        $this->data['sla_hours_alto'] = $repo->getSlaHoursAlto();

        $this->data['sla_hours_medio'] = $repo->getSlaHoursMedio();

        $this->data['sla_hours_baixo'] = $repo->getSlaHoursBaixo();

        $this->data['notify_committee_on_reply'] = $repo->isNotifyCommitteeOnReply();

        $this->data['notify_committee_on_status_change'] = $repo->isNotifyCommitteeOnStatusChange();

        $this->data['notify_committee_on_sla_breach'] = $repo->isNotifyCommitteeOnSlaBreach();

        $this->data['notify_reporter_on_reply'] = $repo->isNotifyReporterOnReply();

        $this->data['captcha_enabled'] = $repo->isCaptchaEnabled();

        $this->data['captcha_provider'] = $repo->getCaptchaProvider();

        $this->data['captcha_site_key'] = $repo->getCaptchaSiteKey();

        $this->data['captcha_secret_key'] = $repo->getCaptchaSecretKey();

        $this->data['cron_line'] = $repo->buildSuggestedCronLine(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/'));

        $this->data['public_channel_url'] = WhistleblowingPublicUrlHelper::baseUrl();

        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('whistleblowing_config');

        $this->data['last_retention_run'] = (new WhistleblowingRetentionRunsRepository())->getLastRun();

        $this->data['rotation_counts'] = (new WhistleblowingMessagesRepository())->getRotationCounts();



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



        if ($action === 'save_policies') {

            $ok = $repo->savePolicies([

                'retention_archive_years' => $_POST['retention_archive_years'] ?? 5,

                'retention_delete_years' => $_POST['retention_delete_years'] ?? 10,

                'cron_enabled' => $_POST['cron_enabled'] ?? '',

                'cron_time' => $_POST['cron_time'] ?? '02:00',

                'rate_limit_max_attempts' => $_POST['rate_limit_max_attempts'] ?? 5,

                'rate_limit_window_minutes' => $_POST['rate_limit_window_minutes'] ?? 15,

                'sla_first_response_hours' => $_POST['sla_first_response_hours'] ?? 72,

                'sla_hours_critico' => $_POST['sla_hours_critico'] ?? 24,

                'sla_hours_alto' => $_POST['sla_hours_alto'] ?? 48,

                'sla_hours_medio' => $_POST['sla_hours_medio'] ?? 72,

                'sla_hours_baixo' => $_POST['sla_hours_baixo'] ?? 120,

                'notify_committee_on_reply' => $_POST['notify_committee_on_reply'] ?? '',

                'notify_committee_on_status_change' => $_POST['notify_committee_on_status_change'] ?? '',

                'notify_committee_on_sla_breach' => $_POST['notify_committee_on_sla_breach'] ?? '',

                'notify_reporter_on_reply' => $_POST['notify_reporter_on_reply'] ?? '',

                'captcha_enabled' => $_POST['captcha_enabled'] ?? '',

                'captcha_provider' => $_POST['captcha_provider'] ?? 'hcaptcha',

                'captcha_site_key' => $_POST['captcha_site_key'] ?? '',

                'captcha_secret_key' => $_POST['captcha_secret_key'] ?? '',

            ]);

            if ($ok) {
                (new WhistleblowingReportsRepository())->recalculateRetentionForClosedReports();
            }

            $_SESSION['msg'] = $ok ? 'Políticas e agendamento atualizados.' : 'Não foi possível salvar as políticas.';

            $_SESSION['msg_type'] = $ok ? 'success' : 'danger';

            header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));

            exit;

        }



        if ($action === 'rotate_key') {

            $oldKey = trim((string) ($_POST['current_encryption_key'] ?? ''));

            $newKey = trim((string) ($_POST['new_encryption_key'] ?? ''));

            $confirmKey = trim((string) ($_POST['confirm_encryption_key'] ?? ''));

            $tryLegacy = !empty($_POST['try_legacy_key']);

            if ($newKey === '' || $confirmKey === '') {

                $_SESSION['msg'] = 'Informe e confirme a nova chave.';

                $_SESSION['msg_type'] = 'warning';

                header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));

                exit;

            }

            if ($newKey !== $confirmKey) {

                $_SESSION['msg'] = 'A confirmação da nova chave não confere.';

                $_SESSION['msg_type'] = 'warning';

                header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));

                exit;

            }

            try {

                $stats = (new WhistleblowingKeyRotationService())->rotate($oldKey, $newKey, $tryLegacy);

                $_SESSION['msg'] = sprintf(

                    'Rotação concluída: %d denúncia(s), %d mensagem(ns), %d anexo(s) (%d legado(s) migrado(s) para .enc).',

                    (int) ($stats['reports'] ?? 0),

                    (int) ($stats['messages'] ?? 0),

                    (int) ($stats['attachments'] ?? 0),

                    (int) ($stats['legacy_migrated'] ?? 0)

                );

                $_SESSION['msg_type'] = 'success';

            } catch (\Throwable $e) {

                $_SESSION['msg'] = 'Rotação não realizada: ' . $e->getMessage();

                $_SESSION['msg_type'] = 'danger';

            }

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

            if ($repo->hasEncryptionKey() && strlen($repo->getEncryptionKey()) >= WhistleblowingChannelSecurityService::MIN_KEY_LENGTH) {

                $_SESSION['msg'] = 'Para trocar a chave, use o formulário de Rotação de chave abaixo — ele recriptografa denúncias, mensagens e anexos.';

                $_SESSION['msg_type'] = 'warning';

                header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));

                exit;

            }

            if ($key !== '' && strlen($key) < 32) {

                $_SESSION['msg'] = 'A chave deve ter pelo menos 32 caracteres (recomendado: 64 hex aleatórios).';

                $_SESSION['msg_type'] = 'warning';

                header('Location: ' . UrlAdmHelper::to('whistleblowing-config'));

                exit;

            }

            if ($key !== '') {

                if ($repo->getEncryptionKey() !== '') {

                    $_SESSION['msg'] = 'Chave de criptografia atualizada. Denúncias antigas só poderão ser lidas se a chave anterior for restaurada.';

                    $_SESSION['msg_type'] = 'warning';

                } else {

                    $_SESSION['msg'] = 'Chave de criptografia guardada. O canal público foi liberado para novos registros.';

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


