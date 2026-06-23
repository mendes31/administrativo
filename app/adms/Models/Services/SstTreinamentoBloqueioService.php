<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\UserAccessHelper;

/**
 * Bloqueio operacional: impede entrega de EPI quando há treinamento SST crítico.
 */
class SstTreinamentoBloqueioService
{
    /** @var list<string> */
    private const SITUACOES_PENDENTE = ['sem_vinculo_treinamento', 'treinamento_pendente'];

    public static function isAtivo(): bool
    {
        return NotificationSettingsService::isAnyEnabled(
            'sst_bloquear_epi_treinamento_vencido',
            'sst_bloquear_epi_treinamento_pendente'
        );
    }

    public static function bloqueiaVencido(): bool
    {
        return NotificationSettingsService::isEnabled('sst_bloquear_epi_treinamento_vencido');
    }

    public static function bloqueiaPendente(): bool
    {
        return NotificationSettingsService::isEnabled('sst_bloquear_epi_treinamento_pendente');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getImpedimentosEntregaEpi(int $userId): array
    {
        if ($userId <= 0 || !self::isAtivo()) {
            return [];
        }

        $pendencias = (new SstPendenciasService())->getPendenciasTreinamentoPorUsuario($userId, true);
        $out = [];
        foreach ($pendencias as $row) {
            if ($this->situacaoBloqueia((string) ($row['situacao'] ?? ''))) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * @return array{permitido: bool, mensagem: string|null, impedimentos: list<array<string, mixed>>}
     */
    public function avaliarEntregaEpi(int $userId, bool $bypassConfirmado = false): array
    {
        $impedimentos = $this->getImpedimentosEntregaEpi($userId);
        if ($impedimentos === []) {
            return ['permitido' => true, 'mensagem' => null, 'impedimentos' => []];
        }

        if ($bypassConfirmado && $this->podeIgnorarBloqueio()) {
            return ['permitido' => true, 'mensagem' => null, 'impedimentos' => $impedimentos];
        }

        return [
            'permitido' => false,
            'mensagem' => $this->montarMensagem($impedimentos),
            'impedimentos' => $impedimentos,
        ];
    }

    public function podeIgnorarBloqueio(): bool
    {
        return UserAccessHelper::hasFullSystemAccess();
    }

    private function situacaoBloqueia(string $situacao): bool
    {
        if ($situacao === 'treinamento_vencido' && self::bloqueiaVencido()) {
            return true;
        }

        return in_array($situacao, self::SITUACOES_PENDENTE, true) && self::bloqueiaPendente();
    }

    /** @param list<array<string, mixed>> $impedimentos */
    private function montarMensagem(array $impedimentos): string
    {
        $nomes = [];
        foreach ($impedimentos as $row) {
            $nome = trim((string) ($row['treinamento_nome'] ?? ''));
            $label = trim((string) ($row['situacao_label'] ?? ''));
            $nomes[] = $nome !== '' ? $nome . ($label !== '' ? ' (' . $label . ')' : '') : $label;
        }
        $lista = implode('; ', array_filter($nomes));

        return 'Entrega de EPI bloqueada por treinamento SST: ' . $lista
            . '. Regularize o treinamento ou solicite liberação de um administrador.';
    }
}
