<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Repository\UsersRepository;
use Mpdf\Mpdf;

/**
 * Comprovante em PDF da ciência registada (Fase 2 — evidência para arquivo).
 */
class PayrollSignatureReceipt
{
    public function index(string|null $id = null): void
    {
        $docId = $id !== null && $id !== '' ? (int)$id : (int)($_GET['id'] ?? 0);
        if ($docId <= 0) {
            header('HTTP/1.0 400 Bad Request');
            exit;
        }

        $sessionUid = (int)($_SESSION['user_id'] ?? 0);
        if ($sessionUid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $repo = new EmployeePayrollDocumentsRepository();
        $doc = $repo->getById($docId);
        if ($doc === null) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        $ownerId = (int)($doc['user_id'] ?? 0);
        if ($ownerId !== $sessionUid && !UserAccessHelper::hasFullSystemAccess()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }

        if (($doc['signature_status'] ?? '') !== 'signed') {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        $user = (new UsersRepository())->getUser($ownerId);
        $employeeName = is_array($user) ? (string)($user['name'] ?? '') : '';
        $hashDoc = (string)($doc['signed_document_hash_sha256'] ?? $doc['file_hash_sha256'] ?? '');
        $signedAt = (string)($doc['signed_at'] ?? '');
        $authMethod = (string)($doc['signed_auth_method'] ?? '');
        $title = htmlspecialchars((string)($doc['title'] ?? 'Documento'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $ver = (int)($doc['document_version'] ?? 1);
        $y = (int)($doc['reference_year'] ?? 0);
        $mo = $doc['reference_month'] ?? null;
        $ref = $mo !== null && $mo !== '' ? str_pad((string)$mo, 2, '0', STR_PAD_LEFT) . '/' . $y : (string)$y;
        $empEsc = htmlspecialchars($employeeName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $hashEsc = htmlspecialchars($hashDoc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $atEsc = htmlspecialchars($signedAt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $amEsc = htmlspecialchars($authMethod, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $html = '<style>body{font-family:DejaVu Sans,sans-serif;font-size:11pt;} h1{font-size:16pt;} table{border-collapse:collapse;width:100%;margin-top:12px;} td{border:1px solid #ccc;padding:6px 8px;vertical-align:top;} td.k{font-weight:bold;width:38%;background:#f5f5f5;}</style>';
        $html .= '<h1>Comprovante de confirmação de recebimento</h1>';
        $html .= '<p>Este documento comprova que o colaborador confirmou o recebimento do ficheiro disponibilizado no portal administrativo.</p>';
        $html .= '<table><tr><td class="k">Colaborador</td><td>' . $empEsc . '</td></tr>';
        $html .= '<tr><td class="k">Título / descrição</td><td>' . $title . '</td></tr>';
        $html .= '<tr><td class="k">Referência</td><td>' . htmlspecialchars($ref, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</td></tr>';
        $html .= '<tr><td class="k">Versão do documento</td><td>' . $ver . '</td></tr>';
        $html .= '<tr><td class="k">Data/hora da confirmação</td><td>' . $atEsc . '</td></tr>';
        $html .= '<tr><td class="k">Método de autenticação</td><td>' . $amEsc . '</td></tr>';
        $html .= '<tr><td class="k">Hash SHA-256 (ficheiro na confirmação)</td><td style="word-break:break-all;font-size:9pt;">' . $hashEsc . '</td></tr></table>';
        $html .= '<p style="margin-top:16px;font-size:9pt;color:#555;">ID interno do documento: ' . (int)$doc['id'] . '</p>';

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 18,
            'margin_bottom' => 18,
        ]);
        $mpdf->SetTitle('Comprovante — ' . (string)($doc['title'] ?? ''));
        $mpdf->SetAuthor('Portal RH');
        $mpdf->WriteHTML($html);
        $mpdf->Output('comprovante_ciencia_documento_' . $docId . '.pdf', 'I');
        exit;
    }
}
