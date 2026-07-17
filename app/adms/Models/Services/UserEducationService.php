<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\UserEducationHelper;
use App\adms\Models\Repository\UserEducationsRepository;

final class UserEducationService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /** @var array<string, list<string>> */
    private const ALLOWED_FILES = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/octet-stream',
        ],
    ];

    public function __construct(private readonly UserEducationsRepository $repository = new UserEducationsRepository())
    {
    }

    /**
     * @param array<string|int, mixed> $rows
     * @param array<string, mixed>|null $files
     * @return list<string>
     */
    public function validateRows(array $rows, ?array $files = null): array
    {
        $errors = [];
        foreach ($rows as $key => $raw) {
            if (!is_array($raw) || !empty($raw['delete'])) {
                continue;
            }
            if ($this->isBlankNewRow($raw)) {
                continue;
            }
            $label = 'Formação #' . ((int) $key + 1);
            if (UserEducationHelper::normalizeType($raw['tipo'] ?? null) === null) {
                $errors[] = "{$label}: selecione um tipo válido.";
            }
            if (trim((string) ($raw['curso'] ?? '')) === '') {
                $errors[] = "{$label}: informe o nome do curso/formação.";
            }
            if (UserEducationHelper::normalizeStatus($raw['situacao'] ?? null) === null) {
                $errors[] = "{$label}: selecione uma situação válida.";
            }
            $startRaw = trim((string) ($raw['data_inicio'] ?? ''));
            $endRaw = trim((string) ($raw['data_conclusao'] ?? ''));
            $start = UserEducationHelper::normalizeDate($startRaw);
            $end = UserEducationHelper::normalizeDate($endRaw);
            if ($startRaw !== '' && $start === null) {
                $errors[] = "{$label}: data de início inválida.";
            }
            if ($endRaw !== '' && $end === null) {
                $errors[] = "{$label}: data de conclusão inválida.";
            }
            if ($start !== null && $end !== null && $end < $start) {
                $errors[] = "{$label}: a conclusão não pode ser anterior ao início.";
            }
            $hours = trim((string) ($raw['carga_horaria'] ?? ''));
            if ($hours !== '' && ((int) $hours <= 0 || (int) $hours > 100000)) {
                $errors[] = "{$label}: carga horária inválida.";
            }
            $file = $this->fileAt($files, (string) $key);
            $fileError = $this->validateFile($file);
            if ($fileError !== null) {
                $errors[] = "{$label}: {$fileError}";
            }
        }

        return $errors;
    }

    /**
     * @param array<string|int, mixed> $rows
     * @param array<string, mixed>|null $files
     */
    public function saveRows(int $userId, array $rows, ?array $files, ?int $actorId): void
    {
        foreach ($rows as $key => $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $id = (int) ($raw['id'] ?? 0);
            if (!empty($raw['delete'])) {
                if ($id > 0) {
                    $deleted = $this->repository->delete($id, $userId, $actorId);
                    if (is_array($deleted)) {
                        self::deleteStoredFile((string) ($deleted['comprovante_path'] ?? ''));
                    }
                }
                continue;
            }
            if ($this->isBlankNewRow($raw)) {
                continue;
            }

            $existing = $id > 0 ? $this->repository->getByIdForUser($id, $userId) : false;
            if ($id > 0 && !is_array($existing)) {
                throw new \RuntimeException('Uma das formações não pertence ao usuário informado.');
            }
            $data = $this->normalizeRow($raw);
            if (is_array($existing)) {
                foreach (['comprovante_path', 'comprovante_nome_original', 'comprovante_mime', 'comprovante_tamanho'] as $field) {
                    $data[$field] = $existing[$field] ?? null;
                }
            }
            $removeExistingFile = is_array($existing) && !empty($raw['remove_comprovante']);
            if ($removeExistingFile) {
                foreach (['comprovante_path', 'comprovante_nome_original', 'comprovante_mime', 'comprovante_tamanho'] as $field) {
                    $data[$field] = null;
                }
            }

            $newFile = $this->fileAt($files, (string) $key);
            $stored = null;
            if ($newFile !== null && (int) ($newFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $stored = $this->storeFile($userId, $newFile);
                $data = array_merge($data, $stored);
            }

            try {
                if ($id > 0) {
                    $this->repository->update($id, $userId, $data, $actorId);
                } else {
                    $this->repository->create($userId, $data, $actorId);
                }
            } catch (\Throwable $e) {
                if ($stored !== null) {
                    self::deleteStoredFile((string) ($stored['comprovante_path'] ?? ''));
                }
                throw $e;
            }

            if (($stored !== null || $removeExistingFile) && is_array($existing)) {
                self::deleteStoredFile((string) ($existing['comprovante_path'] ?? ''));
            }
        }
    }

    /** @param array<string, mixed> $raw @return array<string, mixed> */
    public function normalizeRow(array $raw): array
    {
        $hours = trim((string) ($raw['carga_horaria'] ?? ''));

        return [
            'tipo' => UserEducationHelper::normalizeType($raw['tipo'] ?? null),
            'curso' => mb_substr(trim((string) ($raw['curso'] ?? '')), 0, 191),
            'instituicao' => $this->nullableText($raw['instituicao'] ?? null, 191),
            'situacao' => UserEducationHelper::normalizeStatus($raw['situacao'] ?? null) ?? 'concluido',
            'data_inicio' => UserEducationHelper::normalizeDate($raw['data_inicio'] ?? null),
            'data_conclusao' => UserEducationHelper::normalizeDate($raw['data_conclusao'] ?? null),
            'carga_horaria' => $hours !== '' ? (int) $hours : null,
            'observacoes' => $this->nullableText($raw['observacoes'] ?? null, 2000),
            'comprovante_path' => null,
            'comprovante_nome_original' => null,
            'comprovante_mime' => null,
            'comprovante_tamanho' => null,
        ];
    }

    public static function absolutePath(string $relativePath): ?string
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        if ($relativePath === '' || str_contains($relativePath, '..')
            || !str_starts_with($relativePath, 'storage/private/user-educations/')) {
            return null;
        }
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);

        return rtrim($root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    public static function deleteStoredFile(string $relativePath): void
    {
        $absolute = self::absolutePath($relativePath);
        if ($absolute !== null && is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /** @param array<string, mixed> $raw */
    private function isBlankNewRow(array $raw): bool
    {
        return (int) ($raw['id'] ?? 0) === 0
            && trim((string) ($raw['tipo'] ?? '')) === ''
            && trim((string) ($raw['curso'] ?? '')) === ''
            && trim((string) ($raw['instituicao'] ?? '')) === '';
    }

    /** @param array<string, mixed>|null $files @return array<string, mixed>|null */
    private function fileAt(?array $files, string $key): ?array
    {
        if ($files === null || !isset($files['name']) || !is_array($files['name'])) {
            return null;
        }

        return [
            'name' => $files['name'][$key] ?? '',
            'type' => $files['type'][$key] ?? '',
            'tmp_name' => $files['tmp_name'][$key] ?? '',
            'error' => $files['error'][$key] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$key] ?? 0,
        ];
    }

    /** @param array<string, mixed>|null $file */
    private function validateFile(?array $file): ?string
    {
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'falha no envio do comprovante.';
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            return 'o comprovante deve ter até 10 MB.';
        }
        $name = basename((string) ($file['name'] ?? ''));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED_FILES[$ext])) {
            return 'formato não permitido; use PDF, JPG, PNG, WEBP, DOC ou DOCX.';
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return 'upload do comprovante inválido.';
        }
        $mime = $this->detectMime($tmp);
        if (!in_array($mime, self::ALLOWED_FILES[$ext], true)) {
            return "o conteúdo do comprovante não corresponde à extensão .{$ext}.";
        }

        return null;
    }

    /**
     * @param array<string, mixed> $file
     * @return array{comprovante_path: string, comprovante_nome_original: string, comprovante_mime: string, comprovante_tamanho: int}
     */
    private function storeFile(int $userId, array $file): array
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);
        $relativeDir = 'storage/private/user-educations/' . $userId;
        $dir = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new \RuntimeException('Não foi possível criar a pasta privada de comprovantes.');
        }
        $original = basename((string) $file['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $storedName = bin2hex(random_bytes(20)) . '.' . $ext;
        $absolute = $dir . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file((string) $file['tmp_name'], $absolute)) {
            throw new \RuntimeException('Não foi possível guardar o comprovante.');
        }

        return [
            'comprovante_path' => $relativeDir . '/' . $storedName,
            'comprovante_nome_original' => mb_substr($original, 0, 255),
            'comprovante_mime' => $this->detectMime($absolute),
            'comprovante_tamanho' => (int) filesize($absolute),
        ];
    }

    private function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return (string) ($finfo->file($path) ?: 'application/octet-stream');
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }
}
