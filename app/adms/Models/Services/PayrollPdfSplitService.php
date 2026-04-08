<?php



declare(strict_types=1);



namespace App\adms\Models\Services;



use App\adms\Helpers\GenerateLog;

use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;

use App\adms\Models\Repository\UsersRepository;

use setasign\Fpdi\Fpdi;

use Smalot\PdfParser\Parser;



/**

 * Lê texto com PdfParser, agrupa páginas consecutivas com o mesmo CPF e gera PDFs (cópia, qpdf, Imagick ou FPDI).

 * PDFs só com imagem (sem texto) não terão CPF — ficam como página não identificada.

 * Sem qpdf na hospedagem: tenta Imagick (PDF→página via delegate, ex. Ghostscript) e merge com FPDI; senão FPDI no ficheiro completo.

 */

final class PayrollPdfSplitService

{

    /**
     * @param string $originalFilename Nome do PDF; substituição só se nome + referência do lote coincidirem com a importação atual (tipo/ano/mês).
     * @return array{matched: int, unmatched_pages: list<int>, errors: list<string>, skipped_no_user: list<string>, documents_created: int}
     */
    public static function processUploadedFile(

        string $absolutePdfPath,

        int $batchId,

        string $documentType,

        int $referenceYear,

        ?int $referenceMonth,

        string $titlePrefix,

        int $createdByUserId,

        string $originalFilename

    ): array {

        $repo = new EmployeePayrollDocumentsRepository();

        $usersRepo = new UsersRepository();

        $originalFilename = trim($originalFilename);
        if ($originalFilename === '') {
            $originalFilename = 'documento.pdf';
        }

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
            $pageTextByNum = [];

            $flushGroup = function () use (&$currentCpf, &$currentPageNums, &$matched, &$documentsCreated, &$errors, &$skippedNoUser, &$pageTextByNum, $absolutePdfPath, $batchId, $documentType, $referenceYear, $referenceMonth, $titlePrefix, $repo, $usersRepo, $pageCount, $singlePagePaths, $originalFilename) {

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
                $firstPageNum = $currentPageNums[0];
                $netAmount = self::extractNetAmountFromText((string)($pageTextByNum[$firstPageNum] ?? ''));
                if ($netAmount === null) {
                    self::debugNetAmountExtraction(
                        (string)($pageTextByNum[$firstPageNum] ?? ''),
                        $currentCpf,
                        $firstPageNum
                    );
                }



                $repo->deleteExistingForUserRefSameOriginalFilename($userId, $documentType, $referenceYear, $referenceMonth, $originalFilename);

                $repo->insertDocument([

                    'user_id' => $userId,

                    'import_batch_id' => $batchId,

                    'document_type' => $documentType,

                    'reference_year' => $referenceYear,

                    'reference_month' => $referenceMonth,

                    'title' => $title,

                    'storage_path' => $relPath,

                    'file_size' => $size,
                    'net_amount' => $netAmount,

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
                    $pageTextByNum[$pageNum] = $text;

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

    /**
     * Extração simples de "Valor líquido" para exibição na lista do colaborador.
     *
     * @return string|null Formato decimal com ponto (ex.: 2993.34)
     */
    public static function extractNetAmountFromText(string $text): ?string
    {
        if ($text === '') {
            return null;
        }
        $text = str_replace(["\xc2\xa0", "\r"], [' ', ''], $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        $patterns = [
            '/Valor\s*l[ií]quido[^\d]{0,20}(\d{1,3}(?:\.\d{3})*,\d{2})/iu',
            '/L[ií]quido[^\d]{0,20}(\d{1,3}(?:\.\d{3})*,\d{2})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $m) && !empty($m[1])) {
                $raw = (string)end($m[1]);
                $normalized = str_replace('.', '', $raw);
                $normalized = str_replace(',', '.', $normalized);
                if (preg_match('/^\d+(?:\.\d{2})$/', $normalized)) {
                    return $normalized;
                }
            }
        }

        // Layout comum de folha: "Total dos Vencimentos Total dos Descontos Valor Líquido"
        // seguido por três valores monetários (nessa ordem). Neste caso, usa diretamente o 3º.
        if (preg_match('/Total\s+dos\s+Vencimentos\s+Total\s+dos\s+Descontos\s+Valor\s+L[ií]quido(.{0,1600})/iu', $text, $mTotalsLine)) {
            $window = (string)$mTotalsLine[1];
            if (preg_match_all('/\d{1,3}(?:\.\d{3})*,\d{2}/', $window, $mSeq) && count($mSeq[0]) >= 3) {
                $third = self::normalizeBrazilianMoney((string)$mSeq[0][2]);
                if ($third !== null) {
                    return $third;
                }
            }
        }

        // Busca tolerante: pega o primeiro valor monetário após "Valor líquido".
        if (preg_match('/valor\s*l[ií]quido(.{0,1600})/iu', $text, $mTail)) {
            if (preg_match('/(\d{1,3}(?:\.\d{3})*,\d{2})/', (string)$mTail[1], $mMoney)) {
                $n = self::normalizeBrazilianMoney((string)$mMoney[1]);
                if ($n !== null) {
                    return $n;
                }
            }
        }

        // Fallback para PDFs com texto muito fragmentado (caracteres espaçados).
        $folded = mb_strtolower(
            strtr(
                $text,
                [
                    'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
                    'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
                    'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
                    'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                    'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
                    'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
                    'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
                    'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                    'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
                    'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                    'Ç' => 'C', 'ç' => 'c',
                ]
            ),
            'UTF-8'
        );
        $compact = preg_replace('/\s+/u', '', $folded) ?? $folded;
        if (preg_match('/valorliquido(?:r\$)?([0-9]{1,3}(?:\.[0-9]{3})*,[0-9]{2})/u', $compact, $m4)) {
            $raw = (string)$m4[1];
            $normalized = str_replace('.', '', $raw);
            $normalized = str_replace(',', '.', $normalized);
            if (preg_match('/^\d+(?:\.\d{2})$/', $normalized)) {
                return $normalized;
            }
        }

        // Fallback: alguns PDFs quebram "Valor Liquido", mas mantêm totais.
        // Nesses casos, calcular: Total dos Vencimentos - Total dos Descontos.
        $venc = null;
        $desc = null;
        if (preg_match('/Total\s+dos\s+Vencimentos(.{0,1600})/iu', $text, $mvTail)) {
            if (preg_match('/(\d{1,3}(?:\.\d{3})*,\d{2})/', (string)$mvTail[1], $mv)) {
                $venc = self::normalizeBrazilianMoney((string)$mv[1]);
            }
        }
        if (preg_match('/Total\s+dos\s+Descontos(.{0,1600})/iu', $text, $mdTail)) {
            if (preg_match('/(\d{1,3}(?:\.\d{3})*,\d{2})/', (string)$mdTail[1], $md)) {
                $desc = self::normalizeBrazilianMoney((string)$md[1]);
            }
        }
        if ($venc !== null && $desc !== null) {
            $liquido = (float)$venc - (float)$desc;
            if ($liquido > 0) {
                return number_format($liquido, 2, '.', '');
            }
        }

        return null;
    }

    private static function normalizeBrazilianMoney(string $raw): ?string
    {
        $n = str_replace('.', '', trim($raw));
        $n = str_replace(',', '.', $n);
        if (!preg_match('/^\d+(?:\.\d{2})$/', $n)) {
            return null;
        }
        return $n;
    }

    /**
     * Diagnóstico temporário para entender como o parser lê o "Valor líquido".
     */
    private static function debugNetAmountExtraction(string $text, ?string $cpf, int $pageNum): void
    {
        if ($text === '') {
            GenerateLog::generateLog('warning', 'PayrollPdfSplitService: net_amount null (texto vazio)', [
                'cpf' => $cpf,
                'page' => $pageNum,
            ]);
            return;
        }

        $flat = preg_replace('/\s+/u', ' ', str_replace(["\xc2\xa0", "\r"], [' ', ''], $text)) ?? $text;
        $pos = mb_stripos($flat, 'valor', 0, 'UTF-8');
        $snippet = $pos !== false ? mb_substr($flat, max(0, (int)$pos - 80), 260, 'UTF-8') : mb_substr($flat, 0, 260, 'UTF-8');
        $moneyHits = [];
        if (preg_match_all('/\d{1,3}(?:\.\d{3})*,\d{2}/', $flat, $m)) {
            $moneyHits = array_slice($m[0], 0, 10);
        }

        GenerateLog::generateLog('warning', 'PayrollPdfSplitService: net_amount não extraído', [
            'cpf' => $cpf,
            'page' => $pageNum,
            'snippet' => $snippet,
            'money_hits' => $moneyHits,
        ]);
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

        $qpdf = self::splitPdfIntoSinglePageFilesWithQpdf($absolutePdfPath, $pageCount);

        if ($qpdf !== null) {

            return $qpdf;

        }

        $imagick = self::splitPdfIntoSinglePageFilesViaImagick($absolutePdfPath, $pageCount);

        if ($imagick !== null) {

            GenerateLog::generateLog('info', 'PayrollPdfSplitService: páginas extraídas via Imagick (alternativa a qpdf na hospedagem)', []);

            return $imagick;

        }

        GenerateLog::generateLog('warning', 'PayrollPdfSplitService: sem qpdf nem Imagick para dividir páginas; será tentado FPDI no PDF completo', []);

        return null;

    }

    /**

     * @return array{dir: string, map: array<int, string>}|null

     */

    private static function splitPdfIntoSinglePageFilesWithQpdf(string $absolutePdfPath, int $pageCount): ?array

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
     * Hospedagem sem qpdf: ImageMagick lê cada página do PDF (requer delegate PDF, p.ex. Ghostscript, comum no Linux).
     * Gera um PDF de uma página por ficheiro, normalmente compatível com FPDI no merge.
     *
     * @return array{dir: string, map: array<int, string>}|null
     */
    private static function splitPdfIntoSinglePageFilesViaImagick(string $absolutePdfPath, int $pageCount): ?array
    {
        if ($pageCount < 1 || !is_readable($absolutePdfPath)) {
            return null;
        }
        if (!extension_loaded('imagick') || !class_exists(\Imagick::class)) {
            return null;
        }

        $normalized = self::normalizePathForImagickRead($absolutePdfPath);
        if ($normalized === null) {
            return null;
        }

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'payroll_imagick_' . bin2hex(random_bytes(8));
        if (!@mkdir($dir, 0700, true) && !is_dir($dir)) {
            return null;
        }

        $map = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pageNum = $i + 1;
            $out = $dir . DIRECTORY_SEPARATOR . 'p-' . $pageNum . '.pdf';
            try {
                $im = new \Imagick();
                $im->setResolution(150, 150);
                $im->readImage($normalized . '[' . $i . ']');
                $im->setImageFormat('pdf');
                $im->writeImage($out);
                $im->clear();
                $im->destroy();
            } catch (\Throwable $e) {
                GenerateLog::generateLog('warning', 'PayrollPdfSplitService: Imagick falhou ao extrair página', [
                    'page' => $pageNum,
                    'message' => $e->getMessage(),
                ]);
                self::removeDirectoryRecursive($dir);
                return null;
            }
            if (!is_file($out) || (int)filesize($out) < 1) {
                self::removeDirectoryRecursive($dir);
                return null;
            }
            $map[$pageNum] = $out;
        }

        return ['dir' => $dir, 'map' => $map];
    }

    /**
     * Imagick no Windows/Linux aceita path com seletor [n]; normaliza barras.
     */
    private static function normalizePathForImagickRead(string $absolutePdfPath): ?string
    {
        $rp = realpath($absolutePdfPath);
        if ($rp === false || !is_readable($rp)) {
            return null;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            return str_replace('\\', '/', $rp);
        }
        return $rp;
    }

    /**
     * Junta PDFs de uma página (saída qpdf ou Imagick) com FPDI — cada ficheiro tem só a página 1.
     */
    private static function mergePdfPagesWithFpdi(array $singlePagePaths, array $pageNumbers, string $absoluteOut): bool
    {
        if ($pageNumbers === []) {
            return false;
        }
        try {
            $pdf = new Fpdi();
            foreach ($pageNumbers as $p) {
                if (!isset($singlePagePaths[$p]) || !is_readable($singlePagePaths[$p])) {
                    return false;
                }
                $pdf->setSourceFile($singlePagePaths[$p]);
                $tplId = $pdf->importPage(1);
                $size = $pdf->getTemplateSize($tplId);
                if ($size === false) {
                    return false;
                }
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }
            $pdf->Output('F', $absoluteOut);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'PayrollPdfSplitService: merge FPDI a partir de páginas isoladas falhou', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }

        return is_file($absoluteOut) && filesize($absoluteOut) > 0;
    }



    /**

     * Caminho do executável qpdf: variável de ambiente QPDF_PATH, depois PATH, depois pastas típicas no Windows.

     */

    private static function resolveQpdfBinary(): ?string

    {

        if (!function_exists('exec')) {

            GenerateLog::generateLog('warning', 'PayrollPdfSplitService: função exec() indisponível (PHP disable_functions); instale qpdf e não desative exec para PDFs multi-página.', []);

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

        GenerateLog::generateLog('warning', 'PayrollPdfSplitService: qpdf não encontrado. Coloque qpdf em bin/qpdf.exe (ou bin/qpdf no Linux), ou defina QPDF_PATH no .env, ou instale qpdf no sistema.', []);

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

                    GenerateLog::generateLog('warning', 'PayrollPdfSplitService: copy() falhou ao gravar página única (origem split)', [

                        'page' => $p,

                        'from' => $singlePagePaths[$p],

                        'to' => $absoluteOut,

                        'from_readable' => is_readable($singlePagePaths[$p]),

                    ]);

                } else {

                    $qpdfMerge = self::resolveQpdfBinary();

                    if ($qpdfMerge !== null) {

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

                    if (self::mergePdfPagesWithFpdi($singlePagePaths, $pageNumbers, $absoluteOut)) {

                        return $relReturn;

                    }

                    GenerateLog::generateLog('warning', 'PayrollPdfSplitService: merge via FPDI a partir de páginas isoladas falhou (qpdf indisponível ou merge anterior falhou)', [

                        'pages' => $pageNumbers,

                    ]);

                }

            }

        }



        if ($totalPageCount > 1 && $singlePagePaths === null) {

            GenerateLog::generateLog('warning', 'PayrollPdfSplitService: PDF multi-página sem split (qpdf/Imagick) — a tentar FPDI no ficheiro completo (pode falhar em PDFs compactados). Ative extensão Imagick no PHP se possível.', [

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


