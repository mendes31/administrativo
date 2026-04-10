<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Tipos de documento de folha (importação, filtros, regras futuras).
 */
class PayrollDocumentTypesRepository extends DbConnection
{
    /** @return list<array<string, mixed>> */
    public function listAll(): array
    {
        $sql = 'SELECT * FROM adms_payroll_document_types ORDER BY sort_order ASC, name ASC';
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function listActiveForSelect(): array
    {
        $sql = 'SELECT id, code, name, default_title_prefix, icon FROM adms_payroll_document_types
                WHERE is_active = 1 ORDER BY sort_order ASC, name ASC';
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<string> */
    public function listActiveCodes(): array
    {
        $sql = 'SELECT code FROM adms_payroll_document_types WHERE is_active = 1 ORDER BY sort_order ASC';
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return array_values(array_filter(array_map('strval', $rows)));
    }

    /**
     * @return array<string, string> code => name
     */
    public function getLabelsMapActive(): array
    {
        $sql = 'SELECT code, name FROM adms_payroll_document_types WHERE is_active = 1';
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $map[(string)$r['code']] = (string)$r['name'];
        }

        return $map;
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_payroll_document_types WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findByCode(string $code): ?array
    {
        $sql = 'SELECT * FROM adms_payroll_document_types WHERE code = :code LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $code, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findActiveByCode(string $code): ?array
    {
        $sql = 'SELECT * FROM adms_payroll_document_types WHERE code = :code AND is_active = 1 LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $code, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_payroll_document_types
            (code, name, description, default_title_prefix, icon, requires_signature, signature_auth,
             require_auth_download, signature_reminders_enabled, signature_reminder_day_1, signature_reminder_day_2, signature_reminder_day_3,
             rules_json, is_active, sort_order, created_at, updated_at)
            VALUES
            (:code, :name, :description, :default_title_prefix, :icon, :requires_signature, :signature_auth,
             :require_auth_download, :signature_reminders_enabled, :signature_reminder_day_1, :signature_reminder_day_2, :signature_reminder_day_3,
             :rules_json, :is_active, :sort_order, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $data['code'], PDO::PARAM_STR);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':default_title_prefix', $data['default_title_prefix'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':icon', $data['icon'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':requires_signature', !empty($data['requires_signature']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':signature_auth', $data['signature_auth'] ?? 'none', PDO::PARAM_STR);
        $stmt->bindValue(':require_auth_download', !empty($data['require_auth_download']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':signature_reminders_enabled', !empty($data['signature_reminders_enabled']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':signature_reminder_day_1', max(0, min(365, (int)($data['signature_reminder_day_1'] ?? 1))), PDO::PARAM_INT);
        $stmt->bindValue(':signature_reminder_day_2', max(0, min(365, (int)($data['signature_reminder_day_2'] ?? 3))), PDO::PARAM_INT);
        $stmt->bindValue(':signature_reminder_day_3', max(0, min(365, (int)($data['signature_reminder_day_3'] ?? 7))), PDO::PARAM_INT);
        $stmt->bindValue(':rules_json', $data['rules_json'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':is_active', !empty($data['is_active']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':sort_order', (int)($data['sort_order'] ?? 0), PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_payroll_document_types SET
            name = :name,
            description = :description,
            default_title_prefix = :default_title_prefix,
            icon = :icon,
            requires_signature = :requires_signature,
            signature_auth = :signature_auth,
            require_auth_download = :require_auth_download,
            signature_reminders_enabled = :signature_reminders_enabled,
            signature_reminder_day_1 = :signature_reminder_day_1,
            signature_reminder_day_2 = :signature_reminder_day_2,
            signature_reminder_day_3 = :signature_reminder_day_3,
            rules_json = :rules_json,
            is_active = :is_active,
            sort_order = :sort_order,
            updated_at = NOW()
            WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':default_title_prefix', $data['default_title_prefix'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':icon', $data['icon'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':requires_signature', !empty($data['requires_signature']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':signature_auth', $data['signature_auth'] ?? 'none', PDO::PARAM_STR);
        $stmt->bindValue(':require_auth_download', !empty($data['require_auth_download']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':signature_reminders_enabled', !empty($data['signature_reminders_enabled']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':signature_reminder_day_1', max(0, min(365, (int)($data['signature_reminder_day_1'] ?? 1))), PDO::PARAM_INT);
        $stmt->bindValue(':signature_reminder_day_2', max(0, min(365, (int)($data['signature_reminder_day_2'] ?? 3))), PDO::PARAM_INT);
        $stmt->bindValue(':signature_reminder_day_3', max(0, min(365, (int)($data['signature_reminder_day_3'] ?? 7))), PDO::PARAM_INT);
        $stmt->bindValue(':rules_json', $data['rules_json'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':is_active', !empty($data['is_active']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':sort_order', (int)($data['sort_order'] ?? 0), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function countUsageByCode(string $code): int
    {
        $conn = $this->getConnection();
        $n = 0;
        $s1 = $conn->prepare('SELECT COUNT(*) FROM adms_employee_payroll_documents WHERE document_type = :c');
        $s1->bindValue(':c', $code, PDO::PARAM_STR);
        $s1->execute();
        $n += (int)$s1->fetchColumn();
        $s2 = $conn->prepare('SELECT COUNT(*) FROM adms_payroll_import_batches WHERE document_type = :c');
        $s2->bindValue(':c', $code, PDO::PARAM_STR);
        $s2->execute();
        $n += (int)$s2->fetchColumn();

        return $n;
    }

    public function delete(int $id): bool
    {
        $row = $this->findById($id);
        if ($row === null) {
            return false;
        }
        if ($this->countUsageByCode((string)$row['code']) > 0) {
            return false;
        }
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_payroll_document_types WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
