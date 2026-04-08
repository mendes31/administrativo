<?php



declare(strict_types=1);



namespace App\adms\Models\Services;



use App\adms\Helpers\GenerateLog;

use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;

use App\adms\Models\Repository\UsersRepository;

use setasign\Fpdi\Fpdi;

use Smalot\PdfParser\Parser;



/**

 * Lê texto com PdfParser, agrupa páginas consecutivas com o mesmo CPF e gera PDFs (cópia, qpdf ou FPDI).

 * PDFs só com imagem (sem texto) não terão CPF — ficam como página não identificada.

 */

final class PayrollPdfSplitService

{

    /**

     * @return array{matched: int, unmatched_pages: list<int>, errors: list<string>, skipped_no_user: list<string>, documents_created: int}

     */

    public static function processUploadedFile(

        string $absolutePdfPath,

        int $batchId,

        string $documentType,

        int $referenceYear,

        ?int $referenceMonth,

        string $titlePrefix,

        int $createdByUserId

    ): array {

        $repo = new EmployeePayrollDocumentsRepository();

        $usersRepo = new UsersRepository();



        $parser = new Parser();

        $pdf = $parser->parseFile($absolutePdfPath);

        $pages = $pdf->getPages();

        $pageCount = count($pages);



        $unmatchedPages = [];

        $errors = [];

        /** @var list<string> CPF sem utilizador ativo — ignorado de propósito (não é falha técnica) */
        $skippedNoUser = [];

        $documentsCreated = 0;

        $matched = 0;



        $currentCpf = null;

        /** @var list<int> */

        $currentPageNums = [];



        /** @var array<int, string>|null mapa página 1-based → ficheiro PDF de uma página (qpdf) */

        $singlePagePaths = null;

        $qpdfTempDir = null;

        if ($pageCount >= 2) {

            $split = self::splitPdfIntoSinglePageFiles($absolutePdfPath, $pageCount);

            if ($split !== null) {

                $singlePagePaths = $split['map'];

                $qpdfTempDir = $split['dir'];

            }

        }



        try {

            $flushGroup = function () use (&$currentCpf, &$currentPageNums, &$matched, &$documentsCreated, &$errors, &$skippedNoUser, $absolutePdfPath, $batchId, $documentType, $referenceYear, $referenceMonth, $titlePrefix, $repo, $usersRepo, $pageCount, $singlePagePaths) {

                if ($currentCpf === null || $currentPageNums === []) {

                    return;

                }

                $userId = $usersRepo->findActiveUserIdByNormalizedCpf($currentCpf);

                if ($userId === null) {

                    $skippedNoUser[] = 'CPF sem usuário ativo no cadastro (páginas ignoradas): ' . $currentCpf . ' — páginas ' . implode(',', array_map('strval', $currentPageNums));



                    $currentCpf = null;

                    $currentPageNums = [];



                    return;

                }



                $relPath = self::writePagesToNewPdf($absolutePdfPath, $currentPageNums, $userId, $pageCount, $singlePagePaths);

                if ($relPath === null) {

                    $errors[] = 'Falha ao gerar PDF para CPF ' . $currentCpf;

                    $currentCpf = null;

                    $currentPageNums = [];

                    return;

                }



                $full = $repo->absoluteStoragePath($relPath);

                $size = is_file($full) ? (int)filesize($full) : 0;

                $title = self::buildTitle($titlePrefix, $referenceYear, $referenceMonth);



                $repo->deleteExistingForUserRef($userId, $documentType, $referenceYear, $referenceMonth);

                $repo->insertDocument([

                    'user_id' => $userId,

                    'import_batch_id' => $batchId,

                    'document_type' => $documentType,

                    'reference_year' => $referenceYear,

                    'reference_month' => $referenceMonth,

                    'title' => $title,

                    'storage_path' => $relPath,

                    'file_size' => $size,

                    'cpf_normalized' => $currentCpf,

                    'page_from' => $currentPageNums[0],

                    'page_to' => $currentPageNums[count($currentPageNums) - 1],

                ]);

                $matched += count($currentPageNums);

                $documentsCreated++;

            };



            for ($i = 0; $i < $pageCount; $i++) {

                $pageNum = $i + 1;

                try {

                    $text = $pages[$i]->getText();

                } catch (\Throwable $e) {

                    $unmatchedPages[] = $pageNum;

                    $errors[] = 'Erro ao ler texto da página ' . $pageNum . ': ' . $e->getMessage();

                    $flushGroup();

                    $currentCpf = null;

                    $currentPageNums = [];



                    continue;

                }



                $cpf = self::extractCpfFromText($text);

                if ($cpf === null) {

                    $flushGroup();

                    $currentCpf = null;

                    $currentPageNums = [];

                    $unmatchedPages[] = $pageNum;



                    continue;

                }



                if ($currentCpf === $cpf) {

                    $currentPageNums[] = $pageNum;

                } else {

                    $flushGroup();

                    $currentCpf = $cpf;

                    $currentPageNums = [$pageNum];

                }

            }

            $flushGroup();

        } finally {

            if ($qpdfTempDir !== null && is_dir($qpdfTempDir)) {

                self::removeDirectoryRecursive($qpdfTempDir);

            }

        }



        return [

            'matched' => $matched,

            'unmatched_pages' => $unmatchedPages,

            'errors' => $errors,

            'skipped_no_user' => $skippedNoUser,

            'documents_created' => $documentsCreated,

        ];

    }



