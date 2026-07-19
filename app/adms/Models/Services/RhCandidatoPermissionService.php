<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\PagesRoutesRepository;
use App\adms\Models\Repository\RhVagasRepository;
use PDO;

/**
 * Autorização por objeto para candidatos/currículos (ATS).
 *
 * Permite:
 * - acesso total (super);
 * - operadores com ACL das páginas de candidatos;
 * - gestores (mesma regra do CRM/RH);
 * - responsável de qualquer vaga vinculada ao candidato.
 */
final class RhCandidatoPermissionService
{
    /**
     * Controllers cuja ACL concede acesso operacional ao banco de currículos.
     *
     * @var list<string>
     */
    private const RH_CANDIDATO_CONTROLLERS = [
        'RhCandidatos',
        'RhCandidatosView',
        'RhCandidatosEdit',
        'RhCandidatosCreate',
        'RhCandidatosDelete',
        'RhCandidatosDownloadAnexo',
        'RhCandidatosVagas',
    ];

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

        $pages = new PagesRoutesRepository();
        if ($pages->checkUserAnyPagePermissionForControllers(self::RH_CANDIDATO_CONTROLLERS)) {
            return true;
        }

        if (RhPermissionService::isManager()) {
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
