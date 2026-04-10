<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\RequestHelper;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SendEmailService;
use App\adms\Helpers\SendWhatsAppService;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Repository\PayrollDocumentEventsRepository;
use App\adms\Models\Repository\PayrollDocumentOtpRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Confirmação de recebimento (ciência) com regras por tipo: nenhuma, senha, OTP WhatsApp/e-mail.
 */
class SignPayrollDocument
{
    private array $data = [];

    private const OTP_TTL_SEC = 300;

    private const OTP_MAX_PER_HOUR = 3;

    private const OTP_MAX_ATTEMPTS = 5;

    public function index(string|null $id = null): void
    {
        $docId = $id !== null && $id !== '' ? (int)$id : (int)($_GET['id'] ?? 0);
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $repo = new EmployeePayrollDocumentsRepository();
        $doc = $repo->getByIdForUser($docId, $uid);
        if ($doc === null || (($doc['status_version'] ?? 'active') !== 'active')) {
            $_SESSION['msg'] = '<div class="alert alert-warning">Documento não encontrado ou foi substituído por nova versão.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'my-payroll-documents');
            exit;
        }

        $sigStatus = (string)($doc['signature_status'] ?? 'not_required');
        if ($sigStatus === 'signed') {
            $this->render($doc, 'Já confirmou o recebimento deste documento.');
            return;
        }
        if ($sigStatus !== 'pending' || empty($doc['requires_signature_snapshot'])) {
            $this->render($doc, 'Este documento não exige confirmação de recebimento no portal.');
            return;
        }

        $auth = (string)($doc['signature_auth_snapshot'] ?? 'none');
        $ev = new PayrollDocumentEventsRepository();

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!CSRFHelper::validateCSRFToken('sign_payroll_document', (string)($_POST['csrf_token'] ?? ''))) {
                $_SESSION['msg'] = '<div class="alert alert-danger">Token de segurança inválido. Atualize a página.</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'sign-payroll-document/' . $docId);
                exit;
            }

