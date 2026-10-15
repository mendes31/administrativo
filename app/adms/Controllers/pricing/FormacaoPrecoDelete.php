<?php

declare(strict_types=1);

namespace App\adms\Controllers\pricing;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\FormacaoPrecoRepository;

final class FormacaoPrecoDelete
{
    public function index(int|string $id): void
    {
        $registroId = (int) $id;
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_formacao_preco_delete', $token)) {
            $_SESSION['msg'] = 'Não foi possível validar o formulário. Atualize a página e tente de novo.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'formacao-preco');
            exit;
        }

        $ok = (new FormacaoPrecoRepository())->delete($registroId, (int) ($_SESSION['user_id'] ?? 0));
        $_SESSION['msg'] = $ok ? 'Precificação excluída.' : 'Não foi possível excluir a precificação.';
        $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'formacao-preco');
        exit;
    }
}
