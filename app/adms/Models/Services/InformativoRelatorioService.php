<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InstitutionalSystemUserHelper;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Monta o relatório de visualização/ciência de um informativo.
 *
 * Indicadores usam somente colaboradores ativos. Inativos sem visualização
 * nem ciência saem da listagem e dos totais; inativos com evidência ficam
 * em lista separada (histórico), fora do percentual.
 */
final class InformativoRelatorioService
{
    public static function requiresAck(mixed $val): bool
    {
        return $val === 1
            || $val === '1'
            || $val === true
            || $val === 'true'
            || $val === 'Sim'
            || $val === 'sim';
    }

    /**
     * @param array<string, mixed> $informativo
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
     *     pct_ciencia: float|null
     *   }
     * }
     */
    public function build(int $informativoId, array $informativo, string $usuarioFilter = ''): array
    {
        $requiresAck = self::requiresAck($informativo['requires_ack'] ?? null);

        $usersRepo = new UsersRepository();
        $usuarios = InstitutionalSystemUserHelper::filterReportUsers($usersRepo->getAllUsers(1, 20000, []));

        $filterLower = mb_strtolower(trim($usuarioFilter));
        if ($filterLower !== '') {
            $usuarios = array_values(array_filter($usuarios, static function (array $u) use ($filterLower): bool {
                $haystack = mb_strtolower((string) ($u['name'] ?? '') . ' ' . (string) ($u['email'] ?? ''));

                return mb_strpos($haystack, $filterLower) !== false;
            }));
        }

        $readsMap = (new InformativosRepository())->getReadsMapForInformativo($informativoId);

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
            'kpis' => self::kpisFromAtivos($ativos, $requiresAck),
        ];
    }

    /**
     * @param list<array<string, mixed>> $ativos
     * @return array{
     *   total: int,
     *   visualizaram: int,
     *   pendentes: int,
     *   cientes: int,
     *   pct_visualizacao: float,
     *   pct_ciencia: float|null
     * }
     */
    public static function kpisFromAtivos(array $ativos, bool $requiresAck): array
    {
        $total = count($ativos);
        $visualizaram = 0;
        $pendentes = 0;
        $cientes = 0;

        foreach ($ativos as $dado) {
            if (($dado['visualizou'] ?? '') === 'SIM') {
                $visualizaram++;
            }
            if (($dado['status'] ?? '') === 'PENDENTE') {
                $pendentes++;
            }
            if (($dado['status'] ?? '') === 'CIENTE') {
                $cientes++;
            }
        }

        return [
            'total' => $total,
            'visualizaram' => $visualizaram,
            'pendentes' => $pendentes,
            'cientes' => $cientes,
            'pct_visualizacao' => $total > 0 ? round(($visualizaram / $total) * 100, 1) : 0.0,
            'pct_ciencia' => $requiresAck ? ($total > 0 ? round(($cientes / $total) * 100, 1) : 0.0) : null,
        ];
    }

    public static function formatPct(?float $pct): string
    {
        if ($pct === null) {
            return '';
        }

        return number_format($pct, 1, ',', '.') . '%';
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
