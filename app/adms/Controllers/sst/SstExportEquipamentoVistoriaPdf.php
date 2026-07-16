<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstEquipamentoAcoesCorretivasRepository;
use App\adms\Models\Repository\SstEquipamentoNaoConformidadesRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;
use App\adms\Models\Services\SstEquipamentoVistoriaPdfService;
use Mpdf\Mpdf;

/**
 * PDF / impressão da vistoria de equipamento concluída (com fotos e evidências de NC).
 */
class SstExportEquipamentoVistoriaPdf
{
    private const ENTITY_VISTORIA = 'equipamento_vistorias';
    private const ENTITY_AC = 'equipamento_acoes_corretivas';

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

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            @set_time_limit(120);

            $respostas = $repo->getRespostas($id);
            $anexos = (new SstAnexosRepository())->getByEntity(self::ENTITY_VISTORIA, $id);
            $ncs = (new SstEquipamentoNaoConformidadesRepository())->getByVistoriaId($id);

            $acoesRepo = new SstEquipamentoAcoesCorretivasRepository();
            $anexoRepo = new SstAnexosRepository();
            $acoesPorNc = [];
            $evidenciasPorAcao = [];
            foreach ($ncs as $nc) {
                $ncId = (int) ($nc['id'] ?? 0);
                if ($ncId <= 0) {
                    continue;
                }
                $acoes = $acoesRepo->getByNaoConformidadeId($ncId);
                $acoesPorNc[$ncId] = $acoes;
                foreach ($acoes as $acao) {
                    $aid = (int) ($acao['id'] ?? 0);
                    if ($aid > 0) {
                        $evidenciasPorAcao[$aid] = $anexoRepo->getByEntity(self::ENTITY_AC, $aid);
                    }
                }
            }

            $html = (new SstEquipamentoVistoriaPdfService())->buildHtml(
                $vistoria,
                $respostas,
                $anexos,
                $ncs,
                $acoesPorNc,
                $evidenciasPorAcao
            );

            $codigo = preg_replace('/\W+/', '_', (string) ($vistoria['equipamento_codigo'] ?? 'equipamento')) ?: 'equipamento';
            $comp = preg_replace('/\W+/', '_', (string) ($vistoria['competencia'] ?? '')) ?: date('Y-m');

            $tempDir = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3))
                . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'mpdf';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 12,
                'margin_bottom' => 14,
                'tempDir' => $tempDir,
            ]);
            $mpdf->SetTitle('Vistoria ' . ($vistoria['equipamento_codigo'] ?? '') . ' ' . ($vistoria['competencia'] ?? ''));
            $mpdf->SetAuthor('Tiaraju — SST');
            // mPDF exige 3 seções no rodapé: esquerda|centro|direita
            $mpdf->SetFooter('Vistoria SST||{PAGENO}/{nbpg}');
            $mpdf->WriteHTML($html);
            $mpdf->Output('Vistoria_' . $codigo . '_' . $comp . '.pdf', 'I');
            exit;
        } catch (\Throwable $e) {
            if (!headers_sent()) {
                $_SESSION['msg'] = 'Erro ao gerar PDF da vistoria: ' . $e->getMessage();
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id);
            } else {
                echo 'Erro ao gerar PDF: ' . htmlspecialchars($e->getMessage());
            }
            exit;
        }
    }
}