            $action = (string)($_POST['action'] ?? '');
            if ($action === 'request_otp' && str_contains($auth, 'otp')) {
                $this->handleRequestOtp($ev, $doc, $uid, $auth);
                header('Location: ' . $_ENV['URL_ADM'] . 'sign-payroll-document/' . $docId);
                exit;
            }
            if ($action === 'verify_otp' && str_contains($auth, 'otp')) {
                $this->handleVerifyOtp($repo, $ev, $doc, $uid, $docId);
                header('Location: ' . $_ENV['URL_ADM'] . 'sign-payroll-document/' . $docId);
                exit;
            }
            if ($action === 'confirm_password' && $auth === 'password') {
                $this->handlePassword($repo, $ev, $doc, $uid, $docId);
                header('Location: ' . $_ENV['URL_ADM'] . 'sign-payroll-document/' . $docId);
                exit;
            }
            if ($action === 'confirm_simple' && $auth === 'none') {
                if ($this->completeSignature($repo, $ev, $doc, $uid, 'none')) {
                    $_SESSION['msg'] = '<div class="alert alert-success">Recebimento confirmado com sucesso.</div>';
                } else {
                    $_SESSION['msg'] = '<div class="alert alert-danger">Não foi possível registar a confirmação.</div>';
                }
                header('Location: ' . $_ENV['URL_ADM'] . 'my-payroll-documents');
                exit;
            }
        }

        $this->render($doc, null);
    }

    private function handleRequestOtp(
        PayrollDocumentEventsRepository $ev,
        array $doc,
        int $uid,
        string $auth
    ): void {
        $docId = (int)$doc['id'];
        $ev->insert($docId, $uid, 'otp_requested', ['auth' => $auth], RequestHelper::getClientIp(), $_SERVER['HTTP_USER_AGENT'] ?? null);
        $otpRepo = new PayrollDocumentOtpRepository();
        if ($otpRepo->countChallengesLastHour($uid, $docId) >= self::OTP_MAX_PER_HOUR) {
            $_SESSION['msg'] = '<div class="alert alert-warning">Limite de pedidos de código por hora. Aguarde e tente novamente.</div>';
            $ev->insert($docId, $uid, 'otp_rate_limited', ['auth' => $auth], RequestHelper::getClientIp(), $_SERVER['HTTP_USER_AGENT'] ?? null);
            return;
        }

        $otpRepo->consumeOpenForDocumentUser($docId, $uid);
        $code = (string)random_int(100000, 999999);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $exp = date('Y-m-d H:i:s', time() + self::OTP_TTL_SEC);

        $usersRepo = new UsersRepository();
        $user = $usersRepo->getUser($uid);
        if (!is_array($user)) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Utilizador não encontrado.</div>';
            return;
        }

        $msgText = 'Código de confirmação do documento RH: ' . $code . '. Válido por 5 minutos. Se não foi você, ignore esta mensagem.';
        $sent = false;
        $channel = 'whatsapp';

        $cel = preg_replace('/\D/', '', (string)($user['celular'] ?? ''));
        if (in_array($auth, ['otp_whatsapp', 'otp_whatsapp_fallback_email'], true) && strlen($cel) >= 10) {
            $wa = SendWhatsAppService::sendMessage($cel, $msgText);
            $sent = !empty($wa['success']);
        }

        if (!$sent && in_array($auth, ['otp_email', 'otp_whatsapp_fallback_email'], true)) {
            $email = trim((string)($user['email'] ?? ''));
            if ($email !== '') {
                SendEmailService::sendEmail(
                    $email,
                    (string)($user['name'] ?? ''),
                    'Código de confirmação — documento RH',
                    '<p>' . htmlspecialchars($msgText) . '</p>',
                    $msgText
                );
                $sent = true;
                $channel = 'email';
            }
        }

        if (!$sent) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Não foi possível enviar o código. Atualize celular ou e-mail no perfil ou contacte o RH.</div>';
            $ev->insert($docId, $uid, 'otp_send_fail', ['auth' => $auth], RequestHelper::getClientIp(), $_SERVER['HTTP_USER_AGENT'] ?? null);
            return;
        }

        $otpRepo->createChallenge($docId, $uid, $hash, $exp, self::OTP_MAX_ATTEMPTS, $channel);
        $ev->insert(
            $docId,
            $uid,
            $channel === 'email' ? 'otp_sent_email_fallback' : 'otp_sent_whatsapp',
            ['channel' => $channel],
            RequestHelper::getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        $_SESSION['msg'] = '<div class="alert alert-success">Código enviado por ' . ($channel === 'email' ? 'e-mail' : 'WhatsApp') . '.</div>';
    }

    private function handleVerifyOtp(
        EmployeePayrollDocumentsRepository $repo,
        PayrollDocumentEventsRepository $ev,
        array $doc,
        int $uid,
        int $docId
    ): void {
        $code = preg_replace('/\D/', '', (string)($_POST['otp_code'] ?? ''));
        if (strlen($code) !== 6) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Informe o código de 6 dígitos.</div>';
            return;
        }

        $otpRepo = new PayrollDocumentOtpRepository();
        $row = $otpRepo->findLatestOpenChallenge($docId, $uid);
        if ($row === null) {
            $_SESSION['msg'] = '<div class="alert alert-warning">Não há código ativo. Solicite um novo.</div>';
            return;
        }

        if (strtotime((string)$row['expires_at']) < time()) {
            $otpRepo->markConsumed((int)$row['id']);
            $_SESSION['msg'] = '<div class="alert alert-warning">Código expirado. Solicite um novo.</div>';
            $ev->insert($docId, $uid, 'otp_validate_fail', ['reason' => 'expired'], RequestHelper::getClientIp(), $_SERVER['HTTP_USER_AGENT'] ?? null);
            return;
        }

        if ((int)$row['attempts'] >= (int)$row['max_attempts']) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Número máximo de tentativas excedido.</div>';
            return;
        }

        if (!password_verify($code, (string)$row['code_hash'])) {
            $otpRepo->incrementAttempts((int)$row['id']);
            $_SESSION['msg'] = '<div class="alert alert-danger">Código incorreto.</div>';
            $ev->insert($docId, $uid, 'otp_validate_fail', ['reason' => 'bad_code'], RequestHelper::getClientIp(), $_SERVER['HTTP_USER_AGENT'] ?? null);
            return;
        }

        $otpRepo->markConsumed((int)$row['id']);
        $method = ((string)$row['channel'] === 'email') ? 'email_otp' : 'whatsapp_otp';
        if ($this->completeSignature($repo, $ev, $doc, $uid, $method)) {
            $ev->insert($docId, $uid, 'otp_validate_success', [], RequestHelper::getClientIp(), $_SERVER['HTTP_USER_AGENT'] ?? null);
            $_SESSION['msg'] = '<div class="alert alert-success">Recebimento confirmado com sucesso.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'my-payroll-documents');
            exit;
        }

        $_SESSION['msg'] = '<div class="alert alert-danger">Não foi possível finalizar a assinatura.</div>';
    }

    private function handlePassword(
        EmployeePayrollDocumentsRepository $repo,
        PayrollDocumentEventsRepository $ev,
        array $doc,
        int $uid,
        int $docId
    ): void {
        $pwd = (string)($_POST['password'] ?? '');
        $hash = (new UsersRepository())->getPasswordHashById($uid);
        if ($hash === null || $pwd === '' || !password_verify($pwd, $hash)) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Senha incorreta.</div>';
            $ev->insert($docId, $uid, 'password_sign_fail', [], RequestHelper::getClientIp(), $_SERVER['HTTP_USER_AGENT'] ?? null);
            return;
        }

        if ($this->completeSignature($repo, $ev, $doc, $uid, 'password')) {
            $_SESSION['msg'] = '<div class="alert alert-success">Recebimento confirmado com sucesso.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'my-payroll-documents');
            exit;
        }
        $_SESSION['msg'] = '<div class="alert alert-danger">Não foi possível registar a confirmação.</div>';
    }

    private function completeSignature(
        EmployeePayrollDocumentsRepository $repo,
        PayrollDocumentEventsRepository $ev,
        array $doc,
        int $uid,
        string $authMethod
    ): bool {
        $docId = (int)$doc['id'];
        $hash = (string)($doc['file_hash_sha256'] ?? '');
        if ($hash === '') {
            $abs = $repo->absoluteStoragePath((string)($doc['storage_path'] ?? ''));
            $hash = is_readable($abs) ? hash_file('sha256', $abs) : '';
        }

        $ip = RequestHelper::getClientIp();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ok = $repo->recordSignature($docId, $uid, $ip, $ua, $authMethod, $hash !== '' ? $hash : hash('sha256', (string)$docId));
        if ($ok) {
            $ev->insert($docId, $uid, 'document_signed', [
                'auth_method' => $authMethod,
                'document_hash_sha256' => $hash,
            ], $ip, $ua);
        }

        return $ok;
    }

    private function render(array $doc, ?string $infoMessage): void
    {
        $this->data['doc'] = $doc;
        $this->data['info_only'] = $infoMessage;
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('sign_payroll_document');
        $pageElements = [
            'title_head' => 'Confirmar recebimento do documento',
            'menu' => 'my-payroll-documents',
            'buttonPermission' => ['MyPayrollDocuments'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/portal/sign_payroll_document', $this->data))->loadView();
    }
}
