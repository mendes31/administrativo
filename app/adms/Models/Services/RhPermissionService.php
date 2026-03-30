<?php

namespace App\adms\Models\Services;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\RhVagasRepository;

/**
 * Serviço de permissões para módulo de Recrutamento (RH).
 *
 * Regras gerais:
 * - Super Admin (access_level_id = 1) sempre tem acesso total.
 * - Usuário responsável pela vaga (responsavel_id) pode editar/vincular/mover pipeline dessa vaga.
 * - Gestores (mesma lógica de hierarquia do CRM) também podem gerenciar vagas do seu time/área.
 */
class RhPermissionService extends DbConnection
{
    /**
     * Verifica se o usuário logado é Super Admin.
     */
    public static function isSuperAdmin(): bool
    {
        return UserAccessHelper::hasFullSystemAccess();
    }

    /**
     * Verifica se o usuário logado é o responsável direto pela vaga.
     */
    public static function isVagaResponsavel(array $vaga): bool
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId) {
            return false;
        }

        return !empty($vaga['responsavel_id']) && (int)$vaga['responsavel_id'] === $userId;
    }

    /**
     * Verifica se o usuário logado é gestor (usa mesma regra de hierarquia do CRM).
     */
    public static function isManager(): bool
    {
        // Reaproveita a lógica já consolidada do CRM
        return \App\adms\Models\Services\CrmPermissionService::isManager();
    }

    /**
     * Verifica se o usuário pode editar os dados da vaga.
     */
    public static function canEditVaga(array $vaga): bool
    {
        if (self::isSuperAdmin()) {
            return true;
        }

        if (self::isVagaResponsavel($vaga)) {
            return true;
        }

        if (self::isManager()) {
            return true;
        }

        return false;
    }

    /**
     * Helper para buscar a vaga por ID e aplicar canEditVaga.
     */
    public static function canEditVagaById(int $vagaId): bool
    {
        $repo = new RhVagasRepository();
        $vaga = $repo->getById($vagaId);
        if (!$vaga) {
            return false;
        }

        return self::canEditVaga($vaga);
    }

    /**
     * Verifica se o usuário pode gerenciar o pipeline (vínculo/movimentação de candidatos).
     */
    public static function canManagePipeline(array $vaga): bool
    {
        // Por padrão, mesma lógica de edição da vaga.
        return self::canEditVaga($vaga);
    }

    /**
     * Busca vaga e aplica regra de canManagePipeline.
     *
     * Helper conveniente para controllers que só têm o ID.
     */
    public static function canManagePipelineByVagaId(int $vagaId): bool
    {
        $repo = new RhVagasRepository();
        $vaga = $repo->getById($vagaId);
        if (!$vaga) {
            return false;
        }

        return self::canManagePipeline($vaga);
    }
}

