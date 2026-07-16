<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\WhistleblowingPublicUrlHelper;
use App\adms\Models\Services\WhistleblowingAttachmentFilenameHelper;
use App\adms\Models\Repository\WhistleblowingMessagesRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingChannelSecurityService;
use App\adms\Models\Services\WhistleblowingCommitteeNotificationService;
use App\adms\Models\Services\WhistleblowingCategoryService;
use App\adms\Models\Services\WhistleblowingProtocolService;
use App\adms\Models\Services\WhistleblowingRateLimitService;
use App\adms\Models\Services\WhistleblowingUploadService;

/**
 * Canal público de denúncias — sem login, sem vínculo com usuário do portal.
 * URL interna cadastro: canaldenuncia | URL pública: URL_CANAL_DENUNCIA ou /canaldenuncia na raiz
 */
final class CanalDenuncia
{
    private WhistleblowingReportsRepository $reportsRepo;
    private WhistleblowingMessagesRepository $messagesRepo;
    private WhistleblowingUploadService $uploadService;
    private WhistleblowingRateLimitService $rateLimit;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->reportsRepo = new WhistleblowingReportsRepository();
        $this->messagesRepo = new WhistleblowingMessagesRepository();
        $this->uploadService = new WhistleblowingUploadService();
        $this->rateLimit = new WhistleblowingRateLimitService();
    }

    public function index(): void
    {
        if (!$this->ensureChannelAvailable()) {
            return;
        }

        $this->render('home', [
            'title' => 'Canal de Denúncias',
        ]);
    }

    public function registrar(): void
    {
        if (!$this->ensureChannelAvailable()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->enviar();
            return;
        }

        $this->render('registrar', [
            'title' => 'Registrar denúncia',
            'categories' => WhistleblowingCategoryService::getActiveNames(),
            'risk_levels' => WhistleblowingProtocolService::RISK_LEVELS,
            'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_registrar'),
        ]);
    }

    public function enviar(): void
    {
        if (!$this->ensureChannelAvailable()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->baseUrl() . 'registrar');
            exit;
        }

        if ($this->isPostTooLarge()) {
            $this->render('registrar', [
                'title' => 'Registrar denúncia',
                'categories' => WhistleblowingCategoryService::getActiveNames(),
                'risk_levels' => WhistleblowingProtocolService::RISK_LEVELS,
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_registrar'),
                'error' => 'O(s) anexo(s) ultrapassam o limite de envio do servidor. Máximo '
                    . WhistleblowingUploadService::maxFileSizeLabel()
                    . ' por arquivo (PDF, imagem, áudio MP3/WAV ou vídeo MP4). Comprima o vídeo e tente novamente.',
                'old' => $_POST,
            ]);
            return;
        }

        if (!CSRFHelper::validateCSRFToken('canal_denuncia_registrar', $_POST['csrf_token'] ?? '')) {
            $this->render('registrar', [
                'title' => 'Registrar denúncia',
                'categories' => WhistleblowingCategoryService::getActiveNames(),
                'risk_levels' => WhistleblowingProtocolService::RISK_LEVELS,
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_registrar'),
                'error' => 'Sessão expirada. Atualize a página e tente novamente.',
            ]);
            return;
        }

        $category = trim((string) ($_POST['category'] ?? ''));
        $riskLevel = trim((string) ($_POST['risk_level'] ?? 'Médio'));
        $description = trim((string) ($_POST['description'] ?? ''));
        $involved = trim((string) ($_POST['involved'] ?? ''));

        if ($description === '') {
            $this->render('registrar', [
                'title' => 'Registrar denúncia',
                'categories' => WhistleblowingCategoryService::getActiveNames(),
                'risk_levels' => WhistleblowingProtocolService::RISK_LEVELS,
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_registrar'),
                'error' => 'O relato da denúncia é obrigatório.',
                'old' => $_POST,
            ]);
            return;
        }

        $category = WhistleblowingCategoryService::normalize($category);
        if (!in_array($riskLevel, WhistleblowingProtocolService::RISK_LEVELS, true)) {
            $riskLevel = 'Médio';
        }

        $wantsIdentify = ($_POST['identify_reporter'] ?? '') === '1';
        $reporterName = trim((string) ($_POST['reporter_name'] ?? ''));
        $reporterEmail = trim((string) ($_POST['reporter_email'] ?? ''));
        $reporterPhone = trim((string) ($_POST['reporter_phone'] ?? ''));

        if ($wantsIdentify && $reporterName === '' && $reporterEmail === '' && $reporterPhone === '') {
            $this->render('registrar', [
                'title' => 'Registrar denúncia',
                'categories' => WhistleblowingCategoryService::getActiveNames(),
                'risk_levels' => WhistleblowingProtocolService::RISK_LEVELS,
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_registrar'),
                'error' => 'Informe pelo menos um campo (nome, e-mail ou telefone) ou desmarque a opção de se identificar.',
                'old' => $_POST,
            ]);
            return;
        }

        // Valida anexos ANTES de criar a denúncia — não registra se o arquivo for inválido.
        $uploadResult = $this->uploadService->processMultiple($_FILES['attachments'] ?? null);
        if ($uploadResult['errors'] !== []) {
            $this->render('registrar', [
                'title' => 'Registrar denúncia',
                'categories' => WhistleblowingCategoryService::getActiveNames(),
                'risk_levels' => WhistleblowingProtocolService::RISK_LEVELS,
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_registrar'),
                'error' => 'Não foi possível enviar a denúncia por problema nos anexos: '
                    . implode(' ', $uploadResult['errors']),
                'old' => $_POST,
            ]);
            return;
        }

        $result = $this->reportsRepo->createAnonymousReport(
            ['description' => $description, 'involved' => $involved],
            $category,
            $riskLevel,
            $wantsIdentify,
            $wantsIdentify ? [
                'name' => $reporterName,
                'email' => $reporterEmail,
                'phone' => $reporterPhone,
            ] : null
        );

        if ($result === null) {
            foreach ($uploadResult['uploads'] as $upload) {
                WhistleblowingUploadService::deleteFile((string) ($upload['stored_name'] ?? ''));
            }
            $this->render('registrar', [
                'title' => 'Registrar denúncia',
                'categories' => WhistleblowingCategoryService::getActiveNames(),
                'risk_levels' => WhistleblowingProtocolService::RISK_LEVELS,
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_registrar'),
                'error' => 'Não foi possível registrar a denúncia. Tente novamente.',
                'old' => $_POST,
            ]);
            return;
        }

        $reportId = (int) $result['report_id'];
        foreach ($uploadResult['uploads'] as $upload) {
            $this->messagesRepo->createAttachment(
                $reportId,
                null,
                $upload['stored_name'],
                $upload['original_name'],
                $upload['mime_type'],
                $upload['size_bytes'],
                'denunciante'
            );
        }

        (new WhistleblowingCommitteeNotificationService())->notifyNewReport(
            $reportId,
            (string) $result['protocol'],
            $category,
            $riskLevel,
            isset($result['committee_id']) ? (int) $result['committee_id'] : null
        );

        $this->render('protocolo', [
            'title' => 'Denúncia registrada',
            'protocol' => $result['protocol'],
            'password' => $result['password'],
        ]);
    }

    public function acompanhar(): void
    {
        if (!$this->ensureChannelAvailable()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->consultar();
            return;
        }

        $this->render('acompanhar', [
            'title' => 'Acompanhar denúncia',
            'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_acompanhar'),
        ]);
    }

    public function consultar(): void
    {
        if (!$this->ensureChannelAvailable()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->baseUrl() . 'acompanhar');
            exit;
        }

        $scope = 'acompanhar';

        if ($this->rateLimit->isBlocked($scope)) {
            $wait = $this->rateLimit->secondsUntilUnblock($scope);
            $mins = max(1, (int) ceil($wait / 60));
            $this->render('acompanhar', [
                'title' => 'Acompanhar denúncia',
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_acompanhar'),
                'error' => 'Muitas tentativas incorretas. Aguarde cerca de ' . $mins . ' minuto(s) e tente novamente.',
            ]);
            return;
        }

        if (!CSRFHelper::validateCSRFToken('canal_denuncia_acompanhar', $_POST['csrf_token'] ?? '', false)) {
            $this->render('acompanhar', [
                'title' => 'Acompanhar denúncia',
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_acompanhar'),
                'error' => 'Sessão expirada. Atualize a página e tente novamente.',
            ]);
            return;
        }

        $protocol = strtoupper(trim((string) ($_POST['protocol'] ?? '')));
        $password = trim((string) ($_POST['password'] ?? ''));

        $report = $this->reportsRepo->verifyProtocolAndPassword($protocol, $password);
        if ($report === null) {
            $this->rateLimit->recordFailedAttempt($scope);
            $this->render('acompanhar', [
                'title' => 'Acompanhar denúncia',
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_acompanhar'),
                'error' => 'Protocolo ou senha inválidos.',
            ]);
            return;
        }

        $this->rateLimit->clear($scope);

        $reportId = (int) $report['id'];
        $this->storePublicSession($reportId, $protocol, $password, (string) ($report['uuid'] ?? ''));

        $hydrated = $this->reportsRepo->hydrateReportForWhistleblower($report);
        $messages = $this->messagesRepo->getPublicMessagesByReportId($reportId);
        $attachments = $this->messagesRepo->getAttachmentsByReportId($reportId, true);

        $this->render('detalhe', [
            'title' => 'Acompanhamento — ' . $hydrated['protocol'],
            'report' => $hydrated,
            'messages' => $messages,
            'attachments' => $attachments,
            'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_responder'),
            'protocol' => $protocol,
            'password' => $password,
        ]);
    }

    public function responder(): void
    {
        if (!$this->ensureChannelAvailable()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->baseUrl() . 'acompanhar');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('canal_denuncia_responder', $_POST['csrf_token'] ?? '', false)) {
            header('Location: ' . $this->baseUrl() . 'acompanhar');
            exit;
        }

        $protocol = strtoupper(trim((string) ($_POST['protocol'] ?? '')));
        $password = trim((string) ($_POST['password'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        $report = $this->reportsRepo->verifyProtocolAndPassword($protocol, $password);
        if ($report === null) {
            header('Location: ' . $this->baseUrl() . 'acompanhar');
            exit;
        }

        $reportId = (int) $report['id'];

        $uploadResult = $this->uploadService->processMultiple($_FILES['attachments'] ?? null);
        if ($uploadResult['errors'] !== []) {
            $hydrated = $this->reportsRepo->hydrateReportForWhistleblower($report);
            $messages = $this->messagesRepo->getMessagesByReportId($reportId, true);
            $attachments = $this->messagesRepo->getAttachmentsByReportId($reportId, true);
            $this->render('detalhe', [
                'title' => 'Acompanhamento — ' . $hydrated['protocol'],
                'report' => $hydrated,
                'messages' => $messages,
                'attachments' => $attachments,
                'protocol' => $protocol,
                'password' => $password,
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_responder'),
                'error' => 'Não foi possível enviar o anexo: ' . implode(' ', $uploadResult['errors']),
            ]);
            return;
        }

        if ($message === '' && $uploadResult['uploads'] === []) {
            $hydrated = $this->reportsRepo->hydrateReportForWhistleblower($report);
            $messages = $this->messagesRepo->getMessagesByReportId($reportId, true);
            $attachments = $this->messagesRepo->getAttachmentsByReportId($reportId, true);
            $this->render('detalhe', [
                'title' => 'Acompanhamento — ' . $hydrated['protocol'],
                'report' => $hydrated,
                'messages' => $messages,
                'attachments' => $attachments,
                'protocol' => $protocol,
                'password' => $password,
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_responder'),
                'error' => 'Informe uma mensagem ou anexe um arquivo válido.',
            ]);
            return;
        }

        if ($message !== '') {
            $this->messagesRepo->createMessage($reportId, $message, 'denunciante', null, false);
        }

        foreach ($uploadResult['uploads'] as $upload) {
            $this->messagesRepo->createAttachment(
                $reportId,
                null,
                $upload['stored_name'],
                $upload['original_name'],
                $upload['mime_type'],
                $upload['size_bytes'],
                'denunciante'
            );
        }

        $_POST['protocol'] = $protocol;
        $_POST['password'] = $password;
        $_POST['csrf_token'] = CSRFHelper::generateCSRFToken('canal_denuncia_acompanhar');
        $this->consultar();
    }

    public function downloadAnexo(): void
    {
        if (!$this->ensureChannelAvailable()) {
            return;
        }

        $attachmentId = (int) ($_GET['id'] ?? 0);
        if ($attachmentId <= 0) {
            http_response_code(404);
            exit;
        }

        $attachment = $this->messagesRepo->getAttachmentById($attachmentId);
        if (!$attachment) {
            http_response_code(404);
            exit;
        }

        $reportId = (int) ($attachment['report_id'] ?? 0);
        if (!$this->hasValidPublicSession($reportId)) {
            http_response_code(403);
            echo 'Acesso negado. Consulte a denúncia com protocolo e senha.';
            exit;
        }

        if (($attachment['uploaded_by'] ?? '') !== 'denunciante') {
            http_response_code(403);
            exit;
        }

        $storedName = (string) ($attachment['stored_name'] ?? '');
        $path = WhistleblowingUploadService::getFilePath($storedName);
        if (!is_readable($path)) {
            http_response_code(404);
            exit;
        }

        $contents = $this->uploadService->readFileContents($storedName);
        if ($contents === null) {
            http_response_code(500);
            echo 'Não foi possível ler o anexo.';
            exit;
        }

        $name = WhistleblowingAttachmentFilenameHelper::resolve($attachment);
        $mime = (string) ($attachment['mime_type'] ?? 'application/octet-stream');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . WhistleblowingAttachmentFilenameHelper::contentDispositionHeader($name));
        header('Content-Length: ' . (string) strlen($contents));
        echo $contents;
        exit;
    }

    private function storePublicSession(int $reportId, string $protocol, string $password, string $uuid): void
    {
        $_SESSION['wb_public_access'] = [
            'report_id' => $reportId,
            'auth' => $this->publicAuthToken($protocol, $password, $uuid),
            'expires' => time() + 7200,
        ];
    }

    private function hasValidPublicSession(int $reportId): bool
    {
        $session = $_SESSION['wb_public_access'] ?? null;
        if (!is_array($session)) {
            return false;
        }
        if ((int) ($session['report_id'] ?? 0) !== $reportId) {
            return false;
        }
        if ((int) ($session['expires'] ?? 0) < time()) {
            return false;
        }

        return !empty($session['auth']);
    }

    private function publicAuthToken(string $protocol, string $password, string $uuid): string
    {
        return hash('sha256', strtoupper(trim($protocol)) . '|' . trim($password) . '|' . $uuid);
    }

    private function ensureChannelAvailable(): bool
    {
        if (WhistleblowingChannelSecurityService::isPublicChannelAvailable()) {
            return true;
        }

        $this->render('indisponivel', [
            'title' => 'Canal indisponível',
            'message' => WhistleblowingChannelSecurityService::publicUnavailableMessage(),
        ]);

        return false;
    }

    /**
     * Detecta POST maior que post_max_size (PHP esvazia $_POST/$_FILES).
     */
    private function isPostTooLarge(): bool
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            return false;
        }

        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength <= 0) {
            return false;
        }

        $postMax = $this->iniSizeToBytes((string) ini_get('post_max_size'));
        if ($postMax > 0 && $contentLength > $postMax) {
            return true;
        }

        // Formulário multipart com arquivos, mas $_FILES vazio após POST grande.
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'multipart/form-data')
            && empty($_POST)
            && empty($_FILES)
            && $contentLength > 1024 * 1024) {
            return true;
        }

        return false;
    }

    private function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $viewName, array $data): void
    {
        $data['view'] = $viewName;
        $data['base_url'] = $this->baseUrl();
        $data['url_adm'] = $_ENV['URL_ADM'] ?? '';

        $viewPath = dirname(__DIR__, 2) . '/Views/whistleblowing/public/' . $viewName . '.php';
        if (!is_readable($viewPath)) {
            http_response_code(500);
            echo 'Página não encontrada.';
            return;
        }

        extract($data, EXTR_SKIP);
        include dirname(__DIR__, 2) . '/Views/whistleblowing/public/layout.php';
    }

    private function baseUrl(): string
    {
        return WhistleblowingPublicUrlHelper::baseUrl();
    }
}
