<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsSapServiceLayerConnectionRepository;

class SaveSapServiceLayerConnection
{
    public function index(): void
    {
        $redirect = $_ENV['URL_ADM'] . 'sap-service-layer-connections';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirect);
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_sap_sl_conn_save', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect);
            exit;
        }

        $repo = new AdmsSapServiceLayerConnectionRepository();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $baseUrl = trim((string) ($_POST['base_url'] ?? ''));
        $companyDb = trim((string) ($_POST['company_db'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $healthPath = trim((string) ($_POST['health_path'] ?? ''));
        if ($healthPath === '') {
            $healthPath = '/health';
        }
        $healthPath = '/' . ltrim($healthPath, '/');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        if ($name === '' || $baseUrl === '') {
            $_SESSION['msg'] = 'Preencha pelo menos o nome e a URL base da sua API.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }

        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $_SESSION['msg'] = 'Informe uma URL base válida (ex.: https://api.suaempresa.com/v1).';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }

        if ($repo->nameExists($name, $id > 0 ? $id : null)) {
            $_SESSION['msg'] = 'Já existe uma conexão com este nome. Escolha outro identificador.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }

        $normalizedBase = rtrim($baseUrl, '/');
        $payload = [
            'name' => $name,
            'base_url' => $normalizedBase,
            'health_path' => $healthPath,
            'company_db' => $companyDb,
            'username' => $username,
            'password' => $password,
            'is_active' => $isActive,
            'is_default' => $isDefault,
            'sort_order' => $sortOrder,
        ];

        if ($id > 0) {
            if (!$repo->getById($id)) {
                $_SESSION['msg'] = 'Conexão não encontrada.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $redirect);
                exit;
            }
            $ok = $repo->update($id, $payload);
            if ($ok && $isDefault === 1) {
                $repo->setAsOnlyDefault($id);
            }
            $_SESSION['msg'] = $ok ? 'Conexão atualizada com sucesso.' : 'Erro ao atualizar a conexão.';
            $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
            header('Location: ' . $redirect . '?edit=' . $id);
            exit;
        }

        if (trim($password) === '') {
            $_SESSION['msg'] = 'Informe o token ou palavra-passe da API para uma nova conexão.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect);
            exit;
        }

        $newId = $repo->insert($payload);
        if ($newId && $isDefault === 1) {
            $repo->setAsOnlyDefault($newId);
        }
        $_SESSION['msg'] = $newId ? 'Conexão criada com sucesso.' : 'Erro ao criar a conexão.';
        $_SESSION['msg_type'] = $newId ? 'success' : 'danger';
        header('Location: ' . $redirect . ($newId ? '?edit=' . $newId : ''));
        exit;
    }
}
