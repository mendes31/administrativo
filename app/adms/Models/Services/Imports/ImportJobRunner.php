<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\ImportJobsRepository;

final class ImportJobRunner
{
    public const REPORT_LIMIT = 500;

    /**
     * @param array<string, int> $fieldToIndex
     * @return array{created: int, updated: int, skipped: int, errors: int, report: list<array<string, mixed>>}
     */
    public function run(int $jobId, array $fieldToIndex, string $keyField): array
    {
        $repo = new ImportJobsRepository();
        $job = $repo->getById($jobId);
        if ($job === null) {
            throw new \RuntimeException('Job de importação não encontrado.');
        }
        $profile = ImportProfileCatalog::get((string) $job['profile_key']);
        if ($profile === null) {
            throw new \RuntimeException('Perfil de importação inválido.');
        }
        $path = (string) ($job['stored_path'] ?? '');
        if ($path === '' || !is_file($path)) {
            throw new \RuntimeException('Arquivo do job não está mais disponível.');
        }

        $operation = (string) $job['operation'];
        $emptyPolicy = (string) ($job['empty_policy'] ?? 'skip');
        $dryRun = !empty($job['dry_run']);
        $delimiter = (string) ($job['delimiter'] ?? ';');

        $repo->update($jobId, [
            'status' => 'running',
            'mapping_json' => json_encode($fieldToIndex, JSON_UNESCAPED_UNICODE),
            'key_field' => $keyField,
            'started_at' => date('Y-m-d H:i:s'),
            'error_message' => null,
        ]);

        $reader = new SpreadsheetImportReader();
        $rows = $reader->readDataRows($path, $delimiter);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $report = [];
        $line = 1;

        foreach ($rows as $row) {
            $line++;
            $mapped = [];
            foreach ($fieldToIndex as $field => $idx) {
                $mapped[$field] = trim((string) ($row[$idx] ?? ''));
            }
            $mapped['_key_field'] = $keyField;
            try {
                $result = $profile->processRow($mapped, $operation, $emptyPolicy, $dryRun);
            } catch (\Throwable $e) {
                $result = ['action' => 'error', 'message' => $e->getMessage(), 'key' => $mapped[$keyField] ?? ''];
            }
            $action = (string) ($result['action'] ?? 'error');
            match ($action) {
                'created', 'would_create' => $created++,
                'updated', 'would_update' => $updated++,
                'skipped' => $skipped++,
                default => $errors++,
            };
            if (count($report) < self::REPORT_LIMIT) {
                $report[] = [
                    'linha' => $line,
                    'acao' => $action,
                    'chave' => (string) ($result['key'] ?? ''),
                    'msg' => (string) ($result['message'] ?? ''),
                ];
            }
        }

        $stats = compact('created', 'updated', 'skipped', 'errors');
        $stats['rows'] = count($rows);
        $repo->update($jobId, [
            'status' => $errors > 0 && $created === 0 && $updated === 0 ? 'failed' : 'done',
            'stats_json' => json_encode($stats, JSON_UNESCAPED_UNICODE),
            'report_json' => json_encode($report, JSON_UNESCAPED_UNICODE),
            'finished_at' => date('Y-m-d H:i:s'),
        ]);

        return array_merge($stats, ['report' => $report]);
    }

    /**
     * Reexecuta um job de simulação já mapeado, desta vez gravando no banco.
     *
     * @return array{created: int, updated: int, skipped: int, errors: int, report: list<array<string, mixed>>}
     */
    public function commitSimulation(int $jobId): array
    {
        $repo = new ImportJobsRepository();
        $job = $repo->getById($jobId);
        if ($job === null) {
            throw new \RuntimeException('Job de importação não encontrado.');
        }
        if (empty($job['dry_run'])) {
            throw new \RuntimeException('Esta execução já foi gravada no banco.');
        }
        $status = (string) ($job['status'] ?? '');
        if (!in_array($status, ['done', 'failed'], true)) {
            throw new \RuntimeException('Aguarde a simulação terminar antes de registrar.');
        }

        $mapping = json_decode((string) ($job['mapping_json'] ?? ''), true);
        $keyField = (string) ($job['key_field'] ?? '');
        if (!is_array($mapping) || $mapping === [] || $keyField === '') {
            throw new \RuntimeException('Não há mapeamento salvo para registrar esta simulação.');
        }
        $fieldToIndex = [];
        foreach ($mapping as $field => $idx) {
            $fieldToIndex[(string) $field] = (int) $idx;
        }

        $path = (string) ($job['stored_path'] ?? '');
        if ($path === '' || !is_file($path)) {
            throw new \RuntimeException('Arquivo do job não está mais disponível. Envie a planilha de novo.');
        }

        $repo->update($jobId, ['dry_run' => 0, 'error_message' => null]);

        return $this->run($jobId, $fieldToIndex, $keyField);
    }
}
