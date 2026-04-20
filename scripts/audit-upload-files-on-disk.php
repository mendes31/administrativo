<?php

declare(strict_types=1);

/**
 * Audita referências a ficheiros no MySQL e verifica se existem no disco (após restore de backup).
 *
 * Uso (na raiz do projeto ou a partir de qualquer pasta):
 *   php scripts/audit-upload-files-on-disk.php
 *   php scripts/audit-upload-files-on-disk.php --json
 *   php scripts/audit-upload-files-on-disk.php --no-detail   (só resumo + contagens por tabela)
 *
 * Requer .env com DB_HOST, DB_NAME, DB_USER, DB_PASS.
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(APP_ROOT);
$dotenv->load();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

$json = in_array('--json', $argv ?? [], true);
$noDetail = in_array('--no-detail', $argv ?? [], true);

/**
 * @param list<array{context: string, path: string, expected: string}> $missing
 * @return array<string, int>
 */
function summarize_missing_by_table(array $missing): array
{
    $out = [];
    foreach ($missing as $row) {
        $ctx = (string) ($row['context'] ?? '');
        if (preg_match('/^([a-z0-9_]+)\./i', $ctx, $m)) {
            $tbl = $m[1];
        } else {
            $tbl = '_desconhecido';
        }
        $out[$tbl] = ($out[$tbl] ?? 0) + 1;
    }
    ksort($out);

    return $out;
}

$dsn = 'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_NAME'] ?? '') . ';charset=utf8mb4';
$pdo = new PDO($dsn, $_ENV['DB_USER'] ?? '', $_ENV['DB_PASS'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

/**
 * Converte caminho relativo do projeto ou relativo a public/adms/uploads/ num caminho absoluto no disco.
 */
function resolve_project_path(string $root, string $raw, ?int $userIdForBareImage = null): string
{
    $raw = trim(str_replace('\\', '/', $raw));
    if ($raw === '') {
        return '';
    }

    $root = rtrim(str_replace('\\', '/', $root), '/');

    if (str_starts_with($raw, 'storage/')) {
        return $root . '/' . $raw;
    }

    if (str_starts_with($raw, 'public/')) {
        return $root . '/' . $raw;
    }

    $uploads = $root . '/public/adms/uploads/';
    if (str_contains($raw, '/')) {
        return $uploads . $raw;
    }

    if ($userIdForBareImage !== null && $userIdForBareImage > 0) {
        return $uploads . 'users/' . $userIdForBareImage . '/' . $raw;
    }

    return $uploads . $raw;
}

function is_missing(string $absPath): bool
{
    $absPath = str_replace('/', DIRECTORY_SEPARATOR, $absPath);

    return !is_file($absPath);
}

/**
 * @param list<array{context: string, path: string}> $missing
 */
function record_missing(array &$missing, string $context, string $rawPath, ?int $userId = null): void
{
    $rawPath = trim($rawPath);
    if ($rawPath === '') {
        return;
    }
    $abs = resolve_project_path(APP_ROOT, $rawPath, $userId);
    if ($abs === '' || !is_missing($abs)) {
        return;
    }
    $missing[] = ['context' => $context, 'path' => $rawPath, 'expected' => $abs];
}

/** @return bool */
function table_exists(PDO $pdo, string $table): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    if (!is_string($db) || $db === '') {
        return false;
    }
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?'
    );
    $st->execute([$db, $table]);

    return (int) $st->fetchColumn() > 0;
}

$missing = [];

