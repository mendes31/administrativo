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

    /**
     * Verifica se o usuário pode gerenciar a entrevista (pela vaga ou pelo candidato).
     *
     * @param array<string, mixed> $entrevista
     */
    public static function canManageEntrevista(array $entrevista): bool
    {
        if (self::isSuperAdmin()) {
            return true;
        }

        $vagaId = (int) ($entrevista['rh_vaga_id'] ?? 0);
        if ($vagaId > 0) {
            return self::canManagePipelineByVagaId($vagaId);
        }

        $candidatoId = (int) ($entrevista['rh_candidato_id'] ?? 0);
        if ($candidatoId > 0) {
            return \App\adms\Models\Services\RhCandidatoPermissionService::canAccessCandidato($candidatoId);
        }

        return false;
    }

    /**
     * Escopo da listagem de vagas (Expand Fase 0.5).
     *
     * - `all`: Super Admin ou permissão técnica RhVagasViewAll
     * - `responsible`: apenas vagas em que o usuário é responsavel_id
     *
     * Por padrão a migration concede RhVagasViewAll a quem já tem RhVagas,
     * preservando o comportamento anterior (ver todas).
     *
     * @return array{mode: 'all'|'responsible', user_id: int}
     */
    public static function resolveVagasListScope(?int $userId = null): array
    {
        $userId = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return ['mode' => 'responsible', 'user_id' => 0];
        }

        if (self::isSuperAdmin()) {
            return ['mode' => 'all', 'user_id' => $userId];
        }

        if (self::userHasController('RhVagasViewAll')) {
            return ['mode' => 'all', 'user_id' => $userId];
        }

        return ['mode' => 'responsible', 'user_id' => $userId];
    }

    /**
     * Escopo da listagem de entrevistas (Expand Fase 0.5).
     *
     * - `all`: Super Admin ou RhEntrevistasViewAll
     * - `related`: entrevistador principal, avaliador ativo no painel ou responsável da vaga
     *
     * @return array{mode: 'all'|'related', user_id: int}
     */
    public static function resolveEntrevistasListScope(?int $userId = null): array
    {
        $userId = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return ['mode' => 'related', 'user_id' => 0];
        }

        if (self::isSuperAdmin()) {
            return ['mode' => 'all', 'user_id' => $userId];
        }

        if (self::userHasController('RhEntrevistasViewAll')) {
            return ['mode' => 'all', 'user_id' => $userId];
        }

        return ['mode' => 'related', 'user_id' => $userId];
    }

    /**
     * Escopo da listagem de candidatos (Expand Fase 0.5).
     *
     * - `all`: Super Admin ou RhCandidatosViewAll
     * - `related`: candidato vinculado a vaga cujo responsavel_id é o usuário
     *   (candidatos sem vaga só aparecem em `all`)
     *
     * @return array{mode: 'all'|'related', user_id: int}
     */
    public static function resolveCandidatosListScope(?int $userId = null): array
    {
        $userId = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return ['mode' => 'related', 'user_id' => 0];
        }

        if (self::isSuperAdmin()) {
            return ['mode' => 'all', 'user_id' => $userId];
        }

        if (self::userHasController('RhCandidatosViewAll')) {
            return ['mode' => 'all', 'user_id' => $userId];
        }

        return ['mode' => 'related', 'user_id' => $userId];
    }

    private static function userHasController(string $controller): bool
    {
        $allowed = array_flip(
            (new \App\adms\Models\Repository\MenuPermissionUserRepository())->getAllowedControllersForUser()
        );

        return isset($allowed[$controller]);
    }
}

