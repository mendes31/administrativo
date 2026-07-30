<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhOfertasRepository;
use App\adms\Models\Services\RhOfertaService;
use Exception;

/**
 * Formulário público (token) para o candidato anexar documentos de pré-admissão.
 * URL: pre-admissao-documentos?token=…
 */
final class RhPreAdmissaoDocsPublic
{
    public function index(string|int $token = ''): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tokenStr = trim((string) ($_GET['token'] ?? ''));
        if ($tokenStr === '' && $token !== '' && $token !== 0) {
            $tokenStr = trim((string) $token);
        }
        $service = new RhOfertaService();

        try {
            $oferta = $service->requireOfertaPorTokenValido($tokenStr);
        } catch (Exception $e) {
            $this->render([
                'error' => $e->getMessage(),
                'oferta' => null,
                'documentos' => [],
                'token' => $tokenStr,
                'csrf_token' => CSRFHelper::generateCSRFToken('form_pre_admissao_docs_public'),
                'success' => null,
                'expires_at' => null,
            ]);
            return;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handleUpload($tokenStr);
            return;
        }

        $this->render([
            'error' => null,
            'success' => $_SESSION['msg'] ?? null,
            'oferta' => $oferta,
            'documentos' => (new RhOfertasRepository())->listDocumentos((int) $oferta['id']),
            'token' => $tokenStr,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_pre_admissao_docs_public'),
            'expires_at' => $oferta['docs_request_expires_at'] ?? null,
        ]);
        unset($_SESSION['msg'], $_SESSION['msg_type']);
    }

    private function handleUpload(string $token): void
    {
        if (!CSRFHelper::validateCSRFToken('form_pre_admissao_docs_public', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token inválido. Recarregue a página.';
            header('Location: ' . rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/pre-admissao-documentos?token=' . rawurlencode($token));
            exit;
        }

        $docId = (int) ($_POST['documento_id'] ?? 0);
        $file = $_FILES['arquivo'] ?? [];

        try {
            (new RhOfertaService())->uploadDocumentoPublico($token, $docId, is_array($file) ? $file : []);
            $_SESSION['msg'] = 'Documento enviado com sucesso.';
        } catch (Exception $e) {
            $_SESSION['msg'] = $e->getMessage();
        }

        header('Location: ' . rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/pre-admissao-documentos?token=' . rawurlencode($token));
        exit;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(array $data): void
    {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? '/'), '/') . '/';
        $data['base_url'] = $base . 'vagas-abertas';
        $data['url_adm'] = $base;
        $data['view'] = 'pre_admissao_docs';
        $data['title'] = 'Envio de documentos';
        $data['brand_title'] = 'Pré-admissão';
        $data['brand_subtitle'] = 'Envio de documentos solicitados pelo RH';
        $data['brand_link_label'] = 'Trabalhe conosco';
        $data['brand_link_href'] = $base . 'vagas-abertas';
        $data['footer_text'] = 'Envio seguro de documentos. Use apenas o link enviado pelo recrutador.';

        extract($data, EXTR_SKIP);
        require dirname(__DIR__, 2) . '/Views/rh/public/layout.php';
    }
}
