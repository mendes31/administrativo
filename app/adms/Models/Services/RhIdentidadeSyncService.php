<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhIdentidadeRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;

/**
 * Dual-write identidade (ADR-0006) a partir de adms_users.
 */
final class RhIdentidadeSyncService
{
    /**
     * @return array{pessoa_id: int, vinculo_id: int}
     */
    public function sincronizarDeUsuario(int $userId): array
    {
        if ($userId <= 0) {
            throw new Exception('Usuário inválido para sincronizar identidade.');
        }

        $user = (new UsersRepository())->getUser($userId);
        if (!$user || !is_array($user)) {
            throw new Exception('Usuário não encontrado para sincronizar identidade.');
        }

        $repo = new RhIdentidadeRepository();
        $pessoaId = $repo->upsertPessoaFromUser([
            'adms_user_id' => $userId,
            'cpf' => $user['cpf'] ?? null,
            'nome' => $user['name'] ?? ('Usuário #' . $userId),
            'email' => $user['email'] ?? null,
            'data_nascimento' => $user['data_nascimento'] ?? null,
            'sexo' => $user['sexo'] ?? null,
        ]);
        if (!$pessoaId) {
            throw new Exception('Falha ao sincronizar Pessoa.');
        }

        $encerrado = !empty($user['data_desligamento']);
        $vinculoId = $repo->upsertVinculoAtivo([
            'rh_pessoa_id' => $pessoaId,
            'adms_user_id' => $userId,
            'status' => $encerrado
                ? RhIdentidadeRepository::VINCULO_ENCERRADO
                : RhIdentidadeRepository::VINCULO_ATIVO,
            'data_inicio' => $user['data_admissao'] ?? null,
            'data_fim' => $user['data_desligamento'] ?? null,
        ]);
        if (!$vinculoId) {
            throw new Exception('Falha ao sincronizar Vínculo.');
        }

        if ($encerrado) {
            $repo->encerrarLotacoesDoVinculo($vinculoId, $user['data_desligamento'] ?? null);
        } else {
            if (!$repo->sincronizarLotacaoVigente($vinculoId, [
                'departamento_id' => $user['user_department_id'] ?? null,
                'cargo_id' => $user['user_position_id'] ?? null,
                'gestor_user_id' => $user['immediate_supervisor_id'] ?? null,
                'data_inicio' => date('Y-m-d'),
            ])) {
                throw new Exception('Falha ao sincronizar Lotação.');
            }
        }

        return ['pessoa_id' => $pessoaId, 'vinculo_id' => $vinculoId];
    }

    /**
     * Best-effort: não derruba o fluxo principal se a sombra falhar.
     */
    public function tentarSincronizar(int $userId, string $contexto): void
    {
        try {
            $this->sincronizarDeUsuario($userId);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Falha no dual-write de identidade.', [
                'user_id' => $userId,
                'contexto' => $contexto,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
