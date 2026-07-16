<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstEquipamentoAcoesCorretivasRepository;
use App\adms\Models\Repository\SstEquipamentoNaoConformidadesRepository;
use App\adms\Models\Repository\SstEquipamentoRecargasRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Models\Services\SstEquipamentoAuditoriaPdfService;
use Mpdf\Mpdf;

/**
 * PDF de auditoria: vistorias completas (checklist, fotos, NC) + recargas por período.
 */
class SstExportEquipamentoAuditoriaPdf
{
    private const ENTITY_VISTORIA = 'equipamento_vistorias';
    private const ENTITY_AC = 'equipamento_acoes_corretivas';

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
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            @set_time_limit(300);
            @ini_set('pcre.backtrack_limit', '5000000');
            @ini_set('memory_limit', '512M');

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

            $vistoriasDetalhe = $this->loadVistoriasDetalhe($vistorias);

            $html = (new SstEquipamentoAuditoriaPdfService())->buildHtml(
                $equipamento,
                $vistorias,
                $recargas,
                $dataInicio,
                $dataFim,
                $vistoriasDetalhe
            );

            $codigo = $equipamento
                ? (preg_replace('/\W+/', '_', (string) ($equipamento['codigo'] ?? 'equipamento')) ?: 'equipamento')
                : 'consolidado';
            $fileName = 'Auditoria_SST_' . $codigo . '_' . $dataInicio . '_' . $dataFim . '.pdf';

            $tempDir = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3))
                . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'mpdf';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => $equipamento ? 'P' : 'L',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 12,
                'margin_bottom' => 14,
                'tempDir' => $tempDir,
            ]);
            $mpdf->SetTitle('Relatório de vistorias e recargas SST');
            $mpdf->SetAuthor('Tiaraju — SST');
            $mpdf->SetFooter('SST · Auditoria de equipamentos||{PAGENO}/{nbpg}');

            $chunks = $this->splitHtmlChunks($html);
            foreach ($chunks as $i => $chunk) {
                $mpdf->WriteHTML($chunk, $i === 0 ? 0 : 2);
            }
            $mpdf->Output($fileName, 'I');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Erro ao gerar relatório: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $returnUrl);
            exit;
        }
    }

    /**
     * @param list<array<string, mixed>> $vistorias
     * @return list<array{
     *   vistoria: array<string, mixed>,
     *   respostas: list<array<string, mixed>>,
     *   anexos: list<array<string, mixed>>,
     *   naoConformidades: list<array<string, mixed>>,
     *   acoesPorNcId: array<int, list<array<string, mixed>>>,
     *   evidenciasPorAcaoId: array<int, list<array<string, mixed>>>
     * }>
     */
    private function loadVistoriasDetalhe(array $vistorias): array
    {
        if ($vistorias === []) {
            return [];
        }

        $vistRepo = new SstEquipamentoVistoriasRepository();
        $anexoRepo = new SstAnexosRepository();
        $ncRepo = new SstEquipamentoNaoConformidadesRepository();
        $acoesRepo = new SstEquipamentoAcoesCorretivasRepository();
        $detalhe = [];

        foreach ($vistorias as $vRow) {
            $vid = (int) ($vRow['id'] ?? 0);
            if ($vid <= 0) {
                continue;
            }

            $vistoria = $vistRepo->getById($vid) ?? $vRow;
            $respostas = $vistRepo->getRespostas($vid);
            $anexos = $anexoRepo->getByEntity(self::ENTITY_VISTORIA, $vid);
            $ncs = $ncRepo->getByVistoriaId($vid);

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

            $detalhe[] = [
                'vistoria' => $vistoria,
                'respostas' => $respostas,
                'anexos' => $anexos,
                'naoConformidades' => $ncs,
                'acoesPorNcId' => $acoesPorNc,
                'evidenciasPorAcaoId' => $evidenciasPorAcao,
            ];
        }

        return $detalhe;
    }

    /**
     * @return list<string>
     */
    private function splitHtmlChunks(string $html): array
    {
        $max = 200000;
        if (strlen($html) <= $max) {
            return [$html];
        }

        $chunks = [];
        $offset = 0;
        $len = strlen($html);
        while ($offset < $len) {
            $remaining = $len - $offset;
            if ($remaining <= $max) {
                $chunks[] = substr($html, $offset);
                break;
            }
            $slice = substr($html, $offset, $max);
            $cut = strrpos($slice, '>');
            if ($cut === false || $cut < (int) ($max * 0.5)) {
                $cut = $max - 1;
            }
            $chunks[] = substr($html, $offset, $cut + 1);
            $offset += $cut + 1;
        }

        return $chunks !== [] ? $chunks : [$html];
    }
}
