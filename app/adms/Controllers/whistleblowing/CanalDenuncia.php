<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\WhistleblowingPublicUrlHelper;
use App\adms\Models\Repository\WhistleblowingMessagesRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingProtocolService;
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

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->reportsRepo = new WhistleblowingReportsRepository();
        $this->messagesRepo = new WhistleblowingMessagesRepository();
        $this->uploadService = new WhistleblowingUploadService();
    }

    public function index(): void
    {
        $this->render('home', [
            'title' => 'Canal de Denúncias',
        ]);
    }

    public function registrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->enviar();
            return;
        }

        $this->render('registrar', [
            'title' => 'Registrar denúncia',
            'categories' => WhistleblowingProtocolService::CATEGORIES,
            'risk_levels' => WhistleblowingProtocolService::RISK_LEVELS,
            'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_registrar'),
        ]);
    }

    public function enviar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->baseUrl() . 'registrar');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('canal_denuncia_registrar', $_POST['csrf_token'] ?? '')) {
            $this->render('registrar', [
                'title' => 'Registrar denúncia',
                'categories' => WhistleblowingProtocolService::CATEGORIES,
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
                'categories' => WhistleblowingProtocolService::CATEGORIES,
                'risk_levels' => WhistleblowingProtocolService::RISK_LEVELS,
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_registrar'),
                'error' => 'O relato da denúncia é obrigatório.',
                'old' => $_POST,
            ]);
            return;
        }

        if (!in_array($category, WhistleblowingProtocolService::CATEGORIES, true)) {
            $category = 'Outros';
        }
        if (!in_array($riskLevel, WhistleblowingProtocolService::RISK_LEVELS, true)) {
            $riskLevel = 'Médio';
        }

        $result = $this->reportsRepo->createAnonymousReport(
            ['description' => $description, 'involved' => $involved],
            $category,
            $riskLevel
        );

        if ($result === null) {
            $this->render('registrar', [
                'title' => 'Registrar denúncia',
                'categories' => WhistleblowingProtocolService::CATEGORIES,
                'risk_levels' => WhistleblowingProtocolService::RISK_LEVELS,
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_registrar'),
                'error' => 'Não foi possível registrar a denúncia. Tente novamente.',
                'old' => $_POST,
            ]);
            return;
        }

        $reportId = (int) $result['report_id'];
        $uploads = $this->uploadService->handleMultiple($_FILES['attachments'] ?? null);
        foreach ($uploads as $upload) {
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

        $this->render('protocolo', [
            'title' => 'Denúncia registrada',
            'protocol' => $result['protocol'],
            'password' => $result['password'],
        ]);
    }

    public function acompanhar(): void
    {
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->baseUrl() . 'acompanhar');
            exit;
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
            $this->render('acompanhar', [
                'title' => 'Acompanhar denúncia',
                'csrf_token' => CSRFHelper::generateCSRFToken('canal_denuncia_acompanhar'),
                'error' => 'Protocolo ou senha inválidos.',
            ]);
            return;
        }

        $reportId = (int) $report['id'];
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

        if ($message !== '') {
            $this->messagesRepo->createMessage($reportId, $message, 'denunciante', null, false);
        }

        $uploads = $this->uploadService->handleMultiple($_FILES['attachments'] ?? null);
        foreach ($uploads as $upload) {
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
