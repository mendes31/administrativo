<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhMovimentacoesRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;

/**
 * Movimentações organizacionais (Expand Fase 4) — aplica em adms_users.
 */
final class RhMovimentacaoService
{
    /**
     * @param array<string, mixed> $input
     * @return array{movimentacao_id: int}
     */
    public function registrar(array $input, int $actorId): array
    {
        if (!RhPermissionService::isSuperAdmin() && !RhPermissionService::isManager()) {
            // Espelha gestão RH: quem tem ACL da página já passou no router;
            // reforço mínimo para operações sensíveis.
            if (empty($_SESSION['user_id'])) {
                throw new Exception('Sem permissão para registrar movimentação.');
            }
        }

        $userId = (int) ($input['adms_user_id'] ?? 0);
        if ($userId <= 0) {
            throw new Exception('Informe o colaborador.');
        }

        $tipo = (string) ($input['tipo'] ?? '');
        if (!in_array($tipo, RhMovimentacoesRepository::TIPOS, true)) {
            throw new Exception('Tipo de movimentação inválido.');
        }

        $vigencia = trim((string) ($input['data_vigencia'] ?? ''));
        if ($vigencia === '') {
            throw new Exception('Informe a data de vigência.');
        }

        $motivo = trim((string) ($input['motivo'] ?? ''));
        if ($motivo === '') {
            throw new Exception('Informe o motivo da movimentação.');
        }

        $usersRepo = new UsersRepository();
        $user = $usersRepo->getUser($userId);
        if (!$user || !is_array($user)) {
            throw new Exception('Colaborador não encontrado.');
        }

        $depDepois = (int) ($input['departamento_id_depois'] ?? 0);
        $cargoDepois = (int) ($input['cargo_id_depois'] ?? 0);
        $gestorDepoisRaw = $input['gestor_id_depois'] ?? null;
        $gestorDepois = ($gestorDepoisRaw !== null && $gestorDepoisRaw !== '' && (int) $gestorDepoisRaw > 0)
            ? (int) $gestorDepoisRaw
            : null;

        if ($depDepois <= 0 || $cargoDepois <= 0) {
            throw new Exception('Informe departamento e cargo de destino.');
        }
        if ($gestorDepois === $userId) {
            throw new Exception('O colaborador não pode ser gestor de si mesmo.');
        }

        $depAntes = !empty($user['user_department_id']) ? (int) $user['user_department_id'] : null;
        $cargoAntes = !empty($user['user_position_id']) ? (int) $user['user_position_id'] : null;
        $gestorAntes = !empty($user['immediate_supervisor_id']) ? (int) $user['immediate_supervisor_id'] : null;

        if (
            $depAntes === $depDepois
            && $cargoAntes === $cargoDepois
            && $gestorAntes === $gestorDepois
        ) {
            throw new Exception('Nenhuma alteração de lotação foi informada.');
        }

        $repo = new RhMovimentacoesRepository();
        $pdo = $repo->getConnection();
        $pdo->beginTransaction();

        try {
            $id = $repo->create([
                'adms_user_id' => $userId,
                'tipo' => $tipo,
                'data_vigencia' => $vigencia,
                'departamento_id_antes' => $depAntes,
                'departamento_id_depois' => $depDepois,
                'cargo_id_antes' => $cargoAntes,
                'cargo_id_depois' => $cargoDepois,
                'gestor_id_antes' => $gestorAntes,
                'gestor_id_depois' => $gestorDepois,
                'motivo' => $motivo,
                'observacoes' => trim((string) ($input['observacoes'] ?? '')) ?: null,
                'created_by_user_id' => $actorId > 0 ? $actorId : null,
            ]);
            if (!$id) {
                throw new Exception('Falha ao registrar a movimentação.');
            }

            if (!$repo->aplicarLotacaoUsuario($userId, $depDepois, $cargoDepois, $gestorDepois)) {
                throw new Exception('Falha ao aplicar a nova lotação no usuário.');
            }

            $pdo->commit();

            return ['movimentacao_id' => $id];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha ao registrar movimentação.', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e instanceof Exception ? $e : new Exception('Não foi possível registrar a movimentação.');
        }
    }
}
