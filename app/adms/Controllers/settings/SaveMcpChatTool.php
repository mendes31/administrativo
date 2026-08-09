<?php

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\DynamicReportsRepository;

class SaveMcpChatTool
{
    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $_SESSION['error'] = 'Método inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config?tab=tools');
            exit;
        }

        $perms = (new ButtonPermissionUserRepository())->buttonPermission(['SaveMcpChatTool', 'ListMcpChatTools']);
        if (empty($perms) || (!in_array('SaveMcpChatTool', $perms, true) && !in_array('ListMcpChatTools', $perms, true))) {
            $_SESSION['error'] = 'Sem permissão para alterar tools do chat.';
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config?tab=tools');
            exit;
        }

        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_mcp_chat_tool', $token)) {
            $_SESSION['error'] = 'Token de segurança inválido. Recarregue a página.';
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config?tab=tools');
            exit;
        }

        $reportId = (int) ($_POST['report_id'] ?? 0);
        if ($reportId < 1) {
            $_SESSION['error'] = 'Relatório inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config?tab=tools');
            exit;
        }

        $repo = new DynamicReportsRepository();
        $report = $repo->getById($reportId);
        $viewerId = (int) ($_SESSION['user_id'] ?? 0);
        if (!$report || !$repo->userCanEditReport($report, $viewerId)) {
            $_SESSION['error'] = 'Sem permissão para editar este relatório.';
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config?tab=tools');
            exit;
        }

        $chatEnabled = isset($_POST['chat_enabled']) ? 1 : 0;
        $toolName = trim((string) ($_POST['chat_tool_name'] ?? ''));
        if ($toolName === '' && $chatEnabled) {
            $toolName = $this->slugifyToolName((string) ($report['name'] ?? 'report'));
        }
        $description = trim((string) ($_POST['chat_description'] ?? '')) ?: null;
        $examples = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) ($_POST['chat_example_prompts'] ?? '')) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $examples[] = $line;
            }
        }

        $ok = $repo->updateChatMetadata($reportId, [
            'chat_enabled' => $chatEnabled,
            'chat_tool_name' => $toolName !== '' ? mb_substr($toolName, 0, 100) : null,
            'chat_description' => $description,
            'chat_example_prompts' => $examples,
        ]);

        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Tool do chat atualizada.'
            : 'Não foi possível salvar.';

        header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config?tab=tools');
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
