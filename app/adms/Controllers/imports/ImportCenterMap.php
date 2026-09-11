<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ImportJobsRepository;
use App\adms\Models\Services\Imports\ImportJobRunner;
use App\adms\Models\Services\Imports\ImportProfileCatalog;
use App\adms\Models\Services\Imports\ImportProfileInterface;
use App\adms\Models\Services\Imports\SpreadsheetImportReader;
use App\adms\Views\Services\LoadViewService;

class ImportCenterMap
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        $jobId = (int) $id;
        $job = $jobId > 0 ? (new ImportJobsRepository())->getById($jobId) : null;
        if ($job === null) {
            $_SESSION['msg'] = 'Job não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
            exit;
        }

        $profile = ImportProfileCatalog::get((string) $job['profile_key']);
        if ($profile === null) {
            $_SESSION['msg'] = 'Perfil inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
            exit;
        }

        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if (!UserAccessHelper::hasFullSystemAccess() && (int) ($job['created_by'] ?? 0) !== $uid) {
            $_SESSION['msg'] = 'Sem permissão para este job.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
            exit;
        }

        $pageElements = [
            'title_head' => 'Mapear colunas — ' . $profile->label(),
            'menu' => 'import-center',
            'buttonPermission' => [$profile->permission()],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        $perms = $this->data['buttonPermission'] ?? [];
        if (!UserAccessHelper::hasFullSystemAccess() && !in_array($profile->permission(), $perms, true)) {
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
            exit;
        }

        if (($job['status'] ?? '') === 'done' || ($job['status'] ?? '') === 'failed') {
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center-view/' . $jobId);
            exit;
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && CSRFHelper::validateCSRFToken('form_import_center_map', $_POST['csrf_token'] ?? '')
        ) {
            $this->run($jobId, $profile);
            return;
        }

        $headers = json_decode((string) ($job['headers_json'] ?? '[]'), true) ?: [];
        $this->data['job'] = $job;
        $this->data['profile'] = $profile;
        $this->data['headers'] = $headers;
        $this->data['suggested'] = SpreadsheetImportReader::suggestFieldMap($headers, $profile->fields());
        $this->data['preview'] = $this->preview($job);
        (new LoadViewService('adms/Views/imports/map', $this->data))->loadView();
    }

    private function run(int $jobId, ImportProfileInterface $profile): void
    {
        $allowed = array_keys($profile->fields());
        $fieldMap = $_POST['field_map'] ?? [];
        $fieldToIndex = [];
        $used = [];
        if (is_array($fieldMap)) {
            foreach ($fieldMap as $field => $colIdx) {
                $field = (string) $field;
                if (!in_array($field, $allowed, true) || $colIdx === '' || $colIdx === null) {
                    continue;
                }
                $colIdx = (int) $colIdx;
                if (isset($used[$colIdx])) {
                    $_SESSION['msg'] = 'A mesma coluna foi associada a mais de um campo.';
                    $_SESSION['msg_type'] = 'danger';
                    header('Location: ' . $_ENV['URL_ADM'] . 'import-center-map/' . $jobId);
                    exit;
                }
                $used[$colIdx] = true;
                $fieldToIndex[$field] = $colIdx;
            }
        }

        $keyField = (string) ($_POST['key_field'] ?? $profile->defaultKeyField());
        if (!in_array($keyField, $profile->keyFields(), true) || !isset($fieldToIndex[$keyField])) {
            $_SESSION['msg'] = 'Selecione a chave e associe a coluna correspondente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center-map/' . $jobId);
            exit;
        }

        try {
            (new ImportJobRunner())->run($jobId, $fieldToIndex, $keyField);
        } catch (\Throwable $e) {
            (new ImportJobsRepository())->update($jobId, [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center-view/' . $jobId);
            exit;
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'import-center-view/' . $jobId);
        exit;
    }

    /**
     * @param array<string, mixed> $job
     * @return list<list<string>>
     */
    private function preview(array $job): array
    {
        $path = (string) ($job['stored_path'] ?? '');
        if ($path === '' || !is_file($path)) {
            return [];
        }
        $fp = fopen($path, 'r');
        if ($fp === false) {
            return [];
        }
        $bom = fread($fp, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fp);
        }
        fgetcsv($fp, 0, (string) ($job['delimiter'] ?? ';'));
        $out = [];
        while (count($out) < 5 && ($row = fgetcsv($fp, 0, (string) ($job['delimiter'] ?? ';'))) !== false) {
            $out[] = array_map(static fn ($v): string => (string) $v, $row);
        }
        fclose($fp);

        return $out;
    }
}
