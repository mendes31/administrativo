<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ImportJobsRepository;
use App\adms\Models\Services\Imports\ImportProfileCatalog;
use App\adms\Models\Services\Imports\ImportProfileInterface;
use App\adms\Models\Services\Imports\ImportStorage;
use App\adms\Models\Services\Imports\SpreadsheetImportReader;
use App\adms\Views\Services\LoadViewService;

class ImportCenterCreate
{
    private array $data = [];

    public function index(): void
    {
        $profile = $this->resolveProfile();
        if ($profile === null) {
            $_SESSION['msg'] = 'Selecione um tipo de importação.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
            exit;
        }

        $pageElements = [
            'title_head' => 'Importar — ' . $profile->label(),
            'menu' => 'import-center',
            'buttonPermission' => [$profile->permission()],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        $perms = $this->data['buttonPermission'] ?? [];
        if (!UserAccessHelper::hasFullSystemAccess() && !in_array($profile->permission(), $perms, true)) {
            $_SESSION['msg'] = 'Sem permissão para este tipo de importação.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
            exit;
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && CSRFHelper::validateCSRFToken('form_import_center_upload', $_POST['csrf_token'] ?? '')
        ) {
            $this->store($profile);
            return;
        }

        $this->data['profile'] = $profile;
        $this->data['form'] = $_POST ?? [];
        (new LoadViewService('adms/Views/imports/create', $this->data))->loadView();
    }

    private function resolveProfile(): ?ImportProfileInterface
    {
        $key = (string) ($_POST['profile'] ?? $_GET['profile'] ?? '');

        return ImportProfileCatalog::get($key);
    }

    private function store(ImportProfileInterface $profile): void
    {
        $operation = (string) ($_POST['operation'] ?? 'upsert');
        if (!in_array($operation, ['insert', 'update', 'upsert'], true)) {
            $operation = 'upsert';
        }
        $emptyPolicy = (string) ($_POST['empty_policy'] ?? 'skip');
        if (!in_array($emptyPolicy, ['skip', 'clear'], true)) {
            $emptyPolicy = 'skip';
        }
        $dryRun = !empty($_POST['dry_run']);
        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->data['errors'][] = 'Envie um arquivo Excel (.xlsx) ou CSV.';
            $this->data['profile'] = $profile;
            $this->data['form'] = $_POST;
            $pageElements = [
                'title_head' => 'Importar — ' . $profile->label(),
                'menu' => 'import-center',
                'buttonPermission' => [$profile->permission()],
            ];
            $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
            (new LoadViewService('adms/Views/imports/create', $this->data))->loadView();
            return;
        }

        try {
            $parsed = (new SpreadsheetImportReader())->parseUpload(
                (string) $file['tmp_name'],
                (string) ($file['name'] ?? 'arquivo.csv')
            );
        } catch (\Throwable $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center-create?profile=' . urlencode($profile->key()));
            exit;
        }

        $jobId = (new ImportJobsRepository())->create([
            'profile_key' => $profile->key(),
            'operation' => $operation,
            'empty_policy' => $emptyPolicy,
            'dry_run' => $dryRun,
            'status' => 'uploaded',
            'original_filename' => (string) ($file['name'] ?? ''),
            'delimiter' => $parsed['delimiter'],
            'headers_json' => json_encode($parsed['headers'], JSON_UNESCAPED_UNICODE),
            'created_by' => (int) ($_SESSION['user_id'] ?? 0),
        ]);

        $dest = ImportStorage::pathForJob($jobId);
        if (!@copy($parsed['normalized_path'], $dest)) {
            @unlink($parsed['normalized_path']);
            $_SESSION['msg'] = 'Não foi possível armazenar o arquivo.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
            exit;
        }
        @unlink($parsed['normalized_path']);
        (new ImportJobsRepository())->update($jobId, ['stored_path' => $dest]);

        header('Location: ' . $_ENV['URL_ADM'] . 'import-center-map/' . $jobId);
        exit;
    }
}
