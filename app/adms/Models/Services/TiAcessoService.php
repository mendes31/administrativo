<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\TiAcessoRepository;
use App\adms\Models\Repository\TiSistemaRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;

/**
 * Liberação e revogação do mapa colaborador ↔ sistema (domínio TI / Acessos).
 */
final class TiAcessoService
{
    /**
     * @param array<string, mixed> $input
     * @return array{acesso_id: int}
     */
    public function liberar(array $input, int $actorId): array
    {
        $userId = (int) ($input['adms_user_id'] ?? 0);
        $sistemaId = (int) ($input['ti_sistema_id'] ?? 0);
        if ($userId <= 0) {
            throw new Exception('Informe o colaborador.');
        }
        if ($sistemaId <= 0) {
            throw new Exception('Informe o sistema.');
        }

        $user = (new UsersRepository())->getUser($userId);
        if (!$user || !is_array($user)) {
            throw new Exception('Colaborador não encontrado.');
        }

        $sistema = (new TiSistemaRepository())->getById($sistemaId);
        if ($sistema === null) {
            throw new Exception('Sistema não encontrado.');
        }
        if (($sistema['status'] ?? '') !== TiSistemaRepository::STATUS_ATIVO) {
            throw new Exception('Não é possível liberar acesso a sistema inativo.');
        }

        $repo = new TiAcessoRepository();
        if ($repo->findAtivo($userId, $sistemaId) !== null) {
            throw new Exception('Já existe um acesso ativo deste colaborador neste sistema.');
        }

        $id = $repo->create([
            'adms_user_id' => $userId,
            'ti_sistema_id' => $sistemaId,
            'login_externo' => $input['login_externo'] ?? null,
            'perfil_obs' => $input['perfil_obs'] ?? null,
            'data_liberacao' => $input['data_liberacao'] ?? date('Y-m-d'),
            'observacoes' => $input['observacoes'] ?? null,
        ], $actorId);

        if (!$id) {
            throw new Exception('Não foi possível registrar o acesso.');
        }

        return ['acesso_id' => (int) $id];
    }

    public function revogar(int $acessoId, int $actorId, ?string $dataRevogacao = null, ?string $observacoes = null): void
    {
        $repo = new TiAcessoRepository();
        $acesso = $repo->getById($acessoId);
        if ($acesso === null) {
            throw new Exception('Acesso não encontrado.');
        }
        if (($acesso['status'] ?? '') !== TiAcessoRepository::STATUS_ATIVO) {
            throw new Exception('Este acesso já está revogado.');
        }
        if (!$repo->revogar($acessoId, $actorId, $dataRevogacao, $observacoes)) {
            throw new Exception('Não foi possível revogar o acesso.');
        }
    }

    /**
     * Edita login/perfil/obs de um acesso ativo (não troca colaborador nem sistema).
     *
     * @param array<string, mixed> $input
     */
    public function atualizar(int $acessoId, array $input, int $actorId): void
    {
        $repo = new TiAcessoRepository();
        $acesso = $repo->getById($acessoId);
        if ($acesso === null) {
            throw new Exception('Acesso não encontrado.');
        }
        if (($acesso['status'] ?? '') !== TiAcessoRepository::STATUS_ATIVO) {
            throw new Exception('Só é possível editar acessos ativos. Para vínculos inativos, liberar um novo acesso.');
        }

        if (!$repo->updateDetalhes($acessoId, [
            'login_externo' => $input['login_externo'] ?? null,
            'perfil_obs' => $input['perfil_obs'] ?? null,
            'data_liberacao' => $input['data_liberacao'] ?? null,
            'observacoes' => $input['observacoes'] ?? null,
        ], $actorId)) {
            throw new Exception('Não foi possível salvar as alterações do acesso.');
        }
    }
}
