<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
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

        return (int)$this->getConnection()->lastInsertId();
    }

    public function updateBatchStats(int $batchId, int $matched, int $unmatched, ?string $logJson): void
    {
        $sql = 'UPDATE adms_payroll_import_batches SET pages_matched = :m, pages_unmatched = :u, log_json = :log WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':m', $matched, PDO::PARAM_INT);
        $stmt->bindValue(':u', $unmatched, PDO::PARAM_INT);
        $stmt->bindValue(':log', $logJson, PDO::PARAM_STR);
        $stmt->bindValue(':id', $batchId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Remove documento anterior do mesmo tipo/ref (substituição na reimportação).
     */
    public function deleteExistingForUserRef(int $userId, string $documentType, int $year, ?int $month): void
    {
        $sql = 'SELECT id, storage_path FROM adms_employee_payroll_documents
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
        }
    }

    /**
     * Remove apenas documentos cujo lote tenha o mesmo nome de ficheiro original **e** a mesma
     * referência (ano/mês) no lote — reimportação/correção do mesmo PDF para o mesmo período.
     * Nome igual com referência diferente não substitui; nome diferente acumula (ex.: quinzenal + mensal).
     */
    public function deleteExistingForUserRefSameOriginalFilename(
        int $userId,
        string $documentType,
        int $year,
        ?int $month,
        string $originalFilename
    ): void {
        $orig = trim($originalFilename);
        if ($orig === '') {
            $orig = 'documento.pdf';
        }
        $sql = 'SELECT d.id, d.storage_path FROM adms_employee_payroll_documents d
                INNER JOIN adms_payroll_import_batches b ON b.id = d.import_batch_id
                WHERE d.user_id = :uid AND d.document_type = :dt AND d.reference_year = :y
                AND (d.reference_month <=> :m)
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
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
                'DELETE d FROM adms_employee_payroll_documents d
                INNER JOIN adms_payroll_import_batches b ON b.id = d.import_batch_id
                WHERE d.user_id = :uid AND d.document_type = :dt AND d.reference_year = :y
                AND (d.reference_month <=> :m)
                AND b.reference_year = :by
                AND (b.reference_month <=> :bm)
                AND LOWER(TRIM(b.original_filename)) = LOWER(TRIM(:orig))'
            );
            $del->bindValue(':uid', $userId, PDO::PARAM_INT);
            $del->bindValue(':dt', $documentType, PDO::PARAM_STR);
            $del->bindValue(':y', $year, PDO::PARAM_INT);
            if ($month === null) {
                $del->bindValue(':m', null, PDO::PARAM_NULL);
            } else {
                $del->bindValue(':m', $month, PDO::PARAM_INT);
            }
            $del->bindValue(':by', $year, PDO::PARAM_INT);
            if ($month === null) {
                $del->bindValue(':bm', null, PDO::PARAM_NULL);
            } else {
                $del->bindValue(':bm', $month, PDO::PARAM_INT);
            }
            $del->bindValue(':orig', $orig, PDO::PARAM_STR);
            $del->execute();
        }
    }

    public function insertDocument(array $row): int
    {
        $sql = 'INSERT INTO adms_employee_payroll_documents
            (user_id, import_batch_id, document_type, reference_year, reference_month, title, storage_path, file_size, net_amount, cpf_normalized, page_from, page_to, created_at)
            VALUES (:user_id, :import_batch_id, :document_type, :reference_year, :reference_month, :title, :storage_path, :file_size, :net_amount, :cpf_normalized, :page_from, :page_to, NOW())';
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
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
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
        $sql = 'SELECT d.* FROM adms_employee_payroll_documents d WHERE ' . implode(' AND ', $where) . ' ORDER BY d.reference_year DESC, d.reference_month DESC, d.id DESC';
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
        $stmt = $conn->prepare('SELECT id, storage_path FROM adms_employee_payroll_documents WHERE import_batch_id = :bid');
        $stmt->bindValue(':bid', $batchId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $conn->beginTransaction();
        try {
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
                $delDocs = $conn->prepare('DELETE FROM adms_employee_payroll_documents WHERE import_batch_id = :bid');
                $delDocs->bindValue(':bid', $batchId, PDO::PARAM_INT);
                $delDocs->execute();
            }
            $delBatch = $conn->prepare('DELETE FROM adms_payroll_import_batches WHERE id = :id');
            $delBatch->bindValue(':id', $batchId, PDO::PARAM_INT);
            $delBatch->execute();
            $conn->commit();

            return $delBatch->rowCount() > 0;
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
}
