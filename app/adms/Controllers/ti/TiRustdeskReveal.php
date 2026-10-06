<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\TiRustdeskRepository;
use App\adms\Models\Services\SensitiveActionService;
use App\adms\Models\Services\TiRustdeskSecretService;

/**
 * Descriptografa a senha RustDesk após o usuário logado confirmar a própria senha.
 */
final class TiRustdeskReveal
{
    public function index(int|string $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Use POST.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $registroId = (int) $id;
        $csrf = (string) ($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_ti_rustdesk_reveal', $csrf, false)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token inválido. Recarregue a página.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $password = (string) ($_POST['password'] ?? '');
        $acao = (string) ($_POST['acao'] ?? 'view');
        if (!in_array($acao, ['view', 'copy'], true)) {
            $acao = 'view';
        }

        $validacao = SensitiveActionService::validarConfirmacao($password, null, false);
        if (!$validacao['success']) {
            GenerateLog::generateLog('warning', 'TiRustdeskReveal senha do usuário recusada', [
                'user_id' => (int) ($_SESSION['user_id'] ?? 0),
                'rustdesk_id' => $registroId,
            ]);
            echo json_encode(['success' => false, 'message' => $validacao['message']], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $repo = new TiRustdeskRepository();
        $registro = $repo->getById($registroId);
        if ($registro === null) {
            echo json_encode(['success' => false, 'message' => 'Cadastro não encontrado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $enc = $repo->getEncryptedPassword($registroId);
        if ($enc === null) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma senha cadastrada para este RustDesk.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!TiRustdeskSecretService::isConfigured()) {
            echo json_encode([
                'success' => false,
                'message' => 'Não foi possível preparar a criptografia da senha.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $plain = TiRustdeskSecretService::decrypt($enc);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'TiRustdeskReveal falha ao descriptografar', [
                'user_id' => (int) ($_SESSION['user_id'] ?? 0),
                'rustdesk_id' => $registroId,
                'error' => $e->getMessage(),
            ]);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }

        GenerateLog::generateLog('notice', 'TiRustdeskReveal senha acessada', [
            'user_id' => (int) ($_SESSION['user_id'] ?? 0),
            'username' => (string) ($_SESSION['user_username'] ?? ''),
            'registro_id' => $registroId,
            'rustdesk_id' => (string) ($registro['rustdesk_id'] ?? ''),
            'acao' => $acao,
        ]);

        echo json_encode([
            'success' => true,
            'senha' => $plain,
            'acao' => $acao,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
