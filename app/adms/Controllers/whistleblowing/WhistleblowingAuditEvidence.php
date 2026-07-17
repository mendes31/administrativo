<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UrlAdmHelper;
use App\adms\Models\Repository\WhistleblowingEvidenceRepository;
use App\adms\Models\Services\WhistleblowingPermissionService;
use App\adms\Views\Services\LoadViewService;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Prévia e exportação do pacote seguro de evidências do Canal de Denúncias.
 */
final class WhistleblowingAuditEvidence
{
    private const EXCLUDED_CONTENT = [
        'Texto do relato e envolvidos',
        'Nome, e-mail e telefone do denunciante',
        'Conteúdo de mensagens e notas internas',
        'Nome original e nome armazenado dos anexos',
        'Chaves de criptografia, tokens, secrets e hashes completos',
        'Nomes, e-mails e identificadores dos membros dos comitês',
    ];

    public function index(): void
    {
        if (!WhistleblowingPermissionService::canViewAllReports()) {
            http_response_code(403);
            $_SESSION['msg'] = 'A exportação de evidências é restrita aos administradores do Canal de Denúncias.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . UrlAdmHelper::to('denuncias-dashboard'));
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->export();
            return;
        }

        $protocol = $this->normalizeProtocol((string) ($_GET['protocol'] ?? ''));
        $repository = new WhistleblowingEvidenceRepository();
        $package = $repository->buildPackage($protocol);
        $error = null;
        if ($protocol !== '' && $package === null) {
            $error = 'Protocolo não encontrado. Use preferencialmente uma denúncia criada apenas para teste/auditoria.';
            $package = $repository->buildPackage('');
        }
        $package = $this->finalizePackage($package ?? []);

        $data = [
            'protocol' => $protocol,
            'package' => $package,
            'error' => $error,
            'excluded_content' => self::EXCLUDED_CONTENT,
            'recent_exports' => $repository->recentExports(),
            'csrf_token' => CSRFHelper::generateCSRFToken('whistleblowing_audit_evidence'),
        ];
        $pageElements = [
            'title_head' => 'Evidências auditáveis — Canal de Denúncias',
            'menu' => 'whistleblowing-audit-evidence',
            'buttonPermission' => ['WhistleblowingAuditEvidence'],
        ];
        $data = array_merge($data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/whistleblowing/audit-evidence', $data))->loadView();
    }

    private function export(): void
    {
        // Não consumir: PDF e Excel compartilham o mesmo token na página e o download
        // não recarrega o HTML — com consume=true o 2º clique falhava.
        if (!CSRFHelper::validateCSRFToken(
            'whistleblowing_audit_evidence',
            (string) ($_POST['csrf_token'] ?? ''),
            false
        )) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . UrlAdmHelper::to('whistleblowing-audit-evidence'));
            exit;
        }

        $format = strtolower(trim((string) ($_POST['format'] ?? '')));
        if (!in_array($format, ['pdf', 'excel'], true)) {
            http_response_code(400);
            exit('Formato inválido.');
        }

