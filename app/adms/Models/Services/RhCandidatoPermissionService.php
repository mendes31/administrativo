<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\RhVagasRepository;
use PDO;

/**
 * Autorização por objeto para candidatos/currículos (ATS).
 *
 * Permite:
 * - Super Admin;
 * - operadores com RhCandidatosViewAll (banco completo — alinhado à listagem);
 * - gestores CRM com relação à vaga vinculada (área ou time do responsável);
 * - responsável de qualquer vaga vinculada ao candidato.
 *
 * Expand: ACL operacional (RhCandidatos*) não abre mais qualquer ficha;
 * o Contract de ViewAll ativa o filtro por vínculo.
 */
final class RhCandidatoPermissionService
{
    public static function canAccessCandidato(int $candidatoId): bool
    {
        if ($candidatoId <= 0) {
            return false;
        }

        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return false;
        }

        // Mesmo critério da listagem: ViewAll = acesso a qualquer candidato.
        if (RhPermissionService::resolveCandidatosListScope($userId)['mode'] === 'all') {
            return true;
        }

        if (self::isManagerOfAnyVagaDoCandidato($candidatoId)) {
            return true;
        }

        return self::isResponsavelDeVagaDoCandidato($candidatoId, $userId);
    }

    public static function canEditCandidato(int $candidatoId): bool
    {
        return self::canAccessCandidato($candidatoId);
    }

    public static function canDownloadAnexo(int $candidatoId): bool
    {
        return self::canAccessCandidato($candidatoId);
    }

    /**
     * Extrai o ID do candidato a partir do caminho relativo do anexo
     * (ex.: rh_candidatos/12/arquivo.pdf).
     */
    public static function extractCandidatoIdFromAnexoPath(string $path): ?int
    {
        $normalized = str_replace('\\', '/', ltrim(trim($path), '/'));
        if (preg_match('#(?:^|/)rh_candidatos/(\d+)/#', $normalized, $m) !== 1) {
            return null;
        }

        $id = (int) $m[1];

        return $id > 0 ? $id : null;
    }

    private static function isManagerOfAnyVagaDoCandidato(int $candidatoId): bool
    {
        if (!RhPermissionService::isManager()) {
            return false;
        }

        try {
            $repo = new RhVagasRepository();
            $pdo = $repo->getConnection();
            $sql = 'SELECT v.id, v.area_id, v.responsavel_id
                    FROM rh_candidatos_vagas cv
                    INNER JOIN rh_vagas v ON v.id = cv.rh_vaga_id
                    WHERE cv.rh_candidato_id = :candidato_id';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $vaga) {
                if (RhPermissionService::isManagerOfVaga($vaga)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    private static function isResponsavelDeVagaDoCandidato(int $candidatoId, int $userId): bool
    {
        $repo = new RhVagasRepository();
        $pdo = $repo->getConnection();
        $sql = 'SELECT 1
                FROM rh_candidatos_vagas cv
                INNER JOIN rh_vagas v ON v.id = cv.rh_vaga_id
                WHERE cv.rh_candidato_id = :candidato_id
                  AND v.responsavel_id = :user_id
                LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }
}