    /**

     * Preferir o último "CPF:" válido na página (cabeçalhos podem repetir texto; o do empregado costuma estar no bloco do funcionário).

     * Dígitos verificadores reduzem falso positivo de outros números.

     */

    public static function extractCpfFromText(string $text): ?string

    {

        $text = str_replace(["\xc2\xa0", "\r"], [' ', ''], $text);

        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;



        $candidates = [];

        if (preg_match_all('/CPF[\s:]*([\d\.\-]{11,14})/iu', $text, $m)) {

            foreach ($m[1] as $g) {

                $d = preg_replace('/\D/', '', (string)$g);

                if (strlen($d) === 11 && self::isValidCpfDigits($d)) {

                    $candidates[] = $d;

                }

            }

        }



        if ($candidates !== []) {

            return $candidates[count($candidates) - 1];

        }



        if (preg_match_all('/\b(\d{3}\.?\d{3}\.?\d{3}-?\d{2})\b/', $text, $m2)) {

            foreach ($m2[1] as $g) {

                $d = preg_replace('/\D/', '', (string)$g);

                if (strlen($d) === 11 && self::isValidCpfDigits($d)) {

                    $candidates[] = $d;

                }

            }

        }



        if ($candidates !== []) {

            return $candidates[count($candidates) - 1];

        }

        // Último "CPF:" com 11 dígitos sem validação (OCR pode corromper um dígito verificador).

        if (preg_match_all('/CPF[\s:]*([\d\.\-]{11,14})/iu', $text, $m3)) {

            $last = null;

            foreach ($m3[1] as $g) {

                $d = preg_replace('/\D/', '', (string)$g);

                if (strlen($d) === 11) {

                    $last = $d;

                }

            }

            if ($last !== null) {

                return $last;

            }

        }



        return null;

    }



    private static function isValidCpfDigits(string $d): bool

    {

        if (strlen($d) !== 11 || !ctype_digit($d)) {

            return false;

        }

        if (preg_match('/^(\d)\1{10}$/', $d)) {

            return false;

        }



        $s = 0;

        for ($i = 0; $i < 9; $i++) {

            $s += (int)$d[$i] * (10 - $i);

        }

        $r = $s % 11;

        $d1 = ($r < 2) ? 0 : 11 - $r;

        if ((int)$d[9] !== $d1) {

            return false;

        }

        $s = 0;

        for ($i = 0; $i < 10; $i++) {

            $s += (int)$d[$i] * (11 - $i);

        }

        $r = $s % 11;

        $d2 = ($r < 2) ? 0 : 11 - $r;

        return (int)$d[10] === $d2;

    }



    /**

     * @return array{dir: string, map: array<int, string>}|null

     */

    private static function splitPdfIntoSinglePageFiles(string $absolutePdfPath, int $pageCount): ?array

