<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Models\Repository\SstPppRepository;
use Mpdf\Mpdf;

class SstExportPppPdf
{
    public function index(string|int|null $id = null): void
    {
        try {
            if (ob_get_length()) {
                ob_end_clean();
            }
            @set_time_limit(60);
            @ini_set('memory_limit', '512M');

            $pppId = (int) $id;
            if ($pppId <= 0) {
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ppp');
                exit;
            }

            $item = (new SstPppRepository())->getById($pppId);
            if (!$item) {
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ppp');
                exit;
            }

            $payload = json_decode($item['payload_json'] ?? '{}', true) ?: [];
            $trab = $payload['trabalhador'] ?? [];
            $nome = htmlspecialchars((string) ($trab['nome'] ?? $item['colaborador_nome'] ?? 'Colaborador'));
            $versao = (int) ($item['versao'] ?? 1);

            $html = $this->buildHtml($item, $payload);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 14,
                'margin_bottom' => 14,
            ]);
            $mpdf->SetTitle("PPP rascunho (nao oficial) - {$nome} v{$versao}");
            $mpdf->SetWatermarkText('NÃO OFICIAL');
            $mpdf->showWatermarkText = true;
            $mpdf->watermarkTextAlpha = 0.08;
            $mpdf->WriteHTML($html);
            $filename = 'PPP_' . preg_replace('/\W+/', '_', $nome) . '_v' . $versao . '.pdf';
            $mpdf->Output($filename, 'I');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Erro ao gerar PDF: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-ppp/' . (int) ($id ?? 0));
            exit;
        }
    }

    /** @param array<string, mixed> $item @param array<string, mixed> $payload */
    private function buildHtml(array $item, array $payload): string
    {
        $trab = $payload['trabalhador'] ?? [];
        $esc = static fn (?string $v): string => htmlspecialchars((string) ($v ?? '-'));

        $rowsRiscos = '';
        foreach ($payload['registros_ambientais'] ?? [] as $ra) {
            $rowsRiscos .= '<tr><td>' . $esc($ra['periodo'] ?? null) . '</td><td>' . $esc($ra['agente_nocivo'] ?? null) . '</td><td>' . $esc($ra['tipo'] ?? null) . '</td><td>' . $esc($ra['intensidade_concentracao'] ?? null) . '</td></tr>';
        }
        if ($rowsRiscos === '') {
            $rowsRiscos = '<tr><td colspan="4">Nenhum risco vinculado</td></tr>';
        }

        $rowsAso = '';
        foreach ($payload['monitoracao_biologica'] ?? [] as $m) {
            $data = !empty($m['data']) ? date('d/m/Y', strtotime($m['data'])) : '-';
            $rowsAso .= '<tr><td>' . $data . '</td><td>' . $esc($m['tipo_exame'] ?? null) . '</td><td>' . $esc($m['exame'] ?? null) . '</td><td>' . $esc($m['resultado'] ?? null) . '</td></tr>';
        }
        if ($rowsAso === '') {
            $rowsAso = '<tr><td colspan="4">Sem registros</td></tr>';
        }

        $rowsEpi = '';
        foreach ($payload['entregas_epi'] ?? [] as $e) {
            $data = !empty($e['data']) ? date('d/m/Y', strtotime($e['data'])) : '-';
            $rowsEpi .= '<tr><td>' . $data . '</td><td>' . $esc($e['epi'] ?? null) . '</td><td>' . (int) ($e['quantidade'] ?? 0) . '</td><td>' . (!empty($e['termo_assinado']) ? 'Sim' : 'Não') . '</td></tr>';
        }
        if ($rowsEpi === '') {
            $rowsEpi = '<tr><td colspan="4">Sem registros</td></tr>';
        }

        $gerado = !empty($item['created_at']) ? date('d/m/Y H:i', strtotime($item['created_at'])) : date('d/m/Y H:i');
        $versao = (int) ($item['versao'] ?? 1);
        $nomeTrab = $esc($trab['nome'] ?? null);
        $cpf = $esc($trab['cpf'] ?? null);
        $cargo = $esc($trab['cargo_atual'] ?? null);
        $depto = $esc($trab['departamento_atual'] ?? null);
        $adm = $esc($trab['data_admissao'] ?? null);
        $nasc = $esc($trab['data_nascimento'] ?? null);
        $legal = $esc($payload['observacao_legal'] ?? \App\adms\Models\Services\SstEsocialPolicy::observacaoPpp());

        return <<<HTML
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:9pt;color:#222}
h1{font-size:14pt;margin:0 0 8px}
h2{font-size:11pt;margin:14px 0 6px;border-bottom:1px solid #ccc}
table{width:100%;border-collapse:collapse;margin-bottom:8px}
th,td{border:1px solid #bbb;padding:4px 6px;text-align:left}
th{background:#f0f0f0}
.small{font-size:8pt;color:#555}
</style>
<h1>PPP — rascunho interno (não oficial)</h1>
<p class="small" style="color:#b45309;font-weight:bold">NÃO UTILIZAR para INSS, eSocial ou perícia. Versão {$versao} · Gerado em {$gerado}</p>
<h2>Identificação do trabalhador</h2>
<table>
<tr><th>Nome</th><td>{$nomeTrab}</td><th>CPF</th><td>{$cpf}</td></tr>
<tr><th>Cargo</th><td>{$cargo}</td><th>Departamento</th><td>{$depto}</td></tr>
<tr><th>Admissão</th><td>{$adm}</td><th>Nascimento</th><td>{$nasc}</td></tr>
</table>
<h2>Registros ambientais</h2>
<table><thead><tr><th>Período</th><th>Agente nocivo</th><th>Tipo</th><th>Intensidade</th></tr></thead><tbody>{$rowsRiscos}</tbody></table>
<h2>Monitoração biológica</h2>
<table><thead><tr><th>Data</th><th>Tipo</th><th>Exame</th><th>Resultado</th></tr></thead><tbody>{$rowsAso}</tbody></table>
<h2>Entregas de EPI</h2>
<table><thead><tr><th>Data</th><th>EPI</th><th>Qtd</th><th>Termo</th></tr></thead><tbody>{$rowsEpi}</tbody></table>
<p class="small">{$legal}</p>
HTML;
    }
}
