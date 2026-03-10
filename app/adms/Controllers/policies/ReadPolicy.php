<?php

namespace App\adms\Controllers\policies;

use App\adms\Models\Repository\PoliciesRepository;

/**
 * Endpoint AJAX para registrar leitura de políticas internas.
 *
 * Espelha o comportamento de ReadInformativo, mas usando a tabela adms_policies_reads.
 */
class ReadPolicy
{
    public function index(string $id = null): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }

        $policyId = (int)($id ?? ($_POST['policy_id'] ?? ($_GET['policy_id'] ?? 0)));
        if ($policyId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        try {
            $repo = new PoliciesRepository();
            $repo->upsertRead($policyId, (int)$_SESSION['user_id']);
            $read = $repo->getReadByUser($policyId, (int)$_SESSION['user_id']);
            echo json_encode([
                'success'      => true,
                'read'         => (bool)($read['read_at'] ?? false),
                'acknowledged' => (bool)($read['acknowledged'] ?? false),
                'ack_at'       => $read['ack_at'] ?? null,
            ]);
        } catch (\Throwable $t) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar leitura']);
        }
    }
}

