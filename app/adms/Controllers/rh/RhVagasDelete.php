<?php

namespace App\adms\Controllers\rh;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\LogJustificativasRepository;
use App\adms\Models\Services\SensitiveActionService;
use App\adms\Models\Services\RhPermissionService;

class RhVagasDelete
{
    public function index(int|string $id = null): void
    {
        if (!$id) {
            $id = $_POST['id'] ?? null;
        }

        if (!$id || !is_numeric($id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $id = (int)$id;

        // Verificar permissão para excluir a vaga
        if (!RhPermissionService::canEditVagaById($id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para excluir esta vaga.']);
            exit;
        }

        // Validação de senha + justificativa para exclusão (ação sensível)
        $motivo   = trim($_POST['motivo'] ?? '');
        $password = $_POST['password'] ?? '';
        $validacao = SensitiveActionService::validarConfirmacao($password, $motivo, true);
        if (!$validacao['success']) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $validacao['message']]);
            exit;
        }

        try {
            $repo = new RhVagasRepository();
            $ok = $repo->delete($id);

            if ($ok) {
                // Vincular justificativa ao último log de alteração desta vaga
                if (!empty($_SESSION['user_id'])) {
                    $logRepo = new LogAlteracoesRepository();
                    $sql = 'SELECT id FROM adms_log_alteracoes 
                            WHERE tabela = :tabela 
                              AND objeto_id = :objeto_id 
                              AND usuario_id = :usuario_id 
                            ORDER BY id DESC 
                            LIMIT 1';
                    $conn = $logRepo->getConnection();
                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(':tabela', 'rh_vagas', \PDO::PARAM_STR);
                    $stmt->bindValue(':objeto_id', $id, \PDO::PARAM_INT);
                    $stmt->bindValue(':usuario_id', (int)$_SESSION['user_id'], \PDO::PARAM_INT);
                    $stmt->execute();
                    $ultimoLog = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($ultimoLog && isset($ultimoLog['id'])) {
                        $logJustRepo = new LogJustificativasRepository();
                        $logJustRepo->insert([
                            'log_alteracao_id'   => $ultimoLog['id'],
                            'justificativa'      => $motivo,
                            'assinatura'         => $_SESSION['user_name'] ?? 'Usuário não identificado',
                            'data_justificativa' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Vaga excluída com sucesso!']);
                exit;
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir vaga.']);
            exit;

        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao excluir vaga.', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro inesperado ao excluir vaga.']);
            exit;
        }
    }
}

