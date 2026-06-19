<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\RequestHelper;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEpiFichasRepository;
use App\adms\Models\Services\SstEpiFichaPublishNotifier;
use App\adms\Models\Services\SstEpiFichaSignedBundlePdfService;
use App\adms\Views\Services\LoadViewService;

/**
 * Assinatura da ficha de EPI com sessão do portal (sem OTP).
 */
class SignEpiFicha
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        $fichaId = (int) $id;
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }
        if ($fichaId <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'my-epi-deliveries');
            exit;
        }

        $repo = new SstEpiFichasRepository();
        $ficha = $repo->getByIdForUser($fichaId, $uid);
        if (!$ficha) {
            $_SESSION['msg'] = 'Ficha não encontrada ou você não tem permissão para assiná-la.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'my-epi-deliveries');
            exit;
        }

        $status = (string) ($ficha['status_assinatura'] ?? '');
        if ($status === 'Assinado') {
            $this->render($ficha, $repo->getItens($fichaId), 'Você já confirmou o recebimento desta ficha.');
            return;
        }
        if ($status !== 'Pendente') {
            $this->render($ficha, $repo->getItens($fichaId), 'Esta ficha não está disponível para assinatura.');
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!CSRFHelper::validateCSRFToken('sign_epi_ficha', (string) ($_POST['csrf_token'] ?? ''))) {
                $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sign-epi-ficha/' . $fichaId);
                exit;
            }
            if (($_POST['action'] ?? '') === 'confirm_session') {
                $hash = (string) ($ficha['pdf_hash_sha256'] ?? '');
                if ($hash === '') {
                    $path = (string) ($ficha['pdf_storage_path'] ?? '');
                    $abs = $path !== '' ? $repo->absoluteStoragePath($path) : '';
                    $hash = ($abs !== '' && is_readable($abs)) ? (hash_file('sha256', $abs) ?: '') : hash('sha256', 'ficha_' . $fichaId);
                }
                $ip = RequestHelper::getClientIp();
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                if ($repo->recordSignature($fichaId, $uid, $ip, $ua, $hash)) {
                    try {
                        (new SstEpiFichaSignedBundlePdfService())->appendAuditTrail($fichaId);
                    } catch (\Throwable) {
                    }
                    SstEpiFichaPublishNotifier::markNotificationsRead($uid, $fichaId);
                    $_SESSION['msg'] = 'Recebimento dos EPIs confirmado com sucesso.';
                    $_SESSION['msg_type'] = 'success';
                    header('Location: ' . $_ENV['URL_ADM'] . 'my-epi-deliveries');
                    exit;
                }
                $_SESSION['msg'] = 'Não foi possível registrar a confirmação.';
                $_SESSION['msg_type'] = 'danger';
            }
        }

        $this->render($ficha, $repo->getItens($fichaId), null);
    }

    /** @param array<string, mixed> $ficha @param list<array<string, mixed>> $itens */
    private function render(array $ficha, array $itens, ?string $infoOnly): void
    {
        $this->data['ficha'] = $ficha;
        $this->data['itens'] = $itens;
        $this->data['info_only'] = $infoOnly;
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('sign_epi_ficha');
        $pageElements = [
            'title_head' => 'Confirmar recebimento de EPI',
            'menu' => 'my-epi-deliveries',
            'buttonPermission' => ['MyEpiDeliveries', 'SignEpiFicha'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/portal/sign_epi_ficha', $this->data))->loadView();
    }
}