    {

        $qpdfBin = self::resolveQpdfBinary();

        if ($qpdfBin === null) {

            return null;

        }



        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'payroll_qpdf_' . bin2hex(random_bytes(8));

        if (!@mkdir($dir, 0700, true) && !is_dir($dir)) {

            return null;

        }



        $pattern = $dir . DIRECTORY_SEPARATOR . 'p-%d.pdf';

        $cmd = escapeshellarg($qpdfBin) . ' --split-pages ' . escapeshellarg($absolutePdfPath) . ' ' . escapeshellarg($pattern);

        $out = [];

        $code = 1;

        @exec($cmd . ' 2>&1', $out, $code);

        // qpdf: 0 = OK; 3 = concluído com avisos (ex.: chaves duplicadas no PDF) — ainda gera ficheiros.
        if (!self::qpdfExitMeansSuccess($code)) {

            GenerateLog::generateLog('warning', 'PayrollPdfSplitService: qpdf --split-pages falhou', [

                'code' => $code,

                'output' => implode("\n", array_slice($out, 0, 5)),

            ]);

            self::removeDirectoryRecursive($dir);



            return null;

        }



        $glob = glob($dir . DIRECTORY_SEPARATOR . 'p-*.pdf') ?: [];

        if (count($glob) !== $pageCount) {

            GenerateLog::generateLog('warning', 'PayrollPdfSplitService: qpdf --split-pages ficheiros inesperados', [

                'expected' => $pageCount,

                'got' => count($glob),

                'dir' => $dir,

            ]);

            self::removeDirectoryRecursive($dir);



            return null;

        }

        usort($glob, function (string $a, string $b): int {

            preg_match('/(\d+)/', basename($a), $ma);

            preg_match('/(\d+)/', basename($b), $mb);



            return ((int)($ma[1] ?? 0)) <=> ((int)($mb[1] ?? 0));

        });

        $map = [];

        foreach ($glob as $idx => $f) {

            $map[$idx + 1] = $f;

        }



        return ['dir' => $dir, 'map' => $map];

    }



    /**

     * Caminho do executável qpdf: variável de ambiente QPDF_PATH, depois PATH, depois pastas típicas no Windows.

     */

    private static function resolveQpdfBinary(): ?string

    {

        if (!function_exists('exec')) {

            GenerateLog::generateLog('warning', 'PayrollPdfSplitService: função exec() indisponível (PHP disable_functions); instale qpdf e não desative exec para PDFs multi-página.');

            return null;

        }

        $candidates = [];

        $envPath = $_ENV['QPDF_PATH'] ?? getenv('QPDF_PATH');

        if (!empty($envPath)) {

            $candidates[] = trim((string)$envPath);

        }

        // Executável portátil na raiz do projeto (deploy por cópia, sem instalador no servidor — licença Apache do qpdf).

        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);

