<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Models\Repository\UsersRepository;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

/**
 * Importação em lote de reservas de sala a partir de CSV ou Excel (.xlsx).
 */
final class RoomBookingSpreadsheetImportService
{
    private const MAX_ROWS = 2000;
    private const MAX_FILE_BYTES = 2097152; // 2 MB

    /** Limites de número serial Excel (aprox. 1900–2173) para não confundir com IDs ou horas “10”. */
    private const EXCEL_SERIAL_MIN = 200.0;
    private const EXCEL_SERIAL_MAX = 1000000.0;

    /**
     * @return array{created:int, skipped:int, errors:list<string>, warnings:list<string>}
     */
    public static function importFile(
        string $absolutePath,
        string $originalFilename,
        int $roomId,
        RoomBookingsRepository $bookingsRepo,
        UsersRepository $usersRepo
    ): array {
        $created = 0;
        $skipped = 0;
        $errors = [];
        $warnings = [];

        if (!is_readable($absolutePath)) {
            $errors[] = 'Arquivo não pôde ser lido.';
            return self::result($created, $skipped, $errors, $warnings);
        }

        $size = filesize($absolutePath);
        if ($size === false || $size > self::MAX_FILE_BYTES) {
            $errors[] = 'Arquivo muito grande (máximo 2 MB).';
            return self::result($created, $skipped, $errors, $warnings);
        }

        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $kind = self::detectSpreadsheetKind($absolutePath, $ext);
        $rows = self::readRows($absolutePath, $kind, $errors);
        if ($rows === null) {
            return self::result($created, $skipped, $errors, $warnings);
        }

        if (count($rows) < 2) {
            $errors[] = 'Planilha vazia ou sem linhas de dados (além do cabeçalho).';
            return self::result($created, $skipped, $errors, $warnings);
        }

        $header = array_shift($rows);
        $map = self::mapHeaderRow($header);
        if ($map === null) {
            $errors[] = 'Cabeçalho inválido. Use as colunas do modelo: data;hora_inicio;hora_fim;titulo;usuario;descricao';
            return self::result($created, $skipped, $errors, $warnings);
        }

        $lineNo = 2;
        $tz = new \DateTimeZone($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');
        $rowCount = 0;

        foreach ($rows as $row) {
            if ($rowCount >= self::MAX_ROWS) {
                $warnings[] = 'Limite de ' . self::MAX_ROWS . ' linhas atingido; demais linhas foram ignoradas.';
                break;
            }
            $rowCount++;

            $cells = self::normalizeRowToList($row, count($header));
            if (self::rowIsEmpty($cells)) {
                $lineNo++;
                continue;
            }

            $data = [];
            foreach ($map as $canonical => $idx) {
                $data[$canonical] = isset($cells[$idx]) ? trim((string) $cells[$idx]) : '';
            }

            $parsed = self::parseBookingRow($data, $tz, $lineNo);
            if ($parsed['error'] !== null) {
                $errors[] = $parsed['error'];
                $lineNo++;
                continue;
            }

            /** @var array{start:string,end:string,title:string,desc:string,login:string} $p */
            $p = $parsed['data'];
            $userId = $usersRepo->findUserIdForRoomImport($p['login']);
            if ($userId === null) {
                $errors[] = "Linha {$lineNo}: usuário não encontrado (login ou e-mail): " . $p['login'];
                $lineNo++;
                continue;
            }

            if ($bookingsRepo->hasConflict($roomId, $p['start'], $p['end'], null)) {
                $warnings[] = "Linha {$lineNo}: conflito de horário na sala — ignorada ({$p['start']} → {$p['end']}). "
                    . 'Lido na planilha: data=' . self::previewCellForMessage($data['data'] ?? '')
                    . ', hora_inicio=' . self::previewCellForMessage($data['hora_inicio'] ?? '')
                    . ', hora_fim=' . self::previewCellForMessage($data['hora_fim'] ?? '') . '.';
                $skipped++;
                $lineNo++;
                continue;
            }

            try {
                $bookingsRepo->create([
                    'room_id' => $roomId,
                    'user_id' => $userId,
                    'title' => $p['title'],
                    'description' => $p['desc'] !== '' ? $p['desc'] : 'Importação em lote',
                    'start_datetime' => $p['start'],
                    'end_datetime' => $p['end'],
                    'status' => 'confirmed',
                    'requires_approval' => false,
                    'has_additional_requests' => false,
                    'recurrence_series_id' => null,
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Linha {$lineNo}: erro ao gravar — " . $e->getMessage();
            }

            $lineNo++;
        }

        if ($created === 0 && $skipped === 0 && $rowCount > 0 && $errors === [] && $warnings === []) {
            $warnings[] = 'Nenhuma reserva foi gravada: verifique se data e hora estão reconhecíveis (no Excel evite células só como texto inválido), se o login/e-mail existe no sistema e se a linha não está toda vazia.';
        }

        return self::result($created, $skipped, $errors, $warnings);
    }

    /**
     * Conteúdo UTF-8 do CSV modelo (com BOM para Excel).
     */
    public static function buildCsvTemplate(string $roomName): string
    {
        $lines = [
            'data;hora_inicio;hora_fim;titulo;usuario;descricao',
            '2026-04-15;08:00;09:00;Exemplo de reunião;seu.login;Texto opcional',
            '',
            '# Sala: ' . $roomName,
            '# data: AAAA-MM-DD ou DD/MM/AAAA (padrao BR se dia e mes 1–12); 5/18/2026 (Excel US) = 18 de maio',
            '# hora_inicio / hora_fim: HH:MM (24h)',
            '# usuario: login (username) ou e-mail cadastrado no sistema',
            '# separador: ponto e virgula (;) ou virgula (,) — o importador detecta automaticamente',
        ];

        return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
    }

    /**
     * @param list<string>|list<mixed> $header
     * @return array<string,int>|null canonical => column index
     */
    private static function mapHeaderRow(array $header): ?array
    {
        $map = [];
        foreach ($header as $i => $cell) {
            $key = self::normalizeHeaderKey((string) $cell);
            $canonical = match ($key) {
                'data', 'date', 'dia' => 'data',
                // Excel trunca cabeçalhos longos: "hora_inici" → horainici
                'horainicio', 'horainici', 'inicio', 'start' => 'hora_inicio',
                'horafim', 'fim', 'end' => 'hora_fim',
                'titulo', 'title', 'assunto' => 'titulo',
                'usuario', 'user', 'login', 'solicitante', 'email' => 'usuario',
                'descricao', 'description', 'obs', 'observacao' => 'descricao',
                default => null,
            };
            if ($canonical !== null && !isset($map[$canonical])) {
                $map[$canonical] = (int) $i;
            }
        }

        foreach (['data', 'hora_inicio', 'hora_fim', 'titulo', 'usuario'] as $req) {
            if (!isset($map[$req])) {
                return null;
            }
        }

        return $map;
    }

    private static function previewCellForMessage(string $v): string
    {
        $v = trim($v);
        if ($v === '') {
            return '(vazio)';
        }
        if (strlen($v) > 80) {
            return mb_substr($v, 0, 80, 'UTF-8') . '…';
        }

        return $v;
    }

    private static function normalizeHeaderKey(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = str_replace([' ', '_', '-', '/', '(', ')'], '', $s);
        $s = str_replace(
            ['á', 'à', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'],
            ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'],
            $s
        );

        return $s;
    }

    /**
     * @return array{error: ?string, data: ?array}|array{error: null, data: array}
     */
    private static function parseBookingRow(array $data, \DateTimeZone $tz, int $lineNo): array
    {
        $dateStr = $data['data'] ?? '';
        $t0 = $data['hora_inicio'] ?? '';
        $t1 = $data['hora_fim'] ?? '';
        $title = $data['titulo'] ?? '';
        $login = $data['usuario'] ?? '';
        $desc = $data['descricao'] ?? '';

        if ($title === '' || $login === '') {
            return ['error' => "Linha {$lineNo}: título e usuário são obrigatórios.", 'data' => null];
        }

        $datePart = self::parseDateOnly($dateStr, $tz);
        if ($datePart === null) {
            return ['error' => "Linha {$lineNo}: data inválida ({$dateStr}).", 'data' => null];
        }

        $startT = self::parseClock($t0, $tz);
        $endT = self::parseClock($t1, $tz);
        if ($startT === null || $endT === null) {
            return ['error' => "Linha {$lineNo}: horário inválido (use HH:MM).", 'data' => null];
        }

        try {
            $start = new DateTimeImmutable($datePart . ' ' . $startT, $tz);
            $end = new DateTimeImmutable($datePart . ' ' . $endT, $tz);
        } catch (\Throwable) {
            return ['error' => "Linha {$lineNo}: não foi possível montar data/hora.", 'data' => null];
        }

        if ($end <= $start) {
            return ['error' => "Linha {$lineNo}: hora_fim deve ser maior que hora_inicio.", 'data' => null];
        }

        return [
            'error' => null,
            'data' => [
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
                'title' => mb_substr($title, 0, 255, 'UTF-8'),
                'desc' => $desc,
                'login' => $login,
            ],
        ];
    }

    private static function parseDateOnly(string $s, \DateTimeZone $tz): ?string
    {
        $s = trim($s);
        if ($s === '') {
            return null;
        }
        if (is_numeric($s)) {
            $n = (float) $s;
            if ($n >= self::EXCEL_SERIAL_MIN && $n < self::EXCEL_SERIAL_MAX) {
                try {
                    $dt = SpreadsheetDate::excelToDateTimeObject($n, $tz->getName());

                    return $dt->format('Y-m-d');
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        $iso = DateTimeImmutable::createFromFormat('!Y-m-d', $s, $tz);
        if ($iso instanceof DateTimeImmutable) {
            return $iso->format('Y-m-d');
        }

        $slash = self::parseSlashOrDashCalendarDate($s, $tz);
        if ($slash !== null) {
            return $slash;
        }

        $dmYdash = DateTimeImmutable::createFromFormat('!d-m-Y', $s, $tz);
        if ($dmYdash instanceof DateTimeImmutable) {
            return $dmYdash->format('Y-m-d');
        }

        return null;
    }

    /**
     * Datas com / ou -: prioridade ao calendário brasileiro (DD/MM/AAAA), mas se o 2.º número > 12
     * trata-se de formato americano M/D/AAAA vindo do Excel (ex.: 5/18/2026 = 18 de maio).
     */
    private static function parseSlashOrDashCalendarDate(string $s, \DateTimeZone $tz): ?string
    {
        if (!preg_match('/^(\d{1,2})([\/\-])(\d{1,2})\2(\d{4})$/', $s, $m)) {
            return null;
        }
        $a = (int) $m[1];
        $b = (int) $m[3];
        $y = (int) $m[4];
        $norm = sprintf('%d/%d/%d', $a, $b, $y);

        if ($a > 12) {
            $dt = DateTimeImmutable::createFromFormat('!d/m/Y', $norm, $tz);
        } elseif ($b > 12) {
            $dt = DateTimeImmutable::createFromFormat('!m/d/Y', $norm, $tz);
        } else {
            $dt = DateTimeImmutable::createFromFormat('!d/m/Y', $norm, $tz);
        }

        return $dt instanceof DateTimeImmutable ? $dt->format('Y-m-d') : null;
    }

    private static function parseClock(string $s, \DateTimeZone $tz): ?string
    {
        $s = trim($s);
        if (is_numeric($s)) {
            $n = (float) $s;
            if ($n >= 0.0 && $n < 1.0) {
                try {
                    $dt = SpreadsheetDate::excelToDateTimeObject($n, $tz->getName());

                    return $dt->format('H:i:s');
                } catch (\Throwable) {
                    return null;
                }
            }
            if ($n >= self::EXCEL_SERIAL_MIN && $n < self::EXCEL_SERIAL_MAX) {
                try {
                    $dt = SpreadsheetDate::excelToDateTimeObject($n, $tz->getName());

                    return $dt->format('H:i:s');
                } catch (\Throwable) {
                    return null;
                }
            }
        }
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $s, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59) {
                return sprintf('%02d:%02d:00', $h, $min);
            }
        }

        return null;
    }

    /**
     * @return list<list<mixed>>|null
     */
    private static function readRows(string $path, string $ext, array &$errors): ?array
    {
        if ($ext === 'csv') {
            $content = file_get_contents($path);
            if ($content === false) {
                $errors[] = 'Falha ao ler CSV.';
                return null;
            }
            if (str_starts_with($content, "\xFF\xFE") || str_starts_with($content, "\xFE\xFF")) {
                $errors[] = 'CSV em UTF-16 não é suportado. No Excel use “CSV UTF-8 (delimitado por vírgulas)” ou salve em .xlsx.';
                return null;
            }
            if (str_starts_with($content, "\xEF\xBB\xBF")) {
                $content = substr($content, 3);
            }
            $lines = preg_split('/\R/u', $content) ?: [];
            $dataLines = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $dataLines[] = $line;
            }
            if ($dataLines === []) {
                $errors[] = 'CSV sem linhas de dados (após ignorar linhas vazias e comentários #).';
                return null;
            }
            $first = $dataLines[0];
            $scores = [
                ';' => substr_count($first, ';'),
                ',' => substr_count($first, ','),
                "\t" => substr_count($first, "\t"),
            ];
            arsort($scores);
            $delimiter = array_key_first($scores);
            if ($scores[$delimiter] < 1) {
                $errors[] = 'Não foi possível detectar separador de colunas (; ou ,). A primeira linha deve ser o cabeçalho: data;hora_inicio;hora_fim;titulo;usuario;descricao';
                return null;
            }
            $out = [];
            foreach ($dataLines as $line) {
                $out[] = str_getcsv($line, $delimiter);
            }

            return $out;
        }

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            try {
                $spreadsheet = IOFactory::load($path);
                $sheet = $spreadsheet->getActiveSheet();
                $raw = $sheet->toArray(null, true, true, false);
                $out = [];
                foreach ($raw as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    if (self::excelRowIsBlankOrComment($row)) {
                        continue;
                    }
                    $out[] = array_values($row);
                }

                return $out === [] ? null : $out;
            } catch (\Throwable $e) {
                $errors[] = 'Erro ao ler Excel: ' . $e->getMessage();
                return null;
            }
        }

        $errors[] = 'Formato não suportado. Use .csv, .xlsx ou .xls.';
        return null;
    }

    /**
     * Corrige tipo pelo conteúdo do ficheiro (ex.: .xlsx com nome .csv) e mantém o declarado quando não há assinatura conhecida.
     */
    private static function detectSpreadsheetKind(string $path, string $ext): string
    {
        $head = @file_get_contents($path, false, null, 0, 8);
        if ($head === false || $head === '') {
            return $ext;
        }
        if (str_starts_with($head, "PK\x03\x04") || str_starts_with($head, "PK\x05\x06") || str_starts_with($head, 'PK' . "\x07\x08")) {
            return 'xlsx';
        }
        if (str_starts_with($head, "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1")) {
            return 'xls';
        }

        return $ext;
    }

    /**
     * Ignora linha totalmente vazia ou cujo primeiro texto não vazio começa por #.
     * Não exige dados na coluna A (comum em folhas com margem ou título).
     *
     * @param array<int|string, mixed> $row
     */
    private static function excelRowIsBlankOrComment(array $row): bool
    {
        $firstText = null;
        foreach ($row as $cell) {
            $t = trim((string) $cell);
            if ($t === '') {
                continue;
            }
            $firstText = $t;
            break;
        }
        if ($firstText === null) {
            return true;
        }

        return str_starts_with($firstText, '#');
    }

    /**
     * @param list<mixed> $row
     */
    private static function normalizeRowToList(array $row, int $minCols): array
    {
        $list = array_values($row);
        while (count($list) < $minCols) {
            $list[] = '';
        }

        return $list;
    }

    /**
     * @param list<string> $cells
     */
    private static function rowIsEmpty(array $cells): bool
    {
        foreach ($cells as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function result(int $created, int $skipped, array $errors, array $warnings): array
    {
        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
