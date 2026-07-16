<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\PdfInstitutionalHeaderHelper;
use App\adms\Helpers\UserFormHelper;

/**
 * Relatório de auditoria: vistorias e recargas por período (padrão institucional LNT).
 */
class SstEquipamentoAuditoriaPdfService
{
    /**
     * @param array<string, mixed>|null $equipamento null = consolidado (vários)
     * @param list<array<string, mixed>> $vistorias
     * @param list<array<string, mixed>> $recargas
     */
    public function buildHtml(
        ?array $equipamento,
        array $vistorias,
        array $recargas,
        string $dataInicio,
        string $dataFim,
    ): string {
        $esc = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $deBr = date('d/m/Y', strtotime($dataInicio));
        $ateBr = date('d/m/Y', strtotime($dataFim));
        $periodo = $deBr . ' a ' . $ateBr;

        $isUnitario = is_array($equipamento) && !empty($equipamento['id']);
        $titulo = 'RELATÓRIO DE VISTORIAS E RECARGAS — EQUIPAMENTOS SST';
        $subtitulo = $isUnitario
            ? 'Documento para auditoria · Equipamento ' . (string) ($equipamento['codigo'] ?? '')
            : 'Documento para auditoria · Consolidado por período';

        $empresaSlug = $isUnitario
            ? ($equipamento['empresa_contratante'] ?? null)
            : $this->detectEmpresaUnica($vistorias, $recargas);

        $header = PdfInstitutionalHeaderHelper::buildHeaderTable($titulo, $subtitulo);
        $empresaBlock = PdfInstitutionalHeaderHelper::buildEmpresaInfoTable(
            is_string($empresaSlug) ? $empresaSlug : null,
            'relatório'
        );

        $equipBlock = '';
        if ($isUnitario) {
            $filial = UserFormHelper::empresaContratanteLabel($equipamento['empresa_contratante'] ?? null);
            $equipBlock = '<table width="100%" style="border-collapse:collapse;margin-bottom:10px;font-size:9pt;">'
                . $this->row('Período', $periodo)
                . $this->row('Código', (string) ($equipamento['codigo'] ?? '—'))
                . $this->row('Patrimônio', (string) (($equipamento['patrimonio'] ?? '') !== '' ? $equipamento['patrimonio'] : '—'))
                . $this->row('Nº de série', (string) (($equipamento['numero_serie'] ?? '') !== '' ? $equipamento['numero_serie'] : '—'))
                . $this->row('Grupo', (string) ($equipamento['tipo_nome'] ?? '—'))
                . $this->row('Filial', $filial)
                . $this->row('Localização', (string) (($equipamento['localizacao'] ?? '') !== '' ? $equipamento['localizacao'] : '—'))
                . $this->row('Status do equipamento', (string) ($equipamento['status'] ?? '—'))
                . '</table>';
        } else {
            $equipBlock = '<table width="100%" style="border-collapse:collapse;margin-bottom:10px;font-size:9pt;">'
                . $this->row('Período', $periodo)
                . $this->row('Escopo', 'Todos os equipamentos do filtro (consolidado)')
                . $this->row('Qtde. vistorias', (string) count($vistorias))
                . $this->row('Qtde. recargas', (string) count($recargas))
                . '</table>';
        }

        $vistHtml = $this->buildVistoriasTable($vistorias, $isUnitario, $esc);
        $recHtml = $this->buildRecargasTable($recargas, $isUnitario, $esc);

        $obs = '<p style="font-size:8pt;color:#444;margin-top:14px;">'
            . 'Este documento consolida registros do sistema SST para fins de auditoria interna e externa. '
            . 'Vistorias com resultado <strong>Não conforme</strong> permanecem no histórico; o tratamento ocorre por NC e ação corretiva. '
            . 'Recargas aparecem somente quando o tipo de equipamento controla recarga e há eventos no período.'
            . '</p>'
            . '<p style="font-size:8pt;color:#666;">Gerado em ' . $esc(date('d/m/Y H:i')) . '.</p>';

        return '<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9pt; color: #000; }
h2 { font-size: 11pt; margin: 14px 0 6px; border-bottom: 1px solid #333; padding-bottom: 3px; }
table.grid { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 8px; }
table.grid th, table.grid td { border: 1px solid #000; padding: 4px 5px; }
table.grid th { background: #e8e8e8; }
thead { display: table-header-group; }
.nc { background: #f8d7da; }
</style>
</head><body>
' . $header . '
' . $empresaBlock . '
' . $equipBlock . '
<h2>1. Vistorias no período</h2>
' . $vistHtml . '
<h2>2. Recargas / manutenção no período</h2>
' . $recHtml . '
' . $obs . '
</body></html>';
    }

    /**
     * @param list<array<string, mixed>> $vistorias
     * @param list<array<string, mixed>> $recargas
     */
    private function detectEmpresaUnica(array $vistorias, array $recargas): ?string
    {
        $slugs = [];
        foreach (array_merge($vistorias, $recargas) as $row) {
            $s = trim((string) ($row['empresa_contratante'] ?? ''));
            if ($s !== '') {
                $slugs[$s] = true;
            }
        }
        $keys = array_keys($slugs);

        return count($keys) === 1 ? $keys[0] : null;
    }

    private function row(string $label, string $value): string
    {
        $l = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $v = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return '<tr><td style="border:1px solid #000;padding:5px;width:28%;"><strong>' . $l . '</strong></td>'
            . '<td style="border:1px solid #000;padding:5px;">' . $v . '</td></tr>';
    }

    /** @param list<array<string, mixed>> $vistorias */
    private function buildVistoriasTable(array $vistorias, bool $unitario, callable $esc): string
    {
        if ($vistorias === []) {
            return '<p style="font-size:9pt;color:#666;">Nenhuma vistoria encontrada no período informado.</p>';
        }

        $cols = $unitario
            ? '<th>#</th><th>Competência</th><th>Prevista</th><th>Realizada</th><th>Status</th><th>Resultado</th><th>Executor</th><th>Observação</th>'
            : '<th>#</th><th>Equipamento</th><th>Grupo</th><th>Competência</th><th>Realizada</th><th>Status</th><th>Resultado</th><th>Executor</th>';

        $rows = '';
        $n = 0;
        foreach ($vistorias as $v) {
            $n++;
            $resultado = (string) ($v['resultado'] ?? '—');
            $trClass = $resultado === 'Não conforme' ? ' class="nc"' : '';
            $prevista = !empty($v['data_prevista']) ? date('d/m/Y', strtotime((string) $v['data_prevista'])) : '—';
            $realizada = !empty($v['data_realizada']) ? date('d/m/Y H:i', strtotime((string) $v['data_realizada'])) : '—';
            $obs = trim((string) ($v['observacao'] ?? ''));
            if (mb_strlen($obs) > 80) {
                $obs = mb_substr($obs, 0, 77) . '…';
            }

            if ($unitario) {
                $rows .= '<tr' . $trClass . '>'
                    . '<td style="text-align:center">' . $n . '</td>'
                    . '<td style="text-align:center">' . $esc($v['competencia'] ?? null) . '</td>'
                    . '<td style="text-align:center">' . $esc($prevista) . '</td>'
                    . '<td style="text-align:center">' . $esc($realizada) . '</td>'
                    . '<td>' . $esc($v['status'] ?? null) . '</td>'
                    . '<td><strong>' . $esc($resultado !== '' ? $resultado : '—') . '</strong></td>'
                    . '<td>' . $esc($v['executor_nome'] ?? '—') . '</td>'
                    . '<td>' . $esc($obs !== '' ? $obs : '—') . '</td>'
                    . '</tr>';
            } else {
                $rows .= '<tr' . $trClass . '>'
                    . '<td style="text-align:center">' . $n . '</td>'
                    . '<td>' . $esc($v['equipamento_codigo'] ?? null) . '</td>'
                    . '<td>' . $esc($v['tipo_nome'] ?? null) . '</td>'
                    . '<td style="text-align:center">' . $esc($v['competencia'] ?? null) . '</td>'
                    . '<td style="text-align:center">' . $esc($realizada) . '</td>'
                    . '<td>' . $esc($v['status'] ?? null) . '</td>'
                    . '<td><strong>' . $esc($resultado !== '' ? $resultado : '—') . '</strong></td>'
                    . '<td>' . $esc($v['executor_nome'] ?? '—') . '</td>'
                    . '</tr>';
            }
        }

        return '<table class="grid"><thead><tr>' . $cols . '</tr></thead><tbody>' . $rows . '</tbody></table>';
    }

    /** @param list<array<string, mixed>> $recargas */
    private function buildRecargasTable(array $recargas, bool $unitario, callable $esc): string
    {
        if ($recargas === []) {
            return '<p style="font-size:9pt;color:#666;">Nenhuma recarga/manutenção registrada no período'
                . ($unitario ? ' (ou o tipo não controla recarga).' : '.') . '</p>';
        }

        $cols = $unitario
            ? '<th>#</th><th>Data</th><th>Tipo de evento</th><th>Próx. validade</th><th>Empresa</th><th>Documento</th><th>Registrado por</th>'
            : '<th>#</th><th>Equipamento</th><th>Data</th><th>Tipo</th><th>Próx. validade</th><th>Empresa</th><th>Documento</th>';

        $rows = '';
        $n = 0;
        foreach ($recargas as $r) {
            $n++;
            $data = !empty($r['data_recarga']) ? date('d/m/Y', strtotime((string) $r['data_recarga'])) : '—';
            $prox = !empty($r['data_proxima_recarga']) ? date('d/m/Y', strtotime((string) $r['data_proxima_recarga'])) : '—';
            if ($unitario) {
                $rows .= '<tr>'
                    . '<td style="text-align:center">' . $n . '</td>'
                    . '<td style="text-align:center">' . $esc($data) . '</td>'
                    . '<td>' . $esc($r['tipo_evento'] ?? '—') . '</td>'
                    . '<td style="text-align:center">' . $esc($prox) . '</td>'
                    . '<td>' . $esc(($r['empresa'] ?? '') !== '' ? $r['empresa'] : '—') . '</td>'
                    . '<td>' . $esc(($r['numero_documento'] ?? '') !== '' ? $r['numero_documento'] : '—') . '</td>'
                    . '<td>' . $esc($r['created_by_nome'] ?? '—') . '</td>'
                    . '</tr>';
            } else {
                $rows .= '<tr>'
                    . '<td style="text-align:center">' . $n . '</td>'
                    . '<td>' . $esc($r['equipamento_codigo'] ?? null) . '</td>'
                    . '<td style="text-align:center">' . $esc($data) . '</td>'
                    . '<td>' . $esc($r['tipo_evento'] ?? '—') . '</td>'
                    . '<td style="text-align:center">' . $esc($prox) . '</td>'
                    . '<td>' . $esc(($r['empresa'] ?? '') !== '' ? $r['empresa'] : '—') . '</td>'
                    . '<td>' . $esc(($r['numero_documento'] ?? '') !== '' ? $r['numero_documento'] : '—') . '</td>'
                    . '</tr>';
            }
        }

        return '<table class="grid"><thead><tr>' . $cols . '</tr></thead><tbody>' . $rows . '</tbody></table>';
    }
}
