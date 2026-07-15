<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportUsers
{
    private array|string|null $data = null;

    public function index(string $action = ''): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Sessão inválida! Faça login para continuar.';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            return;
        }

        if ($action === 'template') {
            $this->template();
            return;
        }

        if ($action === 'cancel-map') {
            $this->clearPendingImport();
            $_SESSION['success'] = 'Mapeamento cancelado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-users');
            return;
        }

        $this->data['form'] = $_POST ?? [];
        $this->data['mappable_fields'] = $this->mappableFields();

        // Aplicar mapeamento (2º passo)
        if (
            !empty($this->data['form']['apply_mapping'])
            && isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_import_users_map', $this->data['form']['csrf_token'])
        ) {
            $this->processMappedImport();
            return;
        }

        // Upload (1º passo)
        if (
            !empty($_FILES['file'])
            && isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_import_users', $this->data['form']['csrf_token'])
        ) {
            $mode = (string)($this->data['form']['import_mode'] ?? 'map');
            if ($mode === 'template') {
                $this->processFile();
                return;
            }
            $this->prepareMappingFromUpload();
            return;
        }

        // Se há mapeamento pendente, exibir tela
        if (!empty($_SESSION['import_users_pending']['file'])) {
            $pending = $_SESSION['import_users_pending'];
            $pendingHeaders = $pending['headers'] ?? [];
            if (!is_file((string)$pending['file']) || count($pendingHeaders) < 2) {
                $this->clearPendingImport();
                if (is_array($pendingHeaders) && count($pendingHeaders) < 2) {
                    $this->data['errors'][] = 'O arquivo anterior tinha apenas 1 coluna e foi descartado. Salve no Excel como CSV (separador ; ou ,) com cada campo em uma coluna, e envie de novo.';
                }
            } else {
                $this->data['mapping'] = $pending;
                $this->data['suggested_map'] = $this->suggestColumnMap($pendingHeaders);
            }
        }

        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Importar Usuários',
            'menu' => 'list-users',
            'buttonPermission' => ['ListUsers'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/users/importUsers', $this->data);
        $loadView->loadView();
    }

    /** @return array<string, string> */
    private function mappableFields(): array
    {
        return [
            '' => '(ignorar coluna)',
            'id' => 'ID do usuário (chave)',
            'cpf' => 'CPF (chave)',
            'username' => 'Usuário / login (chave)',
            'name' => 'Nome',
            'email' => 'E-mail corporativo',
            'email_pessoal' => 'E-mail pessoal',
            'celular' => 'Celular',
            'department_id' => 'ID Departamento',
            'position_id' => 'ID Cargo',
            'immediate_supervisor_id' => 'ID Supervisor imediato',
            'status' => 'Status',
            'bloqueado' => 'Bloqueado',
            'senha_nunca_expira' => 'Senha nunca expira',
            'modificar_senha_proximo_logon' => 'Modificar senha no próximo logon',
            'data_nascimento' => 'Data de nascimento',
            'data_admissao' => 'Data de admissão',
            'data_desligamento' => 'Data de desligamento',
            'motivo_desligamento' => 'Motivo do desligamento',
            'escolaridade' => 'Escolaridade',
            'raca' => 'Raça/cor',
            'matricula' => 'Matrícula',
            'pais_residencia_iso' => 'País (ISO)',
            'cep' => 'CEP',
            'endereco' => 'Logradouro',
            'numero_endereco' => 'Número',
            'complemento_endereco' => 'Complemento',
            'bairro' => 'Bairro',
            'municipio' => 'Município',
            'uf' => 'UF',
        ];
    }

    private function clearPendingImport(): void
    {
        $file = $_SESSION['import_users_pending']['file'] ?? null;
        if (is_string($file) && $file !== '' && is_file($file)) {
            @unlink($file);
        }
        unset($_SESSION['import_users_pending']);
    }

    /** @param list<string> $headers */
    private function suggestColumnMap(array $headers): array
    {
        $fields = array_keys($this->mappableFields());
        $suggested = [];
        foreach ($headers as $i => $header) {
            $h = strtolower(trim((string)$header));
            $h = str_replace([' ', '-'], '_', $h);
            $aliases = [
                'departamento' => 'department_id',
                'department' => 'department_id',
                'cargo' => 'position_id',
                'position' => 'position_id',
                'usuario' => 'username',
                'login' => 'username',
                'user.name' => 'username',
                'e-mail' => 'email',
                'email_corporativo' => 'email',
                'raça' => 'raca',
                'raca_cor' => 'raca',
                'logradouro' => 'endereco',
                'numero' => 'numero_endereco',
                'complemento' => 'complemento_endereco',
                'cidade' => 'municipio',
                'pais' => 'pais_residencia_iso',
            ];
            if (isset($aliases[$h])) {
                $suggested[$i] = $aliases[$h];
                continue;
            }
            $suggested[$i] = in_array($h, $fields, true) ? $h : '';
        }

        return $suggested;
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $n = $index;
        do {
            $letter = chr(65 + ($n % 26)) . $letter;
            $n = intdiv($n, 26) - 1;
        } while ($n >= 0);

        return $letter;
    }

    private function prepareMappingFromUpload(): void
    {
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->data['errors'][] = 'Falha ao enviar o arquivo.';
            $this->view();
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $parsed = null;

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            $parsed = $this->parseExcelFile($file['tmp_name']);
            if ($parsed === null) {
                $this->data['errors'][] = 'Não foi possível ler a planilha Excel. Verifique se a primeira linha é o cabeçalho e há mais de uma coluna.';
                $this->view();
                return;
            }
        } elseif ($ext === 'csv') {
            $parsed = $this->parseCsvFile($file['tmp_name']);
            if ($parsed === null) {
                $this->data['errors'][] = 'Não foi possível ler o CSV. Prefira enviar o arquivo .xlsx do Excel, ou salve como CSV com separador ; ou ,.';
                $this->view();
                return;
            }
        } else {
            $this->data['errors'][] = 'Envie um arquivo Excel (.xlsx / .xls) ou CSV.';
            $this->view();
            return;
        }

        if (count($parsed['headers']) < 2) {
            @unlink($parsed['normalized_path']);
            $this->data['errors'][] = 'O arquivo foi lido com apenas 1 coluna. No Excel, confira se cada campo está em uma coluna (A, B, C…) e envie o .xlsx (recomendado) ou CSV com separador ;.';
            $this->view();
            return;
        }

        $this->clearPendingImport();

        $destDir = sys_get_temp_dir();
        $dest = tempnam($destDir, 'imp_users_');
        if ($dest === false || !copy($parsed['normalized_path'], $dest)) {
            @unlink($parsed['normalized_path']);
            $this->data['errors'][] = 'Não foi possível armazenar o arquivo temporário.';
            $this->view();
            return;
        }
        @unlink($parsed['normalized_path']);

        $letters = [];
        foreach (array_keys($parsed['headers']) as $i) {
            $letters[$i] = $this->columnLetter((int)$i);
        }

        $_SESSION['import_users_pending'] = [
            'file' => $dest,
            'original_name' => (string)($file['name'] ?? 'import.csv'),
            'headers' => $parsed['headers'],
            'letters' => $letters,
            'preview' => $parsed['preview'],
            'delimiter' => $parsed['delimiter'],
            'created_at' => time(),
        ];

        $this->data['mapping'] = $_SESSION['import_users_pending'];
        $this->data['suggested_map'] = $this->suggestColumnMap($parsed['headers']);
        $this->view();
    }

    /**
     * Lê .xlsx/.xls e gera CSV temporário com ; (mesmo formato do fluxo de mapeamento).
     *
     * @return array{headers: list<string>, preview: list<list<string>>, delimiter: string, normalized_path: string}|null
     */
    private function parseExcelFile(string $tmpPath): ?array
    {
        try {
            $spreadsheet = IOFactory::load($tmpPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Falha ao ler Excel na importação de usuários.', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        if ($rows === []) {
            return null;
        }

        // Remove linhas totalmente vazias
        $rows = array_values(array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') {
                    return true;
                }
            }
            return false;
        }));

        if ($rows === []) {
            return null;
        }

        $headerRow = array_map(static fn ($v) => trim((string)$v), $rows[0]);
        // Remove colunas vazias à direita no cabeçalho
        while ($headerRow !== [] && end($headerRow) === '') {
            array_pop($headerRow);
        }
        if (count($headerRow) < 1) {
            return null;
        }
        $colCount = count($headerRow);
        $headers = $headerRow;

        $preview = [];
        $dataLines = [];
        for ($r = 1, $n = count($rows); $r < $n; $r++) {
            $row = $rows[$r];
            if (!is_array($row)) {
                continue;
            }
            $cells = [];
            for ($c = 0; $c < $colCount; $c++) {
                $cells[] = trim((string)($row[$c] ?? ''));
            }
            if (count(array_filter($cells, static fn ($v) => $v !== '')) === 0) {
                continue;
            }
            $dataLines[] = $cells;
            if (count($preview) < 5) {
                $preview[] = $cells;
            }
        }

        $normalized = tempnam(sys_get_temp_dir(), 'csv_xlsx_');
        if ($normalized === false) {
            return null;
        }

        $fp = fopen($normalized, 'w');
        if (!$fp) {
            @unlink($normalized);
            return null;
        }
        // BOM UTF-8
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, $headers, ';');
        foreach ($dataLines as $line) {
            fputcsv($fp, $line, ';');
        }
        fclose($fp);

        return [
            'headers' => $headers,
            'preview' => $preview,
            'delimiter' => ';',
            'normalized_path' => $normalized,
        ];
    }

    /**
     * Normaliza BOM/encoding (Excel costuma salvar UTF-16).
     */
    private function normalizeCsvContent(string $content): string
    {
        if (str_starts_with($content, "\xFF\xFE")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($content, "\xFE\xFF")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        } elseif (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        } elseif (strlen($content) > 4 && $content[1] === "\x00" && $content[3] === "\x00") {
            // UTF-16LE sem BOM
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return $content;
    }

    /**
     * Se a linha parece um cabeçalho com campos conhecidos separados por espaço/tab.
     *
     * @return list<string>|null
     */
    private function splitHeaderIfKnownFields(string $line): ?array
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }
        // Remove aspas externas
        if (strlen($line) >= 2 && $line[0] === '"' && str_ends_with($line, '"')) {
            $line = str_replace('""', '"', substr($line, 1, -1));
        }

        foreach (["\t", ';', ','] as $delim) {
            if (substr_count($line, $delim) >= 1) {
                $parts = array_values(array_filter(array_map('trim', explode($delim, $line)), static fn ($v) => $v !== ''));
                if (count($parts) >= 2) {
                    return $parts;
                }
            }
        }

        $parts = preg_split('/\s+/u', $line) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), static fn ($v) => $v !== ''));
        if (count($parts) < 2) {
            return null;
        }

        $fields = array_keys($this->mappableFields());
        $aliases = [
            'departamento', 'department', 'cargo', 'position', 'usuario', 'login', 'user.name',
            'e-mail', 'email_corporativo', 'raça', 'raca_cor', 'logradouro', 'numero', 'complemento', 'cidade', 'pais',
        ];
        $hits = 0;
        foreach ($parts as $p) {
            $h = strtolower(str_replace([' ', '-'], '_', $p));
            if ($h === 'user.name') {
                $h = 'username';
            }
            if (in_array($h, $fields, true) || in_array($h, $aliases, true)) {
                $hits++;
            }
        }

        if ($hits >= 2 && $hits >= (int)ceil(count($parts) * 0.5)) {
            return $parts;
        }

        return null;
    }

    /**
     * Ajusta tokens quando valores (ex.: endereço) têm espaços.
     *
     * @param list<string> $tokens
     * @param list<string> $headers
     * @return list<string>|null
     */
    private function fitTokensToHeaderCount(array $tokens, array $headers): ?array
    {
        $headerCount = count($headers);
        $tokens = array_values($tokens);
        if (count($tokens) === $headerCount) {
            return $tokens;
        }
        if (count($tokens) < $headerCount) {
            return null;
        }

        $norm = array_map(
            static fn ($x) => strtolower(str_replace([' ', '-'], '_', (string)$x)),
            $headers
        );

        $cepIdx = array_search('cep', $norm, true);
        $numIdx = array_search('numero_endereco', $norm, true);
        $endIdx = array_search('endereco', $norm, true);

        // Âncoras: CEP (8 dígitos) e número do endereço
        if ($cepIdx !== false && $endIdx !== false && $numIdx !== false && $numIdx === $endIdx + 1) {
            $cepTok = null;
            $numTok = null;
            foreach ($tokens as $i => $t) {
                $digits = preg_replace('/\D+/', '', (string)$t) ?? '';
                if ($cepTok === null && strlen($digits) === 8 && preg_match('/^\d{8}$/', $digits)) {
                    $cepTok = $i;
                    continue;
                }
                if ($cepTok !== null && $numTok === null && preg_match('/^\d+[A-Za-z]?$/', (string)$t)) {
                    $numTok = $i;
                    break;
                }
            }
            if ($cepTok !== null && $numTok !== null && $numTok > $cepTok) {
                $before = array_slice($tokens, 0, $cepTok);
                // before deve ter cepIdx itens (campos antes do cep)
                if (count($before) === $cepIdx) {
                    $enderecoParts = array_slice($tokens, $cepTok + 1, $numTok - $cepTok - 1);
                    $afterNum = array_slice($tokens, $numTok + 1);
                    $tailHeaders = $headerCount - ($numIdx + 1);
                    $tail = $this->fitTokensToHeaderCount(
                        $afterNum,
                        array_slice($headers, $numIdx + 1)
                    );
                    if ($tail !== null && count($enderecoParts) >= 1 && count($tail) === $tailHeaders) {
                        return array_merge(
                            $before,
                            [(string)$tokens[$cepTok]],
                            [implode(' ', $enderecoParts)],
                            [(string)$tokens[$numTok]],
                            $tail
                        );
                    }
                }
            }
        }

        $prefer = ['endereco', 'complemento_endereco', 'bairro', 'municipio', 'name', 'motivo_desligamento'];
        $mergeAt = null;
        foreach ($prefer as $p) {
            $i = array_search($p, $norm, true);
            if ($i !== false) {
                $mergeAt = (int)$i;
                break;
            }
        }
        if ($mergeAt === null) {
            $mergeAt = max(0, $headerCount - 3);
        }

        while (count($tokens) > $headerCount) {
            if ($mergeAt >= count($tokens) - 1) {
                return null;
            }
            $tokens[$mergeAt] = trim($tokens[$mergeAt] . ' ' . $tokens[$mergeAt + 1]);
            array_splice($tokens, $mergeAt + 1, 1);
        }

        return count($tokens) === $headerCount ? array_values($tokens) : null;
    }

    /**
     * Regrava CSV com delimitador ; a partir de cabeçalho já separado e linhas brutas.
     *
     * @param list<string> $headers
     * @return array{headers: list<string>, preview: list<list<string>>, delimiter: string, normalized_path: string}|null
     */
    private function rebuildCsvWithSemicolon(string $normalizedPath, array $headers, string $rawContent): ?array
    {
        $lines = preg_split("/\n/", $rawContent) ?: [];
        $lines = array_values(array_filter($lines, static fn ($l) => trim((string)$l) !== ''));
        if ($lines === []) {
            return null;
        }

        $outLines = [implode(';', $headers)];
        $preview = [];
        $headerCount = count($headers);

        for ($i = 1, $n = count($lines); $i < $n; $i++) {
            $line = trim((string)$lines[$i]);
            if ($line === '') {
                continue;
            }
            if (strlen($line) >= 2 && $line[0] === '"' && str_ends_with($line, '"')) {
                $line = str_replace('""', '"', substr($line, 1, -1));
            }

            $cols = null;
            foreach (["\t", ';', ','] as $delim) {
                if (substr_count($line, $delim) < 1) {
                    continue;
                }
                $try = array_map('trim', explode($delim, $line));
                $try = array_values(array_filter($try, static fn ($v) => $v !== ''));
                $fitted = $this->fitTokensToHeaderCount($try, $headers);
                if ($fitted !== null) {
                    $cols = $fitted;
                    break;
                }
            }
            if ($cols === null) {
                $try = preg_split('/\s+/u', $line) ?: [];
                $try = array_values(array_filter(array_map('trim', $try), static fn ($v) => $v !== ''));
                $cols = $this->fitTokensToHeaderCount($try, $headers);
            }
            if ($cols === null) {
                return null;
            }
            $outLines[] = implode(';', $cols);
            if (count($preview) < 5) {
                $preview[] = $cols;
            }
        }

        if (count($outLines) < 2) {
            // Só cabeçalho — ainda assim permite mapear (usuário pode ter só header)
            file_put_contents($normalizedPath, $outLines[0] . "\n");
            return [
                'headers' => $headers,
                'preview' => [],
                'delimiter' => ';',
                'normalized_path' => $normalizedPath,
            ];
        }

        file_put_contents($normalizedPath, implode("\n", $outLines) . "\n");

        return [
            'headers' => $headers,
            'preview' => $preview,
            'delimiter' => ';',
            'normalized_path' => $normalizedPath,
        ];
    }

    /**
     * Detecta o separador pela 1ª linha: prioriza o que gera mais colunas úteis.
     */
    private function detectCsvDelimiter(string $firstLine): string
    {
        $candidates = [';' => 0, ',' => 0, "\t" => 0];
        foreach (array_keys($candidates) as $delim) {
            $cols = str_getcsv($firstLine, $delim);
            $nonEmpty = 0;
            foreach ($cols as $col) {
                if (trim((string)$col) !== '') {
                    $nonEmpty++;
                }
            }
            $candidates[$delim] = $nonEmpty;
        }
        arsort($candidates);
        $best = (string)array_key_first($candidates);
        if (($candidates[$best] ?? 0) < 2) {
            $semi = substr_count($firstLine, ';');
            $comma = substr_count($firstLine, ',');
            $tab = substr_count($firstLine, "\t");
            if ($tab >= $semi && $tab >= $comma && $tab > 0) {
                return "\t";
            }
            if ($comma > $semi) {
                return ',';
            }
            return ';';
        }

        return $best;
    }

    /**
     * @return array{headers: list<string>, preview: list<list<string>>, delimiter: string, normalized_path: string}|null
     */
    private function parseCsvFile(string $tmpPath): ?array
    {
        $raw = file_get_contents($tmpPath);
        if ($raw === false || $raw === '') {
            return null;
        }

        $content = $this->normalizeCsvContent($raw);

        $normalized = tempnam(sys_get_temp_dir(), 'csv_utf8_');
        if ($normalized === false) {
            return null;
        }
        file_put_contents($normalized, $content);

        $fp = fopen($normalized, 'r');
        if (!$fp) {
            @unlink($normalized);
            return null;
        }
        $firstLine = fgets($fp);
        rewind($fp);
        if (!is_string($firstLine) || trim($firstLine) === '') {
            fclose($fp);
            @unlink($normalized);
            return null;
        }

        $delimiter = $this->detectCsvDelimiter($firstLine);
        $header = fgetcsv($fp, 0, $delimiter);
        if (!$header || count(array_filter($header, static fn ($v) => trim((string)$v) !== '')) === 0) {
            fclose($fp);
            @unlink($normalized);
            return null;
        }
        $headers = array_map(static fn ($v) => trim((string)$v), $header);

        if (count($headers) === 1) {
            foreach ([';', ',', "\t"] as $retry) {
                if ($retry === $delimiter) {
                    continue;
                }
                $retryCols = str_getcsv($firstLine, $retry);
                $retryNonEmpty = count(array_filter($retryCols, static fn ($v) => trim((string)$v) !== ''));
                if ($retryNonEmpty > 1) {
                    rewind($fp);
                    $delimiter = $retry;
                    $header = fgetcsv($fp, 0, $delimiter);
                    $headers = array_map(static fn ($v) => trim((string)$v), $header ?: []);
                    break;
                }
            }
        }

        // Excel às vezes salva a linha inteira numa única célula (ainda com ; , ou tab dentro)
        if (count($headers) === 1) {
            $only = $headers[0];
            foreach ([';', ',', "\t"] as $inner) {
                if (substr_count($only, $inner) < 1) {
                    continue;
                }
                $parts = array_map('trim', explode($inner, $only));
                $parts = array_values(array_filter($parts, static fn ($v) => $v !== ''));
                if (count($parts) < 2) {
                    continue;
                }
                fclose($fp);
                $rebuilt = $this->rebuildCsvWithSemicolon($normalized, $parts, $content);
                if ($rebuilt !== null) {
                    return $rebuilt;
                }
                // Se não reconstruiu dados, pelo menos usa o cabeçalho separado se as linhas tiverem o mesmo delim
                $delimiter = $inner;
                $headers = $parts;
                $fp = fopen($normalized, 'r');
                if (!$fp) {
                    @unlink($normalized);
                    return null;
                }
                break;
            }
        }

        // Cabeçalho com campos conhecidos separados só por espaço (arquivo “quebrado” no Excel/Bloco de notas)
        if (count($headers) === 1) {
            $known = $this->splitHeaderIfKnownFields($headers[0] !== '' ? $headers[0] : $firstLine);
            if ($known !== null) {
                fclose($fp);
                $rebuilt = $this->rebuildCsvWithSemicolon($normalized, $known, $content);
                if ($rebuilt !== null) {
                    return $rebuilt;
                }
                // Não foi possível alinhar as linhas de dados — falha clara no upload
                @unlink($normalized);
                return null;
            }
        }

        $preview = [];
        while (count($preview) < 5 && ($row = fgetcsv($fp, 0, $delimiter)) !== false) {
            if (count(array_filter($row, static fn ($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }
            $preview[] = array_map(static fn ($v) => (string)$v, $row);
        }
        fclose($fp);

        return [
            'headers' => $headers,
            'preview' => $preview,
            'delimiter' => $delimiter,
            'normalized_path' => $normalized,
        ];
    }

    private function processMappedImport(): void
    {
        $pending = $_SESSION['import_users_pending'] ?? null;
        if (!is_array($pending) || empty($pending['file']) || !is_file((string)$pending['file'])) {
            $this->data['errors'][] = 'Arquivo de importação expirado. Envie novamente.';
            $this->clearPendingImport();
            $this->view();
            return;
        }

        $fieldMap = $_POST['field_map'] ?? null;
        $columnMap = $_POST['column_map'] ?? [];
        if (!is_array($columnMap)) {
            $columnMap = [];
        }

        $keyField = (string)($_POST['key_field'] ?? '');
        $method = (string)($_POST['import_method'] ?? 'update_only');
        if (!in_array($method, ['update_only', 'upsert'], true)) {
            $method = 'update_only';
        }

        $allowed = array_keys($this->mappableFields());
        $fieldToIndex = [];

        if (is_array($fieldMap)) {
            $usedCols = [];
            foreach ($fieldMap as $field => $colIdx) {
                $field = (string)$field;
                if ($field === '' || !in_array($field, $allowed, true)) {
                    continue;
                }
                if ($colIdx === '' || $colIdx === null) {
                    continue;
                }
                $colIdx = (int)$colIdx;
                if (isset($usedCols[$colIdx])) {
                    $this->data['errors'][] = 'A mesma coluna do arquivo foi associada a mais de um campo.';
                    $this->data['mapping'] = $pending;
                    $this->data['suggested_map'] = $this->suggestColumnMap($pending['headers'] ?? []);
                    $this->view();
                    return;
                }
                $usedCols[$colIdx] = true;
                $fieldToIndex[$field] = $colIdx;
            }
        } else {
            foreach ($columnMap as $colIdx => $field) {
                $field = (string)$field;
                if ($field === '' || !in_array($field, $allowed, true)) {
                    continue;
                }
                if (isset($fieldToIndex[$field])) {
                    $this->data['errors'][] = 'O campo "' . $field . '" foi mapeado em mais de uma coluna.';
                    $this->data['mapping'] = $pending;
                    $this->data['suggested_map'] = $columnMap;
                    $this->view();
                    return;
                }
                $fieldToIndex[$field] = (int)$colIdx;
            }
        }

        if (!in_array($keyField, ['id', 'cpf', 'username'], true) || !isset($fieldToIndex[$keyField])) {
            $this->data['errors'][] = 'Selecione uma chave única (ID, CPF ou Usuário) e associe a coluna correspondente.';
            $this->data['mapping'] = $pending;
            $this->data['suggested_map'] = $this->suggestColumnMap($pending['headers'] ?? []);
            $this->view();
            return;
        }

        $ok = $this->runMappedCsv(
            (string)$pending['file'],
            (string)($pending['delimiter'] ?? ';'),
            $fieldToIndex,
            $keyField,
            $method
        );

        $this->clearPendingImport();
        if (!$ok && empty($this->data['errors'])) {
            $this->data['errors'][] = 'Não foi possível processar o arquivo mapeado.';
        }
        $this->view();
    }

    /**
     * @param array<string, int> $fieldToIndex
     */
    private function runMappedCsv(string $filePath, string $delimiter, array $fieldToIndex, string $keyField, string $method): bool
    {
        $fp = fopen($filePath, 'r');
        if (!$fp) {
            return false;
        }

        $header = fgetcsv($fp, 0, $delimiter);
        if (!$header) {
            fclose($fp);
            return false;
        }

        $repo = new UsersRepository();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $rows = 1;
        $this->data['report'] = [];

        $toBoolLabel = static function ($val): string {
            $v = strtolower(trim((string)$val));
            $v = strtr($v, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);
            if (in_array($v, ['sim','s','yes','y','true','1'], true)) {
                return 'Sim';
            }
            if (in_array($v, ['nao','não','n','no','false','0'], true)) {
                return 'Não';
            }
            return 'Não';
        };
        $toDate = static function ($val): ?string {
            $v = trim((string)$val);
            if ($v === '') {
                return null;
            }
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
                return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                return $v;
            }
            $t = strtotime($v);
            return $t ? date('Y-m-d', $t) : null;
        };
        $raw = static function (array $row, array $fieldToIndex, string $field): string {
            if (!isset($fieldToIndex[$field])) {
                return '';
            }
            return trim((string)($row[$fieldToIndex[$field]] ?? ''));
        };

        while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
            $rows++;
            if (count(array_filter($row, static fn ($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            $keyRaw = $raw($row, $fieldToIndex, $keyField);
            if ($keyRaw === '') {
                $errors++;
                $this->data['report'][] = ['linha' => $rows, 'acao' => 'erro', 'email' => '', 'msg' => 'Chave vazia (' . $keyField . ').'];
                continue;
            }

            try {
                $existing = false;
                if ($keyField === 'id') {
                    $id = (int)preg_replace('/\D/', '', $keyRaw);
                    $existing = $id > 0 ? $repo->getUser($id) : false;
                } elseif ($keyField === 'cpf') {
                    $cpf11 = preg_replace('/\D/', '', $keyRaw) ?? '';
                    $existing = $repo->getUserByNormalizedCpf($cpf11);
                } else {
                    $existing = $repo->getUserByUsername($keyRaw);
                    if ($existing) {
                        $existing = $repo->getUser((int)$existing['id']);
                    }
                }

                if (!$existing) {
                    if ($method === 'update_only') {
                        $skipped++;
                        $this->data['report'][] = [
                            'linha' => $rows,
                            'acao' => 'ignorado',
                            'email' => $raw($row, $fieldToIndex, 'email'),
                            'msg' => 'Usuário não encontrado pela chave ' . $keyField . '.',
                        ];
                        continue;
                    }

                    // upsert create
                    $name = $raw($row, $fieldToIndex, 'name');
                    $email = $raw($row, $fieldToIndex, 'email');
                    $username = $raw($row, $fieldToIndex, 'username');
                    $dep = (int)$raw($row, $fieldToIndex, 'department_id');
                    $pos = (int)$raw($row, $fieldToIndex, 'position_id');
                    if ($name === '' || $email === '' || $username === '' || $dep <= 0 || $pos <= 0) {
                        $errors++;
                        $this->data['report'][] = [
                            'linha' => $rows,
                            'acao' => 'erro',
                            'email' => $email,
                            'msg' => 'Para criar: mapeie name, email, username, department_id e position_id.',
                        ];
                        continue;
                    }

                    $payload = $this->buildMappedPayload($row, $fieldToIndex, $toBoolLabel, $toDate, $raw);
                    $payload['name'] = $name;
                    $payload['email'] = $email;
                    $payload['username'] = $username;
                    $payload['user_department_id'] = $dep;
                    $payload['user_position_id'] = $pos;
                    if (($payload['password'] ?? '') === '') {
                        $payload['password'] = bin2hex(random_bytes(6));
                    }
                    if (!empty($payload['data_desligamento'])) {
                        $errors++;
                        $this->data['report'][] = [
                            'linha' => $rows,
                            'acao' => 'erro',
                            'email' => $email,
                            'msg' => 'Não crie usuário já desligado via importação.',
                        ];
                        continue;
                    }
                    $okCreate = $repo->createUser($payload);
                    if ($okCreate) {
                        $created++;
                        $this->data['report'][] = [
                            'linha' => $rows,
                            'acao' => 'criado',
                            'email' => $email,
                            'usuario' => $username,
                            'msg' => 'Usuário criado com os campos mapeados.',
                        ];
                    } else {
                        $errors++;
                        $this->data['report'][] = ['linha' => $rows, 'acao' => 'erro', 'email' => $email, 'msg' => 'Falha ao criar.'];
                    }
                    continue;
                }

                // update
                $payload = $this->buildMappedPayload($row, $fieldToIndex, $toBoolLabel, $toDate, $raw);
                $payload['id'] = (int)$existing['id'];
                // Campos obrigatórios do updateUser: completar com existentes se não mapeados/vazios
                foreach (['name', 'email', 'username', 'status', 'bloqueado', 'senha_nunca_expira', 'modificar_senha_proximo_logon'] as $req) {
                    if (!array_key_exists($req, $payload) || $payload[$req] === null || $payload[$req] === '') {
                        $payload[$req] = $existing[$req] ?? ($req === 'status' ? 'Ativo' : ($req === 'bloqueado' || str_contains($req, 'senha') ? 'Não' : ''));
                    }
                }
                if (!isset($payload['user_department_id']) || (int)$payload['user_department_id'] <= 0) {
                    $payload['user_department_id'] = (int)($existing['user_department_id'] ?? 0);
                }
                if (!isset($payload['user_position_id']) || (int)$payload['user_position_id'] <= 0) {
                    $payload['user_position_id'] = (int)($existing['user_position_id'] ?? 0);
                }
                if (!array_key_exists('immediate_supervisor_id', $payload)) {
                    $payload['immediate_supervisor_id'] = $existing['immediate_supervisor_id'] ?? null;
                }
                if (!array_key_exists('data_admissao', $payload)) {
                    $payload['data_admissao'] = $existing['data_admissao'] ?? null;
                }
                if (!array_key_exists('data_desligamento', $payload)) {
                    $payload['data_desligamento'] = $existing['data_desligamento'] ?? null;
                    $payload['motivo_desligamento'] = $existing['motivo_desligamento'] ?? null;
                } elseif (empty($payload['data_desligamento'])) {
                    $payload['data_desligamento'] = null;
                    $payload['motivo_desligamento'] = null;
                } else {
                    $payload['status'] = 'Inativo';
                }

                // Preservar não mapeados
                $optional = [
                    'cpf', 'celular', 'data_nascimento', 'escolaridade', 'raca', 'matricula',
                    'email_pessoal', 'pais_residencia_iso', 'cep', 'endereco', 'numero_endereco',
                    'complemento_endereco', 'bairro', 'municipio', 'uf', 'adms_work_shift_id',
                    'sexo', 'filhos', 'estado_civil', 'empresa_contratante',
                ];
                foreach ($optional as $opt) {
                    if (!array_key_exists($opt, $payload) || $payload[$opt] === null || $payload[$opt] === '') {
                        if (array_key_exists($opt, $existing) && $existing[$opt] !== null && $existing[$opt] !== '') {
                            $payload[$opt] = $existing[$opt];
                        }
                    }
                }

                unset($payload['password']);

                $okUp = $repo->updateUser($payload);
                if ($okUp) {
                    $updated++;
                    $this->data['report'][] = [
                        'linha' => $rows,
                        'acao' => 'atualizado',
                        'email' => (string)($payload['email'] ?? $existing['email'] ?? ''),
                        'usuario' => (string)($existing['username'] ?? $payload['username'] ?? ''),
                        'msg' => 'Campos mapeados gravados no cadastro.',
                    ];
                } else {
                    $errors++;
                    $this->data['report'][] = [
                        'linha' => $rows,
                        'acao' => 'erro',
                        'email' => (string)($existing['email'] ?? ''),
                        'msg' => 'Falha ao atualizar.',
                    ];
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->data['report'][] = [
                    'linha' => $rows,
                    'acao' => 'erro',
                    'email' => $raw($row, $fieldToIndex, 'email'),
                    'msg' => $e->getMessage(),
                ];
                GenerateLog::generateLog('error', 'Falha na importação mapeada de usuário.', [
                    'linha' => $rows,
                    'e' => $e->getMessage(),
                ]);
            }
        }

        fclose($fp);
        $this->data['summary'] = compact('created', 'updated', 'skipped', 'errors');
        $_SESSION['success'] = "Importação concluída: criados {$created}, atualizados {$updated}, ignorados {$skipped}, erros {$errors}.";
        return true;
    }

    /**
     * Monta payload apenas com campos mapeados (valores normalizados).
     *
     * @param array<string, int> $fieldToIndex
     * @param callable $toBoolLabel
     * @param callable $toDate
     * @param callable $raw
     * @return array<string, mixed>
     */
    private function buildMappedPayload(array $row, array $fieldToIndex, callable $toBoolLabel, callable $toDate, callable $raw): array
    {
        $payload = [];
        $mapped = array_keys($fieldToIndex);

        foreach ($mapped as $field) {
            if (in_array($field, ['id'], true)) {
                continue;
            }
            $value = $raw($row, $fieldToIndex, $field);
            switch ($field) {
                case 'name':
                case 'email':
                case 'username':
                    $payload[$field] = $value;
                    break;
                case 'cpf':
                    if ($value !== '') {
                        $digits = preg_replace('/\D/', '', $value) ?? '';
                        $payload['cpf'] = strlen($digits) === 11
                            ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits)
                            : $value;
                    } else {
                        $payload['cpf'] = null;
                    }
                    break;
                case 'celular':
                    $payload['celular'] = $value !== '' ? $value : null;
                    break;
                case 'department_id':
                    $payload['user_department_id'] = (int)$value;
                    break;
                case 'position_id':
                    $payload['user_position_id'] = (int)$value;
                    break;
                case 'immediate_supervisor_id':
                    $payload['immediate_supervisor_id'] = ((int)$value) > 0 ? (int)$value : null;
                    break;
                case 'status':
                    $payload['status'] = $value !== '' ? $value : 'Ativo';
                    break;
                case 'bloqueado':
                case 'senha_nunca_expira':
                case 'modificar_senha_proximo_logon':
                    $payload[$field] = $toBoolLabel($value);
                    break;
                case 'data_nascimento':
                case 'data_admissao':
                case 'data_desligamento':
                    $payload[$field] = $toDate($value);
                    break;
                case 'motivo_desligamento':
                    $payload[$field] = $value !== '' ? $value : null;
                    break;
                case 'escolaridade':
                    $payload[$field] = UserFormHelper::resolveEscolaridadeSlug($value);
                    break;
                case 'raca':
                    $payload[$field] = UserFormHelper::resolveRacaSlug($value);
                    break;
                case 'matricula':
                    $payload[$field] = UserFormHelper::normalizeOptionalText($value, 40);
                    break;
                case 'email_pessoal':
                    $payload[$field] = UserFormHelper::normalizeEmailPessoal($value);
                    break;
                case 'pais_residencia_iso':
                    $payload[$field] = UserFormHelper::normalizePaisResidenciaIso($value);
                    break;
                case 'cep':
                    $payload[$field] = UserFormHelper::normalizeCep($value);
                    break;
                case 'endereco':
                    $payload[$field] = UserFormHelper::normalizeOptionalText($value, 255);
                    break;
                case 'numero_endereco':
                    $payload[$field] = UserFormHelper::normalizeOptionalText($value, 20);
                    break;
                case 'complemento_endereco':
                    $payload[$field] = UserFormHelper::normalizeOptionalText($value, 80);
                    break;
                case 'bairro':
                case 'municipio':
                    $payload[$field] = UserFormHelper::normalizeOptionalText($value, 120);
                    break;
                case 'uf':
                    $payload[$field] = UserFormHelper::normalizeUf($value);
                    break;
                default:
                    break;
            }
        }

        return $payload;
    }

    private function processFile(): void
    {
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->data['errors'][] = 'Falha ao enviar o arquivo.';
            $this->view();
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
            $this->data['errors'][] = 'Formato inválido. Envie um arquivo CSV (recomendado).';
            $this->view();
            return;
        }

        // Suporte inicial: CSV (UTF-8 com cabeçalho). Planilhas podem ser exportadas para CSV.
        $handled = $this->processCsv($file['tmp_name']);
        if (!$handled) {
            $this->data['errors'][] = 'Não foi possível processar o arquivo. Verifique o template.';
        }
        $this->view();
    }

    private function processCsv(string $tmpPath): bool
    {
        // Detectar e tratar encoding do arquivo
        $content = file_get_contents($tmpPath);
        
        // Remover BOM se existir
        $bom = pack('H*','EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
        
        // Detectar encoding
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        
        // Converter para UTF-8 se necessário
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        // Salvar conteúdo convertido em arquivo temporário
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_utf8_');
        file_put_contents($tempFile, $content);
        
        $fp = fopen($tempFile, 'r');
        if (!$fp) { 
            unlink($tempFile);
            return false; 
        }

        $header = fgetcsv($fp, 0, ';');
        if (!$header) {
            fclose($fp);
            unlink($tempFile);
            return false;
        }

        // Cabeçalhos esperados (template oficial gerado pelo sistema)
        $expected = [
            'name',
            'email',
            'username',
            'cpf',
            'celular',
            'department_id',
            'position_id',
            'immediate_supervisor_id',
            'password',
            'status',
            'bloqueado',
            'tentativas_login',
            'senha_nunca_expira',
            'modificar_senha_proximo_logon',
            'data_nascimento',
            'data_admissao',
            'data_desligamento',
            'motivo_desligamento',
            'escolaridade',
            'raca',
            'matricula',
            'email_pessoal',
            'pais_residencia_iso',
            'cep',
            'endereco',
            'numero_endereco',
            'complemento_endereco',
            'bairro',
            'municipio',
            'uf',
        ];
        $map = [];
        foreach ($expected as $col) {
            $idx = array_search($col, $header, true);
            $map[$col] = $idx !== false ? (int)$idx : null;
        }

        $repo = new UsersRepository();
        $created = 0; $updated = 0; $skipped = 0; $errors = 0; $rows = 1;
        $this->data['report'] = [];

        while (($row = fgetcsv($fp, 0, ';')) !== false) {
            $rows++;
            if (count(array_filter($row, fn($v)=> trim((string)$v) !== '')) === 0) continue;

            // Helpers de normalização
            $toBoolLabel = function ($val): string {
                $v = strtolower(trim((string)$val));
                // remover acentos básicos
                $v = strtr($v, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);
                if (in_array($v, ['sim','s','yes','y','true','1'], true)) return 'Sim';
                if (in_array($v, ['nao','não','n','no','false','0','nao.','nao '], true)) return 'Não';
                return 'Não';
            };
            $toDate = function ($val): ?string {
                $v = trim((string)$val);
                if ($v === '') return null;
                // dd/mm/yyyy -> yyyy-mm-dd
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
                    return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
                }
                // yyyy-mm-dd já compatível
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;
                // Tentar via strtotime
                $t = strtotime($v);
                return $t ? date('Y-m-d', $t) : null;
            };

            // Garantir que os campos de texto estão em UTF-8 válido
            $name = trim((string)($row[$map['name']] ?? ''));
            if (!mb_check_encoding($name, 'UTF-8')) {
                $name = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
            }
            // Preservar exatamente como na planilha (apenas normalizar espaços)
            $name = preg_replace('/\s+/u', ' ', $name);
            
            $email = trim((string)($row[$map['email']] ?? ''));
            if (!mb_check_encoding($email, 'UTF-8')) {
                $email = mb_convert_encoding($email, 'UTF-8', 'UTF-8');
            }
            
            $username = trim((string)($row[$map['username']] ?? ''));
            if (!mb_check_encoding($username, 'UTF-8')) {
                $username = mb_convert_encoding($username, 'UTF-8', 'UTF-8');
            }
            
            $status = trim((string)($row[$map['status']] ?? 'Ativo'));
            if (!mb_check_encoding($status, 'UTF-8')) {
                $status = mb_convert_encoding($status, 'UTF-8', 'UTF-8');
            }
            
            // Normalizar e validar CPF
            $cpf = trim((string)($row[$map['cpf']] ?? ''));
            if ($cpf !== '') {
                $cpfNumeros = preg_replace('/\D/', '', $cpf); // Remove tudo que não é número
                if (strlen($cpfNumeros) === 11 && $this->validarCpf($cpfNumeros)) {
                    $cpf = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfNumeros);
                } else {
                    $cpfOriginal = $cpf; // Salvar CPF original para erro
                    $cpf = ''; // CPF inválido
                    $this->data['report'][] = ['linha'=>$rows, 'acao'=>'erro', 'email'=>$email, 'msg'=>'CPF inválido: ' . $cpfOriginal];
                    $errors++;
                    continue;
                }
            }
            
            // Normalizar Celular (remover caracteres, depois formatar)
            $celular = trim((string)($row[$map['celular']] ?? ''));
            $celularOriginal = $celular; // Para debug
            if ($celular !== '') {
                $celular = preg_replace('/\D/', '', $celular); // Remove tudo que não é número
                if (strlen($celular) === 11) {
                    $celular = preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $celular);
                } elseif (strlen($celular) === 10) {
                    $celular = preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $celular);
                } else {
                    $celular = ''; // Celular inválido
                }
            }
            
            // Supervisor imediato (ID numérico opcional)
            $immediateSupervisorId = 0;
            if ($map['immediate_supervisor_id'] !== null) {
                $immediateSupervisorId = (int)($row[$map['immediate_supervisor_id']] ?? 0);
            }
            
            // Datas de admissão e desligamento
            $dataAdmissao = null;
            if ($map['data_admissao'] !== null) {
                $dataAdmissao = $toDate($row[$map['data_admissao']] ?? null);
            }
            $dataDesligamento = null;
            if ($map['data_desligamento'] !== null) {
                $dataDesligamento = $toDate($row[$map['data_desligamento']] ?? null);
            }
            
            // Motivo de desligamento (opcional)
            $motivoDesligamento = null;
            if ($map['motivo_desligamento'] !== null) {
                $motivoDesligamento = trim((string)($row[$map['motivo_desligamento']] ?? ''));
                if ($motivoDesligamento === '') {
                    $motivoDesligamento = null;
                }
            }

            $cell = static function (array $row, array $map, string $col): string {
                if ($map[$col] === null) {
                    return '';
                }
                return trim((string)($row[$map[$col]] ?? ''));
            };

            $escolaridade = UserFormHelper::resolveEscolaridadeSlug($cell($row, $map, 'escolaridade'));
            $raca = UserFormHelper::resolveRacaSlug($cell($row, $map, 'raca'));
            $emailPessoal = UserFormHelper::normalizeEmailPessoal($cell($row, $map, 'email_pessoal'));
            // Se e-mail pessoal veio preenchido mas inválido, normaliza para null e avisa
            if ($cell($row, $map, 'email_pessoal') !== '' && $emailPessoal === null) {
                $this->data['report'][] = [
                    'linha' => $rows,
                    'acao' => 'aviso',
                    'email' => $email,
                    'msg' => 'email_pessoal inválido — campo ignorado nesta linha.',
                ];
            }
            $paisResidencia = UserFormHelper::normalizePaisResidenciaIso($cell($row, $map, 'pais_residencia_iso'));
            $cep = UserFormHelper::normalizeCep($cell($row, $map, 'cep'));
            $endereco = UserFormHelper::normalizeOptionalText($cell($row, $map, 'endereco'), 255);
            $numeroEndereco = UserFormHelper::normalizeOptionalText($cell($row, $map, 'numero_endereco'), 20);
            $complementoEndereco = UserFormHelper::normalizeOptionalText($cell($row, $map, 'complemento_endereco'), 80);
            $bairro = UserFormHelper::normalizeOptionalText($cell($row, $map, 'bairro'), 120);
            $municipio = UserFormHelper::normalizeOptionalText($cell($row, $map, 'municipio'), 120);
            $uf = UserFormHelper::normalizeUf($cell($row, $map, 'uf'));
            if ($cell($row, $map, 'uf') !== '' && $uf === null) {
                $this->data['report'][] = [
                    'linha' => $rows,
                    'acao' => 'aviso',
                    'email' => $email,
                    'msg' => 'UF inválida — campo ignorado nesta linha.',
                ];
            }
            if ($cell($row, $map, 'escolaridade') !== '' && $escolaridade === null) {
                $this->data['report'][] = [
                    'linha' => $rows,
                    'acao' => 'aviso',
                    'email' => $email,
                    'msg' => 'escolaridade inválida — use o slug ou o rótulo do cadastro.',
                ];
            }
            if ($cell($row, $map, 'raca') !== '' && $raca === null) {
                $this->data['report'][] = [
                    'linha' => $rows,
                    'acao' => 'aviso',
                    'email' => $email,
                    'msg' => 'raca inválida — use o slug ou o rótulo do cadastro.',
                ];
            }

            $payload = [
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'cpf' => $cpf !== '' ? $cpf : null,
                'celular' => $celular !== '' ? $celular : null,
                'user_department_id' => (int)($row[$map['department_id']] ?? 0),
                'user_position_id' => (int)($row[$map['position_id']] ?? 0),
                'immediate_supervisor_id' => $immediateSupervisorId > 0 ? $immediateSupervisorId : null,
                'password' => (string)($row[$map['password']] ?? ''),
                'status' => $status,
                'bloqueado' => $toBoolLabel($row[$map['bloqueado']] ?? 'Não'),
                'tentativas_login' => (int)($row[$map['tentativas_login']] ?? 0),
                'senha_nunca_expira' => $toBoolLabel($row[$map['senha_nunca_expira']] ?? 'Não'),
                'modificar_senha_proximo_logon' => $toBoolLabel($row[$map['modificar_senha_proximo_logon']] ?? 'Não'),
                'data_nascimento' => $toDate($row[$map['data_nascimento']] ?? null),
                'data_admissao' => $dataAdmissao,
                'data_desligamento' => $dataDesligamento,
                'motivo_desligamento' => $motivoDesligamento,
                'escolaridade' => $escolaridade,
                'raca' => $raca,
                'matricula' => UserFormHelper::normalizeOptionalText($cell($row, $map, 'matricula'), 40),
                'email_pessoal' => $emailPessoal,
                'pais_residencia_iso' => $paisResidencia,
                'cep' => $cep,
                'endereco' => $endereco,
                'numero_endereco' => $numeroEndereco,
                'complemento_endereco' => $complementoEndereco,
                'bairro' => $bairro,
                'municipio' => $municipio,
                'uf' => $uf,
                'image' => null,
            ];

            try {
                // Upsert apenas por USERNAME (chave de identificação definida pelo negócio)
                $existing = $repo->getUserByUsername($payload['username']);
                if ($existing) {
                    $payload['id'] = (int)$existing['id'];
                    // Import não altera senha em usuários existentes (há fluxo próprio para senha)
                    if (isset($payload['password'])) unset($payload['password']);
                    // Preencher campos obrigatórios com valores atuais caso venham vazios/zerados no CSV
                    $payload['name'] = $payload['name'] !== '' ? $payload['name'] : ($existing['name'] ?? '');
                    $payload['email'] = $payload['email'] !== '' ? $payload['email'] : ($existing['email'] ?? '');
                    $payload['username'] = $payload['username'] !== '' ? $payload['username'] : ($existing['username'] ?? '');
                    // Preservar CPF e celular existentes apenas se vierem vazios no CSV
                    // Mas se o CSV tem dados, usar os dados do CSV
                    if ($payload['cpf'] === null || $payload['cpf'] === '') {
                        if (!empty($existing['cpf'])) {
                            $payload['cpf'] = $existing['cpf'];
                        }
                    }
                    if ($payload['celular'] === null || $payload['celular'] === '') {
                        if (!empty($existing['celular'])) {
                            $payload['celular'] = $existing['celular'];
                        }
                    }
                    // Preservar department_id e position_id existentes apenas se CSV vier vazio ou 0
                    // Mas se o CSV tem dados válidos (> 0), usar os dados do CSV
                    if ($payload['user_department_id'] <= 0) {
                        $payload['user_department_id'] = (int)($existing['user_department_id'] ?? 0);
                    }
                    if ($payload['user_position_id'] <= 0) {
                        $payload['user_position_id'] = (int)($existing['user_position_id'] ?? 0);
                    }
                    // Supervisor imediato: manter existente se CSV vier vazio/0
                    if (empty($payload['immediate_supervisor_id']) && !empty($existing['immediate_supervisor_id'])) {
                        $payload['immediate_supervisor_id'] = (int)$existing['immediate_supervisor_id'];
                    }
                    if (empty($payload['status']) && !empty($existing['status'])) $payload['status'] = $existing['status'];
                    if (empty($payload['bloqueado']) && !empty($existing['bloqueado'])) $payload['bloqueado'] = $existing['bloqueado'];
                    if (empty($payload['senha_nunca_expira']) && !empty($existing['senha_nunca_expira'])) $payload['senha_nunca_expira'] = $existing['senha_nunca_expira'];
                    if (empty($payload['modificar_senha_proximo_logon']) && !empty($existing['modificar_senha_proximo_logon'])) $payload['modificar_senha_proximo_logon'] = $existing['modificar_senha_proximo_logon'];
                    if (empty($payload['data_nascimento']) && !empty($existing['data_nascimento'])) $payload['data_nascimento'] = $existing['data_nascimento'];

                    // Preservar escolaridade/raça/endereço se CSV vier vazio
                    $optionalPreserve = [
                        'escolaridade',
                        'raca',
                        'matricula',
                        'email_pessoal',
                        'pais_residencia_iso',
                        'cep',
                        'endereco',
                        'numero_endereco',
                        'complemento_endereco',
                        'bairro',
                        'municipio',
                        'uf',
                    ];
                    foreach ($optionalPreserve as $optKey) {
                        if (($payload[$optKey] === null || $payload[$optKey] === '') && !empty($existing[$optKey])) {
                            $payload[$optKey] = $existing[$optKey];
                        }
                    }

                    // Datas de admissão/desligamento e motivo:
                    // - Se CSV trouxer data_desligamento -> aplicar e inativar usuário
                    // - Se vier vazia -> manter valor atual
                    if ($payload['data_desligamento']) {
                        // Para edição, data de desligamento preenchida significa desligar o colaborador
                        $payload['status'] = 'Inativo';
                    } else {
                        // Manter desligamento/motivo existentes se CSV não trouxer nada
                        $payload['data_desligamento'] = $existing['data_desligamento'] ?? null;
                        if ($payload['motivo_desligamento'] === null && !empty($existing['motivo_desligamento'])) {
                            $payload['motivo_desligamento'] = $existing['motivo_desligamento'];
                        }
                    }
                    // Se CSV trouxer data_admissao vazia, manter a existente
                    if (empty($payload['data_admissao']) && !empty($existing['data_admissao'])) {
                        $payload['data_admissao'] = $existing['data_admissao'];
                    }
                    // Verificar diferenças e só atualizar se houver
                    $keysToCompare = [
                        'name',
                        'email',
                        'username',
                        'cpf',
                        'celular',
                        'user_department_id',
                        'user_position_id',
                        'immediate_supervisor_id',
                        'status',
                        'bloqueado',
                        'senha_nunca_expira',
                        'modificar_senha_proximo_logon',
                        'data_nascimento',
                        'data_admissao',
                        'data_desligamento',
                        'motivo_desligamento',
                        'escolaridade',
                        'raca',
                        'matricula',
                        'email_pessoal',
                        'pais_residencia_iso',
                        'cep',
                        'endereco',
                        'numero_endereco',
                        'complemento_endereco',
                        'bairro',
                        'municipio',
                        'uf',
                    ];
                    $hasDiff = false;
                    $diffDetails = [];
                    foreach ($keysToCompare as $k) {
                        $newVal = $payload[$k] ?? null;
                        $oldVal = $existing[$k] ?? null;
                        if (in_array($k, ['user_department_id','user_position_id','immediate_supervisor_id'])) {
                            $newVal = (int)$newVal; $oldVal = (int)$oldVal;
                        } else {
                            $newVal = is_string($newVal) ? trim((string)$newVal) : $newVal;
                            $oldVal = is_string($oldVal) ? trim((string)$oldVal) : $oldVal;
                        }
                        if ($newVal !== $oldVal) { 
                            $hasDiff = true; 
                            $diffDetails[$k] = ['old' => $oldVal, 'new' => $newVal];
                        }
                    }
                    
                    

                    if (!$hasDiff) {
                        $skipped++;
                        $this->data['report'][] = ['linha'=>$rows, 'acao'=>'ignorado', 'email'=>$payload['email']];
                        continue;
                    }

                    $ok = $repo->updateUser($payload);
                    if ($ok) {
                        $updated++;
                        $this->data['report'][] = ['linha'=>$rows, 'acao'=>'atualizado', 'email'=>$payload['email']];
                    } else {
                        $errors++;
                        $this->data['report'][] = ['linha'=>$rows, 'acao'=>'erro', 'email'=>$payload['email'], 'msg'=>'Falha ao atualizar (verifique logs DEBUG updateUser)'];
                    }
                } else {
                    // Criação de novo usuário
                    // Regra de negócio: novos usuários não devem ser criados já desligados
                    if (!empty($payload['data_desligamento'])) {
                        $this->data['report'][] = [
                            'linha' => $rows,
                            'acao'  => 'erro',
                            'email' => $payload['email'],
                            'msg'   => 'Para criação de usuário, data_desligamento deve ficar vazia.'
                        ];
                        $errors++;
                        continue;
                    }

                    if ($payload['password'] === '') {
                        // Gera senha temporária segura para novos usuários sem senha
                        $payload['password'] = bin2hex(random_bytes(6));
                    }
                    $ok = $repo->createUser($payload);
                    if ($ok) {
                        $created++;
                        $this->data['report'][] = ['linha'=>$rows, 'acao'=>'criado', 'email'=>$payload['email']];
                    } else {
                        $errors++;
                        $this->data['report'][] = ['linha'=>$rows, 'acao'=>'erro', 'email'=>$payload['email'], 'msg'=>'Falha ao criar'];
                    }
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->data['report'][] = ['linha'=>$rows, 'acao'=>'erro', 'email'=>$payload['email'], 'msg'=>$e->getMessage()];
                GenerateLog::generateLog('error','Falha ao importar usuário.', ['email'=>$payload['email'], 'e'=>$e->getMessage()]);
            }
        }
        fclose($fp);
        
        // Limpar arquivo temporário
        unlink($tempFile);

        $this->data['summary'] = compact('created','updated','skipped','errors');
        $_SESSION['success'] = "Importação concluída: criados {$created}, atualizados {$updated}, erros {$errors}.";
        return true;
    }

    // Download do template CSV
    public function template(): void
    {
        $filename = 'template_importacao_usuarios.csv';
        
        // Headers para garantir UTF-8
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Adicionar BOM UTF-8 para compatibilidade com Excel
        echo "\xEF\xBB\xBF";
        
        $out = fopen('php://output', 'w');
        
        // Cabeçalho com ; como separador (mesmos campos usados em processCsv)
        fputcsv(
            $out,
            [
                'name',
                'email',
                'username',
                'cpf',
                'celular',
                'department_id',
                'position_id',
                'immediate_supervisor_id',
                'password',
                'status',
                'bloqueado',
                'tentativas_login',
                'senha_nunca_expira',
                'modificar_senha_proximo_logon',
                'data_nascimento',
                'data_admissao',
                'data_desligamento',
                'motivo_desligamento',
                'escolaridade',
                'raca',
                'matricula',
                'email_pessoal',
                'pais_residencia_iso',
                'cep',
                'endereco',
                'numero_endereco',
                'complemento_endereco',
                'bairro',
                'municipio',
                'uf',
            ],
            ';'
        );
        
        // Linhas exemplo com acentos para testar
        // OBS: Para criação, data_desligamento e motivo_desligamento devem ficar vazios
        // escolaridade/raca: slug ou rótulo (ex.: medio_completo ou "Ensino médio completo"; branca ou "Branca")
        fputcsv(
            $out,
            [
                'Maria Silva',
                'maria@empresa.com',
                'maria.silva',
                '123.456.789-00',
                '(11) 98765-4321',
                1,
                2,
                '', // immediate_supervisor_id
                'SenhaForte123!',
                'Ativo',
                'Não',
                0,
                'Não',
                'Não',
                '20/08/1990',
                '01/01/2020', // data_admissao
                '',           // data_desligamento (vazio na criação)
                '',           // motivo_desligamento
                'superior_completo',
                'parda',
                'MAT-00123',
                'maria.pessoal@email.com',
                'BR',
                '80010-000',
                'Rua XV de Novembro',
                '123',
                'Apto 45',
                'Centro',
                'Curitiba',
                'PR',
            ],
            ';'
        );
        fputcsv(
            $out,
            [
                'João Santos',
                'joao@empresa.com',
                'joao.santos',
                '987.654.321-00',
                '(11) 91234-5678',
                2,
                1,
                '', // immediate_supervisor_id
                'SenhaForte123!',
                'Ativo',
                'Não',
                0,
                'Não',
                'Não',
                '15/03/1985',
                '10/05/2018',
                '',
                '',
                'Ensino médio completo',
                'Branca',
                'MAT-00456',
                '',
                'BR',
                '01310-100',
                'Avenida Paulista',
                '1000',
                '',
                'Bela Vista',
                'São Paulo',
                'SP',
            ],
            ';'
        );
        
        fclose($out);
        exit;
    }

    /**
     * Validar CPF
     */
    private function validarCpf(string $cpf): bool
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/\D/', '', $cpf);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) !== 11) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Calcula o primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += intval($cpf[$i]) * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;
        
        // Verifica o primeiro dígito
        if (intval($cpf[9]) !== $digito1) {
            return false;
        }
        
        // Calcula o segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += intval($cpf[$i]) * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;
        
        // Verifica o segundo dígito
        if (intval($cpf[10]) !== $digito2) {
            return false;
        }
        
        return true;
    }
}