try {
    if (table_exists($pdo, 'adms_timeline_posts')) {
        $rows = $pdo->query(
            'SELECT id, image_path, video_path FROM adms_timeline_posts
             WHERE (image_path IS NOT NULL AND TRIM(image_path) <> "")
                OR (video_path IS NOT NULL AND TRIM(video_path) <> "")'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if (!empty($r['image_path'])) {
                record_missing($missing, "adms_timeline_posts.id={$id} image_path", (string) $r['image_path']);
            }
            if (!empty($r['video_path'])) {
                record_missing($missing, "adms_timeline_posts.id={$id} video_path", (string) $r['video_path']);
            }
        }
    }

    if (table_exists($pdo, 'adms_timeline_post_images')) {
        $rows = $pdo->query(
            'SELECT post_id, image_path FROM adms_timeline_post_images WHERE image_path IS NOT NULL AND TRIM(image_path) <> ""'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $pid = (int) ($r['post_id'] ?? 0);
            record_missing($missing, "adms_timeline_post_images post_id={$pid}", (string) $r['image_path']);
        }
    }

    if (table_exists($pdo, 'adms_users')) {
        $rows = $pdo->query(
            'SELECT id, image FROM adms_users WHERE image IS NOT NULL AND TRIM(image) <> ""
             AND LOWER(TRIM(image)) NOT IN ("icon_user.png", "icon_user.PNG")'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $uid = (int) ($r['id'] ?? 0);
            $img = (string) $r['image'];
            if (str_contains($img, '/') || str_contains($img, '\\')) {
                record_missing($missing, "adms_users.id={$uid} image", $img);
            } else {
                record_missing($missing, "adms_users.id={$uid} image", $img, $uid);
            }
        }
    }

    if (table_exists($pdo, 'adms_meeting_rooms')) {
        $rows = $pdo->query(
            'SELECT id, image FROM adms_meeting_rooms WHERE image IS NOT NULL AND TRIM(image) <> ""'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            record_missing($missing, "adms_meeting_rooms.id={$id} image", (string) $r['image']);
        }
    }

    if (table_exists($pdo, 'adms_informativos')) {
        $rows = $pdo->query(
            'SELECT id, imagem, anexo FROM adms_informativos
             WHERE (imagem IS NOT NULL AND TRIM(imagem) <> "") OR (anexo IS NOT NULL AND TRIM(anexo) <> "")'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if (!empty($r['imagem'])) {
                record_missing($missing, "adms_informativos.id={$id} imagem", (string) $r['imagem']);
            }
            if (!empty($r['anexo'])) {
                record_missing($missing, "adms_informativos.id={$id} anexo", (string) $r['anexo']);
            }
        }
    }

    if (table_exists($pdo, 'adms_policies')) {
        $rows = $pdo->query(
            'SELECT id, imagem, anexo FROM adms_policies
             WHERE (imagem IS NOT NULL AND TRIM(imagem) <> "") OR (anexo IS NOT NULL AND TRIM(anexo) <> "")'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if (!empty($r['imagem'])) {
                record_missing($missing, "adms_policies.id={$id} imagem", (string) $r['imagem']);
            }
            if (!empty($r['anexo'])) {
                record_missing($missing, "adms_policies.id={$id} anexo", (string) $r['anexo']);
            }
        }
    }

    if (table_exists($pdo, 'adms_spreadsheets')) {
        $rows = $pdo->query(
            'SELECT id, file_path FROM adms_spreadsheets WHERE file_path IS NOT NULL AND TRIM(file_path) <> ""'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            record_missing($missing, "adms_spreadsheets.id={$id} file_path", (string) $r['file_path']);
        }
    }

    if (table_exists($pdo, 'crm_documents')) {
        $rows = $pdo->query(
            'SELECT id, file_path FROM crm_documents WHERE file_path IS NOT NULL AND TRIM(file_path) <> ""'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            $fp = str_replace('\\', '/', trim((string) $r['file_path']));
            record_missing($missing, "crm_documents.id={$id} file_path", $fp);
        }
    }

    if (table_exists($pdo, 'rh_candidatos_anexos')) {
        $rows = $pdo->query(
            'SELECT id, arquivo_caminho FROM rh_candidatos_anexos WHERE arquivo_caminho IS NOT NULL AND TRIM(arquivo_caminho) <> ""'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            record_missing($missing, "rh_candidatos_anexos.id={$id} arquivo_caminho", (string) $r['arquivo_caminho']);
        }
    }

    if (table_exists($pdo, 'adms_employee_payroll_documents')) {
        $rows = $pdo->query(
            'SELECT id, storage_path, signed_bundle_storage_path FROM adms_employee_payroll_documents'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if (!empty($r['storage_path'])) {
                record_missing($missing, "adms_employee_payroll_documents.id={$id} storage_path", (string) $r['storage_path']);
            }
            if (!empty($r['signed_bundle_storage_path'])) {
                record_missing($missing, "adms_employee_payroll_documents.id={$id} signed_bundle_storage_path", (string) $r['signed_bundle_storage_path']);
            }
        }
    }

    if (table_exists($pdo, 'lgpd_consentimento_arquivos')) {
        $rows = $pdo->query(
            'SELECT id, arquivo_path FROM lgpd_consentimento_arquivos WHERE arquivo_path IS NOT NULL AND TRIM(arquivo_path) <> ""'
        )->fetchAll() ?: [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            record_missing($missing, "lgpd_consentimento_arquivos.id={$id} arquivo_path", (string) $r['arquivo_path']);
        }
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$summaryByTable = summarize_missing_by_table($missing);

if ($json) {
    $payload = [
        'ok' => true,
        'missing_count' => count($missing),
        'summary_by_table' => $summaryByTable,
        'missing' => $missing,
    ];
    if ($noDetail) {
        unset($payload['missing']);
    }
    echo json_encode(
        $payload,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) . PHP_EOL;
    exit(count($missing) > 0 ? 2 : 0);
}

echo 'Auditoria de ficheiros (APP_ROOT=' . APP_ROOT . ')' . PHP_EOL;
echo 'Referências em falta no disco: ' . count($missing) . PHP_EOL;
if ($summaryByTable !== []) {
    echo PHP_EOL . 'Resumo por tabela:' . PHP_EOL;
    foreach ($summaryByTable as $tbl => $cnt) {
        echo '  - ' . $tbl . ': ' . $cnt . PHP_EOL;
    }
    echo PHP_EOL;
}
if (!$noDetail) {
    foreach ($missing as $row) {
        echo '- [' . $row['context'] . '] ' . $row['path'] . PHP_EOL;
        echo '  esperado: ' . $row['expected'] . PHP_EOL;
    }
}

exit(count($missing) > 0 ? 2 : 0);
