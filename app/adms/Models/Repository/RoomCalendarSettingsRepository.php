<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Configuração única (id=1) para integração futura Outlook/Google — Reserva de Salas.
 */
class RoomCalendarSettingsRepository extends DbConnection
{
    private const ROW_ID = 1;

    /**
     * @return array<string, mixed>
     */
    public function getSingleton(): array
    {
        try {
            $sql = 'SELECT * FROM adms_room_calendar_settings WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', self::ROW_ID, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (\Throwable) {
            // Tabela ainda não migrada
        }

        return [
            'id' => self::ROW_ID,
            'outlook_sync_enabled' => 0,
            'google_sync_enabled' => 0,
            'outlook_tenant_id' => null,
            'outlook_client_id' => null,
            'google_client_id' => null,
            'updated_at' => null,
            'updated_by' => null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data, ?int $updatedByUserId = null): bool
    {
        try {
            return $this->doSave($data, $updatedByUserId);
        } catch (\Throwable) {
            return false;
        }
    }

    private function doSave(array $data, ?int $updatedByUserId = null): bool
    {
        $conn = $this->getConnection();
        $oldStmt = $conn->prepare('SELECT * FROM adms_room_calendar_settings WHERE id = :id LIMIT 1');
        $oldStmt->bindValue(':id', self::ROW_ID, PDO::PARAM_INT);
        $oldStmt->execute();
        $oldRow = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $sql = 'UPDATE adms_room_calendar_settings SET
                outlook_sync_enabled = :outlook_sync_enabled,
                google_sync_enabled = :google_sync_enabled,
                outlook_tenant_id = :outlook_tenant_id,
                outlook_client_id = :outlook_client_id,
                google_client_id = :google_client_id,
                updated_at = NOW(),
                updated_by = :updated_by
                WHERE id = :id';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', self::ROW_ID, PDO::PARAM_INT);
        $stmt->bindValue(':outlook_sync_enabled', !empty($data['outlook_sync_enabled']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':google_sync_enabled', !empty($data['google_sync_enabled']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':outlook_tenant_id', $this->emptyToNull($data['outlook_tenant_id'] ?? null));
        $stmt->bindValue(':outlook_client_id', $this->emptyToNull($data['outlook_client_id'] ?? null));
        $stmt->bindValue(':google_client_id', $this->emptyToNull($data['google_client_id'] ?? null));
        if ($updatedByUserId !== null && $updatedByUserId > 0) {
            $stmt->bindValue(':updated_by', $updatedByUserId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':updated_by', null, PDO::PARAM_NULL);
        }

        $ok = $stmt->execute();
        if ($ok && $oldRow) {
            $newStmt = $conn->prepare('SELECT * FROM adms_room_calendar_settings WHERE id = :id LIMIT 1');
            $newStmt->bindValue(':id', self::ROW_ID, PDO::PARAM_INT);
            $newStmt->execute();
            $newRow = $newStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $usuarioId = $_SESSION['user_id'] ?? 1;
            LogAlteracaoService::registrarAlteracao(
                'adms_room_calendar_settings',
                self::ROW_ID,
                $usuarioId,
                'UPDATE',
                $oldRow,
                $newRow
            );
        }

        return $ok;
    }

    private function emptyToNull(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }
}
