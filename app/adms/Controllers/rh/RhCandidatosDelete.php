<?php

namespace App\adms\Controllers\rh;

use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\LogJustificativasRepository;
use App\adms\Models\Services\SensitiveActionService;

class RhCandidatosDelete
{
    public function index(int|string $id = null): void
    {
        // ID pode vir da rota ou da query string (?id=)
        if (!$id) {
            $id = $_GET['id'] ?? null;
        }

        if (!$id || !is_numeric($id)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ID inválido.']);
                exit;
            }

            $_SESSION['error'] = "Erro: ID inválido!";
            header("Location: " . $_ENV['URL_ADM'] . "rh-candidatos");
            exit;
        }

        $id = (int)$id;
        $repo = new RhCandidatosRepository();
        $candidato = $repo->getById($id);

        if (!$candidato) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Candidato não encontrado.']);
                exit;
            }

            $_SESSION['error'] = "Erro: Candidato não encontrado!";
            header("Location: " . $_ENV['URL_ADM'] . "rh-candidatos");
            exit;
        }

        // Fluxo com confirmação via senha + justificativa (AJAX)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $motivo   = trim($_POST['motivo'] ?? '');
            $password = $_POST['password'] ?? '';

            $validacao = SensitiveActionService::validarConfirmacao($password, $motivo, true);
            if (!$validacao['success']) {
                echo json_encode(['success' => false, 'message' => $validacao['message']]);
                exit;
            }

            // Exclusão "hard" de candidato (repositório deve registrar log de alteração)
            $conn = $repo->getConnection();
            $stmtDel = $conn->prepare('DELETE FROM rh_candidatos WHERE id = :id');
            $stmtDel->bindValue(':id', $id, \PDO::PARAM_INT);
            $ok = $stmtDel->execute();

            if ($ok) {
                // Buscar último log de alteração para este registro/usuário
                if (!empty($_SESSION['user_id'])) {
                    $logsRepo = new LogAlteracoesRepository();
                    $sql = 'SELECT id FROM adms_log_alteracoes 
                            WHERE tabela = :tabela 
                              AND objeto_id = :objeto_id 
                              AND usuario_id = :usuario_id 
                            ORDER BY id DESC 
                            LIMIT 1';
                    $stmt = $logsRepo->getConnection()->prepare($sql);
                    $stmt->bindValue(':tabela', 'rh_candidatos', \PDO::PARAM_STR);
                    $stmt->bindValue(':objeto_id', $id, \PDO::PARAM_INT);
                    $stmt->bindValue(':usuario_id', (int)$_SESSION['user_id'], \PDO::PARAM_INT);
                    $stmt->execute();
                    $ultimoLog = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($ultimoLog && isset($ultimoLog['id'])) {
                        $logJustRepo = new LogJustificativasRepository();
                        $logJustRepo->insert([
                            'log_alteracao_id'  => $ultimoLog['id'],
                            'justificativa'     => $motivo,
                            'assinatura'        => $_SESSION['user_name'] ?? 'Usuário não identificado',
                            'data_justificativa'=> date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                echo json_encode(['success' => true, 'message' => 'Candidato excluído com sucesso!']);
                exit;
            }

            echo json_encode(['success' => false, 'message' => 'Erro ao excluir candidato.']);
            exit;
        }

        // Fallback simples (GET) – mantém compatibilidade se for chamado via link direto
        $conn = $repo->getConnection();
        $stmtDel = $conn->prepare('DELETE FROM rh_candidatos WHERE id = :id');
        $stmtDel->bindValue(':id', $id, \PDO::PARAM_INT);
        $ok = $stmtDel->execute();

        if ($ok) {
            $_SESSION['success'] = "Candidato excluído com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao excluir candidato!";
        }

        header("Location: " . $_ENV['URL_ADM'] . "rh-candidatos");
        exit;
    }
}


