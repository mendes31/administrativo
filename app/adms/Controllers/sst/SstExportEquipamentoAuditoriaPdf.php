<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Models\Repository\SstEquipamentoRecargasRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Models\Services\SstEquipamentoAuditoriaPdfService;
use Mpdf\Mpdf;

/**
 * PDF de auditoria: vistorias + recargas por período (cabeçalho institucional LNT).
 */
class SstExportEquipamentoAuditoriaPdf
{
    public function index(string|int $id = 0): void
    {
        $equipamentoId = (int) $id;
        $dataInicio = trim((string) ($_GET['data_inicio'] ?? $_POST['data_inicio'] ?? ''));
        $dataFim = trim((string) ($_GET['data_fim'] ?? $_POST['data_fim'] ?? ''));
        $empresa = trim((string) ($_GET['empresa_contratante'] ?? $_POST['empresa_contratante'] ?? ''));
        $tipoId = (int) ($_GET['tipo_id'] ?? $_POST['tipo_id'] ?? 0);

        $returnUrl = $equipamentoId > 0
            ? $_ENV['URL_ADM'] . 'sst-view-equipamento/' . $equipamentoId
            : $_ENV['URL_ADM'] . 'sst-list-equipamentos';

        if ($dataInicio === '' || $dataFim === '') {
            $_SESSION['msg'] = 'Informe o período (data início e data fim) para gerar o relatório.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $returnUrl);
            exit;
        }

        $tsDe = strtotime($dataInicio);
        $tsAte = strtotime($dataFim);
        if ($tsDe === false || $tsAte === false) {
            $_SESSION['msg'] = 'Datas do período inválidas.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $returnUrl);
            exit;
        }
        if ($tsDe > $tsAte) {
            $_SESSION['msg'] = 'A data início não pode ser posterior à data fim.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $returnUrl);
            exit;
        }

        // Limite razoável para auditoria (2 anos)
        if (($tsAte - $tsDe) > 366 * 2 * 86400) {
            $_SESSION['msg'] = 'O período máximo do relatório é de 24 meses.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $returnUrl);
            exit;
        }

        try {
            if (ob_get_length()) {
                ob_end_clean();
            }
            @set_time_limit(120);

            $eqRepo = new SstEquipamentosRepository();
            $equipamento = null;
            if ($equipamentoId > 0) {
                $equipamento = $eqRepo->getById($equipamentoId);
                if (!$equipamento) {
                    $_SESSION['msg'] = 'Equipamento não encontrado.';
                    $_SESSION['msg_type'] = 'danger';
                    header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
                    exit;
                }
            }

            $eqFilter = $equipamentoId > 0 ? $equipamentoId : null;
            $empresaFilter = $equipamentoId > 0 ? null : ($empresa !== '' ? $empresa : null);
            $tipoFilter = $equipamentoId > 0 ? null : ($tipoId > 0 ? $tipoId : null);

            $vistorias = $eqRepo->listVistoriasByPeriodo(
                $eqFilter,
                $dataInicio,
                $dataFim,
                $empresaFilter,
                $tipoFilter
            );
            $recargas = (new SstEquipamentoRecargasRepository())->listByPeriodo(
                $eqFilter,
                $dataInicio,
                $dataFim,
                $empresaFilter,
                $tipoFilter
            );

            $html = (new SstEquipamentoAuditoriaPdfService())->buildHtml(
                $equipamento,
                $vistorias,
                $recargas,
                $dataInicio,
                $dataFim
            );

            $codigo = $equipamento
                ? (preg_replace('/\W+/', '_', (string) ($equipamento['codigo'] ?? 'equipamento')) ?: 'equipamento')
                : 'consolidado';
            $fileName = 'Auditoria_SST_' . $codigo . '_' . $dataInicio . '_' . $dataFim . '.pdf';

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => $equipamento ? 'P' : 'L',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 12,
                'margin_bottom' => 14,
            ]);
            $mpdf->SetTitle('Relatório de vistorias e recargas SST');
            $mpdf->SetAuthor('Tiaraju — SST');
            $mpdf->SetFooter('SST · Auditoria de equipamentos||{PAGENO}/{nbpg}');
            $mpdf->WriteHTML($html);
            $mpdf->Output($fileName, 'I');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Erro ao gerar relatório: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $returnUrl);
            exit;
        }
    }
}
