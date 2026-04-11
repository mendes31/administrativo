<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\UsersAccessLevelsRepository;
use PDO;
use Throwable;

/**
 * Ao marcar super usuário: grava snapshot dos níveis atuais e associa apenas o nível Super Administrador (id 1).
 * Ao desmarcar: restaura os níveis do snapshot; se não houver snapshot (legado), remove só o vínculo com o nível 1.
 */
final class SuperUsuarioAccessLevelsSyncService extends DbConnection
{
    public function sync(int $userId, int $oldSuperFlag, int $newSuperFlag): void
    {
        if ($userId <= 0 || $oldSuperFlag === $newSuperFlag) {
            return;
        }

        $conn = $this->getConnection();

        try {
            $conn->beginTransaction();
            if ($newSuperFlag === 1) {
                $this->applySuperOn($conn, $userId);
            } else {
                $this->applySuperOff($conn, $userId);
            }
            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            GenerateLog::generateLog('error', 'SuperUsuarioAccessLevelsSyncService: falha na sincronização.', [
                'user_id' => $userId,
                'old_super' => $oldSuperFlag,
                'new_super' => $newSuperFlag,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function applySuperOn(PDO $conn, int $userId): void
    {
        $ualRepo = new UsersAccessLevelsRepository();
        $current = $ualRepo->getUserAccessLevelArray($userId);
        $ids = is_array($current) ? array_values(array_unique(array_map('intval', $current))) : [];
        $json = json_encode($ids, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '[]';
        }

        $stmt = $conn->prepare(
            'UPDATE adms_users SET super_usuario_prev_access_level_ids = :j WHERE id = :id'
        );
        $stmt->bindValue(':j', $json, PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $del = $conn->prepare('DELETE FROM adms_users_access_levels WHERE adms_user_id = :uid');
        $del->bindValue(':uid', $userId, PDO::PARAM_INT);
        $del->execute();

        $ins = $conn->prepare(
            'INSERT INTO adms_users_access_levels (adms_user_id, adms_access_level_id, created_at) VALUES (:uid, :lid, NOW())'
        );
        $ins->bindValue(':uid', $userId, PDO::PARAM_INT);
        $ins->bindValue(':lid', UserAccessHelper::SUPER_ADMIN_LEVEL_ID, PDO::PARAM_INT);
        $ins->execute();
    }

    private function applySuperOff(PDO $conn, int $userId): void
    {
        $stmt = $conn->prepare(
            'SELECT super_usuario_prev_access_level_ids FROM adms_users WHERE id = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $raw = $row['super_usuario_prev_access_level_ids'] ?? null;

        if ($raw !== null && $raw !== '') {
            $decoded = json_decode((string) $raw, true);
            $ids = is_array($decoded)
                ? array_values(array_unique(array_filter(array_map('intval', $decoded), static fn (int $x): bool => $x > 0)))
                : [];

            $del = $conn->prepare('DELETE FROM adms_users_access_levels WHERE adms_user_id = :uid');
            $del->bindValue(':uid', $userId, PDO::PARAM_INT);
            $del->execute();

            $ins = $conn->prepare(
                'INSERT INTO adms_users_access_levels (adms_user_id, adms_access_level_id, created_at) VALUES (:uid, :lid, NOW())'
            );
            foreach ($ids as $lid) {
                $ins->bindValue(':uid', $userId, PDO::PARAM_INT);
                $ins->bindValue(':lid', $lid, PDO::PARAM_INT);
                $ins->execute();
            }

            $upd = $conn->prepare(
                'UPDATE adms_users SET super_usuario_prev_access_level_ids = NULL WHERE id = :id'
            );
            $upd->bindValue(':id', $userId, PDO::PARAM_INT);
            $upd->execute();

            return;
        }

        $del = $conn->prepare(
            'DELETE FROM adms_users_access_levels WHERE adms_user_id = :uid AND adms_access_level_id = :lid'
        );
        $del->bindValue(':uid', $userId, PDO::PARAM_INT);
        $del->bindValue(':lid', UserAccessHelper::SUPER_ADMIN_LEVEL_ID, PDO::PARAM_INT);
        $del->execute();
    }
}
