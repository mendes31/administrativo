<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Upload de documentos de pré-admissão (candidato ou RH).
 * Caminho relativo: rh_pre_admissao/<ofertaId>/<arquivo>
 * Gravação: storage/private/rh_pre_admissao/
 */
final class RhPreAdmissaoUploadService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /** @var array<string, list<string>> */
    private const MIME_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ];

    public static function projectRoot(): string
    {
        return (defined('APP_ROOT') && APP_ROOT !== '')
            ? APP_ROOT
            : dirname(__DIR__, 4);
    }

    public static function privateBaseDir(): string
    {
        return self::projectRoot()
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'private'
            . DIRECTORY_SEPARATOR . 'rh_pre_admissao';
    }

    public static function normalizeRelativePath(string $arquivoCaminho): ?string
    {
        $relative = str_replace('\\', '/', trim($arquivoCaminho));
        $relative = ltrim($relative, '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }
        if (str_starts_with($relative, 'storage/private/')) {
            $relative = substr($relative, strlen('storage/private/'));
        }
        if (!str_starts_with($relative, 'rh_pre_admissao/')) {
            return null;
        }

        return $relative;
    }

    public static function resolvePhysicalPath(string $arquivoCaminho): ?string
    {
        $relative = self::normalizeRelativePath($arquivoCaminho);
        if ($relative === null) {
            return null;
        }
        $suffix = substr($relative, strlen('rh_pre_admissao/'));
        $base = realpath(self::privateBaseDir());
        if ($base === false) {
            return null;
        }
        $full = realpath(self::privateBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $suffix));
        if ($full !== false && str_starts_with($full, $base) && is_file($full)) {
            return $full;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $file Item de $_FILES
     * @return array{ok: true, relative_path: string, original_name: string, mime: string, size: int}|array{ok: false, error: string}
     */
    public static function store(int $ofertaId, array $file): array
    {
        $phpError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($phpError === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Nenhum arquivo enviado.'];
        }
        if ($phpError === UPLOAD_ERR_INI_SIZE || $phpError === UPLOAD_ERR_FORM_SIZE) {
            return ['ok' => false, 'error' => 'Arquivo maior que o permitido (máximo 10 MB).'];
        }
        if ($phpError !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Falha no envio do arquivo (código ' . $phpError . ').'];
        }

        $originalName = basename((string) ($file['name'] ?? 'documento'));
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '' || !isset(self::MIME_BY_EXTENSION[$ext])) {
            return ['ok' => false, 'error' => 'Tipo não permitido. Use PDF, JPG, PNG ou DOC/DOCX.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            return ['ok' => false, 'error' => 'Arquivo inválido ou maior que 10 MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Upload inválido. Selecione o arquivo novamente.'];
        }

        $mime = self::detectMimeType($tmp);
        if (!in_array($mime, self::MIME_BY_EXTENSION[$ext], true)) {
            if (!($mime === 'application/octet-stream' && in_array($ext, ['doc', 'docx', 'jpg', 'jpeg'], true))) {
                return ['ok' => false, 'error' => "O conteúdo do arquivo não corresponde à extensão .{$ext}."];
            }
        }

        $baseDir = self::privateBaseDir();
        if (!is_dir($baseDir) && !mkdir($baseDir, 0770, true) && !is_dir($baseDir)) {
            return ['ok' => false, 'error' => 'Não foi possível preparar o diretório de documentos.'];
        }

        $ofertaDir = $baseDir . DIRECTORY_SEPARATOR . $ofertaId;
        if (!is_dir($ofertaDir) && !mkdir($ofertaDir, 0770, true) && !is_dir($ofertaDir)) {
            return ['ok' => false, 'error' => 'Não foi possível preparar o diretório da oferta.'];
        }

        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $ofertaDir . DIRECTORY_SEPARATOR . $safeName;
        if (!move_uploaded_file($tmp, $destPath)) {
            return ['ok' => false, 'error' => 'Erro ao salvar o arquivo.'];
        }

        return [
            'ok' => true,
            'relative_path' => 'rh_pre_admissao/' . $ofertaId . '/' . $safeName,
            'original_name' => $originalName,
            'mime' => $mime,
            'size' => $size,
        ];
    }

    private static function detectMimeType(string $path): string
    {
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            if (is_string($mime) && $mime !== '') {
                return strtolower($mime);
            }
        }
        $fallback = mime_content_type($path);

        return is_string($fallback) && $fallback !== '' ? strtolower($fallback) : 'application/octet-stream';
    }
}
