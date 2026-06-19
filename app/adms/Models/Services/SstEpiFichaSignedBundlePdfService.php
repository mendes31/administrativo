<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\SstEpiFichasRepository;
use Mpdf\Mpdf;
use setasign\Fpdi\Fpdi;

/**
 * Anexa trilha de auditoria ao PDF da ficha após confirmação de recebimento no portal.
 * O hash em signed_document_hash_sha256 permanece o do documento original assinado.
 */
final class SstEpiFichaSignedBundlePdfService
{
    private const TZ_LABEL = 'America/Sao_Paulo (horário de Brasília)';

    public function appendAuditTrail(int $fichaId): ?string
    {
        if ($fichaId <= 0) {
            return null;
        }

        $repo = new SstEpiFichasRepository();
        $ficha = $repo->getById($fichaId);
        if ($ficha === null || ($ficha['status_assinatura'] ?? '') !== 'Assinado') {
            return null;
        }
        if (!$this->needsAuditAppend($ficha)) {
            return (string) ($ficha['pdf_storage_path'] ?? '') ?: null;
        }

        $relOrig = (string) ($ficha['pdf_storage_path'] ?? '');
        if ($relOrig === '') {
            return null;
        }
        $absOrig = $repo->absoluteStoragePath($relOrig);
        if (!is_readable($absOrig)) {
            GenerateLog::generateLog('warning', 'SstEpiFichaSignedBundlePdfService: PDF original ilegível', ['ficha_id' => $fichaId]);

            return null;
        }

        $auditTmp = tempnam(sys_get_temp_dir(), 'epi_ficha_audit_');
        if ($auditTmp === false) {
            return null;
        }
        $auditPdf = $auditTmp . '.pdf';
        @unlink($auditTmp);

        try {
            $this->writeAuditPdfToFile($ficha, $auditPdf);
        } catch (\Throwable $e) {
            @unlink($auditPdf);
            GenerateLog::generateLog('warning', 'SstEpiFichaSignedBundlePdfService: falha ao gerar trilha', [
                'ficha_id' => $fichaId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (!is_readable($auditPdf) || filesize($auditPdf) < 32) {
            @unlink($auditPdf);

            return null;
        }

        $outTmp = tempnam(sys_get_temp_dir(), 'epi_ficha_bundle_');
        if ($outTmp === false) {
            @unlink($auditPdf);

            return null;
        }
        $outPdf = $outTmp . '.pdf';
        @unlink($outTmp);

        $ok = $this->mergeOriginalAndAudit($absOrig, $auditPdf, $outPdf);
        @unlink($auditPdf);
        if (!$ok) {
            @unlink($outPdf);
            GenerateLog::generateLog('warning', 'SstEpiFichaSignedBundlePdfService: merge FPDI falhou', ['ficha_id' => $fichaId]);

            return null;
        }

        if (!@rename($outPdf, $absOrig)) {
            @unlink($outPdf);
            GenerateLog::generateLog('warning', 'SstEpiFichaSignedBundlePdfService: não substituiu PDF', ['ficha_id' => $fichaId]);

            return null;
        }

        $hash = hash_file('sha256', $absOrig) ?: '';
        $repo->updatePdfMeta($fichaId, $relOrig, $hash);

        return $relOrig;
    }

    public static function ensureAuditTrailIncluded(SstEpiFichasRepository $repo, int $fichaId): void
    {
        if ($fichaId <= 0) {
            return;
        }
        $ficha = $repo->getById($fichaId);
        if ($ficha === null) {
            return;
        }
        if (!(new self())->needsAuditAppend($ficha)) {
            return;
        }
        try {
            (new self())->appendAuditTrail($fichaId);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'SstEpiFichaSignedBundlePdfService::ensureAuditTrailIncluded', [
                'ficha_id' => $fichaId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /** @param array<string, mixed> $ficha */
    public function needsAuditAppend(array $ficha): bool
    {
        if (($ficha['status_assinatura'] ?? '') !== 'Assinado' || empty($ficha['signed_at'])) {
            return false;
        }

        $repo = new SstEpiFichasRepository();
        $rel = (string) ($ficha['pdf_storage_path'] ?? '');
        if ($rel === '') {
            return false;
        }
        $abs = $repo->absoluteStoragePath($rel);
        if (!is_readable($abs)) {
            return false;
        }
        if ($this->pdfAlreadyHasAudit($abs)) {
            return false;
        }

        $signedHash = strtolower(trim((string) ($ficha['signed_document_hash_sha256'] ?? '')));
        $pdfHash = strtolower(trim((string) ($ficha['pdf_hash_sha256'] ?? '')));

        if ($signedHash !== '' && $pdfHash !== '') {
            return hash_equals($signedHash, $pdfHash);
        }

        return true;
    }

    private function pdfAlreadyHasAudit(string $absolutePath): bool
    {
        $chunk = @file_get_contents($absolutePath, false, null, 0, 524288);

        return $chunk !== false && str_contains($chunk, 'Trilha de auditoria');
    }

    /** @param array<string, mixed> $ficha */
    private function writeAuditPdfToFile(array $ficha, string $absolutePath): void
    {
        $html = $this->buildAuditHtml($ficha);
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 18,
            'margin_bottom' => 18,
        ]);
        $fichaId = (int) ($ficha['id'] ?? 0);
        $mpdf->SetTitle('Trilha de auditoria — Ficha EPI #' . $fichaId);
        $mpdf->SetAuthor('SST — Segurança do Trabalho');
        $mpdf->WriteHTML($html);
        $mpdf->Output($absolutePath, 'F');
    }

    /** @param array<string, mixed> $ficha */
    private function buildAuditHtml(array $ficha): string
    {
        $esc = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $fichaId = (int) ($ficha['id'] ?? 0);
        $nome = $esc($ficha['colaborador_nome'] ?? null);
        $cpf = $esc($ficha['colaborador_cpf'] ?? null);
        $dataEntrega = !empty($ficha['data_entrega'])
            ? date('d/m/Y', strtotime((string) $ficha['data_entrega']))
            : '—';
        $resp = $esc($ficha['entregue_por_nome'] ?? null);
        $hashOriginal = $esc($ficha['signed_document_hash_sha256'] ?? $ficha['pdf_hash_sha256'] ?? null);
        $signedAt = $esc($ficha['signed_at'] ?? null);
        $authMethod = $this->authMethodLabel((string) ($ficha['signed_auth_method'] ?? ''));
        $ip = $esc($ficha['signed_ip'] ?? null);
        $ua = $esc(mb_substr((string) ($ficha['signed_user_agent'] ?? ''), 0, 1800));
        $criadoEm = !empty($ficha['created_at'])
            ? $esc(date('d/m/Y H:i:s', strtotime((string) $ficha['created_at'])))
            : '—';
        $geradoEm = date('d/m/Y H:i:s');

        $css = '<style>
body{font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#222}
h1{font-size:14pt;margin:0 0 8px}
h2{font-size:11pt;margin:14px 0 6px;border-bottom:1px solid #ccc;padding-bottom:4px}
table{border-collapse:collapse;width:100%;margin-top:8px}
td{border:1px solid #bbb;padding:5px 7px;vertical-align:top}
td.k{font-weight:bold;width:34%;background:#f4f4f4;font-size:9pt}
.note{font-size:8.5pt;color:#444;margin-top:8px;line-height:1.4}
</style>';

        return $css
            . '<h1>Trilha de auditoria — Ficha de entrega de EPI</h1>'
            . '<p class="note">Fuso: <strong>' . $esc(self::TZ_LABEL) . '</strong>. '
            . 'As páginas anteriores deste arquivo são o <strong>documento original</strong> apresentado ao colaborador antes da confirmação. '
            . 'O hash SHA-256 abaixo comprova a integridade do conteúdo no momento da assinatura.</p>'
            . '<p class="note">Trilha gerada em: <strong>' . $esc($geradoEm) . '</strong></p>'
            . '<h2>Identificação da ficha</h2><table>'
            . '<tr><td class="k">Registro</td><td>#' . $fichaId . '</td></tr>'
            . '<tr><td class="k">Colaborador</td><td>' . $nome . '</td></tr>'
            . '<tr><td class="k">CPF</td><td>' . $cpf . '</td></tr>'
            . '<tr><td class="k">Data da entrega</td><td>' . $esc($dataEntrega) . '</td></tr>'
            . '<tr><td class="k">Responsável pela entrega</td><td>' . $resp . '</td></tr>'
            . '<tr><td class="k">Ficha criada em</td><td>' . $criadoEm . '</td></tr>'
            . '<tr><td class="k">Hash SHA-256 (documento original)</td><td style="word-break:break-all;font-size:8pt;">' . ($hashOriginal !== '' ? $hashOriginal : '—') . '</td></tr>'
            . '</table>'
            . '<h2>Confirmação de recebimento (portal)</h2><table>'
            . '<tr><td class="k">Status</td><td><strong>Assinado</strong></td></tr>'
            . '<tr><td class="k">Data e hora</td><td>' . ($signedAt !== '' ? $signedAt : '—') . '</td></tr>'
            . '<tr><td class="k">Método de autenticação</td><td>' . $esc($authMethod) . '</td></tr>'
            . '<tr><td class="k">Endereço IP</td><td>' . ($ip !== '' ? $ip : '—') . '</td></tr>'
            . '<tr><td class="k">User-Agent (navegador)</td><td style="word-break:break-all;font-size:7.5pt;">' . ($ua !== '' ? $ua : '—') . '</td></tr>'
            . '</table>'
            . '<p class="note">Registro eletrônico de ciência do recebimento dos EPIs, com sessão autenticada no portal do colaborador. '
            . 'Não constitui assinatura qualificada ICP-Brasil. Documento para arquivo interno e demonstração de diligência (NR-06 / SST).</p>';
    }

    private function authMethodLabel(string $method): string
    {
        return match ($method) {
            'session' => 'Sessão autenticada no portal',
            default => $method !== '' ? $method : '—',
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
            GenerateLog::generateLog('warning', 'SstEpiFichaSignedBundlePdfService::mergeOriginalAndAudit', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        return is_file($absoluteOut) && filesize($absoluteOut) > 0;
    }
}
