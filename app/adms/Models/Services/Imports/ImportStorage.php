<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Helpers\SstEpiImagemHelper;

final class ImportStorage
{
    public static function directory(): string
    {
        $dir = dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'imports';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    public static function pathForJob(int $jobId): string
    {
        return self::directory() . DIRECTORY_SEPARATOR . $jobId . '.csv';
    }

    public static function assetsDirForJob(int $jobId): string
    {
        return self::directory() . DIRECTORY_SEPARATOR . $jobId . '.assets';
    }

    /**
     * Extrai só imagens (jpg/png/gif/webp) de um ZIP para a pasta do job.
     */
    public static function extractImageZip(string $zipPath, int $jobId): int
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('O servidor não consegue ler ZIP (extensão ZipArchive).');
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Não foi possível abrir o ZIP das fotos.');
        }
        $dest = self::assetsDirForJob($jobId);
        if (!is_dir($dest) && !mkdir($dest, 0775, true) && !is_dir($dest)) {
            $zip->close();
            throw new \RuntimeException('Não foi possível criar a pasta das fotos da importação.');
        }
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $copied = 0;
        $maxFiles = 400;
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if ($copied >= $maxFiles) {
                    break;
                }
                $stat = $zip->statIndex($i);
                $entryName = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
                if ($entryName === '' || str_ends_with($entryName, '/') || str_contains($entryName, '..')) {
                    continue;
                }
                $decoded = self::decodeZipEntryName($entryName);
                $ext = strtolower((string) pathinfo($decoded, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed, true)) {
                    continue;
                }
                $size = (int) ($stat['size'] ?? 0);
                if ($size <= 0 || $size > 5 * 1024 * 1024) {
                    continue;
                }
                $base = SstEpiImagemHelper::assetMatchKey(basename($decoded));
                if ($base === '.' . $ext || $base === '.' || $base === '') {
                    $base = 'foto' . $i . '.' . $ext;
                }
                $target = $dest . DIRECTORY_SEPARATOR . $base;
                $stream = $zip->getStream($entryName);
                if ($stream === false && $decoded !== $entryName) {
                    $stream = $zip->getStream($decoded);
                }
                if ($stream === false) {
                    continue;
                }
                $out = fopen($target, 'wb');
                if ($out === false) {
                    fclose($stream);
                    continue;
                }
                stream_copy_to_stream($stream, $out);
                fclose($out);
                fclose($stream);
                $copied++;
            }
        } finally {
            $zip->close();
        }
        if ($copied < 1) {
            throw new \RuntimeException('O ZIP não contém fotos JPG, PNG, GIF ou WEBP (máx. 5 MB cada).');
        }

        return $copied;
    }

    private static function decodeZipEntryName(string $name): string
    {
        if (mb_check_encoding($name, 'UTF-8') && !preg_match('/[\x80-\xFF]/', $name)) {
            return $name;
        }
        if (mb_check_encoding($name, 'UTF-8')) {
            return $name;
        }
        foreach (['CP437', 'Windows-1252', 'ISO-8859-1'] as $from) {
            $converted = @iconv($from, 'UTF-8//IGNORE', $name);
            if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                return str_replace('\\', '/', $converted);
            }
        }

        return $name;
    }
}
