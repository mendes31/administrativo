<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ImportJobsRepository;
use App\adms\Models\Services\Imports\ImportJobRunner;
use App\adms\Models\Services\Imports\ImportProfileCatalog;

class ImportCenterCommit
{
    public function index(string|int|null $id = null): void
    {
        $redirectList = $_ENV['URL_ADM'] . 'import-center';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirectList);
            exit;
        }

        $jobId = (int) $id;
        $redirectView = $_ENV['URL_ADM'] . 'import-center-view/' . $jobId;
        $job = $jobId > 0 ? (new ImportJobsRepository())->getById($jobId) : null;
        if ($job === null) {
            $_SESSION['msg'] = 'Job não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirectList);
            exit;
        }

        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if (!UserAccessHelper::hasFullSystemAccess() && (int) ($job['created_by'] ?? 0) !== $uid) {
            $_SESSION['msg'] = 'Sem permissão para registrar este job.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirectList);
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_import_center_commit', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido. Recarregue a página e tente de novo.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirectView);
            exit;
        }

        $profile = ImportProfileCatalog::get((string) ($job['profile_key'] ?? ''));
        if ($profile === null) {
            $_SESSION['msg'] = 'Perfil de importação inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirectView);
            exit;
        }

        try {
            (new ImportJobRunner())->commitSimulation($jobId);
        } catch (\Throwable $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirectView);
            exit;
        }

        $_SESSION['msg'] = 'Importação registrada. Os dados foram gravados no banco.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $redirectView);
        exit;
    }
}
