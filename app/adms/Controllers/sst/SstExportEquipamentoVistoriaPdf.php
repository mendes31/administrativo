<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;
use App\adms\Models\Services\SstEquipamentoVistoriaPdfService;
use Mpdf\Mpdf;

/**
 * PDF / impressão da vistoria de equipamento concluída (com fotos).
 */
class SstExportEquipamentoVistoriaPdf
{
    private const ENTITY = 'equipamento_vistorias';

    public function index(string|int $id = 0): void
    {
        $id = (int) $id;
        try {
            $repo = new SstEquipamentoVistoriasRepository();
            $vistoria = $repo->getById($id);
            if (!$vistoria) {
                $_SESSION['msg'] = 'Vistoria não encontrada.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-minhas-equipamento-vistorias');
                exit;
            }

            if (($vistoria['status'] ?? '') !== 'Concluída') {
                $_SESSION['msg'] = 'O documento de impressão só está disponível após concluir a vistoria.';
                $_SESSION['msg_type'] = 'warning';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id);
                exit;
            }

            if (ob_get_length()) {
                ob_end_clean();
            }
            @set_time_limit(90);

            $respostas = $repo->getRespostas($id);
            $anexos = (new SstAnexosRepository())->getByEntity(self::ENTITY, $id);
            $html = (new SstEquipamentoVistoriaPdfService())->buildHtml($vistoria, $respostas, $anexos);

            $codigo = preg_replace('/\W+/', '_', (string) ($vistoria['equipamento_codigo'] ?? 'equipamento')) ?: 'equipamento';
            $comp = preg_replace('/\W+/', '_', (string) ($vistoria['competencia'] ?? '')) ?: date('Y-m');

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 12,
                'margin_bottom' => 14,
            ]);
            $mpdf->SetTitle('Vistoria ' . ($vistoria['equipamento_codigo'] ?? '') . ' ' . ($vistoria['competencia'] ?? ''));
            $mpdf->SetFooter('Vistoria SST | {PAGENO}/{nbpg}');
            $mpdf->WriteHTML($html);
            $mpdf->Output('Vistoria_' . $codigo . '_' . $comp . '.pdf', 'I');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Erro ao gerar PDF da vistoria: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id);
            exit;
        }
    }
}
