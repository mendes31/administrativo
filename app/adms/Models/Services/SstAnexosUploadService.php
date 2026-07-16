<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SstAnexosRepository;

/**
 * Upload e exclusão de anexos SST (ASO, afastamento, acidente, etc.).
 */
class SstAnexosUploadService
{
    private const MAX_SIZE = 10 * 1024 * 1024;

    /** @var array<int, string> */
    private const ALLOWED_EXTS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx',
        'xls', 'xlsx', 'csv', 'txt', 'zip', 'rar', 'ppt', 'pptx',
    ];

    public function processUploads(string $entityType, int $entityId, string $fieldName = 'attachments', bool $imagesOnly = false): int
    {
        if ($entityId <= 0 || empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]['name'])) {
            return 0;
        }

        $uploadDir = $this->uploadDir($entityType, $entityId);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $repo = new SstAnexosRepository();
        $uploaded = 0;
        $fileCount = count($_FILES[$fieldName]['name']);
        $allowed = $imagesOnly
            ? ['jpg', 'jpeg', 'png', 'gif', 'webp']
            : self::ALLOWED_EXTS;

        for ($i = 0; $i < $fileCount; $i++) {
            if (($_FILES[$fieldName]['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = (string) ($_FILES[$fieldName]['name'][$i] ?? '');
            $tmpName = (string) ($_FILES[$fieldName]['tmp_name'][$i] ?? '');
            $fileSize = (int) ($_FILES[$fieldName]['size'][$i] ?? 0);
            $mimeType = (string) ($_FILES[$fieldName]['type'][$i] ?? '');

            if ($fileSize <= 0 || $fileSize > self::MAX_SIZE || $originalName === '' || $tmpName === '') {
                continue;
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                continue;
            }

            if ($imagesOnly && $mimeType !== '' && !str_starts_with(strtolower($mimeType), 'image/')) {
                continue;
            }

            $stamp = date('Ymd_His');
            $safeOriginal = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName) ?: ('foto.' . $ext);
            $safeName = $stamp . '_' . $i . '_' . $safeOriginal;
            $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

            if (!move_uploaded_file($tmpName, $destPath)) {
                continue;
            }

            $relativePath = 'storage/sst/attachments/' . $entityType . '/' . $entityId . '/' . $safeName;
            $newId = $repo->create([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'file_name' => $originalName,
                'file_path' => $relativePath,
                'mime_type' => $mimeType !== '' ? $mimeType : ('image/' . ($ext === 'jpg' ? 'jpeg' : $ext)),
                'file_size' => $fileSize,
            ]);

            if ($newId) {
                $uploaded++;
            }
        }

        return $uploaded;
    }

    /**
     * @param array<int, mixed> $ids
     */
    public function processDeletions(array $ids, string $entityType, int $entityId): void
    {
        if ($entityId <= 0 || $ids === []) {
            return;
        }

        $repo = new SstAnexosRepository();
        foreach ($ids as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0) {
                continue;
            }
            $anexo = $repo->getById($id);
            if (!$anexo || ($anexo['entity_type'] ?? '') !== $entityType || (int) ($anexo['entity_id'] ?? 0) !== $entityId) {
                continue;
            }
            $abs = $this->absolutePath((string) ($anexo['file_path'] ?? ''));
            if ($abs !== '' && is_file($abs)) {
                @unlink($abs);
            }
            $repo->delete($id);
        }
    }

    public function absolutePath(string $relativePath): string
    {
        $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $base = defined('APP_ROOT')
            ? APP_ROOT
            : rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');

        return $base . DIRECTORY_SEPARATOR . $relativePath;
    }

    private function uploadDir(string $entityType, int $entityId): string
    {
        return $this->storageBase() . DIRECTORY_SEPARATOR . $entityType . DIRECTORY_SEPARATOR . $entityId;
    }

    private function storageBase(): string
    {
        $base = defined('APP_ROOT')
            ? APP_ROOT
            : rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');

        return $base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sst' . DIRECTORY_SEPARATOR . 'attachments';
    }
}
