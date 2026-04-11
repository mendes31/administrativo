<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Repository\PayrollDocumentEventsRepository;
use App\adms\Models\Repository\UsersRepository;
use Mpdf\Mpdf;
use setasign\Fpdi\Fpdi;

/**
 * PDF: documento original + trilha que evolui (disponibilização, visualizações, downloads, ciência).
 * Regenerado após ações do titular (ver stream) e após assinatura.
 */
final class PayrollSignedBundlePdfService
{
    private const TZ_LABEL = 'America/Sao_Paulo (horário de Brasília)';

    /**
     * Regenera o pacote para o documento (pending ou signed, com ciência exigida).
     * Chamado após visualizar/baixar (titular) e após assinar.
     */
    public function regenerateForDocumentId(EmployeePayrollDocumentsRepository $repo, int $docId): ?string
    {
        if ($docId <= 0) {
            return null;
        }
        $doc = $repo->getById($docId);
        if ($doc === null || (($doc['status_version'] ?? '') !== 'active')) {
            return null;
        }
        if ((int)($doc['requires_signature_snapshot'] ?? 0) !== 1) {
            return null;
        }
        $sig = (string)($doc['signature_status'] ?? '');
        if (!in_array($sig, ['pending', 'signed'], true)) {
            return null;
        }

        $userId = (int)($doc['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $relOrig = (string)($doc['storage_path'] ?? '');
        if ($relOrig === '') {
            return null;
        }
        $absOrig = $repo->absoluteStoragePath($relOrig);
        if (!is_readable($absOrig)) {
            GenerateLog::generateLog('warning', 'PayrollSignedBundlePdfService: original ilegível', ['id' => $docId]);

            return null;
        }

        $prevBundle = (string)($doc['signed_bundle_storage_path'] ?? '');
        if ($prevBundle !== '') {
            $prevAbs = $repo->absoluteStoragePath($prevBundle);
            if (is_file($prevAbs)) {
                @unlink($prevAbs);
            }
        }

        $evRepo = new PayrollDocumentEventsRepository();
        $events = $evRepo->listEventsForDocumentIdsDetailed([$docId]);
        $accessLogs = $repo->listAccessLogsForDocumentIds([$docId]);

        $auditTmp = tempnam(sys_get_temp_dir(), 'payroll_audit_');
        if ($auditTmp === false) {
            return null;
        }
        $auditPdf = $auditTmp . '.pdf';
        @unlink($auditTmp);

        try {
            $this->writeAuditPdfToFile($doc, $events, $accessLogs, $auditPdf);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'PayrollSignedBundlePdfService: mPDF falhou', [
                'id' => $docId,
                'message' => $e->getMessage(),
            ]);
            @unlink($auditPdf);

            return null;
        }

        if (!is_readable($auditPdf) || filesize($auditPdf) < 32) {
            @unlink($auditPdf);

            return null;
        }

        $relOut = 'storage/private/payroll/signed_bundles/' . $userId . '/doc_' . $docId . '_pacote.pdf';
        $absOut = $repo->absoluteStoragePath($relOut);
        $dir = dirname($absOut);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            @unlink($auditPdf);
            GenerateLog::generateLog('warning', 'PayrollSignedBundlePdfService: não criou pasta', ['dir' => $dir]);

            return null;
        }

        $ok = $this->mergeOriginalAndAudit($absOrig, $auditPdf, $absOut);
        @unlink($auditPdf);

        if (!$ok) {
            GenerateLog::generateLog('warning', 'PayrollSignedBundlePdfService: merge FPDI falhou', ['id' => $docId]);

            return null;
        }

        $repo->updateSignedBundleStoragePath($docId, $relOut);

        return $relOut;
    }

    /**
     * @deprecated Use regenerateForDocumentId
     */
    public function generateAndPersist(EmployeePayrollDocumentsRepository $repo, array $doc): ?string
    {
        return $this->regenerateForDocumentId($repo, (int)($doc['id'] ?? 0));
    }

