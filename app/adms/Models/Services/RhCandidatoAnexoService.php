<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Upload, resolução de caminho e exclusão física de anexos de candidatos (currículos).
 *
 * Caminho relativo no banco: rh_candidatos/<id>/<arquivo>
 * Preferência de gravação: {APP_ROOT}/storage/private/rh_candidatos/<id>/<arquivo>
 * Leitura dual (Expand/Contract): privado primeiro; legado em public/adms/uploads.
 */
final class RhCandidatoAnexoService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

    /** @var array<string, list<string>> */
    private const MIME_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ];

    public static function projectRoot(): string
    {
        return (defined('APP_ROOT') && APP_ROOT !== '')
            ? APP_ROOT
            : dirname(__DIR__, 4);
    }

    /** Diretório canónico (novos uploads). */
    public static function privateBaseDir(): string
    {
        return self::projectRoot()
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'private'
            . DIRECTORY_SEPARATOR . 'rh_candidatos';
    }

    /** Diretório legado (dual-read). */
    public static function legacyPublicBaseDir(): string
    {
        return self::projectRoot()
            . DIRECTORY_SEPARATOR . 'public'
            . DIRECTORY_SEPARATOR . 'adms'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'rh_candidatos';
    }

    /** @deprecated Use privateBaseDir() / legacyPublicBaseDir() */
    public static function uploadsBaseDir(): string
    {
        return self::projectRoot()
            . DIRECTORY_SEPARATOR . 'public'
            . DIRECTORY_SEPARATOR . 'adms'
            . DIRECTORY_SEPARATOR . 'uploads';
    }

    /**
     * Normaliza o caminho relativo gravado em rh_candidatos_anexos.
     */
    public static function normalizeRelativePath(string $arquivoCaminho): ?string
    {
        $relative = str_replace('\\', '/', trim($arquivoCaminho));
        $relative = ltrim($relative, '/');

        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        foreach ([
            'public/adms/uploads/',
            'storage/private/',
        ] as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                $relative = substr($relative, strlen($prefix));
            }
        }

        if (!str_starts_with($relative, 'rh_candidatos/')) {
            return null;
        }

        return $relative;
    }

    /**
     * Resolve o caminho físico (privado primeiro, depois legado público).
     */
    public static function resolvePhysicalPath(string $arquivoCaminho): ?string
    {
        $relative = self::normalizeRelativePath($arquivoCaminho);
        if ($relative === null) {
            return null;
        }

        $suffix = substr($relative, strlen('rh_candidatos/'));
        $candidates = [
            self::privateBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $suffix),
            self::legacyPublicBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $suffix),
        ];

        foreach ($candidates as $candidate) {
            $base = str_starts_with($candidate, self::privateBaseDir())
                ? realpath(self::privateBaseDir())
                : realpath(self::legacyPublicBaseDir());

            if ($base === false) {
                continue;
            }

            $full = realpath($candidate);
            if ($full !== false && str_starts_with($full, $base) && is_file($full)) {
                return $full;
            }
        }

        return null;
    }

    /**
     * Remove o arquivo físico (privado e/ou legado). Retorna true se removido ou inexistente.
     */
    public static function deletePhysicalFile(string $arquivoCaminho): bool
    {
        $relative = self::normalizeRelativePath($arquivoCaminho);
        if ($relative === null) {
            return true;
        }

        $suffix = substr($relative, strlen('rh_candidatos/'));
        $ok = true;
        foreach ([
            self::privateBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $suffix),
            self::legacyPublicBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $suffix),
        ] as $path) {
            if (!is_file($path)) {
                continue;
            }
            if (!@unlink($path)) {
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * @param array<string, mixed> $file Item de $_FILES
     * @return array{ok: true, relative_path: string, original_name: string}|array{ok: false, error: string}
     */
    public static function storeCurriculo(int $candidatoId, array $file): array
    {
        $phpError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($phpError === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Nenhum arquivo enviado.'];
        }
        if ($phpError === UPLOAD_ERR_INI_SIZE || $phpError === UPLOAD_ERR_FORM_SIZE) {
            return ['ok' => false, 'error' => 'Arquivo maior que o permitido (máximo 10 MB).'];
        }
        if ($phpError !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Falha no envio do currículo (código ' . $phpError . ').'];
        }

        $originalName = basename((string) ($file['name'] ?? 'curriculo'));
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '' || !isset(self::MIME_BY_EXTENSION[$ext])) {
            return ['ok' => false, 'error' => 'Tipo de arquivo não permitido. Use PDF ou DOC/DOCX.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return ['ok' => false, 'error' => 'Arquivo de currículo vazio.'];
        }
        if ($size > self::MAX_FILE_SIZE) {
            return ['ok' => false, 'error' => 'Arquivo maior que o permitido (máximo 10 MB).'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Upload inválido. Selecione o arquivo novamente.'];
        }

        $mime = self::detectMimeType($tmp);
        if (!in_array($mime, self::MIME_BY_EXTENSION[$ext], true)) {
            if (!($mime === 'application/octet-stream' && in_array($ext, ['doc', 'docx'], true))) {
                return [
                    'ok' => false,
                    'error' => "O conteúdo do arquivo não corresponde à extensão .{$ext}.",
                ];
            }
        }

        if (self::containsEmbeddedScript($tmp)) {
            return ['ok' => false, 'error' => 'Conteúdo do arquivo rejeitado por segurança.'];
        }

        $baseDir = self::privateBaseDir();
        if (!is_dir($baseDir) && !mkdir($baseDir, 0770, true) && !is_dir($baseDir)) {
            return ['ok' => false, 'error' => 'Não foi possível preparar o diretório privado de currículos.'];
        }

        $candDir = $baseDir . DIRECTORY_SEPARATOR . $candidatoId;
        if (!is_dir($candDir) && !mkdir($candDir, 0770, true) && !is_dir($candDir)) {
            return ['ok' => false, 'error' => 'Não foi possível preparar o diretório do candidato.'];
        }

        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $candDir . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($tmp, $destPath)) {
            return ['ok' => false, 'error' => 'Erro ao salvar arquivo de currículo.'];
        }

        return [
            'ok' => true,
            'relative_path' => 'rh_candidatos/' . $candidatoId . '/' . $safeName,
            'original_name' => $originalName,
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

    private static function containsEmbeddedScript(string $path): bool
    {
        $head = file_get_contents($path, false, null, 0, 512);
        if ($head === false) {
            return true;
        }

        $snippet = strtolower($head);

        return str_contains($snippet, '<?php')
            || str_contains($snippet, '<?=')
            || str_contains($snippet, '<script');
    }
}
