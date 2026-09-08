<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

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
}
