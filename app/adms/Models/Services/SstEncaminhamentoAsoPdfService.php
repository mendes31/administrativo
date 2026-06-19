<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Helpers\UserFormHelper;

/**
 * Gera HTML do encaminhamento / autorização de ASO (modelo papel).
 */
final class SstEncaminhamentoAsoPdfService
{
    /** @param list<array<string, mixed>> $examesObrigatorios @param list<array<string, mixed>> $examesRecomendados */
    public function buildHtml(
        array $user,
        string $categoriaAso,
        string $dataEncaminhamento,
        array $examesObrigatorios,
        array $examesRecomendados,
        array $recomendadosSelecionadosIds = []
    ): string {
        $nome = htmlspecialchars((string) ($user['name'] ?? ''));
        $funcao = htmlspecialchars((string) ($user['pos_name'] ?? ''));
        $setor = htmlspecialchars((string) ($user['dep_name'] ?? ''));
        $empresaSlug = (string) ($user['empresa_contratante'] ?? '');
        $dataFmt = $this->formatDate($dataEncaminhamento);

        $empresas = [
            'tiaraju_farma' => 'Tiaraju Farma, Alimentos e Cosméticos Ltda',
            'lab_tiaraju_matriz' => 'Lab. Tiaraju Alimentos e Cosméticos Ltda',
            'lab_tiaraju_filial' => 'Lab. Tiaraju Alimentos e Cosméticos Ltda - filial',
        ];

        $categoriasCurto = [
            SstCategoriaAsoHelper::ADMISSIONAL => 'Admissional',
            SstCategoriaAsoHelper::DEMISSIONAL => 'Demissional',
            SstCategoriaAsoHelper::PERIODICO => 'Periódico',
            SstCategoriaAsoHelper::RETORNO_TRABALHO => 'Ret. Trabalho',
            SstCategoriaAsoHelper::MUDANCA_FUNCAO => 'Troc. Função',
        ];

        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 11pt; color: #000; }
            .titulo { text-align: center; font-weight: bold; font-size: 13pt; margin-bottom: 8px; }
            .linha { border-bottom: 1px solid #000; display: inline-block; min-width: 120px; }
            .chk { font-family: DejaVu Sans, sans-serif; font-size: 12pt; }
            table.exames { width: 100%; border-collapse: collapse; margin-top: 8px; }
            table.exames td { padding: 4px 6px; vertical-align: top; }
            .assinatura { margin-top: 40px; border-top: 1px solid #000; width: 280px; text-align: center; padding-top: 4px; font-size: 9pt; }
            .obs { margin-top: 24px; font-size: 9pt; }
        </style>';

        $html .= '<div class="titulo">EMPRESA: LABORATÓRIO TIARAJU</div>';
        $html .= '<p style="text-align:right;margin:0 0 12px;">Data: <strong>' . htmlspecialchars($dataFmt) . '</strong></p>';

        foreach ($empresas as $slug => $label) {
            $mark = ($empresaSlug === $slug) ? '☑' : '☐';
            $html .= '<p style="margin:2px 0;"><span class="chk">' . $mark . '</span> ' . htmlspecialchars($label) . '</p>';
        }

        $html .= '<p style="margin:16px 0 8px;">Autorizamos o Portador a realizar o ASO:</p>';
        foreach (SstCategoriaAsoHelper::all() as $cat) {
            $mark = ($cat === $categoriaAso) ? '☑' : '☐';
            $lbl = $categoriasCurto[$cat] ?? $cat;
            $html .= '<span style="display:inline-block;margin-right:18px;"><span class="chk">' . $mark . '</span> ' . htmlspecialchars($lbl) . '</span>';
        }

        $html .= '<p style="margin:18px 0 6px;font-weight:bold;">Exames complementares:</p>';
        $html .= '<table class="exames">';

        $recomendadosSet = array_flip(array_map('intval', $recomendadosSelecionadosIds));
        $linhas = [];

        foreach ($examesObrigatorios as $ex) {
            $linhas[] = [
                'nome' => (string) ($ex['exame_nome'] ?? ''),
                'checked' => true,
                'tipo' => 'obrigatorio',
            ];
        }
        foreach ($examesRecomendados as $ex) {
            $id = (int) ($ex['adms_sst_exame_id'] ?? 0);
            $linhas[] = [
                'nome' => (string) ($ex['exame_nome'] ?? ''),
                'checked' => isset($recomendadosSet[$id]),
                'tipo' => 'recomendado',
            ];
        }

        if ($linhas === []) {
            $html .= '<tr><td><em>Nenhum exame complementar vinculado à matriz para esta categoria.</em></td></tr>';
        } else {
            $col = 0;
            foreach ($linhas as $linha) {
                if ($col % 2 === 0) {
                    $html .= '<tr>';
                }
                $mark = !empty($linha['checked']) ? '☑' : '☐';
                $sufixo = ($linha['tipo'] === 'recomendado') ? ' <small>(recomendado)</small>' : '';
                $html .= '<td width="50%"><span class="chk">' . $mark . '</span> ' . htmlspecialchars($linha['nome']) . $sufixo . '</td>';
                if ($col % 2 === 1) {
                    $html .= '</tr>';
                }
                $col++;
            }
            if ($col % 2 === 1) {
                $html .= '<td></td></tr>';
            }
        }
        $html .= '</table>';

        $html .= '<p style="margin-top:20px;"><strong>NOME COMPLETO:</strong> <span class="linha" style="min-width:70%;">' . $nome . '</span></p>';
        $html .= '<p><strong>FUNÇÃO:</strong> <span class="linha" style="min-width:35%;">' . $funcao . '</span>';
        $html .= ' &nbsp; <strong>SETOR:</strong> <span class="linha" style="min-width:35%;">' . $setor . '</span></p>';

        $html .= '<div class="assinatura">responsável pelo encaminhamento</div>';
        $html .= '<p class="obs"><strong>OBS:</strong> Trazer SEMPRE a Cart. Identidade.</p>';

        if ($empresaSlug === '' || !UserFormHelper::normalizeEmpresaContratante($empresaSlug)) {
            $html .= '<p class="obs" style="color:#666;">Empresa contratante não informada no cadastro do colaborador.</p>';
        }

        return $html;
    }

    private function formatDate(string $isoDate): string
    {
        if ($isoDate === '') {
            return date('d/m/Y');
        }
        $ts = strtotime($isoDate);

        return $ts ? date('d/m/Y', $ts) : $isoDate;
    }
}
