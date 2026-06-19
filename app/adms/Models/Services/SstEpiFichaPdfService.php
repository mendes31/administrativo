<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SstEpiFichasRepository;
use App\adms\Models\Repository\SstEpisRepository;
use Mpdf\Mpdf;

/**
 * Gera e persiste PDF da ficha de entrega de EPI.
 */
class SstEpiFichaPdfService
{
    public function generateAndStore(int $fichaId): ?string
    {
        $repo = new SstEpiFichasRepository();
        $ficha = $repo->getById($fichaId);
        if (!$ficha) {
            return null;
        }
        $itens = $repo->getItens($fichaId);
        if ($itens === []) {
            return null;
        }

        $html = $this->buildHtml($ficha, $itens);
        $userId = (int) ($ficha['adms_user_id'] ?? 0);
        $dirRel = 'storage/sst/epi_fichas/' . $userId;
        $dirAbs = $repo->absoluteStoragePath($dirRel);
        if (!is_dir($dirAbs) && !mkdir($dirAbs, 0755, true) && !is_dir($dirAbs)) {
            throw new \RuntimeException('Não foi possível criar diretório de armazenamento.');
        }

        $fileName = 'ficha_epi_' . $fichaId . '.pdf';
        $relPath = $dirRel . '/' . $fileName;
        $absPath = $repo->absoluteStoragePath($relPath);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 14,
        ]);
        $nome = (string) ($ficha['colaborador_nome'] ?? 'Colaborador');
        $mpdf->SetTitle('Ficha de Entrega de EPI - ' . $nome);
        $mpdf->WriteHTML($html);
        $mpdf->Output($absPath, 'F');

        $hash = hash_file('sha256', $absPath) ?: '';
        $repo->updatePdfMeta($fichaId, $relPath, $hash);

        return $relPath;
    }

    /** @param array<string, mixed> $ficha @param list<array<string, mixed>> $itens */
    public function buildHtml(array $ficha, array $itens): string
    {
        $esc = static fn (?string $v): string => htmlspecialchars((string) ($v ?? '-'));
        $dataEntrega = !empty($ficha['data_entrega']) ? date('d/m/Y', strtotime((string) $ficha['data_entrega'])) : '-';
        $nome = $esc($ficha['colaborador_nome'] ?? null);
        $cpf = $esc($ficha['colaborador_cpf'] ?? null);
        $cargo = $esc($ficha['cargo_nome'] ?? null);
        $depto = $esc($ficha['departamento_nome'] ?? null);
        $resp = $esc($ficha['entregue_por_nome'] ?? null);
        $fichaId = (int) ($ficha['id'] ?? 0);
        $gerado = date('d/m/Y H:i');

        $rows = '';
        foreach ($itens as $item) {
            $ca = $esc($item['ca_utilizado'] ?? $item['epi_ca_catalogo'] ?? null);
            $prev = !empty($item['data_prevista_troca'])
                ? date('d/m/Y', strtotime((string) $item['data_prevista_troca']))
                : '-';
            $rows .= '<tr>'
                . '<td>' . $esc($item['epi_nome'] ?? null) . '</td>'
                . '<td>' . $ca . '</td>'
                . '<td style="text-align:center">' . (int) ($item['quantidade'] ?? 1) . '</td>'
                . '<td>' . $prev . '</td>'
                . '</tr>';
        }

        $obs = trim((string) ($ficha['observacoes'] ?? ''));
        $obsBlock = $obs !== '' ? '<p><strong>Observações:</strong> ' . $esc($obs) . '</p>' : '';

        return <<<HTML
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#222}
h1{font-size:14pt;margin:0 0 8px;text-align:center}
h2{font-size:11pt;margin:14px 0 6px;border-bottom:1px solid #ccc}
table{width:100%;border-collapse:collapse;margin-bottom:10px}
th,td{border:1px solid #bbb;padding:5px 6px;text-align:left}
th{background:#f0f0f0}
.small{font-size:8pt;color:#555}
.decl{margin-top:24px;font-size:9pt;line-height:1.5}
.sig{margin-top:40px}
.sig td{border:none;padding-top:30px;text-align:center;width:50%}
</style>
<h1>FICHA DE ENTREGA DE EPI</h1>
<p class="small" style="text-align:center">Registro #{$fichaId} · Data da entrega: {$dataEntrega} · Documento gerado em {$gerado}</p>
<h2>Identificação do colaborador</h2>
<table>
<tr><th>Nome</th><td>{$nome}</td><th>CPF</th><td>{$cpf}</td></tr>
<tr><th>Cargo</th><td>{$cargo}</td><th>Departamento</th><td>{$depto}</td></tr>
<tr><th>Responsável pela entrega</th><td colspan="3">{$resp}</td></tr>
</table>
<h2>EPIs entregues</h2>
<table>
<thead><tr><th>EPI</th><th>Nº CA</th><th>Qtde</th><th>Prev. substituição</th></tr></thead>
<tbody>{$rows}</tbody>
</table>
{$obsBlock}
<div class="decl">
<p>Declaro ter recebido os Equipamentos de Proteção Individual relacionados acima, em perfeitas condições de uso,
e ter sido orientado quanto à correta utilização, guarda, conservação, substituição e responsabilidades previstas
na NR-06 e demais normas de Segurança e Saúde no Trabalho aplicáveis.</p>
</div>
<table class="sig"><tr>
<td>_________________________________<br>Assinatura do colaborador</td>
<td>_________________________________<br>Responsável pela entrega</td>
</tr></table>
HTML;
    }

    /** Calcula data prevista troca com base na periodicidade do EPI. */
    public static function calcPrevistaTroca(string $dataEntrega, int $epiId): ?string
    {
        $epi = (new SstEpisRepository())->getById($epiId);
        $dias = (int) ($epi['periodicidade_troca_dias'] ?? 0);
        if ($dias <= 0) {
            return null;
        }
        $ts = strtotime($dataEntrega . ' +' . $dias . ' days');
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d', $ts);
    }
}
