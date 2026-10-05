<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InstitutionalSystemUserHelper;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Monta o relatório de visualização/ciência de uma política interna.
 *
 * Indicadores usam somente colaboradores ativos. Inativos sem visualização
 * nem ciência saem da listagem e dos totais; inativos com evidência ficam
 * em lista separada (histórico), fora do percentual.
 */
final class PoliticaRelatorioService
{
    /**
     * @param array<string, mixed> $policy
     * @return array{
     *   requires_ack: bool,
     *   ativos: list<array<string, mixed>>,
     *   inativos_historico: list<array<string, mixed>>,
     *   excluidos_sem_historico: int,
     *   kpis: array{
     *     total: int,
     *     visualizaram: int,
     *     pendentes: int,
     *     cientes: int,
     *     pct_visualizacao: float,
     *     pct_pendentes: float,
     *     pct_pendentes_ciencia: float|null,
     *     pct_ciencia: float|null
     *   }
     * }
     */
    public function build(int $policyId, array $policy, string $usuarioFilter = ''): array
    {
        $requiresAck = InformativoRelatorioService::requiresAck($policy['requires_ack'] ?? null);

        $usersRepo = new UsersRepository();
        $usuarios = InstitutionalSystemUserHelper::filterReportUsers($usersRepo->getAllUsers(1, 20000, []));

        $filterLower = mb_strtolower(trim($usuarioFilter));
        if ($filterLower !== '') {
            $usuarios = array_values(array_filter($usuarios, static function (array $u) use ($filterLower): bool {
                $haystack = mb_strtolower((string) ($u['name'] ?? '') . ' ' . (string) ($u['email'] ?? ''));

                return mb_strpos($haystack, $filterLower) !== false;
            }));
        }

        $readsMap = (new PoliciesRepository())->getReadsMapForPolicy($policyId);

        $ativos = [];
        $inativosHistorico = [];
        $excluidos = 0;

        foreach ($usuarios as $usuario) {
            $userId = (int) ($usuario['id'] ?? $usuario['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $read = $readsMap[$userId] ?? null;
            $row = $this->mapRow($usuario, $read, $requiresAck);
            $temHistorico = ($row['visualizou'] === 'SIM') || ($row['esta_ciente'] === 'SIM');

            if ($this->isAtivo($usuario)) {
                $ativos[] = $row;
                continue;
            }

            if ($temHistorico) {
                $inativosHistorico[] = $row;
                continue;
            }

            $excluidos++;
        }

        return [
            'requires_ack' => $requiresAck,
            'ativos' => $ativos,
            'inativos_historico' => $inativosHistorico,
            'excluidos_sem_historico' => $excluidos,
            'kpis' => InformativoRelatorioService::kpisFromAtivos($ativos, $requiresAck),
        ];
    }

    /**
     * @param array<string, mixed> $usuario
     */
    private function isAtivo(array $usuario): bool
    {
        $status = trim((string) ($usuario['status'] ?? ''));

        return $status === '' || strcasecmp($status, 'Ativo') === 0;
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed>|null $read
     * @return array<string, mixed>
     */
    private function mapRow(array $usuario, ?array $read, bool $requiresAck): array
    {
        return [
            'usuario_id' => (int) ($usuario['id'] ?? $usuario['user_id'] ?? 0),
            'usuario_nome' => (string) ($usuario['name'] ?? $usuario['user_name'] ?? ''),
            'usuario_email' => (string) ($usuario['email'] ?? ''),
            'usuario_status' => (string) ($usuario['status'] ?? ''),
            'visualizou' => $read ? 'SIM' : 'NÃO',
            'data_visualizacao' => $read && !empty($read['read_at'])
                ? date('d/m/Y H:i:s', strtotime((string) $read['read_at']))
                : '-',
            'esta_ciente' => $requiresAck
                ? (($read && !empty($read['acknowledged'])) ? 'SIM' : 'NÃO')
                : 'N/A',
            'data_ciencia' => $requiresAck && $read && !empty($read['ack_at'])
                ? date('d/m/Y H:i:s', strtotime((string) $read['ack_at']))
                : '-',
            'status' => $this->getStatus($read, $requiresAck),
        ];
    }

    /**
     * @param array<string, mixed>|null $visualizacao
     */
    private function getStatus(?array $visualizacao, bool $requiresAck): string
    {
        if (!$visualizacao) {
            return 'PENDENTE';
        }

        if ($requiresAck) {
            if (empty($visualizacao['acknowledged'])) {
                return 'VISUALIZOU MAS NÃO CIENTE';
            }

            return 'CIENTE';
        }

        return 'VISUALIZOU';
    }
}