        $protocol = $this->normalizeProtocol((string) ($_POST['protocol'] ?? ''));
        $repository = new WhistleblowingEvidenceRepository();
        $package = $repository->buildPackage($protocol);
        if ($package === null) {
            $_SESSION['msg'] = 'Protocolo não encontrado. Nenhum arquivo foi gerado.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . UrlAdmHelper::to(
                'whistleblowing-audit-evidence' . ($protocol !== '' ? '?protocol=' . rawurlencode($protocol) : '')
            ));
            exit;
        }

        $reportId = $repository->findReportIdByProtocol($protocol);
        $package = $this->finalizePackage($package);

        $repository->recordExport(
            WhistleblowingPermissionService::sessionUserId(),
            $reportId,
            $format,
            $protocol
        );

        if ($format === 'pdf') {
            $this->exportPdf($package);
        }
        $this->exportExcel($package);
    }

    /**
     * @param array<string, mixed> $package
     * @return array<string, mixed>
     */
    private function finalizePackage(array $package): array
    {
        $package['excluded_content'] = self::EXCLUDED_CONTENT;
        $json = json_encode($package, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $package['fingerprint_sha256'] = hash('sha256', $json !== false ? $json : serialize($package));

        return $package;
    }

    /**
     * @param array<string, mixed> $package
     */
    private function exportPdf(array $package): never
    {
        $protocol = (string) ($package['protocol'] ?? '');
        $title = 'Pacote de evidências — Canal de Denúncias';
        $html = '<html><head><meta charset="UTF-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#222}'
            . 'h1{font-size:18px;margin-bottom:4px}h2{font-size:13px;margin-top:18px;border-bottom:1px solid #777}'
            . 'table{border-collapse:collapse;width:100%;margin-bottom:10px}th,td{border:1px solid #bbb;padding:4px;vertical-align:top}'
            . 'th{background:#eee;text-align:left}.notice{padding:8px;background:#fff3cd;border:1px solid #e6c55c}'
            . '.muted{color:#666}.break{page-break-before:always}</style></head><body>';
        $html .= '<h1>' . $this->e($title) . '</h1>';
        $html .= '<p>Gerado em ' . $this->e($this->displayValue($package['generated_at'] ?? '')) . '. '
            . ($protocol !== '' ? 'Protocolo de referência: <strong>' . $this->e($protocol) . '</strong>.' : 'Escopo: controles globais.')
            . '</p>';
        $html .= '<div class="notice"><strong>Pacote sanitizado:</strong> não contém relato, contatos, mensagens, arquivos, segredos ou hashes completos.</div>';
        $html .= '<p class="muted">Fingerprint SHA-256: ' . $this->e((string) ($package['fingerprint_sha256'] ?? '')) . '</p>';

        foreach ($this->exportSections($package) as $section) {
            $html .= '<h2>' . $this->e($section['title']) . '</h2>';
            $html .= $this->htmlTable($section['rows']);
        }
        $html .= '</body></html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream($this->filename('pdf', $protocol), ['Attachment' => true]);
        exit;
    }

    /**
     * @param array<string, mixed> $package
     */
    private function exportExcel(array $package): never
    {
        $spreadsheet = new Spreadsheet();
        $sections = $this->exportSections($package);
        foreach ($sections as $index => $section) {
            $sheet = $index === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();
            $sheet->setTitle($this->sheetTitle($section['title'], $index));
            $this->writeSheet($sheet, $section['rows']);
        }

        $spreadsheet->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $this->filename(
            'xlsx',
            (string) ($package['protocol'] ?? '')
        ) . '"');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * @param array<string, mixed> $package
     * @return list<array{title: string, rows: list<array<string, mixed>>}>
     */
    private function exportSections(array $package): array
    {
        $manifest = [
            [
                'gerado_em' => $package['generated_at'] ?? '',
                'ambiente' => $package['environment'] ?? '',
                'protocolo_referencia' => $package['protocol'] ?: 'Controles globais',
                'fingerprint_sha256' => $package['fingerprint_sha256'] ?? '',
            ],
        ];
        $excluded = array_map(
            static fn (string $item): array => ['conteudo_excluido' => $item],
            self::EXCLUDED_CONTENT
        );

        return [
            ['title' => 'Manifesto', 'rows' => $manifest],
            ['title' => 'Conteúdo excluído', 'rows' => $excluded],
            ['title' => 'Denúncia de referência', 'rows' => $this->assocAsRows($package['report'] ?? [])],
            ['title' => 'Criptografia', 'rows' => $this->assocAsRows($package['cryptography'] ?? [])],
            ['title' => 'Acessos agregados', 'rows' => $package['access_summary'] ?? []],
            ['title' => 'Linha do tempo', 'rows' => $package['status_timeline'] ?? []],
            ['title' => 'Mensagens agregadas', 'rows' => $package['messages_summary'] ?? []],
            ['title' => 'Anexos agregados', 'rows' => $package['attachments_summary'] ?? []],
            ['title' => 'Políticas seguras', 'rows' => $this->assocAsRows($package['policies'] ?? [])],
            ['title' => 'Resumo operacional', 'rows' => $this->assocAsRows($package['operational_summary'] ?? [])],
            ['title' => 'Resumo de retenção', 'rows' => $this->assocAsRows($package['retention_summary'] ?? [])],
            ['title' => 'Execuções de retenção', 'rows' => $package['retention_runs'] ?? []],
            ['title' => 'Comitês agregados', 'rows' => $package['committees'] ?? []],
            ['title' => 'Classificações', 'rows' => $package['committee_categories'] ?? []],
            ['title' => 'Estrutura técnica', 'rows' => $package['schema'] ?? []],
        ];
    }

    /**
     * @param array<string, mixed> $values
     * @return list<array<string, mixed>>
     */
    private function assocAsRows(array $values): array
    {
        $rows = [];
        foreach ($values as $key => $value) {
            $rows[] = ['campo' => $this->label((string) $key), 'valor' => $value];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeSheet(Worksheet $sheet, array $rows): void
    {
        if ($rows === []) {
            $sheet->setCellValue('A1', 'Sem registros');
            return;
        }

        $headers = array_keys($rows[0]);
        foreach ($headers as $column => $header) {
            $cell = $sheet->getCell([$column + 1, 1]);
            $cell->setValueExplicit($this->label((string) $header), DataType::TYPE_STRING);
        }
        foreach ($rows as $rowIndex => $row) {
            foreach ($headers as $column => $header) {
                $value = $row[$header] ?? '';
                $cell = $sheet->getCell([$column + 1, $rowIndex + 2]);
                if (is_int($value) || is_float($value)) {
                    $cell->setValue($value);
                } else {
                    $cell->setValueExplicit($this->safeSpreadsheetText($this->displayValue($value)), DataType::TYPE_STRING);
                }
            }
        }
        $lastColumn = count($headers);
        for ($column = 1; $column <= $lastColumn; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }
        $sheet->freezePane('A2');
        $sheet->getStyle([1, 1, $lastColumn, 1])->getFont()->setBold(true);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function htmlTable(array $rows): string
    {
        if ($rows === []) {
            return '<p class="muted">Sem registros.</p>';
        }
        $headers = array_keys($rows[0]);
        $html = '<table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . $this->e($this->label((string) $header)) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $html .= '<td>' . $this->e($this->displayValue($row[$header] ?? '')) . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    private function normalizeProtocol(string $protocol): string
    {
        $protocol = strtoupper(trim($protocol));

        return preg_match('/^[A-Z0-9-]{4,32}$/', $protocol) === 1 ? $protocol : '';
    }

    private function label(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }

    private function displayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        return (string) $value;
    }

    private function safeSpreadsheetText(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'" . $value : $value;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function sheetTitle(string $title, int $index): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', $title) ?: 'Evidência ' . ($index + 1);

        return mb_substr($title, 0, 31);
    }

    private function filename(string $extension, string $protocol): string
    {
        $suffix = $protocol !== '' ? '_' . preg_replace('/[^A-Z0-9-]/', '', $protocol) : '';

        return 'evidencias_canal_denuncias' . $suffix . '_' . date('Y-m-d_H-i-s') . '.' . $extension;
    }
}
