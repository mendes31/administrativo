<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Documentos de folha/recibos por colaborador (armazenamento privado + metadados).
 */
class EmployeePayrollDocumentsRepository extends DbConnection
{
    public function createBatch(array $row): int
    {
        $sql = 'INSERT INTO adms_payroll_import_batches
            (original_filename, document_type, reference_year, reference_month, pages_total, pages_matched, pages_unmatched, log_json, created_by_user_id, created_at)
            VALUES (:original_filename, :document_type, :reference_year, :reference_month, :pages_total, :pages_matched, :pages_unmatched, :log_json, :created_by_user_id, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':original_filename', $row['original_filename'], PDO::PARAM_STR);
        $stmt->bindValue(':document_type', $row['document_type'], PDO::PARAM_STR);
        $stmt->bindValue(':reference_year', (int)$row['reference_year'], PDO::PARAM_INT);
        if (($row['reference_month'] ?? null) === null || $row['reference_month'] === '') {
            $stmt->bindValue(':reference_month', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':reference_month', (int)$row['reference_month'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':pages_total', (int)($row['pages_total'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':pages_matched', (int)($row['pages_matched'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':pages_unmatched', (int)($row['pages_unmatched'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':log_json', $row['log_json'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':created_by_user_id', (int)$row['created_by_user_id'], PDO::PARAM_INT);
        $stmt->execute();

        $batchId = (int) $this->getConnection()->lastInsertId();
        if ($batchId > 0) {
            $snap = $this->getRawPayrollImportBatchRow($batchId);
            if (is_array($snap)) {
                $actor = (int) ($snap['created_by_user_id'] ?? 0) > 0
                    ? (int) $snap['created_by_user_id']
                    : ((int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_payroll_import_batches',
                    $batchId,
                    $actor,
                    'INSERT',
                    [],
                    $snap
                );
            }
        }

        return $batchId;
    }

    public function updateBatchStats(int $batchId, int $matched, int $unmatched, ?string $logJson): void
    {
        $before = $this->getRawPayrollImportBatchRow($batchId);
        $sql = 'UPDATE adms_payroll_import_batches SET pages_matched = :m, pages_unmatched = :u, log_json = :log WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':m', $matched, PDO::PARAM_INT);
        $stmt->bindValue(':u', $unmatched, PDO::PARAM_INT);
        $stmt->bindValue(':log', $logJson, PDO::PARAM_STR);
        $stmt->bindValue(':id', $batchId, PDO::PARAM_INT);
        $stmt->execute();
        if (is_array($before) && $stmt->rowCount() > 0) {
            $after = $this->getRawPayrollImportBatchRow($batchId);
            if (is_array($after)) {
                $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : (int) ($before['created_by_user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_payroll_import_batches',
                    $batchId,
                    $actor,
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }
    }

    /**
     * Remove documento anterior do mesmo tipo/ref (substituição na reimportação).
     */
    public function deleteExistingForUserRef(int $userId, string $documentType, int $year, ?int $month): void
    {
        $sql = 'SELECT * FROM adms_employee_payroll_documents
                WHERE user_id = :uid AND document_type = :dt AND reference_year = :y
                AND (reference_month <=> :m)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':dt', $documentType, PDO::PARAM_STR);
        $stmt->bindValue(':y', $year, PDO::PARAM_INT);
        if ($month === null) {
            $stmt->bindValue(':m', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':m', $month, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : 1;
        foreach ($rows as $r) {
            $path = (string)($r['storage_path'] ?? '');
            if ($path !== '') {
                $full = $this->absoluteStoragePath($path);
                if (is_file($full)) {
                    @unlink($full);
                }
            }
        }
        if ($rows !== []) {
            $del = $this->getConnection()->prepare(
                'DELETE FROM adms_employee_payroll_documents WHERE user_id = :uid AND document_type = :dt AND reference_year = :y AND (reference_month <=> :m)'
            );
            $del->bindValue(':uid', $userId, PDO::PARAM_INT);
            $del->bindValue(':dt', $documentType, PDO::PARAM_STR);
            $del->bindValue(':y', $year, PDO::PARAM_INT);
            if ($month === null) {
                $del->bindValue(':m', null, PDO::PARAM_NULL);
            } else {
                $del->bindValue(':m', $month, PDO::PARAM_INT);
            }
            $del->execute();
            foreach ($rows as $r) {
                $did = (int) ($r['id'] ?? 0);
                if ($did > 0) {
                    LogAlteracaoService::registrarAlteracao(
                        'adms_employee_payroll_documents',
                        $did,
                        $actor,
                        'DELETE',
                        $r,
                        []
                    );
                }
            }
        }
    }

    /**
     * Marca como superseded (mantém PDF em disco) documentos do mesmo lógico com o mesmo nome de ficheiro
     * original no lote — nova versão será inserida em seguida. Invalida OTPs abertos desses IDs.
     *
     * @return list<int> IDs tornados superseded
     */
    public function supersedeExistingForUserRefSameOriginalFilename(
        int $userId,
        string $documentType,
        int $year,
        ?int $month,
        string $originalFilename
    ): array {
        $orig = trim($originalFilename);
        if ($orig === '') {
            $orig = 'documento.pdf';
        }
        $sql = 'SELECT d.id FROM adms_employee_payroll_documents d
                INNER JOIN adms_payroll_import_batches b ON b.id = d.import_batch_id
                WHERE d.user_id = :uid AND d.document_type = :dt AND d.reference_year = :y
                AND (d.reference_month <=> :m)
                AND d.status_version = \'active\'
                AND b.reference_year = :by
                AND (b.reference_month <=> :bm)
                AND LOWER(TRIM(b.original_filename)) = LOWER(TRIM(:orig))';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':dt', $documentType, PDO::PARAM_STR);
        $stmt->bindValue(':y', $year, PDO::PARAM_INT);
        if ($month === null) {
            $stmt->bindValue(':m', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':m', $month, PDO::PARAM_INT);
        }
        $stmt->bindValue(':by', $year, PDO::PARAM_INT);
        if ($month === null) {
            $stmt->bindValue(':bm', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':bm', $month, PDO::PARAM_INT);
        }
        $stmt->bindValue(':orig', $orig, PDO::PARAM_STR);
        $stmt->execute();
        $ids = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'id'));
        if ($ids === []) {
            return [];
        }
        $beforeById = [];
        foreach ($ids as $did) {
            if ($did <= 0) {
                continue;
            }
            $snap = $this->getRawPayrollDocumentRow($did);
            if (is_array($snap)) {
                $beforeById[$did] = $snap;
            }
        }
        (new PayrollDocumentOtpRepository())->invalidateOpenForDocumentIds($ids);
        $in = implode(',', $ids);
        $this->getConnection()->exec(
            "UPDATE adms_employee_payroll_documents SET status_version = 'superseded' WHERE id IN ({$in})"
        );
        $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : 1;
        foreach ($ids as $did) {
            if ($did <= 0 || !isset($beforeById[$did])) {
                continue;
            }
            $after = $this->getRawPayrollDocumentRow($did);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_payroll_documents',
                    $did,
                    $actor,
                    'UPDATE',
                    $beforeById[$did],
                    $after
                );
            }
        }

        return $ids;
    }

    public static function computeDocumentGroupKey(int $userId, string $documentType, int $year, ?int $month): string
    {
        $m = $month === null ? '' : (string)(int)$month;

        return hash('sha256', $userId . '|' . $documentType . '|' . $year . '|' . $m);
    }

    public function getMaxVersionForGroupKey(string $groupKey): int
    {
        $sql = 'SELECT COALESCE(MAX(document_version), 0) FROM adms_employee_payroll_documents WHERE document_group_key = :g';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':g', $groupKey, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    public function insertDocument(array $row): int
    {
        $sql = 'INSERT INTO adms_employee_payroll_documents
            (user_id, import_batch_id, document_type, reference_year, reference_month, title, storage_path, file_size, net_amount, cpf_normalized, page_from, page_to,
             document_group_key, document_version, status_version, supersedes_document_id, file_hash_sha256,
             signature_status, requires_signature_snapshot, signature_auth_snapshot, require_auth_download_snapshot, published_at, reminder_stage, created_at)
            VALUES (:user_id, :import_batch_id, :document_type, :reference_year, :reference_month, :title, :storage_path, :file_size, :net_amount, :cpf_normalized, :page_from, :page_to,
             :dgk, :dver, :stver, :sup_id, :fhash,
             :sigst, :req_sig, :sig_auth, :req_dl, :pub_at, 0, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', (int)$row['user_id'], PDO::PARAM_INT);
        if (!empty($row['import_batch_id'])) {
            $stmt->bindValue(':import_batch_id', (int)$row['import_batch_id'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':import_batch_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':document_type', $row['document_type'], PDO::PARAM_STR);
        $stmt->bindValue(':reference_year', (int)$row['reference_year'], PDO::PARAM_INT);
        if (($row['reference_month'] ?? null) === null || $row['reference_month'] === '') {
            $stmt->bindValue(':reference_month', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':reference_month', (int)$row['reference_month'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':title', $row['title'], PDO::PARAM_STR);
        $stmt->bindValue(':storage_path', $row['storage_path'], PDO::PARAM_STR);
        $stmt->bindValue(':file_size', (int)($row['file_size'] ?? 0), PDO::PARAM_INT);
        if (array_key_exists('net_amount', $row) && $row['net_amount'] !== null && $row['net_amount'] !== '') {
            $stmt->bindValue(':net_amount', (string)$row['net_amount'], PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':net_amount', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':cpf_normalized', $row['cpf_normalized'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':page_from', (int)($row['page_from'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':page_to', (int)($row['page_to'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':dgk', (string)($row['document_group_key'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':dver', (int)($row['document_version'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':stver', (string)($row['status_version'] ?? 'active'), PDO::PARAM_STR);
        if (!empty($row['supersedes_document_id'])) {
            $stmt->bindValue(':sup_id', (int)$row['supersedes_document_id'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':sup_id', null, PDO::PARAM_NULL);
        }
        if (!empty($row['file_hash_sha256'])) {
            $stmt->bindValue(':fhash', strtolower((string)$row['file_hash_sha256']), PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':fhash', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':sigst', (string)($row['signature_status'] ?? 'not_required'), PDO::PARAM_STR);
        $reqSnap = $row['requires_signature_snapshot'] ?? false;
        $stmt->bindValue(':req_sig', ($reqSnap === true || $reqSnap === 1 || $reqSnap === '1') ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':sig_auth', (string)($row['signature_auth_snapshot'] ?? 'none'), PDO::PARAM_STR);
        $reqDl = $row['require_auth_download_snapshot'] ?? false;
        $stmt->bindValue(':req_dl', ($reqDl === true || $reqDl === 1 || $reqDl === '1') ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':pub_at', $row['published_at'] ?? date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $snap = $this->getRawPayrollDocumentRow($newId);
            if (is_array($snap)) {
                $actor = (int) ($_SESSION['user_id'] ?? 0) > 0
                    ? (int) $_SESSION['user_id']
                    : (int) ($row['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_payroll_documents',
                    $newId,
                    $actor,
                    'INSERT',
                    [],
                    $snap
                );
            }
        }

        return $newId;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPendingSignaturesForRh(int $limit = 500): array
    {
        $limit = max(1, min(2000, $limit));
        $sql = 'SELECT d.*, u.name AS user_name, u.email AS user_email, u.celular AS user_celular,
                dep.name AS department_name
            FROM adms_employee_payroll_documents d
            INNER JOIN adms_users u ON u.id = d.user_id
            LEFT JOIN adms_departments dep ON dep.id = u.user_department_id
            WHERE d.status_version = \'active\'
              AND d.signature_status = \'pending\'
              AND d.requires_signature_snapshot = 1
            ORDER BY d.published_at ASC, d.id ASC
            LIMIT ' . $limit;
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Pendentes de assinatura com publicação definida (para régua D+X no cron).
     *
     * @return list<array<string, mixed>>
     */
    public function listPendingSignatureForReminders(int $limit = 2000): array
    {
        $limit = max(1, min(5000, $limit));
        $sql = 'SELECT * FROM adms_employee_payroll_documents
            WHERE status_version = \'active\'
              AND signature_status = \'pending\'
              AND requires_signature_snapshot = 1
              AND published_at IS NOT NULL
              AND reminder_stage < 3
            ORDER BY published_at ASC
            LIMIT ' . $limit;
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function recordSignature(
        int $documentId,
        int $userId,
        string $ip,
        ?string $userAgent,
        string $authMethod,
        string $documentHashAtSign
    ): bool {
        $before = $this->getById($documentId);
        $ua = $userAgent !== null ? mb_substr($userAgent, 0, 512) : null;
        $sql = 'UPDATE adms_employee_payroll_documents SET
            signature_status = \'signed\',
            signed_at = NOW(),
            signed_ip = :ip,
            signed_user_agent = :ua,
            signed_auth_method = :am,
            signed_document_hash_sha256 = :dh
            WHERE id = :id AND user_id = :uid AND status_version = \'active\'
              AND signature_status = \'pending\'';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ip', mb_substr($ip, 0, 45), PDO::PARAM_STR);
        $stmt->bindValue(':ua', $ua, $ua === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':am', mb_substr($authMethod, 0, 40), PDO::PARAM_STR);
        $stmt->bindValue(':dh', strtolower($documentHashAtSign), PDO::PARAM_STR);
        $stmt->bindValue(':id', $documentId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $changed = $stmt->rowCount() > 0;
        if ($changed && is_array($before)) {
            $after = $this->getById($documentId);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_payroll_documents',
                    $documentId,
                    $userId,
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }

        return $changed;
    }

    public function updateSignedBundleStoragePath(int $documentId, ?string $relativePath): void
    {
        $before = $this->getById($documentId);
        $sql = 'UPDATE adms_employee_payroll_documents SET signed_bundle_storage_path = :p WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        if ($relativePath !== null && $relativePath !== '') {
            $stmt->bindValue(':p', $relativePath, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':p', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':id', $documentId, PDO::PARAM_INT);
        $stmt->execute();
        if (is_array($before) && $stmt->rowCount() > 0) {
            $after = $this->getById($documentId);
            if (is_array($after)) {
                $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : (int) ($before['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_payroll_documents',
                    $documentId,
                    $actor,
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }
    }

    public function updateReminderStage(int $documentId, int $stage): void
    {
        $before = $this->getById($documentId);
        $sql = 'UPDATE adms_employee_payroll_documents SET reminder_stage = :s WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':s', $stage, PDO::PARAM_INT);
        $stmt->bindValue(':id', $documentId, PDO::PARAM_INT);
        $stmt->execute();
        if (is_array($before) && $stmt->rowCount() > 0) {
            $after = $this->getById($documentId);
            if (is_array($after)) {
                $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : 1;
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_payroll_documents',
                    $documentId,
                    $actor,
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId, array $filters = []): array
    {
        $where = ['d.user_id = :uid'];
        $params = [':uid' => $userId];
        if (!empty($filters['document_type'])) {
            $where[] = 'd.document_type = :dt';
            $params[':dt'] = (string)$filters['document_type'];
        }
        if (!empty($filters['year'])) {
            $where[] = 'd.reference_year = :y';
            $params[':y'] = (int)$filters['year'];
        }
        if (array_key_exists('month', $filters) && $filters['month'] !== '' && $filters['month'] !== null) {
            $where[] = 'd.reference_month = :mo';
            $params[':mo'] = (int)$filters['month'];
        }
        $where[] = "d.status_version = 'active'";
        $sql = 'SELECT d.* FROM adms_employee_payroll_documents d WHERE ' . implode(' AND ', $where) . ' ORDER BY d.reference_year DESC, d.reference_month DESC, d.document_version DESC, d.id DESC';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getByIdForUser(int $id, int $userId): ?array
    {
        $sql = 'SELECT * FROM adms_employee_payroll_documents WHERE id = :id AND user_id = :uid LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_employee_payroll_documents WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function absoluteStoragePath(string $relativeFromProjectRoot): string
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);

        return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativeFromProjectRoot, '/\\'));
    }

    /**
     * Lotes recentes para ecrã de importação (RH).
     *
     * @return list<array<string, mixed>>
     */
    public function listBatches(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $sql = 'SELECT b.*,
                (SELECT COUNT(*) FROM adms_employee_payroll_documents d WHERE d.import_batch_id = b.id) AS documents_count,
                u.name AS created_by_name
            FROM adms_payroll_import_batches b
            LEFT JOIN adms_users u ON u.id = b.created_by_user_id
            ORDER BY b.id DESC
            LIMIT ' . $limit;
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Remove PDFs em disco, linhas em adms_employee_payroll_documents e o lote.
     */
    public function deleteBatchCascade(int $batchId): bool
    {
        if ($batchId <= 0) {
            return false;
        }

        $conn = $this->getConnection();
        $batchBefore = $this->getRawPayrollImportBatchRow($batchId);
        $stmt = $conn->prepare('SELECT * FROM adms_employee_payroll_documents WHERE import_batch_id = :bid');
        $stmt->bindValue(':bid', $batchId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $conn->beginTransaction();
        try {
            foreach ($rows as $r) {
                foreach (['storage_path', 'signed_bundle_storage_path'] as $col) {
                    $path = (string)($r[$col] ?? '');
                    if ($path !== '') {
                        $full = $this->absoluteStoragePath($path);
                        if (is_file($full)) {
                            @unlink($full);
                        }
                    }
                }
            }
            if ($rows !== []) {
                $delDocs = $conn->prepare('DELETE FROM adms_employee_payroll_documents WHERE import_batch_id = :bid');
                $delDocs->bindValue(':bid', $batchId, PDO::PARAM_INT);
                $delDocs->execute();
            }
            $delBatch = $conn->prepare('DELETE FROM adms_payroll_import_batches WHERE id = :id');
            $delBatch->bindValue(':id', $batchId, PDO::PARAM_INT);
            $delBatch->execute();
            $batchDeleted = $delBatch->rowCount() > 0;
            $conn->commit();
            if ($batchDeleted) {
                $actor = (int) ($_SESSION['user_id'] ?? 0) > 0
                    ? (int) $_SESSION['user_id']
                    : (int) (is_array($batchBefore) ? ($batchBefore['created_by_user_id'] ?? 1) : 1);
                foreach ($rows as $snap) {
                    $did = (int) ($snap['id'] ?? 0);
                    if ($did > 0) {
                        LogAlteracaoService::registrarAlteracao(
                            'adms_employee_payroll_documents',
                            $did,
                            $actor,
                            'DELETE',
                            $snap,
                            []
                        );
                    }
                }
                if (is_array($batchBefore)) {
                    LogAlteracaoService::registrarAlteracao(
                        'adms_payroll_import_batches',
                        $batchId,
                        $actor,
                        'DELETE',
                        $batchBefore,
                        []
                    );
                }
            }

            return $batchDeleted;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * Regista visualização ou download do PDF (LGPD: rastreio de acesso a dados sensíveis).
     * Falhas de log não devem impedir a entrega do ficheiro ao utilizador.
     */
    public function logDocumentAccess(
        ?int $documentId,
        int $ownerUserId,
        int $viewerUserId,
        string $documentType,
        int $referenceYear,
        ?int $referenceMonth,
        string $deliveryMode,
        string $ip,
        ?string $userAgent
    ): void {
        $mode = strtolower(trim($deliveryMode)) === 'attachment' ? 'attachment' : 'inline';
        $ua = $userAgent !== null ? mb_substr($userAgent, 0, 512) : null;
        $sql = 'INSERT INTO adms_payroll_document_access_logs
            (employee_payroll_document_id, owner_user_id, viewer_user_id, document_type, reference_year, reference_month, delivery_mode, ip, user_agent, created_at)
            VALUES (:doc_id, :owner, :viewer, :dtype, :ry, :rm, :mode, :ip, :ua, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        if ($documentId !== null && $documentId > 0) {
            $stmt->bindValue(':doc_id', $documentId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':doc_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':owner', $ownerUserId, PDO::PARAM_INT);
        $stmt->bindValue(':viewer', $viewerUserId, PDO::PARAM_INT);
        $stmt->bindValue(':dtype', $documentType, PDO::PARAM_STR);
        $stmt->bindValue(':ry', $referenceYear, PDO::PARAM_INT);
        if ($referenceMonth === null) {
            $stmt->bindValue(':rm', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':rm', $referenceMonth, PDO::PARAM_INT);
        }
        $stmt->bindValue(':mode', $mode, PDO::PARAM_STR);
        $stmt->bindValue(':ip', mb_substr($ip, 0, 45), PDO::PARAM_STR);
        $stmt->bindValue(':ua', $ua, $ua === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }

    public function getImportBatchById(int $batchId): ?array
    {
        if ($batchId <= 0) {
            return null;
        }
        $sql = 'SELECT b.*, u.name AS created_by_name
                FROM adms_payroll_import_batches b
                LEFT JOIN adms_users u ON u.id = b.created_by_user_id
                WHERE b.id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $batchId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Documentos gerados neste lote, com titular (para relatório RH).
     *
     * @return list<array<string, mixed>>
     */
    public function listDocumentsWithOwnerForBatch(int $batchId): array
    {
        if ($batchId <= 0) {
            return [];
        }
        $sql = 'SELECT d.*, u.name AS owner_name, u.email AS owner_email
                FROM adms_employee_payroll_documents d
                INNER JOIN adms_users u ON u.id = d.user_id
                WHERE d.import_batch_id = :bid
                ORDER BY u.name ASC, u.email ASC, d.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':bid', $batchId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Registos de acesso ao PDF (stream) para trilha de auditoria.
     *
     * @param list<int> $documentIds
     * @return list<array<string, mixed>>
     */
    public function listAccessLogsForDocumentIds(array $documentIds): array
    {
        if ($documentIds === [] || !$this->hasPayrollAccessLogsTable()) {
            return [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $documentIds), fn (int $i) => $i > 0)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', $ids);
        $sql = "SELECT * FROM adms_payroll_document_access_logs
                WHERE employee_payroll_document_id IN ({$in})
                ORDER BY created_at ASC, id ASC";
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawPayrollImportBatchRow(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_payroll_import_batches WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawPayrollDocumentRow(int $id): ?array
    {
        return $this->getById($id);
    }

    private function hasPayrollAccessLogsTable(): bool
    {
        try {
            $this->getConnection()->query('SELECT 1 FROM adms_payroll_document_access_logs LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