        $candidates[] = $root . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'qpdf.exe' : 'qpdf');

        $candidates[] = 'qpdf';

        if (PHP_OS_FAMILY === 'Windows') {

            $candidates[] = 'C:\\Program Files\\qpdf\\bin\\qpdf.exe';

            $candidates[] = 'C:\\Program Files (x86)\\qpdf\\bin\\qpdf.exe';

        }

        foreach ($candidates as $bin) {

            if ($bin === '') {

                continue;

            }

            $out = [];

            $code = 1;

            @exec(escapeshellarg($bin) . ' --version 2>&1', $out, $code);

            if ($code === 0 && $out !== []) {

                return $bin;

            }

        }

        GenerateLog::generateLog('warning', 'PayrollPdfSplitService: qpdf não encontrado. Coloque qpdf em bin/qpdf.exe (ou bin/qpdf no Linux), ou defina QPDF_PATH no .env, ou instale qpdf no sistema.');

        return null;

    }



    /**

     * CLI qpdf: 0 = sucesso; 3 = sucesso com avisos (ex.: dicionários com chaves repetidas — ainda gera saída).

     */

    private static function qpdfExitMeansSuccess(int $code): bool

    {

        return $code === 0 || $code === 3;

    }



    private static function removeDirectoryRecursive(string $dir): void

    {

        if (!is_dir($dir)) {

            return;

        }

        $items = @scandir($dir);

        if ($items === false) {

            return;

        }

        foreach ($items as $item) {

            if ($item === '.' || $item === '..') {

                continue;

            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {

                self::removeDirectoryRecursive($path);

            } else {

                @unlink($path);

            }

        }

        @rmdir($dir);

    }



    /**

     * @param list<int> $pageNumbers 1-based

     * @param array<int, string>|null $singlePagePaths ficheiros de uma página por índice (qpdf)

     */

    private static function writePagesToNewPdf(string $sourceAbsolute, array $pageNumbers, int $userId, int $totalPageCount, ?array $singlePagePaths): ?string

    {

        if ($pageNumbers === []) {

            return null;

        }



        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);

        $dir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'payroll' . DIRECTORY_SEPARATOR . $userId;

        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {

            GenerateLog::generateLog('error', 'PayrollPdfSplitService: não foi possível criar pasta de destino do PDF', [

                'dir' => $dir,

                'user_id' => $userId,

            ]);

            return null;

        }

        if (!is_writable($dir)) {

            GenerateLog::generateLog('error', 'PayrollPdfSplitService: pasta de destino do PDF não é gravável', [

                'dir' => $dir,

                'user_id' => $userId,

            ]);

            return null;

        }



        $name = bin2hex(random_bytes(16)) . '.pdf';

        $absoluteOut = $dir . DIRECTORY_SEPARATOR . $name;

        $relReturn = 'storage/private/payroll/' . $userId . '/' . $name;



        if ($totalPageCount === 1 && count($pageNumbers) === 1 && (int)($pageNumbers[0] ?? 0) === 1) {

            if (@copy($sourceAbsolute, $absoluteOut)) {

                return $relReturn;

            }

            GenerateLog::generateLog('warning', 'PayrollPdfSplitService: copy() falhou para PDF de página única', [

                'source' => $sourceAbsolute,

            ]);

        }



        if ($singlePagePaths !== null) {

            if (!self::pagePathsCover($singlePagePaths, $pageNumbers)) {

                GenerateLog::generateLog('warning', 'PayrollPdfSplitService: ficheiros qpdf por página inacessíveis ou incompletos', [

                    'pages' => $pageNumbers,

                    'keys_sample' => array_slice(array_keys($singlePagePaths), 0, 5),

                ]);

            } elseif (self::pagePathsCover($singlePagePaths, $pageNumbers)) {

            if (count($pageNumbers) === 1) {

                $p = $pageNumbers[0];

                if (@copy($singlePagePaths[$p], $absoluteOut)) {

                    return $relReturn;

                }

                GenerateLog::generateLog('warning', 'PayrollPdfSplitService: copy() falhou ao gravar página única (origem qpdf)', [

                    'page' => $p,

                    'from' => $singlePagePaths[$p],

                    'to' => $absoluteOut,

                    'from_readable' => is_readable($singlePagePaths[$p]),

                ]);

            } elseif (($qpdfMerge = self::resolveQpdfBinary()) !== null) {

                $parts = [];

                foreach ($pageNumbers as $p) {

                    $parts[] = escapeshellarg($singlePagePaths[$p]);

                }

                $cmd = escapeshellarg($qpdfMerge) . ' --empty --pages ' . implode(' ', $parts) . ' -- ' . escapeshellarg($absoluteOut);

                $o = [];

                $c = 1;

                @exec($cmd . ' 2>&1', $o, $c);

                if (self::qpdfExitMeansSuccess($c) && is_file($absoluteOut) && filesize($absoluteOut) > 0) {

                    return $relReturn;

                }

                GenerateLog::generateLog('warning', 'PayrollPdfSplitService: qpdf merge falhou', [

                    'code' => $c,

                    'output' => implode("\n", array_slice($o, 0, 8)),

                ]);

            }

            }

        }



        if ($totalPageCount > 1 && $singlePagePaths === null) {

            GenerateLog::generateLog('warning', 'PayrollPdfSplitService: PDF multi-página sem split qpdf — a tentar FPDI (pode falhar em PDFs compactados); confirme bin/qpdf.exe, QPDF_PATH e exec() no PHP do Apache', [

                'pages' => $pageNumbers,

            ]);

        }



        try {

            $pdf = new Fpdi();

            $pdf->setSourceFile($sourceAbsolute);

            foreach ($pageNumbers as $p) {

                $tplId = $pdf->importPage($p);

                $size = $pdf->getTemplateSize($tplId);

                if ($size === false) {

                    throw new \RuntimeException('getTemplateSize falhou para página ' . $p);

                }

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);

                $pdf->useTemplate($tplId);

            }

            $pdf->Output('F', $absoluteOut);

        } catch (\Throwable $e) {

            GenerateLog::generateLog('error', 'PayrollPdfSplitService::writePagesToNewPdf — ' . $e->getMessage(), [

                'exception' => $e::class,

                'file' => $e->getFile(),

                'line' => $e->getLine(),

            ]);



            return null;

        }



        return $relReturn;

    }



    /**

     * @param array<int, string> $singlePagePaths

     * @param list<int> $pageNumbers

     */

    private static function pagePathsCover(array $singlePagePaths, array $pageNumbers): bool

    {

        foreach ($pageNumbers as $p) {

            if (!isset($singlePagePaths[$p]) || !is_readable($singlePagePaths[$p])) {

                return false;

            }

        }



        return true;

    }



    private static function buildTitle(string $prefix, int $year, ?int $month): string

    {

        if ($month !== null && $month >= 1 && $month <= 12) {

            return trim($prefix) . ' — ' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '/' . $year;

        }



        return trim($prefix) . ' — ' . $year;

    }

}


