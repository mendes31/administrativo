<?php

declare(strict_types=1);

namespace App\adms\Controllers\pricing;

use App\adms\Models\Repository\FormacaoPrecoRepository;
use App\adms\Models\Services\FormacaoPrecoPdfService;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Throwable;

final class FormacaoPrecoPdf
{
    public function index(int|string $id): void
    {
        $registroId = (int) $id;
        $registro = (new FormacaoPrecoRepository())->getById($registroId);
        if ($registro === null) {
            $_SESSION['msg'] = 'Precificação não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'formacao-preco');
            exit;
        }

        $parts = (new FormacaoPrecoPdfService())->buildParts($registro);
        $slug = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string) ($registro['nome'] ?? 'precificacao')) ?: 'precificacao';
        $filename = 'Formacao_Preco_' . $slug . '.pdf';

        try {
            $tempDir = dirname(__DIR__, 4) . '/storage/cache/mpdf';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }
            $mpdf = new Mpdf([
                'tempDir' => is_dir($tempDir) ? $tempDir : sys_get_temp_dir(),
                'format' => 'A4-L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 38,
                'margin_header' => 6,
                'margin_bottom' => 12,
                'setAutoTopMargin' => 'stretch',
                'default_font' => 'dejavusans',
            ]);
            $mpdf->SetTitle('Formação de preço — ' . (string) ($registro['nome'] ?? ''));
            $mpdf->SetAuthor('Grupo Tiaraju');
            $mpdf->WriteHTML('<style>' . $parts['css'] . '</style>', HTMLParserMode::HEADER_CSS);
            $mpdf->SetHTMLHeader($parts['header'], 'O');
            $mpdf->SetHTMLHeader($parts['header'], 'E');
            $mpdf->SetFooter('{PAGENO}/{nbpg}');
            $mpdf->WriteHTML($parts['body']);
            $mpdf->Output($filename, 'I');
            exit;
        } catch (Throwable $e) {
            $_SESSION['msg'] = 'Não foi possível gerar o PDF.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'formacao-preco-view/' . $registroId);
            exit;
        }
    }
}
