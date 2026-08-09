<?php

namespace App\adms\Controllers\reports;

use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;

class SaveDynamicReport
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método inválido';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }
        
        $queryMode = $_POST['query_mode'] ?? 'builder';
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? null,
            'query_mode' => $queryMode,
            'created_by' => $_SESSION['user_id'] ?? 0
        ];
        
        // Campos específicos por modo
        if ($queryMode === 'custom_sql') {
            // Modo SQL Personalizado
            $data['custom_sql'] = $_POST['custom_sql'] ?? '';
            $data['data_source'] = DynamicQueryBuilderService::hasSapSignature($data['custom_sql']) ? 'sap_b1' : 'sql_local';
            $data['fields'] = [];
            $data['filters'] = [];
            $data['groupby'] = [];
            $data['orderby'] = [];
            $data['visualization_type'] = $_POST['visualization_type_sql'] ?? $_POST['visualization_type'] ?? 'table';
            
            // Validar SQL personalizado
            if (empty($data['name']) || empty($data['custom_sql'])) {
                $_SESSION['error'] = 'Nome e SQL são obrigatórios no modo SQL personalizado';
                header('Location: ' . $_ENV['URL_ADM'] . 'dynamic-report-builder');
                exit;
            }
        } else {
            // Modo Builder
            $data['data_source'] = $_POST['data_source'] ?? '';
            $data['fields'] = json_decode($_POST['fields'] ?? '[]', true);
            $data['filters'] = json_decode($_POST['filters'] ?? '[]', true);
            $data['groupby'] = json_decode($_POST['groupby'] ?? '[]', true);
            $data['orderby'] = json_decode($_POST['orderby'] ?? '[]', true);
            $data['custom_sql'] = null;
            $data['visualization_type'] = $_POST['visualization_type'] ?? 'table';
            
            // Validar builder
            if (empty($data['name']) || empty($data['data_source'])) {
                $_SESSION['error'] = 'Nome e Fonte de Dados são obrigatórios no modo construtor';
                header('Location: ' . $_ENV['URL_ADM'] . 'dynamic-report-builder');
                exit;
            }
        }
        
        // Campos comuns
        $data['chart_config'] = json_decode($_POST['chart_config'] ?? '{}', true);
        $data['refresh_interval'] = $_POST['refresh_interval'] ?? null;
        $data['category'] = $_POST['category'] ?? null;
        $data['is_public'] = isset($_POST['is_public']) ? 1 : 0;
        $data['chat_enabled'] = isset($_POST['chat_enabled']) ? 1 : 0;
        $toolName = trim((string) ($_POST['chat_tool_name'] ?? ''));
        if ($toolName === '' && !empty($data['chat_enabled'])) {
            $toolName = $this->slugifyToolName((string) ($data['name'] ?? 'report'));
        }
        $data['chat_tool_name'] = $toolName !== '' ? mb_substr($toolName, 0, 100) : null;
        $data['chat_description'] = trim((string) ($_POST['chat_description'] ?? '')) ?: null;
        $examplesRaw = (string) ($_POST['chat_example_prompts'] ?? '');
        $examples = [];
        foreach (preg_split('/\r\n|\r|\n/', $examplesRaw) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $examples[] = $line;
            }
        }
        $data['chat_example_prompts'] = $examples;
        
        $repo = new DynamicReportsRepository();
        $viewerId = (int) ($_SESSION['user_id'] ?? 0);
        
        if (!empty($_POST['id'])) {
            $reportId = (int)$_POST['id'];
            $existing = $repo->getById($reportId);
            if (!$existing) {
                $_SESSION['error'] = 'Relatório não encontrado';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
                exit;
            }
            if (!$repo->userCanEditReport($existing, $viewerId)) {
                $_SESSION['error'] = 'Você não tem permissão para alterar este relatório.';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
                exit;
            }
            $success = $repo->update($reportId, $data);
            $message = $success ? 'Relatório atualizado com sucesso!' : 'Erro ao atualizar';
        } else {
            $reportId = $repo->create($data);
            $success = $reportId > 0;
            $message = $success ? 'Relatório criado com sucesso!' : 'Erro ao criar';
        }

        $sharedRaw = $_POST['shared_user_ids'] ?? [];
        if (!is_array($sharedRaw)) {
            $sharedRaw = [];
        }
        $sharedIds = array_values(array_unique(array_filter(array_map('intval', $sharedRaw), static fn ($id) => $id > 0)));

        if ($success && $reportId > 0) {
            try {
                $repo->setSharedUsers($reportId, $sharedIds, $viewerId);
            } catch (\Throwable $e) {
                error_log('setSharedUsers: ' . $e->getMessage());
                $_SESSION['warning'] = 'Relatório guardado, mas não foi possível atualizar a lista de utilizadores com acesso. Execute a migração da base de dados se ainda não o fez.';
            }
        }
        
        if ($success) {
            $_SESSION['success'] = $message;
            header('Location: ' . $_ENV['URL_ADM'] . 'view-dynamic-report/' . $reportId);
        } else {
            $_SESSION['error'] = $message;
            header('Location: ' . $_ENV['URL_ADM'] . 'dynamic-report-builder');
        }
        exit;
    }

    private function slugifyToolName(string $name): string
    {
        $s = mb_strtolower(trim($name));
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        $s = preg_replace('/[^a-z0-9]+/', '_', $s) ?? $s;
        $s = trim($s, '_');

        return $s !== '' ? mb_substr($s, 0, 80) : 'report';
    }
}

