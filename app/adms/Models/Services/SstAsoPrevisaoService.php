<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstAsoPrevisaoHelper;
use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Models\Repository\SstAsosRepository;

/** Relação de ASOs periódicos previstos, para planejamento com as lideranças. */
final class SstAsoPrevisaoService
{
    /**
     * @param string $ym Mês Y-m, ou vazio para a lista completa
     * @param array{search?: string, adms_user_id?: int|string, adms_department_id?: int|string} $filters
     * @return array{
     *   mes: string,
     *   mes_label: string,
     *   itens: list<array<string, mixed>>,
     *   por_departamento: array<string, list<array<string, mixed>>>,
     *   totais_departamento: array<string, int>
     * }
     */
    public function listarPorMes(string $ym, array $filters = []): array
    {
        $ym = SstAsoPrevisaoHelper::mesFiltro($ym);
        $hoje = new \DateTimeImmutable('today');
        $asoRepo = new SstAsosRepository();
        $rows = $asoRepo->listUltimosPeriodicosAtivos($filters);
        $aguardandoMap = $asoRepo->mapAguardandoPorUsuario(SstCategoriaAsoHelper::PERIODICO);
        $itens = [];
        foreach ($rows as $row) {
            $previsto = SstAsoPrevisaoHelper::previsaoEm(
                isset($row['data_validade']) ? (string) $row['data_validade'] : null,
                isset($row['data_realizacao']) ? (string) $row['data_realizacao'] : null,
                12
            );
            if ($previsto === null) {
                continue;
            }
            if ($ym !== '' && $previsto->format('Y-m') !== $ym) {
                continue;
            }
            $userId = (int) ($row['adms_user_id'] ?? 0);
            $aguardandoId = $aguardandoMap[$userId] ?? 0;
            $situacao = SstAsoPrevisaoHelper::situacao($previsto, $hoje);
            if ($aguardandoId > 0) {
                $situacao = 'na_fila';
            }
            $itens[] = [
                'adms_user_id' => $userId,
                'colaborador_nome' => (string) ($row['colaborador_nome'] ?? ''),
                'departamento_nome' => (string) ($row['departamento_nome'] ?? ''),
                'cargo_nome' => (string) ($row['cargo_nome'] ?? ''),
                'tipo' => SstCategoriaAsoHelper::PERIODICO,
                'data_realizacao' => $row['data_realizacao'] ?? null,
                'data_validade' => $row['data_validade'] ?? null,
                'previsto_em' => $previsto->format('Y-m-d'),
                'ultimo_aso_id' => (int) ($row['ultimo_aso_id'] ?? 0),
                'aso_aguardando_id' => $aguardandoId,
                'situacao' => $situacao,
                'situacao_label' => $situacao === 'na_fila'
                    ? 'Já na fila'
                    : SstAsoPrevisaoHelper::situacaoLabel($situacao),
            ];
        }
        usort($itens, static function (array $a, array $b): int {
            $cmp = strcmp((string) $a['previsto_em'], (string) $b['previsto_em']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string) $a['colaborador_nome'], (string) $b['colaborador_nome']);
        });

        $porDepartamento = [];
        $totais = [];
        foreach ($itens as $item) {
            $dep = $item['departamento_nome'] !== '' ? $item['departamento_nome'] : 'Sem departamento';
            $porDepartamento[$dep][] = $item;
            $totais[$dep] = ($totais[$dep] ?? 0) + 1;
        }

        return [
            'mes' => $ym,
            'mes_label' => $ym === '' ? 'todos os meses' : SstAsoPrevisaoHelper::labelMes($ym),
            'itens' => $itens,
            'por_departamento' => $porDepartamento,
            'totais_departamento' => $totais,
        ];
    }
}
