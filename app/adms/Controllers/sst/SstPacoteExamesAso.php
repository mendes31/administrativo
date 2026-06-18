<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Models\Services\SstExamesObrigatoriosResolver;

/**
 * Retorna JSON com exames complementares sugeridos para um colaborador + categoria ASO.
 */
class SstPacoteExamesAso
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $userId = (int) ($_GET['adms_user_id'] ?? 0);
        $tipo = trim((string) ($_GET['tipo'] ?? ''));
        if ($userId <= 0 || !SstCategoriaAsoHelper::isValid($tipo)) {
            echo json_encode(['ok' => false, 'message' => 'Parâmetros inválidos.']);
            return;
        }
        $exames = (new SstExamesObrigatoriosResolver())->resolvePacoteParaCategoria($userId, $tipo);
        echo json_encode(['ok' => true, 'exames' => $exames]);
    }
}