    /**
     * Agenda regeneração após enviar o PDF ao browser (não atrasa a resposta).
     */
    public static function scheduleRegenerateAfterResponse(int $documentId): void
    {
        if ($documentId <= 0) {
            return;
        }
        register_shutdown_function(static function () use ($documentId): void {
            try {
                (new self())->regenerateForDocumentId(new EmployeePayrollDocumentsRepository(), $documentId);
            } catch (\Throwable) {
            }
        });
    }

    /**
     * @param list<array<string, mixed>> $events
     * @param list<array<string, mixed>> $accessLogs
     */
    private function writeAuditPdfToFile(array $doc, array $events, array $accessLogs, string $absolutePath): void
    {
        $html = $this->buildAuditHtml($doc, $events, $accessLogs);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 18,
            'margin_bottom' => 18,
        ]);
        $docId = (int)($doc['id'] ?? 0);
        $mpdf->SetTitle('Trilha documento RH — ' . $docId);
        $mpdf->SetAuthor('Portal RH');
        $mpdf->WriteHTML($html);
        $mpdf->Output($absolutePath, 'F');
    }

    /**
     * @param list<array<string, mixed>> $events
     * @param list<array<string, mixed>> $accessLogs
     */
    private function buildAuditHtml(array $doc, array $events, array $accessLogs): string
    {
        $docId = (int)($doc['id'] ?? 0);
        $ownerId = (int)($doc['user_id'] ?? 0);
        $user = (new UsersRepository())->getUser($ownerId);
        $employeeName = is_array($user) ? (string)($user['name'] ?? '') : '';
        $hashDoc = (string)($doc['file_hash_sha256'] ?? '');
        $sigSt = (string)($doc['signature_status'] ?? '');
        $publishedAt = (string)($doc['published_at'] ?? '');
        $ver = (int)($doc['document_version'] ?? 1);
        $y = (int)($doc['reference_year'] ?? 0);
        $mo = $doc['reference_month'] ?? null;
        $ref = $mo !== null && $mo !== '' ? str_pad((string)$mo, 2, '0', STR_PAD_LEFT) . '/' . $y : (string)$y;
        $title = htmlspecialchars((string)($doc['title'] ?? 'Documento'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $empEsc = htmlspecialchars($employeeName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $hashEsc = htmlspecialchars($hashDoc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $generatedAt = date('Y-m-d H:i:s');

        $firstView = null;
        $firstDl = null;
        $lastView = null;
        $lastDl = null;
        $viewCount = 0;
        $dlCount = 0;
        foreach ($events as $ev) {
            $t = (string)($ev['event_type'] ?? '');
            $ts = (string)($ev['created_at'] ?? '');
            if ($t === 'document_viewed' && $ts !== '') {
                $viewCount++;
                if ($firstView === null) {
                    $firstView = $ts;
                }
                $lastView = $ts;
            }
            if ($t === 'document_downloaded' && $ts !== '') {
                $dlCount++;
                if ($firstDl === null) {
                    $firstDl = $ts;
                }
                $lastDl = $ts;
            }
        }

        $css = '<style>body{font-family:DejaVu Sans,sans-serif;font-size:10pt;} h1{font-size:14pt;} h2{font-size:11pt;margin-top:12px;border-bottom:1px solid #ccc;padding-bottom:4px;} table{border-collapse:collapse;width:100%;margin-top:8px;} td{border:1px solid #ccc;padding:5px 7px;vertical-align:top;} td.k{font-weight:bold;width:34%;background:#f4f4f4;font-size:9pt;} .note{font-size:8.5pt;color:#444;margin-top:8px;line-height:1.35;} .small{font-size:8pt;} .ev td{font-size:8pt;} tr:nth-child(even) .ev{background:#fafafa;}</style>';

        $html = $css;
        $html .= '<h1>Trilha do documento (portal RH)</h1>';
        $html .= '<p class="note">Fuso: <strong>' . htmlspecialchars(self::TZ_LABEL, ENT_QUOTES, 'UTF-8') . '</strong>. ';
        $html .= 'Este anexo é atualizado quando o colaborador <strong>visualiza</strong>, <strong>descarrega</strong> ou <strong>confirma o recebimento</strong>. ';
        $html .= 'As páginas anteriores deste ficheiro são o <strong>documento original</strong> sem alteração de conteúdo.</p>';
        $html .= '<p class="note">Trilha gerada em: <strong>' . htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') . '</strong></p>';

        $html .= '<h2>Identificação</h2><table>';
        $html .= '<tr><td class="k">ID interno</td><td>' . $docId . '</td></tr>';
        $html .= '<tr><td class="k">Colaborador</td><td>' . $empEsc . '</td></tr>';
        $html .= '<tr><td class="k">Título</td><td>' . $title . '</td></tr>';
        $html .= '<tr><td class="k">Referência</td><td>' . htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $html .= '<tr><td class="k">Versão</td><td>' . $ver . '</td></tr>';
        $html .= '<tr><td class="k">Hash SHA-256 (ficheiro original)</td><td style="word-break:break-all;font-size:8pt;">' . ($hashEsc !== '' ? $hashEsc : '—') . '</td></tr>';
        $html .= '<tr><td class="k">Disponibilizado ao portal</td><td>' . htmlspecialchars($publishedAt !== '' ? $publishedAt : '—', ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $html .= '<tr><td class="k">Estado da ciência</td><td><strong>' . htmlspecialchars($sigSt, ENT_QUOTES, 'UTF-8') . '</strong></td></tr></table>';

        $html .= '<h2>Resumo de acessos ao PDF</h2><table>';
        $html .= '<tr><td class="k">Visualizações (evento)</td><td>' . $viewCount . ($firstView !== null ? ' — primeira: ' . htmlspecialchars($firstView, ENT_QUOTES, 'UTF-8') : '') . ($lastView !== null && $lastView !== $firstView ? ' — última: ' . htmlspecialchars($lastView, ENT_QUOTES, 'UTF-8') : '') . '</td></tr>';
        $html .= '<tr><td class="k">Downloads (evento)</td><td>' . $dlCount . ($firstDl !== null ? ' — primeiro: ' . htmlspecialchars($firstDl, ENT_QUOTES, 'UTF-8') : '') . ($lastDl !== null && $lastDl !== $firstDl ? ' — último: ' . htmlspecialchars($lastDl, ENT_QUOTES, 'UTF-8') : '') . '</td></tr>';
        $html .= '<tr><td class="k">Linhas no registo de stream</td><td>' . count($accessLogs) . '</td></tr></table>';

        if ($sigSt === 'signed') {
            $signedAt = (string)($doc['signed_at'] ?? '');
            $authMethod = (string)($doc['signed_auth_method'] ?? '');
            $ip = (string)($doc['signed_ip'] ?? '');
            $ua = (string)($doc['signed_user_agent'] ?? '');
            $signHash = (string)($doc['signed_document_hash_sha256'] ?? $hashDoc);
            $html .= '<h2>Confirmação de recebimento (ciência)</h2><table>';
            $html .= '<tr><td class="k">Data/hora</td><td>' . htmlspecialchars($signedAt, ENT_QUOTES, 'UTF-8') . '</td></tr>';
            $html .= '<tr><td class="k">Método de autenticação</td><td>' . htmlspecialchars($authMethod, ENT_QUOTES, 'UTF-8') . '</td></tr>';
            $html .= '<tr><td class="k">IP (confirmação)</td><td>' . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . '</td></tr>';
            $html .= '<tr><td class="k">User-Agent</td><td style="word-break:break-all;font-size:7.5pt;">' . htmlspecialchars(mb_substr($ua, 0, 1800), ENT_QUOTES, 'UTF-8') . '</td></tr>';
            $html .= '<tr><td class="k">Hash na confirmação</td><td style="word-break:break-all;font-size:8pt;">' . htmlspecialchars($signHash, ENT_QUOTES, 'UTF-8') . '</td></tr></table>';
        } else {
            $html .= '<h2>Confirmação de recebimento</h2><p class="note"><strong>Pendente.</strong> A secção completa de ciência será acrescentada após o colaborador confirmar o recebimento no portal.</p>';
        }

        $html .= '<h2>Cronologia de eventos (últimos registos)</h2>';
        $html .= '<table class="ev"><tr><td class="k" style="width:22%;">Quando</td><td class="k" style="width:28%;">Tipo</td><td class="k">IP</td><td class="k">Nota</td></tr>';
        $max = 45;
        $n = 0;
        foreach ($events as $ev) {
            if ($n >= $max) {
                break;
            }
            $when = htmlspecialchars((string)($ev['created_at'] ?? ''), ENT_QUOTES, 'UTF-8');
            $type = htmlspecialchars(self::eventTypeLabel((string)($ev['event_type'] ?? '')), ENT_QUOTES, 'UTF-8');
            $ipEv = htmlspecialchars((string)($ev['ip'] ?? ''), ENT_QUOTES, 'UTF-8');
            $meta = (string)($ev['meta_json'] ?? '');
            $metaShort = $meta !== '' ? (mb_strlen($meta) > 120 ? mb_substr($meta, 0, 117) . '…' : $meta) : '';
            $metaEsc = htmlspecialchars($metaShort, ENT_QUOTES, 'UTF-8');
            $html .= '<tr><td>' . $when . '</td><td>' . $type . '</td><td style="font-size:7.5pt;">' . $ipEv . '</td><td style="font-size:7.5pt;word-break:break-all;">' . $metaEsc . '</td></tr>';
            $n++;
        }
        if ($n === 0) {
            $html .= '<tr><td colspan="4">Sem eventos registados.</td></tr>';
        }
        $html .= '</table>';

        if ($accessLogs !== []) {
            $html .= '<h2>Registo de entrega do PDF (stream)</h2>';
            $html .= '<table class="ev"><tr><td class="k" style="width:22%;">Quando</td><td class="k">Modo</td><td class="k">Visualizador (sessão)</td><td class="k">IP</td></tr>';
            $m = 0;
            foreach ($accessLogs as $log) {
                if ($m >= 30) {
                    break;
                }
                $when = htmlspecialchars((string)($log['created_at'] ?? ''), ENT_QUOTES, 'UTF-8');
                $mode = htmlspecialchars((string)($log['delivery_mode'] ?? ''), ENT_QUOTES, 'UTF-8');
                $viewer = (int)($log['viewer_user_id'] ?? 0);
                $ipL = htmlspecialchars((string)($log['ip'] ?? ''), ENT_QUOTES, 'UTF-8');
                $html .= '<tr><td>' . $when . '</td><td>' . $mode . '</td><td>' . $viewer . '</td><td style="font-size:7.5pt;">' . $ipL . '</td></tr>';
                $m++;
            }
            $html .= '</table>';
        }

        $html .= '<p class="note">Documento interno para arquivo e demonstração de diligência (ex.: LGPD, trâmites trabalhistas). Não constitui assinatura qualificada ICP-Brasil.</p>';

        return $html;
    }

    private static function eventTypeLabel(string $type): string
    {
        return match ($type) {
            'document_published' => 'Publicação no portal',
            'document_viewed' => 'Visualização do PDF',
            'document_downloaded' => 'Download do PDF',
            'document_signed' => 'Ciência confirmada',
            'otp_requested' => 'Pedido de código OTP',
            'otp_sent_whatsapp' => 'OTP enviado (WhatsApp)',
            'otp_sent_email_fallback' => 'OTP enviado (e-mail)',
            'otp_send_fail' => 'Falha envio OTP',
            'otp_validate_success' => 'OTP validado',
            'otp_validate_fail' => 'Falha validação OTP',
            'otp_rate_limited' => 'Limite de pedidos OTP',
            'password_sign_fail' => 'Falha de senha',
            default => $type,
        };
    }

    private function mergeOriginalAndAudit(string $absoluteOriginal, string $absoluteAudit, string $absoluteOut): bool
    {
        try {
            $pdf = new Fpdi();
            $n = $pdf->setSourceFile($absoluteOriginal);
            for ($i = 1; $i <= $n; $i++) {
                $tplId = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tplId);
                if ($size === false) {
                    return false;
                }
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }
            $n2 = $pdf->setSourceFile($absoluteAudit);
            for ($i = 1; $i <= $n2; $i++) {
                $tplId = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tplId);
                if ($size === false) {
                    return false;
                }
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }
            $pdf->Output('F', $absoluteOut);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'PayrollSignedBundlePdfService::mergeOriginalAndAudit', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        return is_file($absoluteOut) && filesize($absoluteOut) > 0;
    }
}
