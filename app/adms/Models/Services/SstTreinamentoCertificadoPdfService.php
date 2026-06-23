<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SstTreinamentoAplicacoesRepository;
use App\adms\Models\Repository\SstTreinamentoVinculosRepository;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Models\Repository\UsersRepository;
use Mpdf\Mpdf;

/**
 * Gera certificado PDF de conclusão de treinamento SST.
 */
class SstTreinamentoCertificadoPdfService
{
    public static function absoluteStoragePath(string $relativeFromProjectRoot): string
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);

        return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativeFromProjectRoot, '/\\'));
    }

    public function generateForVinculo(int $vinculoId, ?int $aplicacaoId = null): ?string
    {
        $vinculoRepo = new SstTreinamentoVinculosRepository();
        $vinculo = $vinculoRepo->getById($vinculoId);
        if (!$vinculo || empty($vinculo['data_realizacao'])) {
            return null;
        }

        $aplicacao = null;
        if ($aplicacaoId !== null && $aplicacaoId > 0) {
            $aplicacao = (new SstTreinamentoAplicacoesRepository())->getById($aplicacaoId);
        }
        if ($aplicacao === null) {
            $aplicacoes = (new SstTreinamentoAplicacoesRepository())->getByVinculoId($vinculoId, 1);
            $aplicacao = $aplicacoes[0] ?? null;
        }

        $treinamentoId = (int) ($vinculo['adms_sst_treinamento_id'] ?? 0);
        $treinamento = (new SstTreinamentosRepository())->getById($treinamentoId);
        if (!$treinamento) {
            return null;
        }

        $userId = (int) ($vinculo['adms_user_id'] ?? 0);
        $user = $userId > 0 ? (new UsersRepository())->getUser($userId) : null;

        $html = $this->buildHtml($vinculo, $treinamento, $aplicacao, $user);
        $dirRel = 'storage/sst/treinamento_certificados/' . $userId;
        $dirAbs = self::absoluteStoragePath($dirRel);
        if (!is_dir($dirAbs) && !mkdir($dirAbs, 0755, true) && !is_dir($dirAbs)) {
            throw new \RuntimeException('Não foi possível criar diretório de certificados SST.');
        }

        $fileName = 'certificado_sst_' . $vinculoId . '_' . date('YmdHis') . '.pdf';
        $relPath = $dirRel . '/' . $fileName;
        $absPath = self::absoluteStoragePath($relPath);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 18,
            'margin_right' => 18,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);
        $mpdf->SetTitle('Certificado SST - ' . (string) ($treinamento['nome'] ?? ''));
        $mpdf->WriteHTML($html);
        $mpdf->Output($absPath, 'F');

        $vinculoRepo->update($vinculoId, array_merge($vinculo, ['certificado' => $relPath]));
        if ($aplicacao !== null && !empty($aplicacao['id'])) {
            (new SstTreinamentoAplicacoesRepository())->updateCertificado((int) $aplicacao['id'], $relPath);
        }

        return $relPath;
    }

    /**
     * @param array<string, mixed> $vinculo
     * @param array<string, mixed> $treinamento
     * @param array<string, mixed>|null $aplicacao
     * @param array<string, mixed>|null $user
     */
    private function buildHtml(array $vinculo, array $treinamento, ?array $aplicacao, ?array $user): string
    {
        $esc = static fn (?string $v): string => htmlspecialchars((string) ($v ?? '-'));
        $nome = $esc($user['name'] ?? $vinculo['colaborador_nome'] ?? null);
        $cpf = $esc($user['cpf'] ?? null);
        $cargo = $esc($user['name_pos'] ?? $vinculo['cargo_nome'] ?? null);
        $depto = $esc($user['name_dep'] ?? $vinculo['departamento_nome'] ?? null);
        $treinamentoNome = $esc($treinamento['nome'] ?? null);
        $nr = $esc($treinamento['nr_referencia'] ?? null);
        $codigo = $esc($treinamento['codigo'] ?? null);
        $modalidade = $esc($aplicacao['modalidade_aplicada'] ?? $treinamento['modalidade'] ?? null);
        $instrutor = $esc($aplicacao['instrutor_nome'] ?? null);
        $registro = $esc($aplicacao['instrutor_registro'] ?? null);
        $nota = $aplicacao['nota'] ?? $vinculo['nota'] ?? null;
        $notaStr = $nota !== null && $nota !== '' ? $esc((string) $nota) : '-';
        $dataReal = !empty($vinculo['data_realizacao'])
            ? date('d/m/Y', strtotime((string) $vinculo['data_realizacao']))
            : '-';
        $dataVal = !empty($vinculo['data_validade'])
            ? date('d/m/Y', strtotime((string) $vinculo['data_validade']))
            : 'Conforme programação interna';
        $cargaMin = (int) ($treinamento['carga_horaria_minutos'] ?? 0);
        $cargaStr = $cargaMin > 0 ? sprintf('%d h %02d min', intdiv($cargaMin, 60), $cargaMin % 60) : '-';
        $vinculoId = (int) ($vinculo['id'] ?? 0);
        $gerado = date('d/m/Y H:i');

        return <<<HTML
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:11pt;color:#1a1a1a}
.wrap{border:3px double #2c5282;padding:28px 32px;min-height:420px}
h1{font-size:22pt;text-align:center;margin:0 0 6px;color:#2c5282;letter-spacing:1px}
.subtitle{text-align:center;font-size:12pt;margin-bottom:24px;color:#555}
.label{font-size:9pt;color:#666;text-transform:uppercase;letter-spacing:.5px}
.value{font-size:11pt;margin-bottom:12px}
.grid td{padding:6px 10px;vertical-align:top;width:50%}
.footer{margin-top:28px;font-size:8pt;color:#666;text-align:center;border-top:1px solid #ccc;padding-top:10px}
</style>
<div class="wrap">
<h1>CERTIFICADO DE TREINAMENTO</h1>
<p class="subtitle">Saúde e Segurança do Trabalho · Registro #{$vinculoId}</p>
<p style="text-align:center;font-size:12pt;line-height:1.6;margin:20px 0">
Certificamos que <strong>{$nome}</strong>, CPF {$cpf}, ocupante do cargo <strong>{$cargo}</strong>
(setor {$depto}), concluiu o treinamento de SST:
</p>
<p style="text-align:center;font-size:14pt;font-weight:bold;color:#2c5282;margin:16px 0">{$treinamentoNome}</p>
<table class="grid" width="100%">
<tr>
<td><div class="label">Código interno</div><div class="value">{$codigo}</div></td>
<td><div class="label">NR / referência</div><div class="value">{$nr}</div></td>
</tr>
<tr>
<td><div class="label">Data de realização</div><div class="value">{$dataReal}</div></td>
<td><div class="label">Validade / reciclagem até</div><div class="value">{$dataVal}</div></td>
</tr>
<tr>
<td><div class="label">Carga horária mínima</div><div class="value">{$cargaStr}</div></td>
<td><div class="label">Modalidade</div><div class="value">{$modalidade}</div></td>
</tr>
<tr>
<td><div class="label">Instrutor</div><div class="value">{$instrutor}</div></td>
<td><div class="label">Registro instrutor</div><div class="value">{$registro}</div></td>
</tr>
<tr>
<td colspan="2"><div class="label">Nota / aproveitamento</div><div class="value">{$notaStr}</div></td>
</tr>
</table>
<div class="footer">Documento gerado em {$gerado} · Sistema Administrativo · SST</div>
</div>
HTML;
    }
}
