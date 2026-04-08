<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Services\PayrollPdfSplitService;
use App\adms\Views\Services\LoadViewService;
use Smalot\PdfParser\Parser;

/**
 * Upload do PDF consolidado do escritório; separação por CPF e entrega aos colaboradores.
 */
class ImportPayrollDocuments
{
    private array $data = [];

    public function index(string|null $param = null): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->processPost();

                return;
            }

            $this->renderForm();
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'ImportPayrollDocuments::index — ' . $e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao carregar a página de importação. '
                . htmlspecialchars($e->getMessage())
                . '</div>';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'dashboard');
            exit;
        }
    }

    private function renderForm(): void
    {
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('form_import_payroll_pdf');
        $_SESSION['payroll_import_nonce'] = bin2hex(random_bytes(16));
        $this->data['import_nonce'] = $_SESSION['payroll_import_nonce'];
        $repo = new EmployeePayrollDocumentsRepository();
        $this->data['import_batches'] = $repo->listBatches(50);
        $pageElements = [
            'title_head' => 'Importar documentos de folha (PDF)',
            'menu' => 'import-payroll-documents',
            'buttonPermission' => ['ImportPayrollDocuments'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/import_payroll_documents', $this->data);
        $loadView->loadView();
    }

    private function processPost(): void
    {
        // Se post_max_size / upload exceder o limite do PHP, $_POST e $_FILES ficam vazios — o CSRF parece "inválido".
        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMax = self::parseIniSizeToBytes((string)ini_get('post_max_size'));
        if ($postMax > 0 && $contentLength > $postMax) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">O envio excede o limite <code>post_max_size</code> do PHP ('
                . htmlspecialchars(ini_get('post_max_size')) . '). Reduza o PDF ou peça ao administrador para aumentar esse limite no <code>php.ini</code>.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }

        if (
            ($_POST['csrf_token'] ?? '') === ''
            && empty($_POST)
            && empty($_FILES)
            && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        ) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Nenhum dado recebido no envio. Normalmente indica ficheiro acima do limite do servidor (<code>post_max_size</code> / <code>upload_max_filesize</code>). Verifique o tamanho do PDF e a configuração do PHP.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_import_payroll_pdf', (string)($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token de segurança inválido ou sessão expirada. Atualize a página e tente de novo (evite várias abas a competir pelo mesmo formulário).</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }

        try {
            $this->processPostAfterCsrf();
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'ImportPayrollDocuments::processPost — ' . $e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não foi possível processar o envio. '
                . htmlspecialchars($e->getMessage())
                . ' Verifique se executou as migrações da base de dados (tabelas de folha) e se o Composer tem as bibliotecas PDF instaladas no servidor.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }
    }

    private function processPostAfterCsrf(): void
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        if (($_POST['action'] ?? '') === 'delete_batch') {
            $bid = (int)($_POST['delete_batch_id'] ?? 0);
            if ($bid > 0) {
                try {
                    $repo = new EmployeePayrollDocumentsRepository();
                    $ok = $repo->deleteBatchCascade($bid);
                    $_SESSION['msg'] = $ok
                        ? '<div class="alert alert-success" role="alert">Importação removida (documentos e ficheiros associados).</div>'
                        : '<div class="alert alert-warning" role="alert">Lote não encontrado ou já removido.</div>';
                } catch (\Throwable $e) {
                    GenerateLog::generateLog('error', 'ImportPayrollDocuments::deleteBatch — ' . $e->getMessage(), [
                        'exception' => $e::class,
                    ]);
                    $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não foi possível remover o lote. ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }

        $postedNonce = (string)($_POST['import_nonce'] ?? '');
        $sessionNonce = (string)($_SESSION['payroll_import_nonce'] ?? '');
        if ($postedNonce === '' || $sessionNonce === '' || !hash_equals($sessionNonce, $postedNonce)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Este envio já foi processado ou a página expirou. <strong>Atualize a página</strong> e envie o PDF uma única vez; não clique duas vezes em «Processar PDF».</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }
        unset($_SESSION['payroll_import_nonce']);

        @set_time_limit(0);

        $docType = preg_replace('/[^a-z_]/', '', strtolower((string)($_POST['document_type'] ?? 'payroll')));
        $allowed = ['payroll', 'vacation_receipt', 'ir_statement', 'time_bank', 'other'];
        if (!in_array($docType, $allowed, true)) {
            $docType = 'payroll';
        }

        $year = (int)($_POST['reference_year'] ?? date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int)date('Y');
        }

        $monthRaw = $_POST['reference_month'] ?? '';
        $month = $monthRaw === '' || $monthRaw === null ? null : (int)$monthRaw;
        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }

        $titlePrefix = trim((string)($_POST['title_prefix'] ?? ''));
        if ($titlePrefix === '') {
            $titlePrefix = self::defaultPrefix($docType);
        }

        if (empty($_FILES['pdf_file']['tmp_name']) || (int)($_FILES['pdf_file']['error'] ?? 0) !== UPLOAD_ERR_OK) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Envie um ficheiro PDF válido.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }

        $tmp = (string)$_FILES['pdf_file']['tmp_name'];
        $mime = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            $mime = (string)mime_content_type($tmp);
        }
        if ($mime !== 'application/pdf' && !str_ends_with(strtolower((string)$_FILES['pdf_file']['name']), '.pdf')) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">O ficheiro deve ser PDF.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }

        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);
        $importDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'payroll' . DIRECTORY_SEPARATOR . '_imports';
        if (!is_dir($importDir) && !mkdir($importDir, 0770, true) && !is_dir($importDir)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não foi possível criar pasta de importação no servidor.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }

        $destName = bin2hex(random_bytes(12)) . '.pdf';
        $absoluteDest = $importDir . DIRECTORY_SEPARATOR . $destName;
        if (!move_uploaded_file($tmp, $absoluteDest)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Falha ao guardar o upload.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }

        $originalName = (string)($_FILES['pdf_file']['name'] ?? 'documento.pdf');
        $pageCount = 0;
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($absoluteDest);
            $pageCount = count($pdf->getPages());
        } catch (\Throwable $e) {
            @unlink($absoluteDest);
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não foi possível ler o PDF. Verifique se o ficheiro não está corrompido ou protegido por senha. Detalhe: ' . htmlspecialchars($e->getMessage()) . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }

        $repo = new EmployeePayrollDocumentsRepository();
        $batchId = $repo->createBatch([
            'original_filename' => $originalName,
            'document_type' => $docType,
            'reference_year' => $year,
            'reference_month' => $month,
            'pages_total' => $pageCount,
            'pages_matched' => 0,
            'pages_unmatched' => 0,
            'log_json' => null,
            'created_by_user_id' => $uid,
        ]);

        try {
            $result = PayrollPdfSplitService::processUploadedFile(
                $absoluteDest,
                $batchId,
                $docType,
                $year,
                $month,
                $titlePrefix,
                $uid,
                $originalName
            );
        } catch (\Throwable $e) {
            @unlink($absoluteDest);
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao processar: ' . htmlspecialchars($e->getMessage()) . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
            exit;
        }

        $log = [
            'unmatched_pages' => $result['unmatched_pages'],
            'errors' => $result['errors'],
            'skipped_no_user' => $result['skipped_no_user'],
            'documents_created' => $result['documents_created'],
        ];
        $repo->updateBatchStats(
            $batchId,
            $result['matched'],
            count($result['unmatched_pages']),
            json_encode($log, JSON_UNESCAPED_UNICODE)
        );

        @unlink($absoluteDest);

        $docCount = (int)$result['documents_created'];
        $skippedUser = $result['skipped_no_user'];
        $hasRealErrors = $result['errors'] !== [];
        $onlySkippedUsers = $docCount === 0 && !$hasRealErrors && $skippedUser !== [] && $result['unmatched_pages'] === [];
        $matched = (int)$result['matched'];
        $unmatchedCount = count($result['unmatched_pages']);
        $skippedCount = count($skippedUser);
        $alertClass = $docCount === 0 ? 'alert-warning' : 'alert-success';

        $msg = '<div class="alert ' . $alertClass . '" role="alert">';
        $msg .= '<p class="mb-2 fw-semibold">Processamento concluído.</p>';
        $msg .= '<ul class="small mb-2 ps-3">';
        $msg .= '<li><strong>Páginas no PDF:</strong> ' . $pageCount . '</li>';
        $msg .= '<li><strong>Documentos gerados (colaboradores):</strong> ' . $docCount . '</li>';
        $msg .= '<li><strong>Páginas incluídas nos documentos:</strong> ' . $matched . ' de ' . $pageCount . '</li>';
        if ($unmatchedCount > 0) {
            $msg .= '<li class="text-warning"><strong>Páginas sem CPF identificado (scan/OCR ou texto ilegível):</strong> ' . $unmatchedCount . '</li>';
        }
        if ($skippedCount > 0) {
            $msg .= '<li class="text-secondary"><strong>CPFs ignorados (sem utilizador ativo com o mesmo CPF):</strong> ' . $skippedCount . '</li>';
        }
        $msg .= '</ul>';

        if ($pageCount > $docCount) {
            $msg .= '<div class="border rounded p-2 mb-2 bg-light small"><strong>Por que o número de páginas é maior que o de documentos?</strong><ul class="mb-0 mt-1 ps-3">';
            $msg .= '<li>Cada <strong>colaborador</strong> recebe <strong>um</strong> PDF; esse PDF pode ter <strong>várias páginas</strong> (ex.: 2 páginas por holerite). Por isso <em>documentos ≤ páginas</em> é normal.</li>';
            if ($unmatchedCount > 0) {
                $msg .= '<li>Algumas páginas não geraram documento porque <strong>não foi encontrado CPF</strong> no texto extraído do PDF.</li>';
            }
            if ($skippedCount > 0) {
                $msg .= '<li>Alguns CPFs foram <strong>ignorados</strong> por não haver utilizador ativo com o mesmo documento no cadastro.</li>';
            }
            if ($hasRealErrors) {
                $msg .= '<li>Ocorreram <strong>erros técnicos</strong> ao gerar alguns ficheiros (ver abaixo).</li>';
            }
            if ($unmatchedCount === 0 && $skippedCount === 0 && !$hasRealErrors && $matched === $pageCount && $docCount > 0) {
                $msg .= '<li>Todas as páginas foram associadas a documentos; a diferença para o total de páginas deve-se a <strong>várias páginas por colaborador</strong>.</li>';
            }
            $msg .= '</ul></div>';
        }

        if ($skippedUser !== []) {
            $skipSlice = $docCount === 0 ? $skippedUser : array_slice($skippedUser, 0, 8);
            $msg .= '<p class="small text-muted mb-2"><strong>Detalhe — ignorados:</strong> '
                . htmlspecialchars(implode(' | ', $skipSlice));
            if ($docCount > 0 && count($skippedUser) > 8) {
                $msg .= ' …';
            }
            $msg .= '</p>';
        }
        if ($hasRealErrors) {
            $errSlice = $docCount === 0 ? $result['errors'] : array_slice($result['errors'], 0, 8);
            $msg .= '<p class="small text-danger mb-2"><strong>Erros:</strong> ' . htmlspecialchars(implode(' | ', $errSlice));
            if ($docCount > 0 && count($result['errors']) > 8) {
                $msg .= ' …';
            }
            $msg .= '</p>';
            $errs = $result['errors'];
            $onlyPdfWriteFails = $pageCount >= 2 && $errs !== []
                && count(array_filter($errs, static fn(string $e): bool => str_starts_with($e, 'Falha ao gerar PDF'))) === count($errs);
            if ($onlyPdfWriteFails) {
                $msg .= '<p class="small text-secondary mb-0"><strong>Diagnóstico:</strong> o CPF existe no cadastro; a falha é ao criar o ficheiro. Verifique extensão <strong>Imagick</strong> no PHP, permissões em <code>storage/private/payroll/</code>, e o log <code>PayrollPdfSplitService</code>. Em servidores sem <code>qpdf</code>, o sistema usa Imagick + FPDI.</p>';
            }
        }
        if ($docCount === 0 && !$onlySkippedUsers) {
            $msg .= '<p class="small mb-0">PDF com várias páginas: em muitos servidores é necessário <strong>Imagick</strong> (PHP) ou o binário <strong>qpdf</strong> + <code>exec</code> permitido. Consulte o administrador.</p>';
        }
        if ($onlySkippedUsers) {
            $msg .= '<p class="small mb-0">Nenhum CPF identificado no PDF corresponde a um <strong>utilizador ativo</strong> com o mesmo documento; cadastre ou ative o utilizador para gerar recibos.</p>';
        }
        $msg .= '</div>';
        $_SESSION['msg'] = $msg;
        header('Location: ' . $_ENV['URL_ADM'] . 'import-payroll-documents');
        exit;
    }

    private static function parseIniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $unit = strtolower(substr($value, -1));
        $num = (float)$value;
        return match ($unit) {
            'g' => (int)round($num * 1024 * 1024 * 1024),
            'm' => (int)round($num * 1024 * 1024),
            'k' => (int)round($num * 1024),
            default => (int)round((float)$value),
        };
    }

    private static function defaultPrefix(string $docType): string
    {
        return match ($docType) {
            'vacation_receipt' => 'Recibo de férias',
            'ir_statement' => 'Informe de IR',
            'time_bank' => 'Banco de horas',
            'other' => 'Documento',
            default => 'Folha de pagamento',
        };
    }
}
